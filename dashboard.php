<?php
require_once 'config.php';

Session::start();
$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

// İstatistikler
$personel_id = Session::getPersonelId();
$is_admin = Session::isAdmin();

// Bugünün tarihi
$bugun = date('Y-m-d');

// Kullanıcıya özel istatistikler
$bugunun_nobeti = $db->fetchOne(
    "SELECT np.*, vs.vardiya_adi, vs.baslangic_saat, vs.bitis_saat, vs.lokasyon 
     FROM nobet_programi np 
     LEFT JOIN vardiya_sablonlari vs ON np.vardiya_id = vs.id 
     WHERE np.personel_id = ? AND np.tarih = ?",
    array($personel_id, $bugun)
);

// Bu ayki nöbet sayısı
$bu_ay_baslangic = date('Y-m-01');
$bu_ay_bitis = date('Y-m-t');

$bu_ay_nobet_sayisi = $db->fetchOne(
    "SELECT COUNT(*) as sayi FROM nobet_programi 
     WHERE personel_id = ? AND tarih BETWEEN ? AND ? AND vardiya_id IS NOT NULL",
    array($personel_id, $bu_ay_baslangic, $bu_ay_bitis)
)['sayi'];

// Bu ayki hafta tatili sayısı
$bu_ay_tatil_sayisi = $db->fetchOne(
    "SELECT COUNT(*) as sayi FROM nobet_programi 
     WHERE personel_id = ? AND tarih BETWEEN ? AND ? AND durum = 'hafta_tatili'",
    array($personel_id, $bu_ay_baslangic, $bu_ay_bitis)
)['sayi'];

// Yaklaşan nöbetler (gelecek 7 gün)
$gelecek_7_gun = date('Y-m-d', strtotime('+7 days'));
$yaklasan_nobetler = $db->fetchAll(
    "SELECT np.*, vs.vardiya_adi, vs.baslangic_saat, vs.bitis_saat, vs.lokasyon 
     FROM nobet_programi np 
     LEFT JOIN vardiya_sablonlari vs ON np.vardiya_id = vs.id 
     WHERE np.personel_id = ? AND np.tarih BETWEEN ? AND ? AND vardiya_id IS NOT NULL
     ORDER BY np.tarih ASC",
    array($personel_id, $bugun, $gelecek_7_gun)
);


// Yönetici için ek istatistikler
if ($is_admin) {
    // Toplam personel sayısı
    $toplam_personel = $db->fetchOne("SELECT COUNT(*) as sayi FROM personel WHERE aktif = 1")['sayi'];
    
    // Bu ayki toplam nöbet sayısı
    $bu_ay_toplam_nobet = $db->fetchOne(
        "SELECT COUNT(*) as sayi FROM nobet_programi 
         WHERE tarih BETWEEN ? AND ? AND vardiya_id IS NOT NULL",
        array($bu_ay_baslangic, $bu_ay_bitis)
    )['sayi'];
    
    // Bugün nöbetçi sayısı
    $bugun_nobetci_sayisi = $db->fetchOne(
        "SELECT COUNT(*) as sayi FROM nobet_programi 
         WHERE tarih = ? AND vardiya_id IS NOT NULL",
        array($bugun)
    )['sayi'];
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>Ana Sayfa
                    </a>
                    <a class="nav-link" href="nobet_listesi.php">
                        <i class="bi bi-calendar3"></i>Nöbet Listem
                    </a>

                    
                    <?php if ($is_admin): ?>
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
                        <a class="nav-link" href="ayarlar.php">
                            <i class="bi bi-gear"></i>Ayarlar
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <h2 class="mb-4">Hoş Geldiniz, <?php echo Helper::escape(Session::getUserName()); ?>!</h2>
                
                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <?php if ($is_admin): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="text-muted mb-1">Toplam Personel</h6>
                                            <h3 class="mb-0"><?php echo $toplam_personel; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-calendar-check"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="text-muted mb-1">Bu Ay Nöbet</h6>
                                            <h3 class="mb-0"><?php echo $bu_ay_toplam_nobet; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-clock"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="text-muted mb-1">Bugün Nöbetçi</h6>
                                            <h3 class="mb-0"><?php echo $bugun_nobetci_sayisi; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                                        <i class="bi bi-calendar3"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="text-muted mb-1">Bu Ay Nöbetim</h6>
                                        <h3 class="mb-0"><?php echo $bu_ay_nobet_sayisi; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                                        <i class="bi bi-calendar-x"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="text-muted mb-1">Hafta Tatilim</h6>
                                        <h3 class="mb-0"><?php echo $bu_ay_tatil_sayisi; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bugünün Nöbeti -->
                <?php if ($bugunun_nobeti && $bugunun_nobeti['vardiya_id']): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="bi bi-calendar-day text-primary me-2"></i>Bugünün Nöbeti
                                </h5>
                                <div class="alert alert-primary d-flex align-items-center" role="alert">
                                    <i class="bi bi-info-circle-fill me-3" style="font-size: 24px;"></i>
                                    <div>
                                        <strong><?php echo Helper::escape($bugunun_nobeti['vardiya_adi']); ?></strong> - 
                                        <?php echo Helper::escape($bugunun_nobeti['lokasyon'] === 'kampus_giris' ? 'Kampüs Girişi' : 'Kampüs İçi'); ?>
                                        <br>
                                        <small>
                                            Saat: <?php echo substr($bugunun_nobeti['baslangic_saat'], 0, 5); ?> - 
                                            <?php echo substr($bugunun_nobeti['bitis_saat'], 0, 5); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Yaklaşan Nöbetler -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="bi bi-calendar-range text-success me-2"></i>Yaklaşan Nöbetlerim (7 Gün)
                                </h5>
                                
                                <?php if (count($yaklasan_nobetler) > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($yaklasan_nobetler as $nobet): ?>
                                            <div class="list-group-item nobet-card mb-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <?php echo Helper::getTurkishDayName($nobet['tarih']); ?>, 
                                                            <?php echo Helper::formatDate($nobet['tarih']); ?>
                                                        </h6>
                                                        <p class="mb-1">
                                                            <strong><?php echo Helper::escape($nobet['vardiya_adi']); ?></strong>
                                                        </p>
                                                        <small class="text-muted">
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?php echo substr($nobet['baslangic_saat'], 0, 5); ?> - 
                                                            <?php echo substr($nobet['bitis_saat'], 0, 5); ?>
                                                            <span class="ms-3">
                                                                <i class="bi bi-geo-alt me-1"></i>
                                                                <?php echo $nobet['lokasyon'] === 'kampus_giris' ? 'Kampüs Girişi' : 'Kampüs İçi'; ?>
                                                            </span>
                                                        </small>
                                                    </div>
                                                    <span class="badge badge-custom bg-primary">
                                                        Nöbet
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Önümüzdeki 7 gün içinde planlanmış nöbetiniz bulunmamaktadır!
                                    </div>
                                <?php endif; ?>
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