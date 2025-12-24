<?php
require_once 'config.php';

class NobetAlgoritma {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Otomatik nöbet programı oluştur
     */
    public function nobetOlustur($baslangic_tarihi, $bitis_tarihi) {
        try {
            // Tarihleri kontrol et
            $baslangic = new DateTime($baslangic_tarihi);
            $bitis = new DateTime($bitis_tarihi);

            // Aktif ve yönetici OLMAYAN personelleri getir
            $personeller = $this->db->fetchAll(
                "SELECT p.*, it.tercih_edilen_gun1, it.tercih_edilen_gun2 
                 FROM personel p 
                 INNER JOIN kullanicilar k ON p.id = k.personel_id
                 LEFT JOIN izin_tercihleri it ON p.id = it.personel_id 
                 WHERE p.aktif = 1 AND k.rol = 'kullanici'
                 ORDER BY p.id"
            );

            if (count($personeller) === 0) {
                return array('basarili' => false, 'mesaj' => 'Nöbet yazılabilecek aktif personel bulunamadı!');
            }

            // Vardiya şablonlarını getir
            $vardialar = $this->db->fetchAll(
                "SELECT * FROM vardiya_sablonlari WHERE aktif = 1 ORDER BY lokasyon, id"
            );

            if (count($vardialar) === 0) {
                return array('basarili' => false, 'mesaj' => 'Aktif vardiya şablonu bulunamadı!');
            }

            // Tarih aralığındaki tüm yılları kapsayacak şekilde resmi tatilleri çek
            $baslangic_yil = date('Y', strtotime($baslangic_tarihi));
            $bitis_yil = date('Y', strtotime($bitis_tarihi));

            $resmi_tatiller = $this->db->fetchAll(
                "SELECT tarih FROM resmi_tatiller WHERE yil BETWEEN ? AND ?",
                array($baslangic_yil, $bitis_yil)
            );

            $resmi_tatil_tarihleri = array();
            foreach ($resmi_tatiller as $tatil) {
                $resmi_tatil_tarihleri[] = $tatil['tarih'];
            }

            // Mevcut kayıtları sil
            $this->db->query(
                "DELETE FROM nobet_programi WHERE tarih BETWEEN ? AND ?",
                array($baslangic_tarihi, $bitis_tarihi)
            );

            // Her personel için nöbet sayacı
            $personel_nobet_sayisi = array();
            foreach ($personeller as $personel) {
                $personel_nobet_sayisi[$personel['id']] = 0;
            }

            // Gün gün döngü
            $interval = new DateInterval('P1D');
            $daterange = new DatePeriod($baslangic, $interval, $bitis->modify('+1 day'));

            $toplam_gun = 0;
            $eklenen_nobet = 0;

            foreach ($daterange as $tarih) {
                $tarih_str = $tarih->format('Y-m-d');
                $gun_no = (int)$tarih->format('w'); // 0=Pazar, 6=Cumartesi
                $toplam_gun++;

                $resmi_tatil_mi = in_array($tarih_str, $resmi_tatil_tarihleri);

                foreach ($personeller as $personel) {
                    $personel_id = $personel['id'];
                    $kadro_turu = $personel['kadro_turu'];
                    $hafta_tatili_mi = false;

                    // Hafta tatili kontrolü
                    if ($kadro_turu === 'memur') {
                        $tercih1 = $personel['tercih_edilen_gun1'];
                        $tercih2 = $personel['tercih_edilen_gun2'];
                        if ($tercih1 !== null && $tercih2 !== null) {
                            if ($gun_no == $tercih1 || $gun_no == $tercih2) $hafta_tatili_mi = true;
                        } else {
                            if ($gun_no == 0 || $gun_no == 6) $hafta_tatili_mi = true;
                        }
                    } elseif ($kadro_turu === 'isci') {
                        $tercih1 = $personel['tercih_edilen_gun1'];
                        if ($tercih1 !== null) {
                            if ($gun_no == $tercih1) $hafta_tatili_mi = true;
                        } else {
                            if ($gun_no == 0) $hafta_tatili_mi = true;
                        }
                    }

                    if ($hafta_tatili_mi && !$resmi_tatil_mi) {
                        $this->db->query(
                            "INSERT INTO nobet_programi (personel_id, tarih, vardiya_id, durum) 
                             VALUES (?, ?, NULL, 'hafta_tatili')",
                            array($personel_id, $tarih_str)
                        );
                        continue;
                    }

                    if ($resmi_tatil_mi) {
                        $vardiya = $this->enAzNobetliVardiyaSecResmiTatil($vardialar, $tarih_str, $personeller);

                        $this->db->query(
                            "INSERT INTO nobet_programi (personel_id, tarih, vardiya_id, durum) 
                             VALUES (?, ?, ?, 'resmi_tatil')",
                            array($personel_id, $tarih_str, $vardiya['id'])
                        );

                        // Ayarlardan ödeme tipini al
                        $ayar = $this->db->fetchOne("SELECT ayar_degeri FROM program_ayarlari WHERE ayar_adi = 'resmi_tatil_odeme'");
                        $odeme_tipi = $ayar ? $ayar['ayar_degeri'] : 'ucret';

                        // Mesai kaydı oluştur
                        $this->db->query(
                            "INSERT INTO mesai_kayitlari (personel_id, tarih, fazla_saat, odeme_tipi, aciklama) 
                             VALUES (?, ?, 8, ?, 'Resmi tatil çalışması')",
                            array($personel_id, $tarih_str, $odeme_tipi)
                        );

                        $eklenen_nobet++;
                        $personel_nobet_sayisi[$personel_id]++;
                        continue;
                    }

                    $vardiya = $this->enUygunVardiyaSec($vardialar, $tarih_str, $personel_id, $personeller, $personel_nobet_sayisi);

                    if ($vardiya) {
                        $this->db->query(
                            "INSERT INTO nobet_programi (personel_id, tarih, vardiya_id, durum) 
                             VALUES (?, ?, ?, 'planlandi')",
                            array($personel_id, $tarih_str, $vardiya['id'])
                        );
                        $eklenen_nobet++;
                        $personel_nobet_sayisi[$personel_id]++;
                    }
                }
            }

            return array('basarili' => true, 'mesaj' => "Nöbet programı başarıyla oluşturuldu! Toplam {$eklenen_nobet} nöbet ve resmi tatil mesaisi eklendi.");

        } catch (Exception $e) {
            return array('basarili' => false, 'mesaj' => 'HATA: ' . $e->getMessage());
        }
    }

