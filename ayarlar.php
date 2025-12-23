<?php
require_once 'config.php';

Session::start();
$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$message = '';
$error = '';

// Resmi tatil ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tatil_ekle'])) {
    $tatil_adi = trim($_POST['tatil_adi'] ?? '');
    $tarih = $_POST['tarih'] ?? '';
    
    if (empty($tatil_adi) || empty($tarih)) {
        $error = 'Tatil adı ve tarih gereklidir!';
    } else {
        try {
            $yil = date('Y', strtotime($tarih));
            $db->query(
                "INSERT INTO resmi_tatiller (tatil_adi, tarih, yil) VALUES (?, ?, ?)",
                array($tatil_adi, $tarih, $yil)
            );
            $message = 'Resmi tatil başarıyla eklendi!';
        } catch (Exception $e) {
            $error = 'HATA: ' . $e->getMessage();
        }
    }
}

// Resmi tatil silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tatil_sil'])) {
    $tatil_id = $_POST['tatil_id'] ?? 0;
    try {
        $db->query("DELETE FROM resmi_tatiller WHERE id = ?", array($tatil_id));
        $message = 'Resmi tatil silindi!';
    } catch (Exception $e) {
        $error = 'HATA: ' . $e->getMessage();
    }
}

// Vardiya ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vardiya_ekle'])) {
    $vardiya_adi = trim($_POST['vardiya_adi'] ?? '');
    $lokasyon = $_POST['lokasyon'] ?? '';
    $baslangic_saat = $_POST['baslangic_saat'] ?? '';
    $bitis_saat = $_POST['bitis_saat'] ?? '';
    
    if (empty($vardiya_adi) || empty($lokasyon) || empty($baslangic_saat) || empty($bitis_saat)) {
        $error = 'Tüm alanları doldurunuz!';
    } else {
        try {
            // Çalışma saatini hesapla
            $baslangic = new DateTime($baslangic_saat);
            $bitis = new DateTime($bitis_saat);
            if ($bitis < $baslangic) {
                $bitis->modify('+1 day');
            }
            $fark = $baslangic->diff($bitis);
            $calisma_saati = $fark->h + ($fark->i / 60);
            
            $db->query(
                "INSERT INTO vardiya_sablonlari (vardiya_adi, lokasyon, baslangic_saat, bitis_saat, calisma_saati) 
                 VALUES (?, ?, ?, ?, ?)",
                array($vardiya_adi, $lokasyon, $baslangic_saat, $bitis_saat, $calisma_saati)
            );
            $message = 'Vardiya başarıyla eklendi!';
        } catch (Exception $e) {
            $error = 'HATA: ' . $e->getMessage();
        }
    }
}

// Vardiya pasif yapma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vardiya_sil'])) {
    $vardiya_id = $_POST['vardiya_id'] ?? 0;
    try {
        $db->query("UPDATE vardiya_sablonlari SET aktif = 0 WHERE id = ?", array($vardiya_id));
        $message = 'Vardiya pasif duruma getirildi!';
    } catch (Exception $e) {
        $error = 'HATA: ' . $e->getMessage();
    }
}

// Vardiya aktifleştirme (YENİ EKLENDİ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vardiya_aktiflestir'])) {
    $vardiya_id = $_POST['vardiya_id'] ?? 0;
    try {
        $db->query("UPDATE vardiya_sablonlari SET aktif = 1 WHERE id = ?", array($vardiya_id));
        $message = 'Vardiya başarıyla tekrar aktif duruma getirildi!';
    } catch (Exception $e) {
        $error = 'HATA: ' . $e->getMessage();
    }
}

// Resmi tatilleri getir
$resmi_tatiller = $db->fetchAll("SELECT * FROM resmi_tatiller ORDER BY tarih DESC");

