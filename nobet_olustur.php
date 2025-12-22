<?php
require_once 'config.php';
require_once 'nobet_algoritma.php';


Session::start();
$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$message = '';
$error = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['olustur'])) {
    $baslangic_tarihi = $_POST['baslangic_tarihi'] ?? '';
    $bitis_tarihi = $_POST['bitis_tarihi'] ?? '';
    $resmi_tatil_odeme = $_POST['resmi_tatil_odeme'] ?? 'ucret';
    $fazla_mesai_odeme = $_POST['fazla_mesai_odeme'] ?? 'izin';
    
    if (empty($baslangic_tarihi) || empty($bitis_tarihi)) {
        $error = 'Başlangıç ve bitiş tarihleri gereklidir!';
    } elseif (strtotime($baslangic_tarihi) > strtotime($bitis_tarihi)) {
        $error = 'Başlangıç tarihi bitiş tarihinden sonra olamaz!';
    } else {
        try {
            // Ayarları güncelle
            $db->query("UPDATE program_ayarlari SET ayar_degeri = ? WHERE ayar_adi = 'resmi_tatil_odeme'", array($resmi_tatil_odeme));
            $db->query("UPDATE program_ayarlari SET ayar_degeri = ? WHERE ayar_adi = 'fazla_mesai_odeme'", array($fazla_mesai_odeme));
            
            // Nöbet algoritmasını çalıştır
            $algoritma = new NobetAlgoritma();
            $sonuc = $algoritma->nobetOlustur($baslangic_tarihi, $bitis_tarihi);
            
            if ($sonuc['basarili']) {
                $message = $sonuc['mesaj'];
            } else {
                $error = $sonuc['mesaj'];
            }
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Mevcut ayarları getir
$ayarlar = $db->fetchAll("SELECT * FROM program_ayarlari");
$mevcut_ayarlar = array();
foreach ($ayarlar as $ayar) {
    $mevcut_ayarlar[$ayar['ayar_adi']] = $ayar['ayar_degeri'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nöbet Oluştur - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link active" href="nobet_olustur.php">
                        <i class="bi bi-calendar-plus"></i>Nöbet Oluştur
                    </a>
                    <a class="nav-link" href="nobet_duzenle.php">
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
                    <i class="bi bi-calendar-plus text-primary me-2"></i>Otomatik Nöbet Programı Oluştur
                </h2>
                
                <?php if ($message): ?>
                    <?php echo Helper::showSuccess($message); ?>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <?php echo Helper::showError($error); ?>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card form-card">
                            <div class="card-body p-4">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Başlangıç Tarihi</label>
                                            <input type="date" class="form-control" name="baslangic_tarihi" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Bitiş Tarihi</label>
                                            <input type="date" class="form-control" name="bitis_tarihi" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Resmi Tatilde Çalışma Karşılığı</label>
                                        <select class="form-select" name="resmi_tatil_odeme">
                                            <option value="ucret" <?php echo ($mevcut_ayarlar['resmi_tatil_odeme'] ?? '') === 'ucret' ? 'selected' : ''; ?>>
                                                Ücret Öde
                                            </option>
                                            <option value="izin" <?php echo ($mevcut_ayarlar['resmi_tatil_odeme'] ?? '') === 'izin' ? 'selected' : ''; ?>>
                                                İzin Ver
                                            </option>
                                        </select>
                                        <small class="text-muted">Resmi tatil günlerinde çalışan personellere ne uygulanacağını seçin!</small>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Fazla Mesai Karşılığı</label>
                                        <select class="form-select" name="fazla_mesai_odeme">
                                            <option value="ucret" <?php echo ($mevcut_ayarlar['fazla_mesai_odeme'] ?? '') === 'ucret' ? 'selected' : ''; ?>>
                                                Ücret Öde
                                            </option>
                                            <option value="izin" <?php echo ($mevcut_ayarlar['fazla_mesai_odeme'] ?? '') === 'izin' ? 'selected' : ''; ?>>
                                                İzin Ver
                                            </option>
                                        </select>
                                        <small class="text-muted">Haftalık çalışma saatini aşan personellere ne uygulanacağını seçin!</small>
                                    </div>
                                    
                                    <button type="submit" name="olustur" class="btn btn-primary btn-lg w-100">
                                        <i class="bi bi-magic me-2"></i>Otomatik Nöbet Programı Oluştur
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="info-box mb-3">
                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-info-circle me-2"></i>Bilgilendirme
                            </h5>
                            <ul class="mb-0 ps-3">
                                <li class="mb-2">Sistem seçilen tarih aralığında tüm personel için otomatik nöbet programı oluşturur!</li>
                                <li class="mb-2">Memurlar haftada 5 gün (40 saat), işçiler haftada 6 gün (45 saat) çalışır!</li>
                                <li class="mb-2">Her personel günlük 8 saat çalışır!</li>
                                <li class="mb-2">Hafta tatili tercihleri dikkate alınır!</li>
                                <li class="mb-2">Vardiyalar kampüs girişi ve kampüs içi için adil şekilde dağıtılır!</li>
                                <li>Resmi tatil günleri otomatik olarak işaretlenir!</li>
                            </ul>
                        </div>
                        
                        <div class="card border-warning">
                            <div class="card-body">
                                <h6 class="text-warning fw-bold mb-2">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Uyarı
                                </h6>
                                <p class="mb-0 small">
                                    Seçili tarih aralığındaki mevcut nöbet kayıtları silinecek ve yeniden oluşturulacaktır. 
                                    Bu işlem geri alınamaz!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>