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
}
