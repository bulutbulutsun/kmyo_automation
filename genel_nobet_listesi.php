<?php
require_once 'config.php';

Session::start();
$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();

// Tarih aralığı seçimi
$baslangic_tarihi = isset($_GET['baslangic_tarihi']) ? $_GET['baslangic_tarihi'] : date('Y-m-01');
$bitis_tarihi = isset($_GET['bitis_tarihi']) ? $_GET['bitis_tarihi'] : date('Y-m-t');

// Personel filtresi
$secili_personel = isset($_GET['personel_id']) ? (int)$_GET['personel_id'] : 0;

// Aktif personelleri getir
$personeller = $db->fetchAll("SELECT id, sicil_no, ad, soyad FROM personel WHERE aktif = 1 ORDER BY ad, soyad");

// Nöbet kayıtlarını getir
$where_clause = "np.tarih BETWEEN ? AND ?";
$params = array($baslangic_tarihi, $bitis_tarihi);

if ($secili_personel > 0) {
    $where_clause .= " AND np.personel_id = ?";
    $params[] = $secili_personel;
}

$nobetler = $db->fetchAll(
    "SELECT np.*, vs.vardiya_adi, vs.baslangic_saat, vs.bitis_saat, vs.lokasyon, vs.calisma_saati,
     p.ad, p.soyad, p.sicil_no, p.kadro_turu
     FROM nobet_programi np
     INNER JOIN personel p ON np.personel_id = p.id
     LEFT JOIN vardiya_sablonlari vs ON np.vardiya_id = vs.id
     WHERE {$where_clause}
     ORDER BY np.tarih ASC, p.ad ASC, p.soyad ASC",
    $params
);

// Tarihe göre grupla
$nobetler_tarih = array();
foreach ($nobetler as $nobet) {
    $nobetler_tarih[$nobet['tarih']][] = $nobet;
}

// İstatistikler
$toplam_nobet = 0;
$toplam_saat = 0;
$personel_istatistik = array();

