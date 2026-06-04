<?php

namespace App\Enums;

/**
 * Status laporan pemeliharaan. Berbeda dengan operasional, modul ini tidak
 * memiliki tahap "acknowledged" (tidak ada serah-terima antar regu): alur
 * cukup draft -> submitted -> approved (pengesahan dua pihak).
 */
enum MaintenanceStatus: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Approved  = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Submitted => 'Diserahkan',
            self::Approved  => 'Diarsipkan',
        };
    }

    /**
     * Kelas badge mengikuti pola design system operasional (lihat MD §2.2).
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'draft',
            self::Submitted => 'submit',
            self::Approved  => 'archive',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft     => 'fi fi-rr-blueprint',
            self::Submitted => 'fi fi-rr-memo-circle-check',
            self::Approved  => 'fi fi-rr-archive',
        };
    }
}
