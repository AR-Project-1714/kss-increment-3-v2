<?php

namespace Tests\Unit;

use App\Support\ShipNameNormalizer;
use PHPUnit\Framework\TestCase;

class ShipNameNormalizerTest extends TestCase
{
    /**
     * Contoh yang dikeluhkan manajer operasi: satu kapal, banyak ejaan.
     */
    public function test_ejaan_yang_berbeda_menghasilkan_kunci_yang_sama(): void
    {
        $variants = [
            'KLM. Sumber Utama Keluarga',
            'klm sumber utama keluarga',
            'Sumber Utama Keluarga',
            'SUMBER UTAMA KELUARGA',
            'KLM.SUMBER  UTAMA  KELUARGA',
            'KLM/Sumber-Utama-Keluarga',
        ];

        foreach ($variants as $variant) {
            $this->assertSame('SUMBER UTAMA KELUARGA', ShipNameNormalizer::key($variant), $variant);
        }
    }

    public function test_awalan_jenis_kapal_dibuang(): void
    {
        $this->assertSame('GOLDEN REJEKI', ShipNameNormalizer::key('KM. Golden Rejeki'));
        $this->assertSame('GOLDEN REJEKI', ShipNameNormalizer::key('KM. GOLDEN REJEKI'));
        $this->assertSame('MALACCA STRAIT', ShipNameNormalizer::key('Km.Malacca Strait'));
        $this->assertSame('OCEAN PHOENIX', ShipNameNormalizer::key('MV OCEAN PHOENIX'));
        $this->assertSame('MARIANNA 28', ShipNameNormalizer::key('LPG/C Marianna 28'));
    }

    public function test_nama_yang_hanya_terdiri_dari_awalan_tidak_habis_terpangkas(): void
    {
        $this->assertSame('KM', ShipNameNormalizer::key('KM'));
        $this->assertSame('', ShipNameNormalizer::key('   '));
        $this->assertSame('', ShipNameNormalizer::key(null));
    }

    public function test_singkatan_dan_nama_terpotong_dikenali_sebagai_kapal_yang_sama(): void
    {
        $this->assertTrue(ShipNameNormalizer::isSameShip('KLM. Sumber Utama Keluarga', 'SUK'));
        $this->assertTrue(ShipNameNormalizer::isSameShip('KLM. Sumber Utama Keluarga', 'Sumber Utama K'));
        $this->assertTrue(ShipNameNormalizer::isSameShip('SUK', 'Sumber Utama K'));
    }

    public function test_salah_ketik_satu_huruf_masih_dikenali(): void
    {
        $this->assertTrue(ShipNameNormalizer::isSameShip('KM. Golden Rejeki', 'MV. Golden Rezeki'));
        $this->assertTrue(ShipNameNormalizer::isSameShip('Sumber Utama Keluarga', 'Sumber Utama Kelurga'));
    }

    /**
     * Batas keamanan: kapal yang memang berbeda tidak boleh ikut tersatukan,
     * terutama kapal kembar yang dibedakan oleh angka di belakang namanya.
     */
    public function test_kapal_berbeda_tetap_terpisah(): void
    {
        $this->assertFalse(ShipNameNormalizer::isSameShip('KM. Malacca Strait', 'KM. Malacca Star'));
        $this->assertFalse(ShipNameNormalizer::isSameShip('KM. Golden Rejeki', 'KM. Noah Asyera'));
        $this->assertFalse(ShipNameNormalizer::isSameShip('KM Tanto Sejahtera', 'KM Tanto Sejati'));
        $this->assertFalse(ShipNameNormalizer::isSameShip('KM Sinar Sulawesi', 'KM Sinar Sulawesi 8'));
        $this->assertFalse(ShipNameNormalizer::isSameShip('MV. Buana Gemilang II', 'MV. Buana Gemilang'));
    }

    public function test_nama_kosong_tidak_pernah_cocok(): void
    {
        $this->assertFalse(ShipNameNormalizer::isSameShip('', 'KM. Golden Rejeki'));
        $this->assertFalse(ShipNameNormalizer::isSameShip(null, null));
        $this->assertSame(0.0, ShipNameNormalizer::score('KM.', ''));
    }
}
