/*
 Navicat Premium Data Transfer
 Source Server Type    : MySQL
 File Encoding         : 65001
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for izin_tercihleri
-- ----------------------------
DROP TABLE IF EXISTS `izin_tercihleri`;
CREATE TABLE `izin_tercihleri`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `personel_id` int NOT NULL,
  `tercih_edilen_gun1` tinyint NULL DEFAULT NULL,
  `tercih_edilen_gun2` tinyint NULL DEFAULT NULL,
  `aktif` tinyint(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_personel`(`personel_id` ASC) USING BTREE,
  CONSTRAINT `izin_tercihleri_ibfk_1` FOREIGN KEY (`personel_id`) REFERENCES `personel` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of izin_tercihleri
-- ----------------------------
INSERT INTO `izin_tercihleri` VALUES (1, 1, 6, 0, 1);
INSERT INTO `izin_tercihleri` VALUES (2, 2, 0, 1, 1);
INSERT INTO `izin_tercihleri` VALUES (3, 3, 3, 4, 1);
INSERT INTO `izin_tercihleri` VALUES (4, 4, 2, 3, 1);
INSERT INTO `izin_tercihleri` VALUES (5, 5, 4, 5, 1);
INSERT INTO `izin_tercihleri` VALUES (6, 6, 1, 6, 1);
INSERT INTO `izin_tercihleri` VALUES (7, 7, 5, 6, 1);

-- ----------------------------
-- Table structure for kullanicilar
-- ----------------------------
DROP TABLE IF EXISTS `kullanicilar`;
CREATE TABLE `kullanicilar`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `personel_id` int NOT NULL,
  `sifre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `rol` enum('yonetici','kullanici') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL DEFAULT 'kullanici',
  `son_giris` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_personel_login`(`personel_id` ASC) USING BTREE,
  CONSTRAINT `kullanicilar_ibfk_1` FOREIGN KEY (`personel_id`) REFERENCES `personel` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of kullanicilar (Kullanıcı Adı Sütunu Kaldırıldı)
-- ----------------------------
INSERT INTO `kullanicilar` VALUES (1, 1, '$2y$10$JOdsHlvTdbrS7Vn1U/z9Xus9N/0GRFZkmQRV/G2TdqTqzybY435N2', 'yonetici', NULL);
INSERT INTO `kullanicilar` VALUES (2, 2, '$2y$10$JOdsHlvTdbrS7Vn1U/z9Xus9N/0GRFZkmQRV/G2TdqTqzybY435N2', 'kullanici', NULL);
INSERT INTO `kullanicilar` VALUES (3, 3, '$2y$10$JOdsHlvTdbrS7Vn1U/z9Xus9N/0GRFZkmQRV/G2TdqTqzybY435N2', 'kullanici', NULL);
INSERT INTO `kullanicilar` VALUES (4, 4, '$2y$10$JOdsHlvTdbrS7Vn1U/z9Xus9N/0GRFZkmQRV/G2TdqTqzybY435N2', 'kullanici', NULL);
INSERT INTO `kullanicilar` VALUES (5, 5, '$2y$10$JOdsHlvTdbrS7Vn1U/z9Xus9N/0GRFZkmQRV/G2TdqTqzybY435N2', 'kullanici', NULL);
INSERT INTO `kullanicilar` VALUES (6, 6, '$2y$10$JOdsHlvTdbrS7Vn1U/z9Xus9N/0GRFZkmQRV/G2TdqTqzybY435N2', 'kullanici', NULL);
INSERT INTO `kullanicilar` VALUES (7, 7, '$2y$10$JOdsHlvTdbrS7Vn1U/z9Xus9N/0GRFZkmQRV/G2TdqTqzybY435N2', 'kullanici', NULL);

-- ----------------------------
-- Table structure for mesai_kayitlari
-- ----------------------------
DROP TABLE IF EXISTS `mesai_kayitlari`;
CREATE TABLE `mesai_kayitlari`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `personel_id` int NOT NULL,
  `tarih` date NOT NULL,
  `fazla_saat` decimal(4, 2) NOT NULL,
  `odeme_tipi` enum('ucret','izin') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `durum` enum('beklemede','onaylandi','kullanildi') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL DEFAULT 'beklemede',
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_personel_tarih`(`personel_id` ASC, `tarih` ASC) USING BTREE,
  CONSTRAINT `mesai_kayitlari_ibfk_1` FOREIGN KEY (`personel_id`) REFERENCES `personel` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for nobet_programi
-- ----------------------------
DROP TABLE IF EXISTS `nobet_programi`;
CREATE TABLE `nobet_programi`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `personel_id` int NOT NULL,
  `tarih` date NOT NULL,
  `vardiya_id` int NULL DEFAULT NULL,
  `durum` enum('planlandi','tamamlandi','degistirildi','hafta_tatili','resmi_tatil') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL DEFAULT 'planlandi',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp,
  `degistirme_tarihi` timestamp NULL DEFAULT NULL,
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_personel_tarih`(`personel_id` ASC, `tarih` ASC) USING BTREE,
  INDEX `vardiya_id`(`vardiya_id` ASC) USING BTREE,
  INDEX `idx_tarih`(`tarih` ASC) USING BTREE,
  INDEX `idx_personel_tarih`(`personel_id` ASC, `tarih` ASC) USING BTREE,
  CONSTRAINT `nobet_programi_ibfk_1` FOREIGN KEY (`personel_id`) REFERENCES `personel` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `nobet_programi_ibfk_2` FOREIGN KEY (`vardiya_id`) REFERENCES `vardiya_sablonlari` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for personel
-- ----------------------------
DROP TABLE IF EXISTS `personel`;
CREATE TABLE `personel`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `sicil_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `ad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `soyad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `kimlik_no` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `kadro_turu` enum('memur','isci') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `gorev_unvani` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `adres` text CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL,
  `telefon` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL DEFAULT NULL,
  `eposta` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `aktif` tinyint(1) NULL DEFAULT 1,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `sicil_no`(`sicil_no` ASC) USING BTREE,
  UNIQUE INDEX `kimlik_no`(`kimlik_no` ASC) USING BTREE,
  UNIQUE INDEX `eposta`(`eposta` ASC) USING BTREE,
  INDEX `idx_sicil`(`sicil_no` ASC) USING BTREE,
  INDEX `idx_kadro`(`kadro_turu` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of personel
-- ----------------------------
INSERT INTO `personel` VALUES (1, 'P001', 'Bulut', 'Bulutsun', '12345678901', 'memur', 'Güvenlik Şefi', '', '5551234567', 'bulut.bulutsun@cbu.edu.tr', 1, '2025-10-18 21:33:55');
INSERT INTO `personel` VALUES (2, 'P002', 'Rukiye', 'Baksi', '12345678902', 'isci', 'Güvenlik Görevlisi', '', '5551234568', 'rukiye.baksi@cbu.edu.tr', 1, '2025-10-18 21:33:55');
INSERT INTO `personel` VALUES (3, 'P003', 'Cihan', 'Koçak', '12345678903', 'isci', 'Güvenlik Görevlisi', '', '5551234569', 'cihan.kocak@cbu.edu.tr', 1, '2025-10-18 21:33:55');
INSERT INTO `personel` VALUES (4, 'P004', 'Büşra', 'Ton', '12345678904', 'isci', 'Güvenlik Memuru', '', '5551234570', 'busra.ton@cbu.edu.tr', 1, '2025-10-18 21:33:55');
INSERT INTO `personel` VALUES (5, 'P005', 'Merve', 'Arslan', '12345678905', 'isci', 'Güvenlik Görevlisi', '', '5551234571', 'merve.arslan@cbu.edu.tr', 1, '2025-10-18 21:33:55');
INSERT INTO `personel` VALUES (6, 'P006', 'Berkay', 'Turhan', '12345678906', 'isci', 'Güvenlik Memuru', '', '5551234572', 'berkay.turhan@cbu.edu.tr', 1, '2025-10-18 21:33:55');
INSERT INTO `personel` VALUES (7, 'P007', 'Murat', 'Albayrak', '12345678907', 'isci', 'Güvenlik Şefi', '', '5551234572', 'murat.albayrak@cbu.edu.tr', 1, '2025-10-18 21:33:55');

-- ----------------------------
-- Table structure for program_ayarlari
-- ----------------------------
DROP TABLE IF EXISTS `program_ayarlari`;
CREATE TABLE `program_ayarlari`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `ayar_adi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `ayar_degeri` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `ayar_adi`(`ayar_adi` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of program_ayarlari
-- ----------------------------
INSERT INTO `program_ayarlari` VALUES (1, 'resmi_tatil_odeme', 'izin', 'Resmi tatilde çalışma karşılığı: ucret veya izin');
INSERT INTO `program_ayarlari` VALUES (2, 'fazla_mesai_odeme', 'izin', 'Fazla mesai karşılığı: ucret veya izin');

-- ----------------------------
-- Table structure for resmi_tatiller
-- ----------------------------
DROP TABLE IF EXISTS `resmi_tatiller`;
CREATE TABLE `resmi_tatiller`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `tatil_adi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `tarih` date NOT NULL,
  `yil` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_tarih`(`tarih` ASC) USING BTREE,
  INDEX `idx_yil`(`yil` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of resmi_tatiller
-- ----------------------------
INSERT INTO `resmi_tatiller` VALUES (11, '29 Ekim Cumhuriyet Bayramı', '2025-10-29', 2025);
INSERT INTO `resmi_tatiller` VALUES (12, 'Yılbaşı Tatili', '2025-01-01', 2025);
INSERT INTO `resmi_tatiller` VALUES (13, 'Ramazan Bayramı #1', '2025-03-30', 2025);
INSERT INTO `resmi_tatiller` VALUES (14, 'Ramazan Bayramı #2', '2025-03-31', 2025);
INSERT INTO `resmi_tatiller` VALUES (15, 'Ramazan Bayramı #3', '2025-04-01', 2025);
INSERT INTO `resmi_tatiller` VALUES (16, 'Ulusal Egemenlik ve  Çocuk Bayramı', '2025-04-23', 2025);
INSERT INTO `resmi_tatiller` VALUES (17, 'İşçi Bayramı', '2025-05-01', 2025);
INSERT INTO `resmi_tatiller` VALUES (18, 'Atatürk’ü Anma,  Gençlik ve Spor Bayramı', '2025-05-19', 2025);
INSERT INTO `resmi_tatiller` VALUES (19, 'Kurban Bayramı #1', '2025-06-06', 2025);
INSERT INTO `resmi_tatiller` VALUES (20, 'Kurban Bayramı #2', '2025-06-07', 2025);
INSERT INTO `resmi_tatiller` VALUES (21, 'Kurban Bayramı #3', '2025-06-08', 2025);
INSERT INTO `resmi_tatiller` VALUES (22, 'Kurban Bayramı #4', '2025-06-09', 2025);
INSERT INTO `resmi_tatiller` VALUES (23, 'Demokrasi ve Milli  Birlik Günü', '2025-07-15', 2025);
INSERT INTO `resmi_tatiller` VALUES (24, 'Zafer Bayramı', '2025-08-30', 2025);

-- ----------------------------
-- Table structure for vardiya_sablonlari
-- ----------------------------
DROP TABLE IF EXISTS `vardiya_sablonlari`;
CREATE TABLE `vardiya_sablonlari`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `vardiya_adi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `lokasyon` enum('kampus_giris','kampus_ici') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `baslangic_saat` time NOT NULL,
  `bitis_saat` time NOT NULL,
  `calisma_saati` decimal(4, 2) NOT NULL,
  `aktif` tinyint(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_lokasyon`(`lokasyon` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of vardiya_sablonlari
-- ----------------------------
INSERT INTO `vardiya_sablonlari` VALUES (1, 'Gece Vardiyası', 'kampus_giris', '00:00:00', '08:00:00', 8.00, 1);
INSERT INTO `vardiya_sablonlari` VALUES (2, 'Gündüz Vardiyası', 'kampus_giris', '08:00:00', '16:00:00', 8.00, 1);
INSERT INTO `vardiya_sablonlari` VALUES (3, 'Akşam Vardiyası', 'kampus_giris', '16:00:00', '24:00:00', 8.00, 1);
INSERT INTO `vardiya_sablonlari` VALUES (4, 'İç Vardiya 1', 'kampus_ici', '08:00:00', '16:00:00', 8.00, 1);
INSERT INTO `vardiya_sablonlari` VALUES (5, 'İç Vardiya 2', 'kampus_ici', '09:00:00', '17:00:00', 8.00, 1);

SET FOREIGN_KEY_CHECKS = 1;
