<?php
require_once 'config.php';

Session::start();

// Zaten giriş yapmışsa dashboard'a yönlendir
if (Session::isLoggedIn()) {
    Helper::redirect('dashboard.php');
}

$error = '';
$success = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    
    if (empty($kullanici_adi) || empty($sifre)) {
        $error = 'Kullanıcı adı ve şifre gereklidir!';
    } else {
        $auth = new Auth();
        if ($auth->login($kullanici_adi, $sifre)) {
            Helper::redirect('dashboard.php');
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Giriş</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="bi bi-shield-lock" style="font-size: 48px;"></i>
            <h1 class="mt-3"><?php echo APP_NAME; ?></h1>
            <p>Manisa Celal Bayar Üniversitesi</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <?php echo Helper::showError($error); ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <?php echo Helper::showSuccess($success); ?>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Kullanıcı Adı</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" class="form-control" name="kullanici_adi" 
                               placeholder="Kullanıcı adınızı giriniz" required autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Şifre</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="sifre" 
                               placeholder="Şifrenizi giriniz" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Yap
                </button>
            </form>
            
            <div class="demo-info">
                <strong><i class="bi bi-info-circle me-1"></i> Deneme Hesapları:</strong>
                <div class="mb-2">
                    <strong>Yönetici:</strong><br>
                    Kullanıcı: <code>admin</code><br>
                    Şifre: <code>123456</code>
                </div>
                <div>
                    <strong>Kullanıcı:</strong><br>
                    Kullanıcı: <code>busra</code><br>
                    Şifre: <code>123456</code>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
