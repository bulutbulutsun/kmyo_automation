<?php
require_once 'config.php';

Session::start();
$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$message = '';
$error = '';

// Ay ve yıl seçimi
$secili_ay = isset($_GET['ay']) ? (int)$_GET['ay'] : (int)date('m');
$secili_yil = isset($_GET['yil']) ? (int)$_GET['yil'] : (int)date('Y');

// Nöbet güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guncelle'])) {
    $nobet_id = $_POST['nobet_id'] ?? 0;
    $vardiya_id = $_POST['vardiya_id'] ?? null;
    
    try {
        if ($vardiya_id) {
            $db->query(
                "UPDATE nobet_programi SET vardiya_id = ?, durum = 'degistirildi', degistirme_tarihi = NOW() WHERE id = ?",
                array($vardiya_id, $nobet_id)
            );
        } else {
            $db->query(
                "UPDATE nobet_programi SET vardiya_id = NULL, durum = 'hafta_tatili', degistirme_tarihi = NOW() WHERE id = ?",
                array($nobet_id)
            );
        }
        $message = 'Nöbet kaydı güncellendi!';
    } catch (Exception $e) {
        $error = 'HATA: ' . $e->getMessage();
    }
}

// Ayın ilk ve son günü
$ay_baslangic = sprintf('%04d-%02d-01', $secili_yil, $secili_ay);
$ay_bitis = date('Y-m-t', strtotime($ay_baslangic));

// Personel listesi
$personeller = $db->fetchAll("SELECT id, sicil_no, ad, soyad FROM personel WHERE aktif = 1 ORDER BY ad, soyad");

// Vardiya listesi
$vardialar = $db->fetchAll("SELECT * FROM vardiya_sablonlari WHERE aktif = 1 ORDER BY lokasyon, id");

// Nöbet kayıtlarını getir
$nobetler = $db->fetchAll(
    "SELECT np.*, vs.vardiya_adi, vs.lokasyon, p.ad, p.soyad, p.sicil_no
     FROM nobet_programi np
     INNER JOIN personel p ON np.personel_id = p.id
     LEFT JOIN vardiya_sablonlari vs ON np.vardiya_id = vs.id
     WHERE np.tarih BETWEEN ? AND ?
     ORDER BY np.tarih ASC, p.ad ASC",
    array($ay_baslangic, $ay_bitis)
);

// Tarihe göre grupla
$nobetler_tarih = array();
foreach ($nobetler as $nobet) {
    $nobetler_tarih[$nobet['tarih']][] = $nobet;
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nöbet Düzenle - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link active" href="nobet_duzenle.php">
                        <i class="bi bi-calendar-check"></i>Nöbet Düzenle
                    </a>
                    <a class="nav-link" href="raporlar.php">
                        <i class="bi bi-file-earmark-text"></i>Raporlar
                    </a>
                    <a class="nav-link" href="ayarlar.php">
                        <i class="bi bi-gear"></i>Ayarlar
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <h2 class="mb-4">
                    <i class="bi bi-calendar-check text-primary me-2"></i>Nöbet Programını Düzenle
                </h2>
                
                <?php if ($message): ?>
                    <?php echo Helper::showSuccess($message); ?>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <?php echo Helper::showError($error); ?>
                <?php endif; ?>
                
                <!-- Ay Seçimi -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ay</label>
                                <select name="ay" class="form-select">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $secili_ay == $i ? 'selected' : ''; ?>>
                                            <?php echo Helper::getTurkishMonthName($i); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Yıl</label>
                                <select name="yil" class="form-select">
                                    <?php for ($y = 2022; $y <= 2030; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $secili_yil == $y ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Görüntüle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Nöbet Listesi -->
                <?php if (count($nobetler_tarih) > 0): ?>
                    <?php foreach ($nobetler_tarih as $tarih => $gun_nobetleri): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-calendar-date me-2"></i>
                                    <?php echo Helper::getTurkishDayName($tarih) . ', ' . Helper::formatDate($tarih); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($gun_nobetleri as $nobet): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card nobet-card">
                                                <div class="card-body">
                                                    <h6 class="fw-bold">
                                                        <?php echo Helper::escape($nobet['ad'] . ' ' . $nobet['soyad']); ?>
                                                    </h6>
                                                    <p class="text-muted small mb-2">Sicil: <?php echo Helper::escape($nobet['sicil_no']); ?></p>
                                                    
                                                    <?php if ($nobet['durum'] === 'hafta_tatili'): ?>
                                                        <span class="badge bg-warning text-dark">Hafta Tatili</span>
                                                    <?php elseif ($nobet['durum'] === 'resmi_tatil'): ?>
                                                        <span class="badge bg-danger">Resmi Tatil</span><br>
                                                        <small><?php echo Helper::escape($nobet['vardiya_adi']); ?></small>
                                                    <?php else: ?>
                                                        <div class="mb-2">
                                                            <strong><?php echo Helper::escape($nobet['vardiya_adi']); ?></strong><br>
                                                            <small class="text-muted">
                                                                <?php echo $nobet['lokasyon'] === 'kampus_giris' ? 'Kampüs Girişi' : 'Kampüs İçi'; ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-sm btn-primary mt-2" 
                                                            onclick='duzenleModal(<?php echo json_encode($nobet); ?>, <?php echo json_encode($vardialar); ?>)'>
                                                        <i class="bi bi-pencil"></i> Düzenle
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Seçilen ay için nöbet kaydı bulunamadı!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Düzenleme Modal -->
    <div class="modal fade" id="duzenleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Nöbet Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="nobet_id" id="edit_nobet_id">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Personel</label>
                            <input type="text" class="form-control" id="edit_personel" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tarih</label>
                            <input type="text" class="form-control" id="edit_tarih" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Vardiya Seçin</label>
                            <select name="vardiya_id" class="form-select" id="edit_vardiya_id">
                                <option value="">Hafta Tatili</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small>
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Vardiya seçilmezse personel o gün hafta tatiline alınır!
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="guncelle" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function duzenleModal(nobet, vardialar) {
            document.getElementById('edit_nobet_id').value = nobet.id;
            document.getElementById('edit_personel').value = nobet.sicil_no + ' - ' + nobet.ad + ' ' + nobet.soyad;
            document.getElementById('edit_tarih').value = nobet.tarih;
            
            // Vardiya seçeneklerini doldur
            var select = document.getElementById('edit_vardiya_id');
            select.innerHTML = '<option value="">Hafta Tatili</option>';
            
            vardialar.forEach(function(vardiya) {
                var option = document.createElement('option');
                option.value = vardiya.id;
                option.text = vardiya.vardiya_adi + ' (' + 
                    (vardiya.lokasyon === 'kampus_giris' ? 'Kampüs Girişi' : 'Kampüs İçi') + 
                    ') - ' + vardiya.baslangic_saat.substr(0, 5) + ' - ' + vardiya.bitis_saat.substr(0, 5);
                
                if (nobet.vardiya_id == vardiya.id) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            
            var modal = new bootstrap.Modal(document.getElementById('duzenleModal'));
            modal.show();
        }
    </script>
</body>
</html>