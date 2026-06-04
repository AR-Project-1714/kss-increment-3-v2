<?php

namespace App\Enums;

/**
 * Status laporan K3. Seperti pemeliharaan, modul ini tidak memiliki tahap
 * "acknowledged" (tidak ada serah-terima antar regu pada form K3): alur cukup
 * draft -> submitted -> approved (pengesahan dua pihak: Karu Safety & Manajer).
 * Lihat PERANCANGAN_MODUL_SAFETY.md §3.1.
 */
enum SafetyStatus: string
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
     * Kelas badge mengikuti pola design system operasional/pemeliharaan.
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

    /**
     * Transisi FSM eksplisit agar dapat diuji (selaras rencana refactor FSM).
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft     => in_array($target, [self::Draft, self::Submitted], true),
            self::Submitted => in_array($target, [self::Submitted, self::Approved], true),
            self::Approved  => false,
        };
    }
}
