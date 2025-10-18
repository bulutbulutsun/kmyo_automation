# kmyo_automation
# Personel Vardiya Otomasyonu Sistemi

Manisa Celal Bayar Üniversitesi için geliştirilmiş, MySQL tabanlı modern bir vardiya yönetim sistemi.

## Özellikler

### Genel Özellikler
- Otomatik vardiya programı oluşturma
- Memur ve işçi kadro ayrımı
- Haftalık çalışma saati kontrolü (Memur: 40 saat, İşçi: 45 saat)
- Hafta tatili tercihleri
- Resmi tatil yönetimi
- Fazla mesai takibi
- Kampüs girişi ve kampüs içi vardiya ayrımı

### Yönetici Paneli
- Personel yönetimi (CRUD işlemleri)
- Otomatik nöbet programı oluşturma
- Manuel nöbet düzenleme
- Detaylı raporlama
- Sistem ayarları (Resmi tatiller, Vardiya şablonları)

### Kullanıcı Paneli
- Kişisel nöbet listesi görüntüleme
- PDF/Yazdırma desteği
- Aylık özet istatistikler

## Sistem Gereksinimleri

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache/Nginx web sunucusu
- PDO PHP Extension

## Kurulum

### 1. Dosyaları Yükleyin
Tüm dosyaları web sunucunuzun dizinine kopyalayın (örn: `htdocs/vardiya/`)

### 2. Veritabanı Kurulumu

MySQL'de yeni bir veritabanı oluşturun:

```sql
CREATE DATABASE vardiya_otomasyonu CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
```

`database.sql` dosyasını içe aktarın:

```bash
mysql -u root -p vardiya_otomasyonu < database.sql
```

Ya da phpMyAdmin üzerinden:
- phpMyAdmin'e giriş yapın
- `vardiya_otomasyonu` veritabanını seçin
- "İçe Aktar" sekmesine tıklayın
- `database.sql` dosyasını seçip içe aktarın

### 3. Veritabanı Bağlantı Ayarları

