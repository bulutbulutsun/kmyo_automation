<?php
require_once 'config.php';

Session::start();
$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$message = '';
$error = '';

// Personel ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekle'])) {
    $sicil_no = trim($_POST['sicil_no'] ?? '');
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    $kimlik_no = trim($_POST['kimlik_no'] ?? '');
    $kadro_turu = $_POST['kadro_turu'] ?? '';
    $gorev_unvani = trim($_POST['gorev_unvani'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $eposta = trim($_POST['eposta'] ?? '');
    $adres = trim($_POST['adres'] ?? '');
    $rol = $_POST['rol'] ?? 'kullanici';
    $tercih_gun1 = $_POST['tercih_gun1'] ?? null;
    $tercih_gun2 = $_POST['tercih_gun2'] ?? null;
    
    if (empty($sicil_no) || empty($ad) || empty($soyad) || empty($kimlik_no) || empty($kadro_turu) || empty($eposta)) {
        $error = 'E-posta dahil zorunlu alanları doldurunuz!';
    } else {
        try {
            // 1. E-posta benzersizlik kontrolü
            $mevcut_eposta = $db->fetchOne("SELECT id FROM personel WHERE eposta = ?", array($eposta));
            if ($mevcut_eposta) {
                throw new Exception("Bu e-posta adresi zaten kullanılıyor!");
            }

            // 2. Sicil no benzersizlik kontrolü
            $mevcut_sicil = $db->fetchOne("SELECT id FROM personel WHERE sicil_no = ?", array($sicil_no));
            if ($mevcut_sicil) {
                throw new Exception("Bu sicil numarası zaten kullanılıyor!");
            }

            // 3. T.C. Kimlik No benzersizlik kontrolü
            $mevcut_kimlik = $db->fetchOne("SELECT id FROM personel WHERE kimlik_no = ?", array($kimlik_no));
            if ($mevcut_kimlik) {
                throw new Exception("Bu T.C. kimlik numarası zaten sisteme kayıtlı!");
            }

            $db->query(
                "INSERT INTO personel (sicil_no, ad, soyad, kimlik_no, kadro_turu, gorev_unvani, telefon, eposta, adres) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array($sicil_no, $ad, $soyad, $kimlik_no, $kadro_turu, $gorev_unvani, $telefon, $eposta, $adres)
            );
            
            $personel_id = $db->lastInsertId();
            
            // Kullanıcı hesabı oluştur (Kullanıcı adı yok, sadece şifre ve rol)
            $sifre = Helper::hashPassword('123456');
            
            $db->query(
                "INSERT INTO kullanicilar (personel_id, sifre, rol) 
                 VALUES (?, ?, ?)",
                array($personel_id, $sifre, $rol)
            );
            
            // İzin tercihleri
            if ($tercih_gun1 !== null || $tercih_gun2 !== null) {
                $db->query(
                    "INSERT INTO izin_tercihleri (personel_id, tercih_edilen_gun1, tercih_edilen_gun2) 
                     VALUES (?, ?, ?)",
                    array($personel_id, $tercih_gun1, $tercih_gun2)
                );
            }
            
            $message = 'Personel başarıyla eklendi! Giriş e-postası: ' . $eposta . ', Varsayılan Şifre: 123456';
        } catch (Exception $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    }
}

// Personel güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guncelle'])) {
    $personel_id = $_POST['personel_id'] ?? 0;
    $sicil_no = trim($_POST['sicil_no'] ?? '');
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    $kimlik_no = trim($_POST['kimlik_no'] ?? '');
    $kadro_turu = $_POST['kadro_turu'] ?? '';
    $gorev_unvani = trim($_POST['gorev_unvani'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $eposta = trim($_POST['eposta'] ?? '');
    $adres = trim($_POST['adres'] ?? '');
    $rol = $_POST['rol'] ?? 'kullanici';
    $tercih_gun1 = $_POST['tercih_gun1'] ?? null;
    $tercih_gun2 = $_POST['tercih_gun2'] ?? null;
    
    try {
        // E-posta başkası tarafından kullanılıyor mu kontrolü
        $mevcut_eposta = $db->fetchOne("SELECT id FROM personel WHERE eposta = ? AND id != ?", array($eposta, $personel_id));
        if ($mevcut_eposta) {
            throw new Exception("Bu e-posta adresi başka bir personel tarafından kullanılıyor!");
        }

        // Sicil No başkası tarafından kullanılıyor mu kontrolü
        $mevcut_sicil = $db->fetchOne("SELECT id FROM personel WHERE sicil_no = ? AND id != ?", array($sicil_no, $personel_id));
        if ($mevcut_sicil) {
            throw new Exception("Bu sicil numarası başka bir personel tarafından kullanılıyor!");
        }

        // T.C. Kimlik No başkası tarafından kullanılıyor mu kontrolü
        $mevcut_kimlik = $db->fetchOne("SELECT id FROM personel WHERE kimlik_no = ? AND id != ?", array($kimlik_no, $personel_id));
        if ($mevcut_kimlik) {
            throw new Exception("Bu T.C. Kimlik numarası başka bir personel tarafından kullanılıyor!");
        }

        $db->query(
            "UPDATE personel SET sicil_no=?, ad=?, soyad=?, kimlik_no=?, kadro_turu=?, 
             gorev_unvani=?, telefon=?, eposta=?, adres=? WHERE id=?",
            array($sicil_no, $ad, $soyad, $kimlik_no, $kadro_turu, $gorev_unvani, $telefon, $eposta, $adres, $personel_id)
        );
        
        // Rolü güncelle
        $db->query(
            "UPDATE kullanicilar SET rol=? WHERE personel_id=?",
            array($rol, $personel_id)
        );

        // İzin tercihleri güncelle
        $mevcut_tercih = $db->fetchOne("SELECT * FROM izin_tercihleri WHERE personel_id = ?", array($personel_id));
        
        if ($mevcut_tercih) {
            $db->query(
                "UPDATE izin_tercihleri SET tercih_edilen_gun1=?, tercih_edilen_gun2=? WHERE personel_id=?",
                array($tercih_gun1, $tercih_gun2, $personel_id)
            );
        } else {
            $db->query(
                "INSERT INTO izin_tercihleri (personel_id, tercih_edilen_gun1, tercih_edilen_gun2) 
                 VALUES (?, ?, ?)",
                array($personel_id, $tercih_gun1, $tercih_gun2)
            );
        }
        
        $message = 'Personel bilgileri ve yetkileri güncellendi!';
    } catch (Exception $e) {
        $error = 'HATA: ' . $e->getMessage();
    }
}

// Personel pasif duruma getirme (Silme)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil'])) {
    $personel_id = $_POST['personel_id'] ?? 0;
    
    try {
        $db->query("UPDATE personel SET aktif = 0 WHERE id = ?", array($personel_id));
        $message = 'Personel pasif duruma getirildi!';
    } catch (Exception $e) {
        $error = 'HATA: ' . $e->getMessage();
    }
}

// Personel aktifleştirme (YENİ EKLENDİ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aktiflestir'])) {
    $personel_id = $_POST['personel_id'] ?? 0;
    
    try {
        $db->query("UPDATE personel SET aktif = 1 WHERE id = ?", array($personel_id));
        $message = 'Personel başarıyla tekrar aktif duruma getirildi!';
    } catch (Exception $e) {
        $error = 'HATA: ' . $e->getMessage();
    }
}

// Tüm personelleri ve rollerini getir
$personeller = $db->fetchAll(
    "SELECT p.*, k.rol, it.tercih_edilen_gun1, it.tercih_edilen_gun2 
     FROM personel p 
     LEFT JOIN kullanicilar k ON p.id = k.personel_id
     LEFT JOIN izin_tercihleri it ON p.id = it.personel_id 
     ORDER BY p.aktif DESC, p.ad ASC"
);

// Gün isimleri
$gun_isimleri = array('Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personel Yönetimi - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link active" href="personel_yonetimi.php">
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
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="bi bi-people text-primary me-2"></i>Personel Yönetimi
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ekleModal">
                        <i class="bi bi-person-plus me-2"></i>Yeni Personel Ekle
                    </button>
                </div>
                
                <?php if ($message): ?>
                    <?php echo Helper::showSuccess($message); ?>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <?php echo Helper::showError($error); ?>
                <?php endif; ?>
                
                <!-- Personel Listesi -->
                <div class="card table-card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sicil No</th>
                                        <th>Ad Soyad</th>
                                        <th>Kadro</th>
                                        <th>Yetki</th>
                                        <th>E-posta</th>
                                        <th>Hafta Tatili Tercihi</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($personeller as $p): ?>
                                        <tr class="<?php echo $p['aktif'] ? '' : 'table-secondary'; ?>">
                                            <td><?php echo Helper::escape($p['sicil_no']); ?></td>
                                            <td><?php echo Helper::escape($p['ad'] . ' ' . $p['soyad']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $p['kadro_turu'] === 'memur' ? 'bg-primary' : 'bg-info'; ?>">
                                                    <?php echo $p['kadro_turu'] === 'memur' ? 'Memur' : 'İşçi'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $p['rol'] === 'yonetici' ? 'bg-danger' : 'bg-secondary'; ?>">
                                                    <?php echo $p['rol'] === 'yonetici' ? 'Yönetici' : 'Kullanıcı'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo Helper::escape($p['eposta']); ?></td>
                                            <td>
                                                <?php 
                                                $tercihler = array();
                                                if ($p['tercih_edilen_gun1'] !== null) {
                                                    $tercihler[] = $gun_isimleri[$p['tercih_edilen_gun1']];
                                                }
                                                if ($p['tercih_edilen_gun2'] !== null) {
                                                    $tercihler[] = $gun_isimleri[$p['tercih_edilen_gun2']];
                                                }
                                                echo count($tercihler) > 0 ? implode(', ', $tercihler) : '-';
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $p['aktif'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $p['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning btn-edit-personel"
                                                        data-id="<?php echo $p['id']; ?>"
                                                        data-sicil="<?php echo Helper::escape($p['sicil_no']); ?>"
                                                        data-kimlik="<?php echo Helper::escape($p['kimlik_no']); ?>"
                                                        data-ad="<?php echo Helper::escape($p['ad']); ?>"
                                                        data-soyad="<?php echo Helper::escape($p['soyad']); ?>"
                                                        data-kadro="<?php echo $p['kadro_turu']; ?>"
                                                        data-gorev="<?php echo Helper::escape($p['gorev_unvani']); ?>"
                                                        data-telefon="<?php echo Helper::escape($p['telefon']); ?>"
                                                        data-eposta="<?php echo Helper::escape($p['eposta']); ?>"
                                                        data-adres="<?php echo Helper::escape($p['adres']); ?>"
                                                        data-rol="<?php echo $p['rol']; ?>"
                                                        data-tercih1="<?php echo $p['tercih_edilen_gun1'] ?? ''; ?>"
                                                        data-tercih2="<?php echo $p['tercih_edilen_gun2'] ?? ''; ?>">
                                                    <i class="bi bi-pencil" title="Düzenle"></i>
                                                </button>
                                                
                                                <?php if ($p['aktif']): ?>
                                                <!-- Aktifse Pasif Yap butonu göster -->
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Pasif duruma getirmek istediğinizden emin misiniz?')">
                                                    <input type="hidden" name="personel_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" name="sil" class="btn btn-sm btn-danger" title="Pasif Yap">
                                                        <i class="bi bi-person-x"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <!-- Pasifse Aktifleştir butonu göster -->
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Personeli tekrar aktif duruma getirmek istediğinizden emin misiniz?')">
                                                    <input type="hidden" name="personel_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" name="aktiflestir" class="btn btn-sm btn-success" title="Aktifleştir">
                                                        <i class="bi bi-person-check"></i>
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

    <!-- Ekleme Modal -->
    <div class="modal fade" id="ekleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Yeni Personel Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sicil No *</label>
                                <input type="text" class="form-control" name="sicil_no" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kimlik No *</label>
                                <input type="text" class="form-control" name="kimlik_no" maxlength="11" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad *</label>
                                <input type="text" class="form-control" name="ad" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Soyad *</label>
                                <input type="text" class="form-control" name="soyad" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kadro Türü *</label>
                                <select class="form-select" name="kadro_turu" required>
                                    <option value="">Seçiniz</option>
                                    <option value="memur">Memur</option>
                                    <option value="isci">İşçi</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sistem Rolü *</label>
                                <select class="form-select" name="rol" required>
                                    <option value="kullanici">Kullanıcı (Standart)</option>
                                    <option value="yonetici">Yönetici</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-posta (Giriş İçin) *</label>
                                <input type="email" class="form-control" name="eposta" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="text" class="form-control" name="telefon">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Görev Unvanı</label>
                                <input type="text" class="form-control" name="gorev_unvani">
                            </div>
                             <div class="col-md-12 mb-3">
                                <label class="form-label">Adres</label>
                                <textarea class="form-control" name="adres" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hafta Tatili Tercihi 1</label>
                                <select class="form-select" name="tercih_gun1">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($gun_isimleri as $index => $gun): ?>
                                        <option value="<?php echo $index; ?>"><?php echo $gun; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hafta Tatili Tercihi 2</label>
                                <select class="form-select" name="tercih_gun2">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($gun_isimleri as $index => $gun): ?>
                                        <option value="<?php echo $index; ?>"><?php echo $gun; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="ekle" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Düzenleme Modal -->
    <div class="modal fade" id="duzenleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Personel Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="personel_id" id="edit_personel_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sicil No *</label>
                                <input type="text" class="form-control" name="sicil_no" id="edit_sicil_no" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kimlik No *</label>
                                <input type="text" class="form-control" name="kimlik_no" id="edit_kimlik_no" maxlength="11" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad *</label>
                                <input type="text" class="form-control" name="ad" id="edit_ad" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Soyad *</label>
                                <input type="text" class="form-control" name="soyad" id="edit_soyad" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kadro Türü *</label>
                                <select class="form-select" name="kadro_turu" id="edit_kadro_turu" required>
                                    <option value="">Seçiniz</option>
                                    <option value="memur">Memur</option>
                                    <option value="isci">İşçi</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sistem Rolü *</label>
                                <select class="form-select" name="rol" id="edit_rol" required>
                                    <option value="kullanici">Kullanıcı (Standart)</option>
                                    <option value="yonetici">Yönetici</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-posta (Giriş İçin) *</label>
                                <input type="email" class="form-control" name="eposta" id="edit_eposta" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="text" class="form-control" name="telefon" id="edit_telefon">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Görev Unvanı</label>
                                <input type="text" class="form-control" name="gorev_unvani" id="edit_gorev_unvani">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Adres</label>
                                <textarea class="form-control" name="adres" id="edit_adres" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hafta Tatili Tercihi 1</label>
                                <select class="form-select" name="tercih_gun1" id="edit_tercih_gun1">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($gun_isimleri as $index => $gun): ?>
                                        <option value="<?php echo $index; ?>"><?php echo $gun; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hafta Tatili Tercihi 2</label>
                                <select class="form-select" name="tercih_gun2" id="edit_tercih_gun2">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($gun_isimleri as $index => $gun): ?>
                                        <option value="<?php echo $index; ?>"><?php echo $gun; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.btn-edit-personel');
        
        editButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const sicil = this.getAttribute('data-sicil');
                const kimlik = this.getAttribute('data-kimlik');
                const ad = this.getAttribute('data-ad');
                const soyad = this.getAttribute('data-soyad');
                const kadro = this.getAttribute('data-kadro');
                const gorev = this.getAttribute('data-gorev');
                const telefon = this.getAttribute('data-telefon');
                const eposta = this.getAttribute('data-eposta');
                const adres = this.getAttribute('data-adres');
                const rol = this.getAttribute('data-rol');
                const tercih1 = this.getAttribute('data-tercih1');
                const tercih2 = this.getAttribute('data-tercih2');
                
                document.getElementById('edit_personel_id').value = id;
                document.getElementById('edit_sicil_no').value = sicil;
                document.getElementById('edit_kimlik_no').value = kimlik;
                document.getElementById('edit_ad').value = ad;
                document.getElementById('edit_soyad').value = soyad;
                document.getElementById('edit_kadro_turu').value = kadro;
                document.getElementById('edit_gorev_unvani').value = gorev;
                document.getElementById('edit_telefon').value = telefon;
                document.getElementById('edit_eposta').value = eposta;
                document.getElementById('edit_adres').value = adres;
                document.getElementById('edit_rol').value = rol;
                document.getElementById('edit_tercih_gun1').value = tercih1;
                document.getElementById('edit_tercih_gun2').value = tercih2;
                
                const modal = new bootstrap.Modal(document.getElementById('duzenleModal'));
                modal.show();
            });
        });
    });
    </script>
</body>
</html>