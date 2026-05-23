<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const ADMIN = 'admin';
    public const MANAGER = 'manajer';
    public const OPERATIONAL = 'operasional';
    public const MAINTENANCE = 'pemeliharaan';
    public const SAFETY = 'safety';

    public const NAMES = [
        self::ADMIN,
        self::MANAGER,
        self::OPERATIONAL,
        self::MAINTENANCE,
        self::SAFETY,
    ];

    public const MANAGEMENT_ROLES = [
        self::ADMIN,
        self::MANAGER,
    ];

    /**
     * Division (petugas operasional) roles that may access the report-ops pages.
     */
    public const DIVISION_ROLES = [
        self::OPERATIONAL,
        self::MAINTENANCE,
        self::SAFETY,
    ];

    protected $guarded = ['id'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public static function displayName(?string $name): string
    {
        return match (strtolower((string) $name)) {
            self::ADMIN => 'Admin',
            self::MANAGER => 'Manajer',
            self::OPERATIONAL, 'petugas' => 'Operasional',
            self::MAINTENANCE => 'Pemeliharaan',
            self::SAFETY => 'Safety',
            default => $name ? ucwords(str_replace(['_', '-'], ' ', $name)) : 'Operasional',
        };
    }

    public static function hasManagementAccess(?string $name): bool
    {
        return in_array(strtolower((string) $name), self::MANAGEMENT_ROLES, true);
    }

    /**
     * Normalize a stored role name to its canonical key (handles the legacy "petugas" alias).
     */
    public static function normalize(?string $name): string
    {
        $name = strtolower(trim((string) $name));

        return $name === 'petugas' ? self::OPERATIONAL : $name;
    }

    /**
     * The dashboard route name a user should land on, based on their role.
     */
    public static function homeRoute(?string $name): string
    {
        return match (self::normalize($name)) {
            self::ADMIN => 'admin.index',
            self::MANAGER => 'manajer.index',
            default => 'report-ops.index',
        };
    }

    /**
     * Whether the given role maps to a known dashboard (used to avoid redirect loops).
     */
    public static function hasKnownHome(?string $name): bool
    {
        $name = self::normalize($name);

        return $name === self::ADMIN
            || $name === self::MANAGER
            || in_array($name, self::DIVISION_ROLES, true);
    }
}
