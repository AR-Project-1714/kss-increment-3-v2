<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_OPERATOR = 'petugas';

    private const ADMIN = 'admin';
    private const MANAGER = 'manajer';
    private const OPERATIONAL = 'operasional';
    private const MAINTENANCE = 'pemeliharaan';
    private const SAFETY = 'safety';

    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::transaction(function (): void {
            $operationalId = $this->roleId(self::OPERATIONAL);
            $legacyOperatorId = $this->roleId(self::LEGACY_OPERATOR);

            if ($legacyOperatorId && ! $operationalId) {
                DB::table('roles')
                    ->where('id', $legacyOperatorId)
                    ->update($this->updatePayload(['name' => self::OPERATIONAL]));

                $operationalId = $legacyOperatorId;
            } else {
                $operationalId ??= $this->ensureRole(self::OPERATIONAL);
            }

            foreach ([self::ADMIN, self::MANAGER, self::MAINTENANCE, self::SAFETY] as $roleName) {
                $this->ensureRole($roleName);
            }

            if ($legacyOperatorId && $legacyOperatorId !== $operationalId) {
                $this->moveUsersToRole($legacyOperatorId, $operationalId);
                DB::table('roles')->where('id', $legacyOperatorId)->delete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::transaction(function (): void {
            $adminId = $this->ensureRole(self::ADMIN);
            $legacyOperatorId = $this->ensureRole(self::LEGACY_OPERATOR);

            foreach ([self::MANAGER => $adminId, self::OPERATIONAL => $legacyOperatorId, self::MAINTENANCE => $legacyOperatorId, self::SAFETY => $legacyOperatorId] as $roleName => $fallbackRoleId) {
                $roleId = $this->roleId($roleName);

                if (! $roleId) {
                    continue;
                }

                $this->moveUsersToRole($roleId, $fallbackRoleId);
                DB::table('roles')->where('id', $roleId)->delete();
            }
        });
    }

    private function ensureRole(string $name): int
    {
        $roleId = $this->roleId($name);

        if ($roleId) {
            return $roleId;
        }

        return (int) DB::table('roles')->insertGetId($this->createPayload(['name' => $name]));
    }

    private function roleId(string $name): ?int
    {
        $roleId = DB::table('roles')->where('name', $name)->value('id');

        return $roleId ? (int) $roleId : null;
    }

    private function moveUsersToRole(int $fromRoleId, int $toRoleId): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role_id')) {
            return;
        }

        DB::table('users')
            ->where('role_id', $fromRoleId)
            ->update($this->updatePayload(['role_id' => $toRoleId], 'users'));
    }

    private function createPayload(array $payload, string $table = 'roles'): array
    {
        $now = now();

        if (Schema::hasColumn($table, 'created_at')) {
            $payload['created_at'] = $now;
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $payload['updated_at'] = $now;
        }

        return $payload;
    }

    private function updatePayload(array $payload, string $table = 'roles'): array
    {
        if (Schema::hasColumn($table, 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return $payload;
    }
};
