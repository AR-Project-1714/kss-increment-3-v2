<?php

namespace Tests\Unit;

use App\Support\MaterialPackaging;
use PHPUnit\Framework\TestCase;

class MaterialPackagingTest extends TestCase
{
    public function test_faktor_konversi_setiap_kemasan(): void
    {
        $this->assertSame(1.0, MaterialPackaging::factorFor(MaterialPackaging::CODE_JUMBO_1000));
        $this->assertSame(1.5, MaterialPackaging::factorFor(MaterialPackaging::CODE_JUMBO_1500));
        $this->assertSame(0.05, MaterialPackaging::factorFor(MaterialPackaging::CODE_BAG_50));
        $this->assertSame(0.025, MaterialPackaging::factorFor(MaterialPackaging::CODE_BAG_25));
    }

    /**
     * Angka inilah yang dipakai lapangan: 1 bag jumbo besar = 1,5 ton, dan
     * 40 bag ukuran 25 kg = 1 ton.
     */
    public function test_konversi_bag_ke_ton(): void
    {
        $this->assertSame(300.0, 200 * MaterialPackaging::factorFor(MaterialPackaging::CODE_JUMBO_1500));
        $this->assertSame(20.0, 800 * MaterialPackaging::factorFor(MaterialPackaging::CODE_BAG_25));
        $this->assertSame(75.0, 1500 * MaterialPackaging::factorFor(MaterialPackaging::CODE_BAG_50));
    }

    /**
     * Label "Jumbo Bag" dipakai seluruh laporan sebelum ukuran 1,5 Ton ada.
     * Pemetaan ini yang dipakai migrasi pengisian data lama.
     */
    public function test_label_lama_dipetakan_ke_kemasan_satu_ton(): void
    {
        $package = MaterialPackaging::findByLabel('Jumbo Bag');

        $this->assertNotNull($package);
        $this->assertSame(MaterialPackaging::CODE_JUMBO_1000, $package['code']);
        $this->assertSame('Jumbo Bag 1 Ton', $package['label']);
        $this->assertSame(1.0, $package['tonPerBag']);
    }

    public function test_ejaan_label_yang_berbeda_tetap_dikenali(): void
    {
        foreach (['bag 50 kg', 'Bag 50kg', 'BAG 50 KG', ' Bag  50  Kg '] as $label) {
            $this->assertSame(
                MaterialPackaging::CODE_BAG_50,
                MaterialPackaging::findByLabel($label)['code'] ?? null,
                'Label "'.$label.'" seharusnya dikenali sebagai Bag 50 Kg.'
            );
        }

        // Penulisan desimal Indonesia dan Inggris menunjuk kemasan yang sama.
        $this->assertSame(MaterialPackaging::CODE_JUMBO_1500, MaterialPackaging::findByLabel('Jumbo Bag 1,5 Ton')['code']);
        $this->assertSame(MaterialPackaging::CODE_JUMBO_1500, MaterialPackaging::findByLabel('Jumbo Bag 1.5 Ton')['code']);
    }

    public function test_kemasan_tidak_dikenali_tidak_punya_faktor(): void
    {
        $this->assertNull(MaterialPackaging::factorFor(null, null));
        $this->assertNull(MaterialPackaging::factorFor('bag_10'));
        $this->assertNull(MaterialPackaging::factorFor(null, 'Karung Goni'));

        // Teks aslinya tetap ditampilkan supaya angkanya bisa ditelusuri.
        $this->assertSame('Karung Goni', MaterialPackaging::labelFor(null, 'Karung Goni'));
        $this->assertSame(MaterialPackaging::UNRECORDED_LABEL, MaterialPackaging::labelFor(null, null));
    }

    /**
     * Kode menang atas label. Kiriman form yang labelnya diubah tidak boleh
     * menggeser kemasan yang sebenarnya dipilih.
     */
    public function test_kode_diutamakan_daripada_label(): void
    {
        $package = MaterialPackaging::resolve(MaterialPackaging::CODE_BAG_50, 'Jumbo Bag 1,5 Ton');

        $this->assertSame(MaterialPackaging::CODE_BAG_50, $package['code']);
        $this->assertSame(0.05, $package['tonPerBag']);
    }

