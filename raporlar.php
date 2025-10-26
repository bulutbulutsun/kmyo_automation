<?php
require_once 'config.php';

Session::start();
$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();

// Rapor parametreleri
$personel_id = isset($_GET['personel_id']) ? (int)$_GET['personel_id'] : 0;
$baslangic_tarihi = isset($_GET['baslangic_tarihi']) ? $_GET['baslangic_tarihi'] : date('Y-m-01');
$bitis_tarihi = isset($_GET['bitis_tarihi']) ? $_GET['bitis_tarihi'] : date('Y-m-t');

// Personel listesi
$personeller = $db->fetchAll("SELECT id, sicil_no, ad, soyad FROM personel WHERE aktif = 1 ORDER BY ad, soyad");

// Rapor verilerini getir
$rapor_verileri = array();
if ($personel_id > 0) {
    $rapor_verileri = $db->fetchAll(
        "SELECT np.*, vs.vardiya_adi, vs.baslangic_saat, vs.bitis_saat, vs.lokasyon, vs.calisma_saati,
         p.ad, p.soyad, p.sicil_no
         FROM nobet_programi np
         LEFT JOIN vardiya_sablonlari vs ON np.vardiya_id = vs.id
         INNER JOIN personel p ON np.personel_id = p.id
         WHERE np.personel_id = ? AND np.tarih BETWEEN ? AND ?
         ORDER BY np.tarih ASC",
        array($personel_id, $baslangic_tarihi, $bitis_tarihi)
    );
}

// İstatistikler
$toplam_nobet = 0;
$toplam_saat = 0;
$hafta_tatili_sayisi = 0;
$resmi_tatil_sayisi = 0;
$kampus_giris_sayisi = 0;
$kampus_ici_sayisi = 0;

