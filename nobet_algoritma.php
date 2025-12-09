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
            
            // Aktif personelleri getir
            $personeller = $this->db->fetchAll(
                "SELECT p.*, it.tercih_edilen_gun1, it.tercih_edilen_gun2 
                 FROM personel p 
                 LEFT JOIN izin_tercihleri it ON p.id = it.personel_id 
                 WHERE p.aktif = 1 
                 ORDER BY p.id"
            );
            
            if (count($personeller) === 0) {
                return array('basarili' => false, 'mesaj' => 'Aktif personel bulunamadı!');
            }
            
            // Vardiya şablonlarını getir
            $vardialar = $this->db->fetchAll(
                "SELECT * FROM vardiya_sablonlari WHERE aktif = 1 ORDER BY lokasyon, id"
            );
            
            if (count($vardialar) === 0) {
                return array('basarili' => false, 'mesaj' => 'Aktif vardiya şablonu bulunamadı!');
            }
            
            // Resmi tatilleri getir
            $yil = date('Y', strtotime($baslangic_tarihi));
            $resmi_tatiller = $this->db->fetchAll(
                "SELECT tarih FROM resmi_tatiller WHERE yil = ?",
                array($yil)
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
                
                // Resmi tatil mi kontrol et
                $resmi_tatil_mi = in_array($tarih_str, $resmi_tatil_tarihleri);
                
                // Her personel için bu günü işle
                foreach ($personeller as $personel) {
                    $personel_id = $personel['id'];
                    $kadro_turu = $personel['kadro_turu'];
                    
                    // Hafta tatili kontrolü
                    $hafta_tatili_mi = false;
                    
                    if ($kadro_turu === 'memur') {
                        // Memurlar haftada 2 gün tatil
                        $tercih1 = $personel['tercih_edilen_gun1'];
                        $tercih2 = $personel['tercih_edilen_gun2'];
                        
                        if ($tercih1 !== null && $tercih2 !== null) {
                            if ($gun_no == $tercih1 || $gun_no == $tercih2) {
                                $hafta_tatili_mi = true;
                            }
                        } else {
                            // Tercih yoksa varsayılan: Cumartesi-Pazar
                            if ($gun_no == 0 || $gun_no == 6) {
                                $hafta_tatili_mi = true;
                            }
                        }
                    } elseif ($kadro_turu === 'isci') {
                        // İşçiler haftada 1 gün tatil
                        $tercih1 = $personel['tercih_edilen_gun1'];
                        
                        if ($tercih1 !== null) {
                            if ($gun_no == $tercih1) {
                                $hafta_tatili_mi = true;
                            }
                        } else {
                            // Tercih yoksa varsayılan: Pazar
                            if ($gun_no == 0) {
                                $hafta_tatili_mi = true;
                            }
                        }
                    }
                    
                    // Hafta tatili ise
                    if ($hafta_tatili_mi && !$resmi_tatil_mi) {
                        $this->db->query(
                            "INSERT INTO nobet_programi (personel_id, tarih, vardiya_id, durum) 
                             VALUES (?, ?, NULL, 'hafta_tatili')",
                            array($personel_id, $tarih_str)
                        );
                        continue;
                    }
                    
                    // Resmi tatil ise
                    if ($resmi_tatil_mi) {
                        // Resmi tatilde de vardiya atayacağız ama durum farklı olacak
                        $vardiya = $this->enAzNobetliVardiyaSecResmiTatil($vardialar, $tarih_str, $personeller);
                        
                        $this->db->query(
                            "INSERT INTO nobet_programi (personel_id, tarih, vardiya_id, durum) 
                             VALUES (?, ?, ?, 'resmi_tatil')",
                            array($personel_id, $tarih_str, $vardiya['id'])
                        );
                        
                        // Fazla mesai kaydı ekle
                        $ayarlar = $this->db->fetchOne("SELECT ayar_degeri FROM program_ayarlari WHERE ayar_adi = 'resmi_tatil_odeme'");
                        $odeme_tipi = $ayarlar ? $ayarlar['ayar_degeri'] : 'ucret';
                        
                        $this->db->query(
                            "INSERT INTO mesai_kayitlari (personel_id, tarih, fazla_saat, odeme_tipi, aciklama) 
                             VALUES (?, ?, 8, ?, 'Resmi tatil çalışması')",
                            array($personel_id, $tarih_str, $odeme_tipi)
                        );
                        
                        $eklenen_nobet++;
                        $personel_nobet_sayisi[$personel_id]++;
                        continue;
                    }
                    
                    // Normal çalışma günü - vardiya ata
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
            
            return array(
                'basarili' => true, 
                'mesaj' => "Nöbet programı başarıyla oluşturuldu! Toplam {$toplam_gun} gün için {$eklenen_nobet} nöbet kaydı eklendi!"
            );
            
        } catch (Exception $e) {
            return array('basarili' => false, 'mesaj' => 'HATA: ' . $e->getMessage());
        }
    }
    
    /**
     * En uygun vardiyayı seç
     */
    private function enUygunVardiyaSec($vardialar, $tarih, $personel_id, $personeller, $personel_nobet_sayisi) {
        // Personelin son vardiyasını kontrol et
        $son_vardiya = $this->db->fetchOne(
            "SELECT vardiya_id FROM nobet_programi 
             WHERE personel_id = ? AND tarih < ? AND vardiya_id IS NOT NULL 
             ORDER BY tarih DESC LIMIT 1",
            array($personel_id, $tarih)
        );
        
        // Dengeli dağılım için lokasyon kontrolü
        $kampus_giris_sayisi = $this->db->fetchOne(
            "SELECT COUNT(*) as sayi FROM nobet_programi np 
             INNER JOIN vardiya_sablonlari vs ON np.vardiya_id = vs.id 
             WHERE np.personel_id = ? AND vs.lokasyon = 'kampus_giris'",
            array($personel_id)
        )['sayi'];
        
        $kampus_ici_sayisi = $this->db->fetchOne(
            "SELECT COUNT(*) as sayi FROM nobet_programi np 
             INNER JOIN vardiya_sablonlari vs ON np.vardiya_id = vs.id 
             WHERE np.personel_id = ? AND vs.lokasyon = 'kampus_ici'",
            array($personel_id)
        )['sayi'];
        
        // Lokasyon tercihi belirle
        $tercih_lokasyon = null;
        if ($kampus_giris_sayisi < $kampus_ici_sayisi) {
            $tercih_lokasyon = 'kampus_giris';
        } elseif ($kampus_ici_sayisi < $kampus_giris_sayisi) {
            $tercih_lokasyon = 'kampus_ici';
        }
        
        // Uygun vardiyalar arasından seç
        $uygun_vardialar = array();
        foreach ($vardialar as $vardiya) {
            // Son vardiya gece vardiyası ise, aynı gün gündüz vardiyası verme
            if ($son_vardiya && $son_vardiya['vardiya_id']) {
                $son_v = $this->db->fetchOne(
                    "SELECT baslangic_saat FROM vardiya_sablonlari WHERE id = ?",
                    array($son_vardiya['vardiya_id'])
                );
                if ($son_v && $son_v['baslangic_saat'] == '00:00:00') {
                    // Bir gün dinlenme ver
                    continue;
                }
            }
            
            // Lokasyon tercihi varsa uygula
            if ($tercih_lokasyon && $vardiya['lokasyon'] === $tercih_lokasyon) {
                $uygun_vardialar[] = $vardiya;
            } elseif (!$tercih_lokasyon) {
                $uygun_vardialar[] = $vardiya;
            }
        }
        
        // Eğer uygun vardiya yoksa tüm vardiyalardan seç
        if (count($uygun_vardialar) === 0) {
            $uygun_vardialar = $vardialar;
        }
        
        // En az atanan vardiyayı bul
        $vardiya_sayilari = array();
        foreach ($uygun_vardialar as $vardiya) {
            $sayi = $this->db->fetchOne(
                "SELECT COUNT(*) as sayi FROM nobet_programi 
                 WHERE tarih = ? AND vardiya_id = ?",
                array($tarih, $vardiya['id'])
            )['sayi'];
            $vardiya_sayilari[$vardiya['id']] = $sayi;
        }
        
        asort($vardiya_sayilari);
        $en_az_vardiya_id = key($vardiya_sayilari);
        
        foreach ($uygun_vardialar as $vardiya) {
            if ($vardiya['id'] == $en_az_vardiya_id) {
                return $vardiya;
            }
        }
        
        // Varsayılan olarak ilk vardiyayı döndür
        return $uygun_vardialar[0];
    }
    
    /**
     * Resmi tatil için vardiya seç (dengeli dağılım)
     */
    private function enAzNobetliVardiyaSecResmiTatil($vardialar, $tarih, $personeller) {
        $vardiya_sayilari = array();
        
        foreach ($vardialar as $vardiya) {
            $sayi = $this->db->fetchOne(
                "SELECT COUNT(*) as sayi FROM nobet_programi 
                 WHERE tarih = ? AND vardiya_id = ?",
                array($tarih, $vardiya['id'])
            )['sayi'];
            $vardiya_sayilari[$vardiya['id']] = $sayi;
        }
        
        asort($vardiya_sayilari);
        $en_az_vardiya_id = key($vardiya_sayilari);
        
        foreach ($vardialar as $vardiya) {
            if ($vardiya['id'] == $en_az_vardiya_id) {
                return $vardiya;
            }
        }
        
        return $vardialar[0];
    }
}
?>