foreach ($nobetler as $nobet) {
    $p_id = $nobet['personel_id'];
    
    if (!isset($personel_istatistik[$p_id])) {
        $personel_istatistik[$p_id] = array(
            'ad' => $nobet['ad'] . ' ' . $nobet['soyad'],
            'sicil' => $nobet['sicil_no'],
            'nobet_sayisi' => 0,
            'hafta_tatili' => 0,
            'resmi_tatil' => 0,
            'toplam_saat' => 0
        );
    }
    
    if ($nobet['durum'] === 'hafta_tatili') {
        $personel_istatistik[$p_id]['hafta_tatili']++;
    } elseif ($nobet['durum'] === 'resmi_tatil') {
        $personel_istatistik[$p_id]['resmi_tatil']++;
        $personel_istatistik[$p_id]['nobet_sayisi']++;
        $personel_istatistik[$p_id]['toplam_saat'] += $nobet['calisma_saati'];
        $toplam_saat += $nobet['calisma_saati'];
        $toplam_nobet++;
    } elseif ($nobet['vardiya_id']) {
        $personel_istatistik[$p_id]['nobet_sayisi']++;
        $personel_istatistik[$p_id]['toplam_saat'] += $nobet['calisma_saati'];
        $toplam_saat += $nobet['calisma_saati'];
        $toplam_nobet++;
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genel Nöbet Listesi - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .nobet-row {
            border-bottom: 1px solid #e0e0e0;
        }
        .nobet-row:hover {
            background-color: #f8f9fa;
        }
        .personel-name {
            font-weight: 600;
            color: #333;
        }
        .vardiya-badge {
            font-size: 11px;
            padding: 4px 8px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .sidebar {
                display: none !important;
            }
            .navbar {
                display: none !important;
            }
        }
    </style>
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
                    
                    <?php if (Session::isAdmin()): ?>
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
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h2>
                        <i class="bi bi-list-ul text-primary me-2"></i>Genel Nöbet Listesi
                    </h2>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bi bi-printer me-2"></i>Yazdır
                    </button>
                </div>
                
                <!-- Filtreler -->
                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Personel</label>
                                <select name="personel_id" class="form-select">
                                    <option value="0">Tüm Personel</option>
                                    <?php foreach ($personeller as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo $secili_personel == $p['id'] ? 'selected' : ''; ?>>
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
                                    <i class="bi bi-search me-2"></i>Filtrele
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Özet İstatistikler -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo $toplam_nobet; ?></h3>
                                <p class="text-muted mb-0">Toplam Nöbet</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo $toplam_saat; ?></h3>
                                <p class="text-muted mb-0">Toplam Saat</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo count($personel_istatistik); ?></h3>
                                <p class="text-muted mb-0">Personel Sayısı</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?php echo count($nobetler_tarih); ?></h3>
                                <p class="text-muted mb-0">Gün Sayısı</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personel İstatistikleri -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-bar-chart me-2"></i>Personel Bazlı İstatistikler
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sicil No</th>
                                        <th>Ad Soyad</th>
                                        <th class="text-center">Nöbet Sayısı</th>
                                        <th class="text-center">Hafta Tatili</th>
                                        <th class="text-center">Resmi Tatil</th>
                                        <th class="text-center">Toplam Saat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($personel_istatistik as $stat): ?>
                                        <tr>
                                            <td><?php echo Helper::escape($stat['sicil']); ?></td>
                                            <td><strong><?php echo Helper::escape($stat['ad']); ?></strong></td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?php echo $stat['nobet_sayisi']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning text-dark"><?php echo $stat['hafta_tatili']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-danger"><?php echo $stat['resmi_tatil']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><?php echo $stat['toplam_saat']; ?> saat</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Detaylı Nöbet Listesi -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-week me-2"></i>
                            <?php echo Helper::formatDate($baslangic_tarihi) . ' - ' . Helper::formatDate($bitis_tarihi); ?> - Detaylı Nöbet Listesi
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Gün</th>
                                        <th>Sicil No</th>
                                        <th>Ad Soyad</th>
                                        <th>Kadro</th>
                                        <th>Vardiya</th>
                                        <th>Lokasyon</th>
                                        <th>Saat</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($nobetler_tarih) > 0): ?>
                                        <?php foreach ($nobetler_tarih as $tarih => $gun_nobetleri): ?>
                                            <?php foreach ($gun_nobetleri as $index => $nobet): ?>
                                                <tr class="nobet-row">
                                                    <?php if ($index === 0): ?>
                                                        <td rowspan="<?php echo count($gun_nobetleri); ?>" class="align-middle bg-light">
                                                            <strong><?php echo Helper::formatDate($tarih); ?></strong>
                                                        </td>
                                                        <td rowspan="<?php echo count($gun_nobetleri); ?>" class="align-middle bg-light">
                                                            <strong><?php echo Helper::getTurkishDayName($tarih); ?></strong>
                                                        </td>
                                                    <?php endif; ?>
                                                    
                                                    <td><?php echo Helper::escape($nobet['sicil_no']); ?></td>
                                                    <td class="personel-name"><?php echo Helper::escape($nobet['ad'] . ' ' . $nobet['soyad']); ?></td>
                                                    <td>
                                                        <span class="badge vardiya-badge <?php echo $nobet['kadro_turu'] === 'memur' ? 'bg-primary' : 'bg-info'; ?>">
                                                            <?php echo $nobet['kadro_turu'] === 'memur' ? 'Memur' : 'İşçi'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $nobet['vardiya_adi'] ? Helper::escape($nobet['vardiya_adi']) : '-'; ?></td>
                                                    <td>
                                                        <?php if ($nobet['lokasyon']): ?>
                                                            <span class="badge vardiya-badge <?php echo $nobet['lokasyon'] === 'kampus_giris' ? 'bg-primary' : 'bg-secondary'; ?>">
                                                                <?php echo $nobet['lokasyon'] === 'kampus_giris' ? 'Kampüs Giriş' : 'Kampüs İçi'; ?>
                                                            </span>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($nobet['baslangic_saat']): ?>
                                                            <small><?php echo substr($nobet['baslangic_saat'], 0, 5) . ' - ' . substr($nobet['bitis_saat'], 0, 5); ?></small>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($nobet['durum'] === 'hafta_tatili'): ?>
                                                            <span class="badge bg-warning text-dark">Hafta Tatili</span>
                                                        <?php elseif ($nobet['durum'] === 'resmi_tatil'): ?>
                                                            <span class="badge bg-danger">Resmi Tatil</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Nöbet</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="bi bi-info-circle me-2"></i>
                                                Seçilen tarih aralığında nöbet kaydı bulunamadı!
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>