    private function enUygunVardiyaSec($vardialar, $tarih, $personel_id, $personeller, $personel_nobet_sayisi) {
        $son_vardiya = $this->db->fetchOne(
            "SELECT vardiya_id FROM nobet_programi 
             WHERE personel_id = ? AND tarih < ? AND vardiya_id IS NOT NULL 
             ORDER BY tarih DESC LIMIT 1",
            array($personel_id, $tarih)
        );

        $uygun_vardialar = array();
        foreach ($vardialar as $vardiya) {
            if ($son_vardiya && $son_vardiya['vardiya_id']) {
                $son_v = $this->db->fetchOne("SELECT baslangic_saat FROM vardiya_sablonlari WHERE id = ?", array($son_vardiya['vardiya_id']));
                if ($son_v && $son_v['baslangic_saat'] == '00:00:00') continue;
            }
            $uygun_vardialar[] = $vardiya;
        }

        if (count($uygun_vardialar) === 0) $uygun_vardialar = $vardialar;

        $vardiya_sayilari = array();
        foreach ($uygun_vardialar as $vardiya) {
            $res = $this->db->fetchOne("SELECT COUNT(*) as sayi FROM nobet_programi WHERE tarih = ? AND vardiya_id = ?", array($tarih, $vardiya['id']));
            $vardiya_sayilari[$vardiya['id']] = $res['sayi'] ?? 0;
        }

        asort($vardiya_sayilari);
        $en_az_vardiya_id = key($vardiya_sayilari);
        foreach ($uygun_vardialar as $vardiya) {
            if ($vardiya['id'] == $en_az_vardiya_id) return $vardiya;
        }
        return $uygun_vardialar[0];
    }

    private function enAzNobetliVardiyaSecResmiTatil($vardialar, $tarih, $personeller) {
        $vardiya_sayilari = array();
        foreach ($vardialar as $vardiya) {
            $res = $this->db->fetchOne("SELECT COUNT(*) as sayi FROM nobet_programi WHERE tarih = ? AND vardiya_id = ?", array($tarih, $vardiya['id']));
            $vardiya_sayilari[$vardiya['id']] = $res['sayi'] ?? 0;
        }
        asort($vardiya_sayilari);
        $en_az_vardiya_id = key($vardiya_sayilari);
        foreach ($vardialar as $vardiya) {
            if ($vardiya['id'] == $en_az_vardiya_id) return $vardiya;
        }
        return $vardialar[0];
    }
}
?>
