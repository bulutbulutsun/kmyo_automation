<?php
// Veritabanı Yapılandırması
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vardiya_otomasyonu');

// Uygulama Ayarları
define('APP_NAME', 'Personel Vardiya Otomasyonu');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Europe/Istanbul');

// Oturum Ayarları
define('SESSION_LIFETIME', 7200); // 2 saat

// Tarih ve Saat Ayarları
date_default_timezone_set(TIMEZONE);

// Veritabanı Bağlantı Sınıfı
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch(PDOException $e) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // SQL sorgularını çalıştırma
    public function query($sql, $params = array()) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("SQL HATA: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Tek satır getir
    public function fetchOne($sql, $params = array()) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // Tüm satırları getir
    public function fetchAll($sql, $params = array()) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Son eklenen ID'yi getir
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// Yardımcı Fonksiyonlar
class Helper {
    
    // Güvenli çıktı
    public static function escape($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    // Tarih formatlama
    public static function formatDate($date, $format = 'd.m.Y') {
        if (empty($date)) return '';
        $timestamp = strtotime($date);
        return date($format, $timestamp);
    }
    
    // Tarih ve saat formatlama
    public static function formatDateTime($datetime, $format = 'd.m.Y H:i') {
        if (empty($datetime)) return '';
        $timestamp = strtotime($datetime);
        return date($format, $timestamp);
    }
    
    // Türkçe gün adı
    public static function getTurkishDayName($date) {
        $days = array('Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi');
        $dayNumber = date('w', strtotime($date));
        return $days[$dayNumber];
    }
    
    // Türkçe ay adı
    public static function getTurkishMonthName($month) {
        $months = array(
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        );
        return $months[(int)$month];
    }
    
    // Şifre hashleme
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Şifre doğrulama
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Başarı mesajı
    public static function showSuccess($message) {
        return '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    ' . self::escape($message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    
    // Hata mesajı
    public static function showError($message) {
        return '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ' . self::escape($message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    
    // Uyarı mesajı
    public static function showWarning($message) {
        return '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                    ' . self::escape($message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    
    // Yönlendirme
    public static function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}

// Oturum Yönetimi
class Session {
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // HTTPS kullanılıyorsa 1 yapın
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public static function delete($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function destroy() {
        session_destroy();
        $_SESSION = array();
    }
    
    // Kullanıcı oturum bilgileri
    public static function isLoggedIn() {
        return self::has('user_id') && self::has('user_role');
    }
    
    public static function isAdmin() {
        return self::get('user_role') === 'yonetici';
    }
    
    public static function getUserId() {
        return self::get('user_id');
    }
    
    public static function getPersonelId() {
        return self::get('personel_id');
    }
    
    public static function getUserRole() {
        return self::get('user_role');
    }
    
    public static function getUserName() {
        return self::get('user_name');
    }
}

// Auth Sınıfı
class Auth {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Giriş yap - E-POSTA İLE
    public function login($eposta, $sifre) {
        // E-posta üzerinden personel ve kullanıcı bilgilerini birleştiriyoruz
        $sql = "SELECT k.*, p.ad, p.soyad, p.sicil_no, p.eposta
                FROM kullanicilar k 
                INNER JOIN personel p ON k.personel_id = p.id 
                WHERE p.eposta = ? AND p.aktif = 1";
        
        $user = $this->db->fetchOne($sql, array($eposta));
        
        if ($user && Helper::verifyPassword($sifre, $user['sifre'])) {
            Session::set('user_id', $user['id']);
            Session::set('personel_id', $user['personel_id']);
            Session::set('user_role', $user['rol']);
            Session::set('user_name', $user['ad'] . ' ' . $user['soyad']);
            Session::set('sicil_no', $user['sicil_no']);
            
            // Son giriş zamanını güncelle
            $this->db->query("UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?", array($user['id']));
            
            return true;
        }
        
        return false;
    }
    
    // Çıkış yap
    public function logout() {
        Session::destroy();
        Helper::redirect('index.php');
    }
    
    // Yetki kontrolü
    public function requireLogin() {
        if (!Session::isLoggedIn()) {
            Helper::redirect('index.php');
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!Session::isAdmin()) {
            Helper::redirect('dashboard.php');
        }
    }
}
?>
