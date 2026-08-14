<?php

namespace App\Support;

/**
 * Katalog kemasan bongkar bahan baku.
 *
 * Petugas selalu mencatat dalam satuan Bag, sedangkan laporan dan rekap kinerja
 * memakai Ton. Yang menghubungkan keduanya adalah faktor `tonPerBag` di sini.
 *
 * Sebelumnya nama kemasan dan angka konversinya ditulis ulang di form, aturan
 * validasi, tampilan laporan, dan SQL rekap. Menambah satu kemasan berarti
 * menyunting semuanya, dan satu tempat yang terlewat menghasilkan angka yang
 * berbeda antar halaman. Seluruh berkas itu sekarang membaca katalog ini.
 *
 * Faktor di sini hanya dipakai ketika baris bahan baku *dibuat*. Nilainya ikut
 * disimpan pada kolom `material_items.packaging_factor`, sehingga menyunting
 * angka di katalog tidak pernah menggeser tonase laporan yang sudah tersimpan.
 *
 * Menambah kemasan baru cukup dengan menambah satu entri pada CATALOG.
 */
final class MaterialPackaging
{
    public const CODE_JUMBO_1000 = 'jumbo_1000';

    public const CODE_JUMBO_1500 = 'jumbo_1500';

    public const CODE_BAG_50 = 'bag_50';

    public const CODE_BAG_25 = 'bag_25';

    /**
     * Kemasan yang diketik petugas sendiri pada satu laporan, di luar katalog.
     * Lapangan sesekali membongkar kemasan yang belum pernah tercatat, dan
     * laporannya tidak boleh tertahan hanya karena menunggu rilis baru.
     */
    public const CODE_CUSTOM = 'custom';

    /** Label baris lama yang dibuat sebelum kolom kemasan ada. */
    public const UNRECORDED_LABEL = 'Kemasan belum dicatat';

    /**
     * Batas kewajaran faktor kemasan buatan petugas. Batas bawah mengikuti
     * ketelitian kolom `packaging_factor` (4 angka desimal); batas atas
     * menahan salah ketik yang bisa melipatgandakan tonase laporan.
     */
    private const MIN_FACTOR = 0.0001;

    private const MAX_FACTOR = 100.0;

    /** Panjang nama kemasan mengikuti lebar kolom `packaging_type`. */
    private const MAX_LABEL_LENGTH = 100;

    /**
     * Urutan entri menentukan urutan kelompok pada form maupun laporan.
     *
     * `default` menandai kemasan yang langsung tersedia pada form kosong —
     * dua kemasan yang paling sering dibongkar. `active` yang bernilai false
     * memensiunkan sebuah kemasan: hilang dari pilihan form, tetapi laporan
     * lama yang memakainya tetap terbaca dan tetap benar tonasenya.
     *
     * @var array<int, array{code: string, label: string, tonPerBag: float, hint: string, default: bool, active: bool}>
     */
    private const CATALOG = [
        [
            'code' => self::CODE_JUMBO_1000,
            'label' => 'Jumbo Bag 1 Ton',
            'tonPerBag' => 1.0,
            'hint' => '1 Bag = 1 Ton',
            'default' => true,
            'active' => true,
        ],
        [
            'code' => self::CODE_JUMBO_1500,
            'label' => 'Jumbo Bag 1,5 Ton',
            'tonPerBag' => 1.5,
            'hint' => '1 Bag = 1,5 Ton',
            'default' => false,
            'active' => true,
        ],
        [
            'code' => self::CODE_BAG_50,
            'label' => 'Bag 50 Kg',
            'tonPerBag' => 0.05,
            'hint' => '20 Bag = 1 Ton',
            'default' => true,
            'active' => true,
        ],
        [
            'code' => self::CODE_BAG_25,
            'label' => 'Bag 25 Kg',
            'tonPerBag' => 0.025,
            'hint' => '40 Bag = 1 Ton',
            'default' => false,
            'active' => true,
        ],
    ];

