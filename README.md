
# <img width="300" height="300" alt="Manisa-celal-bayar-universitesi-logo" src="https://github.com/user-attachments/assets/f2a39aa9-9583-40c5-9e60-8e4481da62d6" />
Personel Vardiya Otomasyonu Sistemi

Manisa Celal Bayar Üniversitesi için geliştirilmiş, MySQL tabanlı modern bir vardiya yönetim sistemi.

## Diyagram
<img width="4498" height="2978" alt="o5fit2l" src="https://github.com/user-attachments/assets/c61b5c0e-bb31-4184-b678-7088302a2eee" />



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
- Genel nöbet listesi
- Manuel nöbet düzenleme
- Detaylı raporlama
- Sistem ayarları (Resmi tatiller, Vardiya şablonları)

### Kullanıcı Paneli
- Kişisel nöbet listesi görüntüleme
- PDF/Yazdırma desteği
- Aylık özet istatistikler
- Profil düzenleme, görüntüleme

## Sistem Gereksinimleri

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache/Nginx web sunucusu
- PDO PHP Extension

## Kurulum

### 1. Dosyaları Yükleyin
Tüm dosyaları web sunucunuzun dizinine kopyalayın (örn: `htdocs/vardiya/`)

**Önemli:** Klasör yapısını koruyun:
- `assets/css/style.css` dosyasının doğru konumda olduğundan emin olun
- Eğer CSS stilleri yüklenmiyorsa, tarayıcı konsolundan (F12) dosya yolunu kontrol edin

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
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vardiya_otomasyonu');
```

### 4. Dosya Yapısı

Proje dosyalarınız şu şekilde olmalı:

```
/vardiya/
├── assets/
│   └── css/
│       └── style.css
├── config.php
├── database.sql
├── index.php
├── dashboard.php
├── logout.php
├── genel_nobet_listesi.php
├── nobet_listesi.php
├── nobet_olustur.php
├── nobet_duzenle.php
├── nobet_algoritma.php
├── personel_yonetimi.php
├── raporlar.php
├── profil.php
└── ayarlar.php

```

**Önemli:** `assets/css/` klasörünü oluşturup `style.css` dosyasını içine koyduğunuzdan emin olun.

### 5. İlk Giriş

Tarayıcınızda sisteme erişin:
```
http://localhost/vardiya/
```

**Deneme Hesapları:**

Yönetici:
- Kullanıcı Adı: `bulut`
- Şifre: `123456`

Kullanıcı:
- Kullanıcı Adı: `busra`
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

### Dosya Yapısı ve Açıklamalar

| Dosya | Açıklama |
|-------|----------|
| `assets/css/style.css` | Ana stil dosyası (tüm CSS kodları burada) |
| `config.php` | Veritabanı bağlantısı ve yardımcı fonksiyonlar |
| `database.sql` | Veritabanı yapısı ve örnek veriler |
| `index.php` | Giriş sayfası |
| `dashboard.php` | Ana kontrol paneli |
| `logout.php` | Çıkış işlemi |
| `nobet_listesi.php` | Kullanıcı nöbet listesi (takvim görünümü) |
| `genel_nobet_listesi.php` | Toplu nöbet listesi |
| `nobet_olustur.php` | Otomatik nöbet oluşturma sayfası |
| `nobet_algoritma.php` | Nöbet dağıtım algoritması |
| `nobet_duzenle.php` | Manuel nöbet düzenleme |
| `personel_yonetimi.php` | Personel CRUD işlemleri |
| `raporlar.php` | Detaylı raporlama |
| `ayarlar.php` | Sistem ayarları |
| `profil.php` | Profil sayfası |

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