    public function test_urutan_kelompok_laporan(): void
    {
        $this->assertSame(0, MaterialPackaging::orderFor(MaterialPackaging::CODE_JUMBO_1000));
        $this->assertSame(1, MaterialPackaging::orderFor(MaterialPackaging::CODE_JUMBO_1500));
        $this->assertSame(2, MaterialPackaging::orderFor(MaterialPackaging::CODE_BAG_50));
        $this->assertSame(3, MaterialPackaging::orderFor(MaterialPackaging::CODE_BAG_25));

        // Kemasan yang tidak dikenali selalu jatuh paling akhir.
        $this->assertGreaterThan(
            MaterialPackaging::orderFor(MaterialPackaging::CODE_BAG_25),
            MaterialPackaging::orderFor(null, null),
        );
    }

    public function test_kemasan_tambahan_dibentuk_dari_isian_petugas(): void
    {
        $package = MaterialPackaging::custom('Bag 40 Kg', 0.04);

        $this->assertSame(MaterialPackaging::CODE_CUSTOM, $package['code']);
        $this->assertSame('Bag 40 Kg', $package['label']);
        $this->assertSame(0.04, $package['tonPerBag']);
        $this->assertSame('25 Bag = 1 Ton', $package['hint']);
    }

    public function test_kemasan_tambahan_menolak_isian_yang_tidak_masuk_akal(): void
    {
        $this->assertNull(MaterialPackaging::custom('', 0.04));
        $this->assertNull(MaterialPackaging::custom('Bag 40 Kg', null));
        $this->assertNull(MaterialPackaging::custom('Bag 40 Kg', 0));
        $this->assertNull(MaterialPackaging::custom('Bag 40 Kg', -1));
        $this->assertNull(MaterialPackaging::custom('Bag 40 Kg', 'dua'));
        $this->assertNull(MaterialPackaging::custom('Bag 40 Kg', 1000));
        $this->assertNull(MaterialPackaging::custom(str_repeat('a', 101), 1));
    }

    /**
     * Tanpa penjagaan ini, "Bag 50 Kg" bisa didefinisikan ulang lewat form dan
     * dua laporan memakai nama kemasan sama dengan tonase berbeda.
     */
    public function test_kemasan_tambahan_bernama_sama_dengan_katalog_memakai_faktor_katalog(): void
    {
        $package = MaterialPackaging::custom('Bag 50 Kg', 9.0);

        $this->assertSame(MaterialPackaging::CODE_BAG_50, $package['code']);
        $this->assertSame(0.05, $package['tonPerBag']);
    }

    public function test_kiriman_form_dibaca_lewat_satu_pintu(): void
    {
        $catalog = MaterialPackaging::fromSubmission([
            'packaging_code' => MaterialPackaging::CODE_BAG_25,
            'packaging_factor' => '99',
        ]);
        $this->assertSame(0.025, $catalog['tonPerBag'], 'Kemasan katalog tidak boleh memakai faktor kiriman.');

        $custom = MaterialPackaging::fromSubmission([
            'packaging_code' => MaterialPackaging::CODE_CUSTOM,
            'packaging_type' => 'Karung Goni',
            'packaging_factor' => '0.06',
        ]);
        $this->assertSame('Karung Goni', $custom['label']);
        $this->assertSame(0.06, $custom['tonPerBag']);

        $this->assertNull(MaterialPackaging::fromSubmission(['packaging_code' => 'bag_10']));
    }

    /** Dua kemasan tambahan berbagi satu kode, jadi namanya ikut membedakan. */
    public function test_penanda_kelompok_membedakan_kemasan_tambahan(): void
    {
        $first = MaterialPackaging::custom('Karung Goni', 0.06);
        $second = MaterialPackaging::custom('Karung Plastik', 0.06);

        $this->assertNotSame(MaterialPackaging::groupKey($first), MaterialPackaging::groupKey($second));
        $this->assertSame(
            MaterialPackaging::groupKey($first),
            MaterialPackaging::groupKey(MaterialPackaging::custom('karung goni', 0.06)),
        );
        $this->assertSame(
            MaterialPackaging::CODE_BAG_50,
            MaterialPackaging::groupKey(MaterialPackaging::find(MaterialPackaging::CODE_BAG_50)),
        );
    }

    public function test_keterangan_konversi_mengikuti_ukuran_kemasan(): void
    {
        $this->assertSame('1 Bag = 1,5 Ton', MaterialPackaging::hintFor(1.5));
        $this->assertSame('1 Bag = 1 Ton', MaterialPackaging::hintFor(1.0));
        $this->assertSame('40 Bag = 1 Ton', MaterialPackaging::hintFor(0.025));
    }

    public function test_kemasan_bawaan_form_adalah_dua_yang_paling_sering(): void
    {
        $this->assertSame(
            [MaterialPackaging::CODE_JUMBO_1000, MaterialPackaging::CODE_BAG_50],
            array_column(MaterialPackaging::defaults(), 'code'),
        );
    }
}
