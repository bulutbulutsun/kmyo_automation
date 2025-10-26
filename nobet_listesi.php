<?php
require_once 'config.php';

Session::start();
$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$personel_id = Session::getPersonelId();

// Ay ve yıl seçimi
$secili_ay = isset($_GET['ay']) ? (int)$_GET['ay'] : (int)date('m');
$secili_yil = isset($_GET['yil']) ? (int)$_GET['yil'] : (int)date('Y');

// Ayın ilk ve son günü
$ay_baslangic = sprintf('%04d-%02d-01', $secili_yil, $secili_ay);
$ay_bitis = date('Y-m-t', strtotime($ay_baslangic));

// Nöbet kayıtlarını getir
$nobetler = $db->fetchAll(
    "SELECT np.*, vs.vardiya_adi, vs.baslangic_saat, vs.bitis_saat, vs.lokasyon 
     FROM nobet_programi np 
     LEFT JOIN vardiya_sablonlari vs ON np.vardiya_id = vs.id 
     WHERE np.personel_id = ? AND np.tarih BETWEEN ? AND ? 
     ORDER BY np.tarih ASC",
    array($personel_id, $ay_baslangic, $ay_bitis)
);

// Tarihe göre indexle
$nobet_map = array();
foreach ($nobetler as $nobet) {
    $nobet_map[$nobet['tarih']] = $nobet;
}

// İstatistikler
$nobet_sayisi = 0;
$tatil_sayisi = 0;
$resmi_tatil_sayisi = 0;

foreach ($nobetler as $nobet) {
    if ($nobet['durum'] === 'hafta_tatili') {
        $tatil_sayisi++;
    } elseif ($nobet['durum'] === 'resmi_tatil') {
        $resmi_tatil_sayisi++;
        $nobet_sayisi++;
    } elseif ($nobet['vardiya_id']) {
        $nobet_sayisi++;
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nöbet Listem - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link active" href="nobet_listesi.php">
                        <i class="bi bi-calendar3"></i>Nöbet Listem
                    </a>
                    
                    <?php if (Session::isAdmin()): ?>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="bi bi-calendar3 text-primary me-2"></i>Nöbet Listem
                    </h2>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bi bi-printer me-2"></i>Yazdır
                    </button>
                </div>
                
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
                
                <!-- İstatistikler -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="bi bi-bar-chart me-2"></i>Aylık Özet
                        </h5>
                        <div>
                            <span class="stat-badge bg-primary text-white">
                                <i class="bi bi-calendar-check me-1"></i>Nöbet: <?php echo $nobet_sayisi; ?>
                            </span>
                            <span class="stat-badge bg-warning text-dark">
                                <i class="bi bi-calendar-x me-1"></i>Hafta Tatili: <?php echo $tatil_sayisi; ?>
                            </span>
                            <span class="stat-badge bg-danger text-white">
                                <i class="bi bi-calendar-event me-1"></i>Resmi Tatil: <?php echo $resmi_tatil_sayisi; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Takvim -->
                <div class="table-responsive">
                    <table class="table calendar-table">
                        <thead>
                            <tr>
                                <th>Pazartesi</th>
                                <th>Salı</th>
                                <th>Çarşamba</th>
                                <th>Perşembe</th>
                                <th>Cuma</th>
                                <th>Cumartesi</th>
                                <th>Pazar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Ayın ilk gününün haftanın hangi günü olduğunu bul
                            $ilk_gun = new DateTime($ay_baslangic);
                            $ilk_gun_no = (int)$ilk_gun->format('N'); // 1=Pazartesi, 7=Pazar
                            
                            // Ayın toplam gün sayısı
                            $toplam_gun = (int)date('t', strtotime($ay_baslangic));
                            
                            // Bugünün tarihi
                            $bugun = date('Y-m-d');
                            
                            // Takvimi oluştur
                            $gun_sayaci = 1;
                            $hafta_sayaci = 0;
                            
                            while ($gun_sayaci <= $toplam_gun) {
                                echo '<tr>';
                                
                                for ($gun_index = 1; $gun_index <= 7; $gun_index++) {
                                    if (($hafta_sayaci === 0 && $gun_index < $ilk_gun_no) || $gun_sayaci > $toplam_gun) {
                                        echo '<td class="weekend"></td>';
                                    } else {
                                        $tarih_str = sprintf('%04d-%02d-%02d', $secili_yil, $secili_ay, $gun_sayaci);
                                        $nobet = isset($nobet_map[$tarih_str]) ? $nobet_map[$tarih_str] : null;
                                        
                                        $td_class = '';
                                        if ($tarih_str === $bugun) {
                                            $td_class = 'today';
                                        } elseif ($gun_index == 6 || $gun_index == 7) {
                                            $td_class = 'weekend';
                                        }
                                        
                                        echo '<td class="' . $td_class . '">';
                                        echo '<div class="day-number">' . $gun_sayaci . '</div>';
                                        
                                        if ($nobet) {
                                            if ($nobet['durum'] === 'hafta_tatili') {
                                                echo '<div class="vardiya-info hafta-tatili">';
                                                echo '<i class="bi bi-calendar-x me-1"></i>Hafta Tatili';
                                                echo '</div>';
                                            } elseif ($nobet['durum'] === 'resmi_tatil') {
                                                echo '<div class="vardiya-info resmi-tatil">';
                                                echo '<i class="bi bi-calendar-event me-1"></i>Resmi Tatil<br>';
                                                echo '<strong>' . Helper::escape($nobet['vardiya_adi']) . '</strong><br>';
                                                echo '<small>' . substr($nobet['baslangic_saat'], 0, 5) . ' - ' . substr($nobet['bitis_saat'], 0, 5) . '</small>';
                                                echo '</div>';
                                            } elseif ($nobet['vardiya_id']) {
                                                $vardiya_class = $nobet['lokasyon'] === 'kampus_giris' ? 'vardiya-kampus-giris' : 'vardiya-kampus-ici';
                                                echo '<div class="vardiya-info ' . $vardiya_class . '">';
                                                echo '<strong>' . Helper::escape($nobet['vardiya_adi']) . '</strong><br>';
                                                echo '<small>' . substr($nobet['baslangic_saat'], 0, 5) . ' - ' . substr($nobet['bitis_saat'], 0, 5) . '</small><br>';
                                                echo '<small>' . ($nobet['lokasyon'] === 'kampus_giris' ? 'Kampüs Giriş' : 'Kampüs İçi') . '</small>';
                                                echo '</div>';
                                            }
                                        }
                                        
                                        echo '</td>';
                                        $gun_sayaci++;
                                    }
                                }
                                
                                echo '</tr>';
                                $hafta_sayaci++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Açıklama -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Açıklama:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="vardiya-info vardiya-kampus-giris mb-2">
                                    <i class="bi bi-shield me-1"></i>Kampüs Girişi Vardiyası
                                </div>
                                <div class="vardiya-info vardiya-kampus-ici mb-2">
                                    <i class="bi bi-building me-1"></i>Kampüs İçi Vardiyası
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="vardiya-info hafta-tatili mb-2">
                                    <i class="bi bi-calendar-x me-1"></i>Hafta Tatili
                                </div>
                                <div class="vardiya-info resmi-tatil mb-2">
                                    <i class="bi bi-calendar-event me-1"></i>Resmi Tatil (Çalışma)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style media="print">
        .navbar, .sidebar, button, .no-print {
            display: none !important;
        }
        .calendar-table {
            page-break-inside: avoid;
        }
    </style>
</body>
</html>