    /**
     * Ejaan lain yang harus tetap dikenali. Label `Jumbo Bag` dipakai seluruh
     * laporan sebelum ukuran 1,5 Ton muncul, dan kiriman lama yang hanya
     * membawa label (tanpa kode) masih boleh menyimpan laporan.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'jumbo bag' => self::CODE_JUMBO_1000,
        'jumbo bag 1 ton' => self::CODE_JUMBO_1000,
        'kemasan jumbo bag' => self::CODE_JUMBO_1000,
        'jumbo bag 1.5 ton' => self::CODE_JUMBO_1500,
        'jumbo bag 1.5' => self::CODE_JUMBO_1500,
        'bag 50 kg' => self::CODE_BAG_50,
        'bag 50kg' => self::CODE_BAG_50,
        'kemasan bag 50 kg' => self::CODE_BAG_50,
        'bag 25 kg' => self::CODE_BAG_25,
        'bag 25kg' => self::CODE_BAG_25,
        'kemasan bag 25 kg' => self::CODE_BAG_25,
    ];

    /**
     * Seluruh kemasan, termasuk yang sudah dipensiunkan.
     *
     * @return array<int, array{code: string, label: string, tonPerBag: float, hint: string, default: bool, active: bool}>
     */
    public static function all(): array
    {
        return self::CATALOG;
    }

    /**
     * Kemasan yang boleh dipilih petugas pada form baru.
     *
     * @return array<int, array{code: string, label: string, tonPerBag: float, hint: string, default: bool, active: bool}>
     */
    public static function active(): array
    {
        return array_values(array_filter(
            self::CATALOG,
            static fn (array $package): bool => $package['active']
        ));
    }

    /**
     * Kemasan yang langsung tampil pada form kosong.
     *
     * @return array<int, array{code: string, label: string, tonPerBag: float, hint: string, default: bool, active: bool}>
     */
    public static function defaults(): array
    {
        $defaults = array_values(array_filter(
            self::active(),
            static fn (array $package): bool => $package['default']
        ));

        // Form tetap harus punya satu kelompok walau seluruh kemasan bawaan
        // dipensiunkan suatu saat.
        return $defaults !== [] ? $defaults : array_slice(self::active(), 0, 1);
    }

    /**
     * Kode kemasan katalog yang boleh dipilih petugas.
     *
     * @return array<int, string>
     */
    public static function activeCodes(): array
    {
        return array_column(self::active(), 'code');
    }

    /**
     * Seluruh kode yang boleh dikirim form, termasuk kemasan buatan petugas.
     *
     * @return array<int, string>
     */
    public static function submittableCodes(): array
    {
        return [...self::activeCodes(), self::CODE_CUSTOM];
    }

    public static function isCustom(?string $code): bool
    {
        return trim((string) $code) === self::CODE_CUSTOM;
    }

    /**
     * Bentuk kemasan buatan petugas dari nama dan faktor yang diketiknya.
     * Mengembalikan null bila isiannya tidak masuk akal.
     *
     * @return array{code: string, label: string, tonPerBag: float, hint: string, default: bool, active: bool}|null
     */
    public static function custom(?string $label, mixed $factor): ?array
    {
        $label = preg_replace('/\s+/u', ' ', trim((string) $label)) ?? '';

        if ($label === '' || mb_strlen($label) > self::MAX_LABEL_LENGTH) {
            return null;
        }

        // Nama yang sama dengan kemasan katalog selalu memakai faktor katalog.
        // Tanpa ini, "Bag 50 Kg" dapat didefinisikan ulang lewat form dan dua
        // laporan memakai nama sama dengan tonase berbeda.
        $known = self::findByLabel($label);

        if ($known !== null) {
            return $known;
        }

        if (! is_numeric($factor)) {
            return null;
        }

        $factor = round((float) $factor, 4);

        if ($factor < self::MIN_FACTOR || $factor > self::MAX_FACTOR) {
            return null;
        }

        return [
            'code' => self::CODE_CUSTOM,
            'label' => $label,
            'tonPerBag' => $factor,
            'hint' => self::hintFor($factor),
            'default' => false,
            'active' => true,
        ];
    }

    /**
     * Kemasan sebuah baris kiriman form, baik dari katalog maupun buatan
     * petugas. Satu pintu ini dipakai aturan validasi sekaligus penyimpanan,
     * supaya keduanya tidak pernah menilai kiriman dengan cara berbeda.
     *
     * @param  array<string, mixed>  $row
     * @return array{code: string, label: string, tonPerBag: float, hint: string, default: bool, active: bool}|null
     */
    public static function fromSubmission(array $row): ?array
    {
        $code = trim((string) ($row['packaging_code'] ?? ''));

        if (self::isCustom($code)) {
            return self::custom($row['packaging_type'] ?? null, $row['packaging_factor'] ?? null);
        }

        return self::resolve($code, $row['packaging_type'] ?? null);
    }

