<?php

namespace Tests\Unit;

use App\Support\MaterialNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Ejaan yang dipakai di sini diambil apa adanya dari laporan 10–15 Agustus 2026.
 */
class MaterialNameNormalizerTest extends TestCase
{
    #[DataProvider('ejaanLapanganProvider')]
    public function test_ejaan_satu_bahan_menghasilkan_kunci_yang_sama(string $name, string $key): void
    {
        $this->assertSame($key, MaterialNameNormalizer::key($name));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function ejaanLapanganProvider(): array
    {
        return [
            'clay apa adanya' => ['Clay', 'clay'],
            'clay huruf besar' => ['CLAY', 'clay'],
            'clay spasi berlebih' => ['Clay  ', 'clay'],
            'clay dengan kemasan' => ['Clay Jumbo Bag @ 1 Ton', 'clay'],
            'clay kemasan huruf besar' => ['CLAY JUMBO @1 Ton', 'clay'],
            'clay kemasan tanpa ukuran' => ['Clay jumbo bag', 'clay'],
            'clay dengan kadar' => ['CLAY JUMBO 17%', 'clay'],

            'limestone apa adanya' => ['Limestone', 'limestone'],
            'limestone huruf besar' => ['LIMESTONE', 'limestone'],
            'limestone huruf kecil dengan kemasan' => ['limestone Jumbo Bag @ 1 Ton', 'limestone'],
            'limestone kemasan tanpa ukuran' => ['Limestone Jumbo bag', 'limestone'],
            'limestone kemasan singkat' => ['LIMESTONE JUMBO', 'limestone'],

            'mgo ejaan kimia' => ['MgO', 'mgo'],
            'mgo huruf besar' => ['MGO', 'mgo'],
            'mgo dengan kadar' => ['Mgo 18%', 'mgo'],
            'mgo kadar dan kemasan' => ['MGO 18% Bag @50Kg', 'mgo'],
            'mgo kadar lain' => ['Mgo 17% in Bag @50 Kg', 'mgo'],
            'mgo kadar berspasi' => ['Mgo 18 % bag @50 Kg', 'mgo'],
            'mgo tanpa kata bag' => ['Mgo 18% @50Kg', 'mgo'],
        ];
    }

    public function test_bahan_berbeda_tidak_pernah_melebur(): void
    {
        $keys = array_map(
            static fn (string $name): string => MaterialNameNormalizer::key($name),
            ['Clay', 'Limestone', 'MgO', 'Dolomite JB', 'Phosphate Rock']
        );

        $this->assertSame($keys, array_unique($keys));
    }

    public function test_label_mempertahankan_ejaan_petugas(): void
    {
        $this->assertSame('MgO', MaterialNameNormalizer::label('MgO'));
        $this->assertSame('MGO', MaterialNameNormalizer::label('MGO 18% Bag @50Kg'));
        $this->assertSame('Clay', MaterialNameNormalizer::label('Clay Jumbo Bag @ 1 Ton'));
        $this->assertSame('Phosphate Rock', MaterialNameNormalizer::label('Phosphate Rock'));
    }

    public function test_nama_kosong_dan_nama_yang_hanya_berisi_kemasan(): void
    {
        $this->assertSame('', MaterialNameNormalizer::key(null));
        $this->assertSame('', MaterialNameNormalizer::key('   '));
        $this->assertSame('', MaterialNameNormalizer::key('Jumbo Bag @ 1 Ton'));
        $this->assertSame('', MaterialNameNormalizer::label('Bag 50 Kg'));
    }
}
