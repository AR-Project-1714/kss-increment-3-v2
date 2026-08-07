<?php

namespace Tests\Unit;

use App\Support\ContainerStatusNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContainerStatusNormalizerTest extends TestCase
{
    /**
     * Ejaan yang benar-benar ditemukan pada laporan Juli-Agustus 2026. Inilah
     * yang membuat Bongkar Container tercatat 112 Teus padahal 275 Teus.
     *
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function lapanganProvider(): array
    {
        return [
            'sudah baku empty' => ['Empty', 'Empty'],
            'sudah baku full' => ['Full', 'Full'],
            'huruf kecil' => ['empty', 'Empty'],
            'huruf besar' => ['FULL', 'Full'],
            'berspasi' => ['  Empty  ', 'Empty'],
            'container di depan' => ['Container empty', 'Empty'],
            'container di belakang' => ['Empty Container', 'Empty'],
            'salah ketik sisip' => ['EMPYTY', 'Empty'],
            'salah ketik tukar posisi' => ['EMTPY', 'Empty'],
            'ejaan container keliru + isi' => ['Coutener isi', 'Full'],
            'ejaan container keliru 2 + isi' => ['Contaner Isi', 'Full'],
            'kalimat muat' => ['Muat container isi', 'Full'],
            'padanan indonesia kosong' => ['Kontainer kosong', 'Empty'],
            'kata kerja bongkar' => ['Bongkar container', 'Empty'],
            'kata kerja muat' => ['Muat container', 'Full'],
        ];
    }

    #[DataProvider('lapanganProvider')]
    public function test_menyeragamkan_penanda_dari_lapangan(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, ContainerStatusNormalizer::normalize($input));
    }

    /**
     * Isian kosong tetap kosong. Menebaknya berarti mengarang angka, dan itu
     * justru mengulang kesalahan yang sedang diperbaiki.
     *
     * @return array<string, array{0: ?string}>
     */
    public static function tidakDapatDipastikanProvider(): array
    {
        return [
            'null' => [null],
            'kosong' => [''],
            'spasi saja' => ['   '],
            'tanda hubung' => ['-'],
            'hanya kata container' => ['Container'],
            'keterangan lain' => ['Hujan deras'],
            'angka' => ['20'],
        ];
    }

    #[DataProvider('tidakDapatDipastikanProvider')]
    public function test_penanda_yang_tidak_dapat_dipastikan_menjadi_null(?string $input): void
    {
        $this->assertNull(ContainerStatusNormalizer::normalize($input));
    }

    /**
     * Dua penanda berlawanan dalam satu isian tidak boleh dipilih salah satu.
     */
    public function test_penanda_berlawanan_menjadi_null(): void
    {
        $this->assertNull(ContainerStatusNormalizer::normalize('Empty / Full'));
        $this->assertNull(ContainerStatusNormalizer::normalize('kosong isi'));
    }

    /**
     * "isi" dicocokkan sebagai kata utuh. Tanpa itu, "posisi" dan "kondisi"
     * ikut tergolong Full — kesalahan yang jauh lebih sulit dilacak daripada
     * yang sedang diperbaiki.
     */
    public function test_kata_berakhiran_isi_tidak_tergolong_full(): void
    {
        foreach (['Posisi dermaga', 'Kondisi cuaca', 'Revisi jam'] as $input) {
            $this->assertNull(
                ContainerStatusNormalizer::normalize($input),
                sprintf('"%s" tidak boleh dianggap penanda Full.', $input),
            );
        }
    }

    public function test_penanda_baku_dikenali_sebagai_baku(): void
    {
        $this->assertTrue(ContainerStatusNormalizer::isCanonical('Empty'));
        $this->assertTrue(ContainerStatusNormalizer::isCanonical('Full'));
        $this->assertFalse(ContainerStatusNormalizer::isCanonical('empty'));
        $this->assertFalse(ContainerStatusNormalizer::isCanonical('Container empty'));
        $this->assertFalse(ContainerStatusNormalizer::isCanonical(null));
    }
}