    /**
     * Penanda pembeda antar kelompok kemasan dalam satu kegiatan. Kemasan
     * buatan petugas berbagi satu kode, jadi namanya ikut menjadi pembeda.
     *
     * @param  array{code: string, label: string}  $package
     */
    public static function groupKey(array $package): string
    {
        return self::isCustom($package['code'])
            ? $package['code'].'|'.mb_strtolower($package['label'], 'UTF-8')
            : $package['code'];
    }

    /**
     * Keterangan konversi yang dibaca petugas, mengikuti arah yang paling
     * masuk akal: kemasan besar dibaca per Bag, kemasan kecil per Ton.
     */
    public static function hintFor(float $tonPerBag): string
    {
        if ($tonPerBag >= 1.0) {
            return '1 Bag = '.self::number($tonPerBag).' Ton';
        }

        return self::number(1 / $tonPerBag).' Bag = 1 Ton';
    }

    /** Angka bergaya Indonesia tanpa nol desimal yang tidak perlu. */
    private static function number(float $value): string
    {
        $rounded = round($value, 2);

        return rtrim(rtrim(number_format($rounded, 2, ',', '.'), '0'), ',');
    }

    /**
     * @return array{code: string, label: string, tonPerBag: float, hint: string, default: bool, active: bool}|null
     */
    public static function find(?string $code): ?array
    {
        $code = trim((string) $code);

        foreach (self::CATALOG as $package) {
            if ($package['code'] === $code) {
                return $package;
            }
        }

        return null;
    }

    /**
     * Cari kemasan dari labelnya. Dipakai untuk kiriman dan data lama yang
     * belum mengenal kode.
     *
     * @return array{code: string, label: string, tonPerBag: float, hint: string, default: bool, active: bool}|null
     */
    public static function findByLabel(?string $label): ?array
    {
        $normalized = self::normalizeLabel($label);

        if ($normalized === '') {
            return null;
        }

        foreach (self::CATALOG as $package) {
            if (self::normalizeLabel($package['label']) === $normalized) {
                return $package;
            }
        }

        return isset(self::ALIASES[$normalized]) ? self::find(self::ALIASES[$normalized]) : null;
    }

    /**
     * Kemasan sebuah baris. Kode diutamakan; label hanya dipakai bila kodenya
     * belum ada, yaitu pada baris lama dan kiriman dari integrasi lama.
     *
     * @return array{code: string, label: string, tonPerBag: float, hint: string, default: bool, active: bool}|null
     */
    public static function resolve(?string $code, ?string $label = null): ?array
    {
        return self::find($code) ?? self::findByLabel($label);
    }

    /**
     * Label tampilan sebuah baris, termasuk baris yang belum punya kemasan.
     */
    public static function labelFor(?string $code, ?string $label = null): string
    {
        $package = self::resolve($code, $label);

        if ($package !== null) {
            return $package['label'];
        }

        // Kemasan di luar katalog tidak pernah dibuang begitu saja: teks
        // aslinya tetap ditampilkan supaya angkanya bisa ditelusuri.
        $fallback = preg_replace('/\s+/u', ' ', trim((string) $label)) ?? '';

        return $fallback !== '' ? $fallback : self::UNRECORDED_LABEL;
    }

    /**
     * Ton per Bag sebuah kemasan, atau null bila kemasannya tidak dikenali.
     */
    public static function factorFor(?string $code, ?string $label = null): ?float
    {
        return self::resolve($code, $label)['tonPerBag'] ?? null;
    }

    /**
     * Urutan kelompok pada laporan. Kemasan yang tidak dikenali dan baris lama
     * tanpa kemasan selalu jatuh paling akhir.
     */
    public static function orderFor(?string $code, ?string $label = null): int
    {
        $package = self::resolve($code, $label);

        if ($package === null) {
            return count(self::CATALOG) + 1;
        }

        foreach (self::CATALOG as $index => $candidate) {
            if ($candidate['code'] === $package['code']) {
                return $index;
            }
        }

        return count(self::CATALOG) + 1;
    }

    /**
     * Samakan bentuk label sebelum dicocokkan: beda huruf besar-kecil, spasi
     * ganda, dan penulisan desimal "1,5" versus "1.5" tidak boleh membuat
     * kemasan yang sama dianggap berbeda.
     */
    private static function normalizeLabel(?string $label): string
    {
        $text = preg_replace('/\s+/u', ' ', trim((string) $label)) ?? '';
        $text = str_replace(',', '.', $text);

        return mb_strtolower($text, 'UTF-8');
    }
}
