<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dukungan auto-save draft: form laporan (ops/pemeliharaan/safety) memanggil
 * endpoint store/update yang sama dengan flag `autosave=1`. Permintaan autosave
 * SELALU dipaksa berstatus draft (tidak pernah submit), dan dijawab JSON berisi
 * `update_url` agar autosave berikutnya memperbarui draft yang sama
 * (tidak membuat duplikat).
 */
trait AutosavesDraftReports
{
    protected function isAutosaveRequest(Request $request): bool
    {
        return $request->boolean('autosave');
    }

    protected function autosaveResponse(Model $report, string $updateRouteName): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'id' => $report->getKey(),
            'update_url' => route($updateRouteName, $report),
        ]);
    }
}
