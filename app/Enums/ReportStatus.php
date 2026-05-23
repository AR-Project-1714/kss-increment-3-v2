<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Draft        = 'draft';
    case Submitted    = 'submitted';
    case Acknowledged = 'acknowledged';
    case Approved     = 'approved';

    public function label(): string
    {
        return match($this) {
            self::Draft        => 'Draft',
            self::Submitted    => 'Menunggu TTD',
            self::Acknowledged => 'Menunggu Approval',
            self::Approved     => 'Disetujui',
        };
    }
}
