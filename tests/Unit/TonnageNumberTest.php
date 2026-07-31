<?php

namespace Tests\Unit;

use App\Support\TonnageNumber;
use PHPUnit\Framework\TestCase;

class TonnageNumberTest extends TestCase
{
    /**
     * Titik yang diikuti tepat tiga angka adalah pemisah ribuan gaya Indonesia.
     * Buktinya ada pada laporan berurutan untuk kapal yang sama: shift malam
     * menulis "16.750" lalu shift pagi menulis "16750" untuk pembacaan yang
     * sama persis.
     */
    public function test_titik_dengan_tiga_angka_adalah_pemisah_ribuan(): void
    {
        $this->assertSame(16750.0, TonnageNumber::parse('16.750'));
        $this->assertSame(19400.0, TonnageNumber::parse('19.400'));
        $this->assertSame(32000.0, TonnageNumber::parse('32.000'));
        $this->assertSame(1234567.0, TonnageNumber::parse('1.234.567'));
    }

    public function test_titik_dengan_satu_atau_dua_angka_adalah_koma_desimal(): void
    {
        $this->assertSame(4420.25, TonnageNumber::parse('4420.25'));
        $this->assertSame(604.5, TonnageNumber::parse('604.5'));
        $this->assertSame(311.4, TonnageNumber::parse('311.40'));
    }

    public function test_dua_jenis_pemisah_yang_terakhir_adalah_komanya(): void
    {
        $this->assertSame(4420.25, TonnageNumber::parse('4.420,25'));
        $this->assertSame(4420.25, TonnageNumber::parse('4,420.25'));
    }

    public function test_angka_polos_dan_masukan_kosong(): void
    {
        $this->assertSame(8630.0, TonnageNumber::parse('8630'));
        $this->assertSame(0.0, TonnageNumber::parse('0'));
        $this->assertNull(TonnageNumber::parse(''));
        $this->assertNull(TonnageNumber::parse(null));
        $this->assertNull(TonnageNumber::parse('  '));
    }

    /**
     * COB bernilai nol berarti tidak ada penimbangan pada kejadian itu, bukan
     * muatan nol.
     */
    public function test_pembacaan_nol_dianggap_tidak_ada(): void
    {
        $this->assertNull(TonnageNumber::reading('0'));
        $this->assertNull(TonnageNumber::reading(''));
        $this->assertNull(TonnageNumber::reading('-5'));
        $this->assertSame(16750.0, TonnageNumber::reading('16.750'));
    }
}