foreach ($rapor_verileri as $veri) {
    if ($veri['durum'] === 'hafta_tatili') {
        $hafta_tatili_sayisi++;
    } elseif ($veri['durum'] === 'resmi_tatil') {
        $resmi_tatil_sayisi++;
        $toplam_nobet++;
        $toplam_saat += $veri['calisma_saati'];
    } elseif ($veri['vardiya_id']) {
        $toplam_nobet++;
        $toplam_saat += $veri['calisma_saati'];
        if ($veri['lokasyon'] === 'kampus_giris') {
            $kampus_giris_sayisi++;
        } else {
            $kampus_ici_sayisi++;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporlar - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark no-print">
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
            <div class="col-md-3 col-lg-2 px-0 sidebar no-print">
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
                    <a class="nav-link active" href="genel_nobet_listesi.php">
                        <i class="bi bi-list-ul"></i>Genel Nöbet Listesi
                    </a>
                    <a class="nav-link" href="nobet_olustur.php">
                        <i class="bi bi-calendar-plus"></i>Nöbet Oluştur
                    </a>
                    <a class="nav-link" href="nobet_duzenle.php">
                        <i class="bi bi-calendar-check"></i>Nöbet Düzenle
                    </a>
                    <a class="nav-link active" href="raporlar.php">
                        <i class="bi bi-file-earmark-text"></i>Raporlar
                    </a>
                    <a class="nav-link" href="ayarlar.php">
                        <i class="bi bi-gear"></i>Ayarlar
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h2>
                        <i class="bi bi-file-earmark-text text-primary me-2"></i>Personel Nöbet Raporu
                    </h2>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bi bi-printer me-2"></i>Yazdır / PDF
                    </button>
                </div>
                
                <!-- Filtreler -->
                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Personel Seçin</label>
                                    <select name="personel_id" class="form-select" required>
                                        <option value="">Personel Seçiniz</option>
                                        <?php foreach ($personeller as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" <?php echo $personel_id == $p['id'] ? 'selected' : ''; ?>>
                                                <?php echo Helper::escape($p['sicil_no'] . ' - ' . $p['ad'] . ' ' . $p['soyad']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Başlangıç Tarihi</label>
                                    <input type="date" name="baslangic_tarihi" class="form-control" value="<?php echo $baslangic_tarihi; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Bitiş Tarihi</label>
                                    <input type="date" name="bitis_tarihi" class="form-control" value="<?php echo $bitis_tarihi; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search me-2"></i>Sorgula
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (count($rapor_verileri) > 0): ?>
                    <!-- İstatistikler -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-box">
                                <i class="bi bi-calendar-check text-primary" style="font-size: 32px;"></i>
                                <h3><?php echo $toplam_nobet; ?></h3>
                                <p class="text-muted mb-0">Toplam Nöbet</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-box">
                                <i class="bi bi-clock text-success" style="font-size: 32px;"></i>
                                <h3><?php echo $toplam_saat; ?></h3>
                                <p class="text-muted mb-0">Toplam Saat</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-box">
                                <i class="bi bi-calendar-x text-warning" style="font-size: 32px;"></i>
                                <h3><?php echo $hafta_tatili_sayisi; ?></h3>
                                <p class="text-muted mb-0">Hafta Tatili</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-box">
                                <i class="bi bi-calendar-event text-danger" style="font-size: 32px;"></i>
                                <h3><?php echo $resmi_tatil_sayisi; ?></h3>
                                <p class="text-muted mb-0">Resmi Tatil</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Personel Bilgisi -->
                    <?php if (count($rapor_verileri) > 0): 
                        $ilk_kayit = $rapor_verileri[0];
                    ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Personel Bilgileri</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Sicil No:</strong> <?php echo Helper::escape($ilk_kayit['sicil_no']); ?></p>
                                    <p><strong>Ad Soyad:</strong> <?php echo Helper::escape($ilk_kayit['ad'] . ' ' . $ilk_kayit['soyad']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Rapor Tarihi:</strong> <?php echo Helper::formatDate($baslangic_tarihi) . ' - ' . Helper::formatDate($bitis_tarihi); ?></p>
                                    <p><strong>Rapor Oluşturma:</strong> <?php echo date('d.m.Y H:i'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Detaylı Liste -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Detaylı Nöbet Listesi</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Gün</th>
                                            <th>Vardiya</th>
                                            <th>Lokasyon</th>
                                            <th>Saat</th>
                                            <th>Çalışma Saati</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rapor_verileri as $veri): ?>
                                            <tr>
                                                <td><?php echo Helper::formatDate($veri['tarih']); ?></td>
                                                <td><?php echo Helper::getTurkishDayName($veri['tarih']); ?></td>
                                                <td><?php echo $veri['vardiya_adi'] ? Helper::escape($veri['vardiya_adi']) : '-'; ?></td>
                                                <td>
                                                    <?php 
                                                    if ($veri['lokasyon']) {
                                                        echo $veri['lokasyon'] === 'kampus_giris' ? 'Kampüs Girişi' : 'Kampüs İçi';
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($veri['baslangic_saat']) {
                                                        echo substr($veri['baslangic_saat'], 0, 5) . ' - ' . substr($veri['bitis_saat'], 0, 5);
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center"><?php echo $veri['calisma_saati'] ? $veri['calisma_saati'] : '-'; ?></td>
                                                <td>
                                                    <?php if ($veri['durum'] === 'hafta_tatili'): ?>
                                                        <span class="badge bg-warning">Hafta Tatili</span>
                                                    <?php elseif ($veri['durum'] === 'resmi_tatil'): ?>
                                                        <span class="badge bg-danger">Resmi Tatil</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Nöbet</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lokasyon Dağılımı -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Lokasyon Dağılımı</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="progress" style="height: 30px;">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: <?php echo $toplam_nobet > 0 ? ($kampus_giris_sayisi / $toplam_nobet * 100) : 0; ?>%">
                                            Kampüs Girişi: <?php echo $kampus_giris_sayisi; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="progress" style="height: 30px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo $toplam_nobet > 0 ? ($kampus_ici_sayisi / $toplam_nobet * 100) : 0; ?>%">
                                            Kampüs İçi: <?php echo $kampus_ici_sayisi; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($personel_id > 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Seçilen tarih aralığında bu personele ait nöbet kaydı bulunamadı!
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Rapor görüntülemek için lütfen personel seçip tarih aralığı belirleyin!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>