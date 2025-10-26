<?php
require_once 'config.php';

Session::start();
$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$personel_id = Session::getPersonelId();
$message = '';
$error = '';

// Personel bilgilerini getir
$personel = $db->fetchOne(
    "SELECT p.*, k.kullanici_adi, k.rol, it.tercih_edilen_gun1, it.tercih_edilen_gun2 
     FROM personel p 
     INNER JOIN kullanicilar k ON p.id = k.personel_id 
     LEFT JOIN izin_tercihleri it ON p.id = it.personel_id 
     WHERE p.id = ?",
    array($personel_id)
);

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sifre_degistir'])) {
    $eski_sifre = $_POST['eski_sifre'] ?? '';
    $yeni_sifre = $_POST['yeni_sifre'] ?? '';
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'] ?? '';
    
    if (empty($eski_sifre) || empty($yeni_sifre) || empty($yeni_sifre_tekrar)) {
        $error = 'Tüm alanları doldurunuz!';
    } elseif ($yeni_sifre !== $yeni_sifre_tekrar) {
        $error = 'Yeni şifreler eşleşmiyor!';
    } elseif (strlen($yeni_sifre) < 6) {
        $error = 'Yeni şifre en az 6 karakter olmalıdır!';
    } else {
        // Eski şifreyi kontrol et
        $kullanici = $db->fetchOne(
            "SELECT * FROM kullanicilar WHERE personel_id = ?",
            array($personel_id)
        );
        
        if (Helper::verifyPassword($eski_sifre, $kullanici['sifre'])) {
            // Yeni şifreyi güncelle
            $yeni_hash = Helper::hashPassword($yeni_sifre);
            $db->query(
                "UPDATE kullanicilar SET sifre = ? WHERE personel_id = ?",
                array($yeni_hash, $personel_id)
            );
            $message = 'Şifreniz başarıyla değiştirildi!';
        } else {
            $error = 'Eski şifre hatalı!';
        }
    }
}

// İletişim bilgilerini güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bilgi_guncelle'])) {
    $telefon = trim($_POST['telefon'] ?? '');
    $eposta = trim($_POST['eposta'] ?? '');
    $adres = trim($_POST['adres'] ?? '');
    
    try {
        $db->query(
            "UPDATE personel SET telefon = ?, eposta = ?, adres = ? WHERE id = ?",
            array($telefon, $eposta, $adres, $personel_id)
        );
        $message = 'İletişim bilgileriniz güncellendi!';
        
        // Sayfayı yenile
        header("Location: profil.php?success=1");
        exit();
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Gün isimleri
$gun_isimleri = array('Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi');

// Başarı mesajı
if (isset($_GET['success'])) {
    $message = 'İletişim bilgileriniz güncellendi!';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - <?php echo APP_NAME; ?></title>
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
					<a class="nav-link" href="genel_nobet_listesi.php">
                        <i class="bi bi-list-ul"></i>Genel Nöbet Listesi
                    </a>
                    <?php if (Session::isAdmin()): ?>
                        <hr class="my-2">
                        <h6 class="px-3 text-muted small fw-bold">YÖNETİCİ PANELİ</h6>
                        <a class="nav-link" href="personel_yonetimi.php">
                            <i class="bi bi-people"></i>Personel Yönetimi
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
                        <a class="nav-link" href="ayarlar.php">
                            <i class="bi bi-gear"></i>Ayarlar
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <h2 class="mb-4">
                    <i class="bi bi-person-circle text-primary me-2"></i>Profilim
                </h2>
                
                <?php if ($message): ?>
                    <?php echo Helper::showSuccess($message); ?>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <?php echo Helper::showError($error); ?>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Kişisel Bilgiler -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-badge me-2"></i>Kişisel Bilgiler
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="text-muted small">Sicil No</label>
                                    <p class="fw-bold"><?php echo Helper::escape($personel['sicil_no']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Ad Soyad</label>
                                    <p class="fw-bold"><?php echo Helper::escape($personel['ad'] . ' ' . $personel['soyad']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Kimlik No</label>
                                    <p class="fw-bold"><?php echo Helper::escape($personel['kimlik_no']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Kadro Türü</label>
                                    <p>
                                        <span class="badge <?php echo $personel['kadro_turu'] === 'memur' ? 'bg-primary' : 'bg-info'; ?>">
                                            <?php echo $personel['kadro_turu'] === 'memur' ? 'Memur' : 'İşçi'; ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Görev Unvanı</label>
                                    <p class="fw-bold"><?php echo Helper::escape($personel['gorev_unvani']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Kullanıcı Adı</label>
                                    <p class="fw-bold"><?php echo Helper::escape($personel['kullanici_adi']); ?></p>
                                </div>
                                
                                <div class="mb-0">
                                    <label class="text-muted small">Rol</label>
                                    <p>
                                        <span class="badge <?php echo $personel['rol'] === 'yonetici' ? 'bg-danger' : 'bg-success'; ?>">
                                            <?php echo $personel['rol'] === 'yonetici' ? 'Yönetici' : 'Kullanıcı'; ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <div class="mb-0">
                                    <label class="text-muted small">Hafta Tatili Tercihleri</label>
                                    <p class="fw-bold">
                                        <?php 
                                        $tercihler = array();
                                        if ($personel['tercih_edilen_gun1'] !== null) {
                                            $tercihler[] = $gun_isimleri[$personel['tercih_edilen_gun1']];
                                        }
                                        if ($personel['tercih_edilen_gun2'] !== null) {
                                            $tercihler[] = $gun_isimleri[$personel['tercih_edilen_gun2']];
                                        }
                                        echo count($tercihler) > 0 ? implode(', ', $tercihler) : 'Belirtilmemiş';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- İletişim Bilgileri -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-telephone me-2"></i>İletişim Bilgileri
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" class="form-control" name="telefon" 
                                               value="<?php echo Helper::escape($personel['telefon']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">E-posta</label>
                                        <input type="email" class="form-control" name="eposta" 
                                               value="<?php echo Helper::escape($personel['eposta']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Adres</label>
                                        <textarea class="form-control" name="adres" rows="3"><?php echo Helper::escape($personel['adres']); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="bilgi_guncelle" class="btn btn-success w-100">
                                        <i class="bi bi-check-circle me-2"></i>Güncelle
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Şifre Değiştir -->
                    <div class="col-lg-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="bi bi-key me-2"></i>Şifre Değiştir
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Eski Şifre *</label>
                                            <input type="password" class="form-control" name="eski_sifre" required>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Yeni Şifre * (Min. 6 karakter)</label>
                                            <input type="password" class="form-control" name="yeni_sifre" minlength="6" required>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Yeni Şifre Tekrar *</label>
                                            <input type="password" class="form-control" name="yeni_sifre_tekrar" minlength="6" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="sifre_degistir" class="btn btn-warning">
                                        <i class="bi bi-shield-lock me-2"></i>Şifreyi Değiştir
                                    </button>
                                </form>
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