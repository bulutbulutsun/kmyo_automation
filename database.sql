/*
 Navicat Premium Data Transfer

 Source Server         : localhost_3306
 Source Server Type    : MySQL
 Source Server Version : 100432 (10.4.32-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : vardiya_otomasyonu

 Target Server Type    : MySQL
 Target Server Version : 100432 (10.4.32-MariaDB)
 File Encoding         : 65001

 Date: 26/10/2025 03:34:21
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
-- Table structure for kullanicilar
-- ----------------------------
DROP TABLE IF EXISTS `kullanicilar`;
CREATE TABLE `kullanicilar`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `personel_id` int NOT NULL,
  `kullanici_adi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `sifre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `rol` enum('yonetici','kullanici') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL DEFAULT 'kullanici',
  `son_giris` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `kullanici_adi`(`kullanici_adi` ASC) USING BTREE,
  INDEX `personel_id`(`personel_id` ASC) USING BTREE,
  INDEX `idx_kullanici`(`kullanici_adi` ASC) USING BTREE,
  CONSTRAINT `kullanicilar_ibfk_1` FOREIGN KEY (`personel_id`) REFERENCES `personel` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

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
  `eposta` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NULL DEFAULT NULL,
  `aktif` tinyint(1) NULL DEFAULT 1,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `sicil_no`(`sicil_no` ASC) USING BTREE,
  UNIQUE INDEX `kimlik_no`(`kimlik_no` ASC) USING BTREE,
  INDEX `idx_sicil`(`sicil_no` ASC) USING BTREE,
  INDEX `idx_kadro`(`kadro_turu` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_turkish_ci ROW_FORMAT = Dynamic;

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

SET FOREIGN_KEY_CHECKS = 1;