`config.php` dosyasını açın ve veritabanı bilgilerinizi girin:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Kullanıcı adınız
define('DB_PASS', '');                // Şifreniz
define('DB_NAME', 'vardiya_otomasyonu');
```

### 4. Dosya Yapısı

```
/vardiya/
├── config.php
├── database.sql
├── index.php
├── dashboard.php
├── logout.php
├── nobet_listesi.php
├── nobet_olustur.php
├── nobet_duzenle.php
├── nobet_algoritma.php
├── personel_yonetimi.php
├── raporlar.php
└── ayarlar.php
```

### 5. İlk Giriş

Tarayıcınızda sisteme erişin:
```
http://localhost/vardiya/
```

**Demo Hesaplar:**

Yönetici:
- Kullanıcı Adı: `admin`
- Şifre: `123456`

Kullanıcı:
- Kullanıcı Adı: `ayse`
- Şifre: `123456`

## Kullanım Kılavuzu

### Yönetici İşlemleri

#### 1. Personel Ekleme
1. **Personel Yönetimi** menüsüne gidin
2. **Yeni Personel Ekle** butonuna tıklayın
3. Gerekli bilgileri doldurun:
   - Sicil No (Zorunlu)
   - Kimlik No (Zorunlu)
   - Ad-Soyad (Zorunlu)
   - Kadro Türü: Memur veya İşçi (Zorunlu)
   - Hafta tatili tercihleri (İsteğe bağlı)
4. **Kaydet** butonuna tıklayın
5. Sistem otomatik olarak kullanıcı hesabı oluşturur

#### 2. Otomatik Nöbet Programı Oluşturma
1. **Nöbet Oluştur** menüsüne gidin
2. Başlangıç ve bitiş tarihlerini seçin
3. Resmi tatil ve fazla mesai ayarlarını yapın:
   - **Resmi Tatil Karşılığı:** Ücret veya İzin
   - **Fazla Mesai Karşılığı:** Ücret veya İzin
4. **Otomatik Nöbet Programı Oluştur** butonuna tıklayın

**Sistem Otomatik Olarak:**
- Memurları haftada 5 gün (40 saat) çalıştırır
- İşçileri haftada 6 gün (45 saat) çalıştırır
- Hafta tatili tercihlerini dikkate alır
- Vardiyaları adil şekilde dağıtır
- Resmi tatilleri işaretler

#### 3. Manuel Nöbet Düzenleme
1. **Nöbet Düzenle** menüsüne gidin
2. Ay ve yıl seçin
3. Düzenlemek istediğiniz personelin **Düzenle** butonuna tıklayın
4. Vardiya seçin veya hafta tatiline alın
5. **Güncelle** butonuna tıklayın

#### 4. Rapor Oluşturma
1. **Raporlar** menüsüne gidin
2. Personel seçin
3. Başlangıç ve bitiş tarihlerini belirleyin
4. **Sorgula** butonuna tıklayın
5. Raporu görüntüleyin ve yazdırın

#### 5. Sistem Ayarları

**Resmi Tatil Ekleme:**
1. **Ayarlar** menüsüne gidin
2. **Tatil Ekle** butonuna tıklayın
3. Tatil adı ve tarihi girin
4. **Kaydet** butonuna tıklayın

**Vardiya Şablonu Ekleme:**
1. **Ayarlar** menüsüne gidin
2. **Vardiya Ekle** butonuna tıklayın
3. Vardiya bilgilerini girin:
   - Vardiya Adı
   - Lokasyon (Kampüs Girişi/Kampüs İçi)
   - Başlangıç ve Bitiş Saatleri
4. **Kaydet** butonuna tıklayın

### Kullanıcı İşlemleri

#### Nöbet Listesini Görüntüleme
1. **Nöbet Listem** menüsüne gidin
2. Ay ve yıl seçin
3. **Görüntüle** butonuna tıklayın
4. İsteğe bağlı olarak **Yazdır** butonuna tıklayarak çıktı alın

## Güvenlik

- Tüm şifreler bcrypt ile hashlenmiştir
- SQL Injection koruması (Prepared Statements)
- Session güvenliği
- Rol tabanlı yetkilendirme
- XSS koruması

## Teknik Detaylar

### Veritabanı Tabloları
- `personel` - Personel bilgileri
- `kullanicilar` - Giriş bilgileri
- `vardiya_sablonlari` - Vardiya tanımları
- `nobet_programi` - Nöbet kayıtları
- `izin_tercihleri` - Hafta tatili tercihleri
- `mesai_kayitlari` - Fazla mesai takibi
- `resmi_tatiller` - Resmi tatil günleri
- `program_ayarlari` - Sistem ayarları

### Kullanılan Teknolojiler
- **Backend:** PHP 7.4+ (PDO)
- **Frontend:** Bootstrap 5.3, Bootstrap Icons
- **Veritabanı:** MySQL 8.0
- **JavaScript:** Vanilla JS

## Sorun Giderme

### Veritabanı Bağlantı Hatası
```
SQLSTATE[HY000] [1045] Access denied for user
```
**Çözüm:** `config.php` dosyasında veritabanı bilgilerinizi kontrol edin.

### Oturum Hatası
```
session_start(): Failed to read session data
```
**Çözüm:** PHP session klasörünün yazılabilir olduğundan emin olun.

### Türkçe Karakter Sorunu
**Çözüm:** 
- Veritabanı karakter setinin `utf8mb4` olduğundan emin olun
- PHP dosyalarını UTF-8 encoding ile kaydedin

## Destek

Herhangi bir sorun veya öneriniz için:
- Veritabanı yapısını kontrol edin
- PHP hata loglarını inceleyin
- Tarayıcı konsolunu kontrol edin

## Notlar

- İlk kurulumda 5 örnek personel ve kullanıcı hesabı oluşturulur
- Varsayılan şifre tüm kullanıcılar için: `123456`
- Şifreleri değiştirmeyi unutmayın!
- Üretim ortamına geçmeden önce güvenlik ayarlarını yapın

## İleri Seviye Özelleştirmeler

### Şifre Değiştirme
Yeni bir kullanıcı eklendiğinde otomatik şifre: `123456`

Manuel şifre hashlemek için:
```php
echo password_hash('yeni_sifre', PASSWORD_DEFAULT);
```

### Çalışma Saatlerini Değiştirme
`nobet_algoritma.php` dosyasında değiştirebilirsiniz.

## 📄 Lisans

Bu proje eğitim amaçlı geliştirilmiştir.

---

**Geliştirme Tarihi:** 2025  
**Versiyon:** 1.0.0  
**Geliştirici:** Claude (Anthropic)