// Vardiyaları getir
$vardialar = $db->fetchAll("SELECT * FROM vardiya_sablonlari ORDER BY aktif DESC, lokasyon, id");

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-shield-check me-2"></i><?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?php echo Helper::escape(Session::getUserName()); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i>Profilim</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <nav class="nav flex-column py-3">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>Ana Sayfa
                    </a>
                    <a class="nav-link" href="nobet_listesi.php">
                        <i class="bi bi-calendar3"></i>Nöbet Listem
                    </a>
                    <hr class="my-2">
                    <h6 class="px-3 text-muted small fw-bold">YÖNETİCİ PANELİ</h6>
                    <a class="nav-link" href="personel_yonetimi.php">
                        <i class="bi bi-people"></i>Personel Yönetimi
                    </a>
					<a class="nav-link" href="genel_nobet_listesi.php">
                        <i class="bi bi-list-ul"></i>Genel Nöbet Listesi
                    </a>
                    <a class="nav-link" href="nobet_olustur.php">
                        <i class="bi bi-calendar-plus"></i>Nöbet Oluştur
                    </a>
                    <a class="nav-link" href="nobet_duzenle.php">
                        <i class="bi bi-calendar-check"></i>Nöbet Düzenle
                    </a>
                    <a class="nav-link" href="raporlar.php">
                        <i class="bi bi-file-earmark-text"></i>Raporlar
                    </a>
                    <a class="nav-link active" href="ayarlar.php">
                        <i class="bi bi-gear"></i>Ayarlar
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <h2 class="mb-4">
                    <i class="bi bi-gear text-primary me-2"></i>Sistem Ayarları
                </h2>
                
                <?php if ($message): ?>
                    <?php echo Helper::showSuccess($message); ?>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <?php echo Helper::showError($error); ?>
                <?php endif; ?>
                
                <!-- Resmi Tatiller -->
                <div class="card settings-card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-calendar-event me-2"></i>Resmi Tatiller
                            </h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tatilEkleModal">
                                <i class="bi bi-plus-circle me-1"></i>Tatil Ekle
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tatil Adı</th>
                                        <th>Tarih</th>
                                        <th>Yıl</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resmi_tatiller as $tatil): ?>
                                        <tr>
                                            <td><?php echo Helper::escape($tatil['tatil_adi']); ?></td>
                                            <td><?php echo Helper::formatDate($tatil['tarih']); ?></td>
                                            <td><?php echo $tatil['yil']; ?></td>
                                            <td>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Silmek istediğinizden emin misiniz?')">
                                                    <input type="hidden" name="tatil_id" value="<?php echo $tatil['id']; ?>">
                                                    <button type="submit" name="tatil_sil" class="btn btn-sm btn-danger" title="Sil">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Vardiya Şablonları -->
                <div class="card settings-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-clock-history me-2"></i>Vardiya Şablonları
                            </h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#vardiyaEkleModal">
                                <i class="bi bi-plus-circle me-1"></i>Vardiya Ekle
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vardiya Adı</th>
                                        <th>Lokasyon</th>
                                        <th>Başlangıç</th>
                                        <th>Bitiş</th>
                                        <th>Çalışma Saati</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vardialar as $vardiya): ?>
                                        <tr class="<?php echo $vardiya['aktif'] ? '' : 'table-secondary'; ?>">
                                            <td><?php echo Helper::escape($vardiya['vardiya_adi']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $vardiya['lokasyon'] === 'kampus_giris' ? 'bg-primary' : 'bg-info'; ?>">
                                                    <?php echo $vardiya['lokasyon'] === 'kampus_giris' ? 'Kampüs Girişi' : 'Kampüs İçi'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo substr($vardiya['baslangic_saat'], 0, 5); ?></td>
                                            <td><?php echo substr($vardiya['bitis_saat'], 0, 5); ?></td>
                                            <td><?php echo $vardiya['calisma_saati']; ?> saat</td>
                                            <td>
                                                <span class="badge <?php echo $vardiya['aktif'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $vardiya['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($vardiya['aktif']): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Pasif duruma getirmek istediğinizden emin misiniz?')">
                                                    <input type="hidden" name="vardiya_id" value="<?php echo $vardiya['id']; ?>">
                                                    <button type="submit" name="vardiya_sil" class="btn btn-sm btn-danger" title="Pasif Yap">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Vardiyayı tekrar aktif duruma getirmek istediğinizden emin misiniz?')">
                                                    <input type="hidden" name="vardiya_id" value="<?php echo $vardiya['id']; ?>">
                                                    <button type="submit" name="vardiya_aktiflestir" class="btn btn-sm btn-success" title="Aktifleştir">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resmi Tatil Ekleme Modal -->
    <div class="modal fade" id="tatilEkleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Resmi Tatil Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tatil Adı *</label>
                            <input type="text" class="form-control" name="tatil_adi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tarih *</label>
                            <input type="date" class="form-control" name="tarih" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="tatil_ekle" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vardiya Ekleme Modal -->
    <div class="modal fade" id="vardiyaEkleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Vardiya Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss=\"modal\"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Vardiya Adı *</label>
                            <input type="text" class="form-control" name="vardiya_adi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lokasyon *</label>
                            <select class="form-select" name="lokasyon" required>
                                <option value="">Seçiniz</option>
                                <option value="kampus_giris">Kampüs Girişi</option>
                                <option value="kampus_ici">Kampüs İçi</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Başlangıç Saati *</label>
                            <input type="time" class="form-control" name="baslangic_saat" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bitiş Saati *</label>
                            <input type="time" class="form-control" name="bitis_saat" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="vardiya_ekle" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>