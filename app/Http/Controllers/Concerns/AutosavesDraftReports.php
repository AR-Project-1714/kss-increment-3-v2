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
 *
 * Pencegahan duplikat yang sebenarnya ada di reserveDraftReport(): begitu form
 * baru dibuka, satu baris draft langsung direservasi dan form menembak endpoint
 * update sejak keystroke pertama. Ini penting karena penyimpanan lewat
 * navigator.sendBeacon (saat tab disembunyikan / ditutup) tidak bisa membaca
 * response, jadi ia tak akan pernah tahu `update_url` — dulu tiap beacon dari
 * form baru menciptakan draft baru.
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

    /**
     * Siapkan satu baris draft untuk form baru.
     *
     * Draft kosong milik pengguna dipakai ulang (membuka form berkali-kali tidak
     * menumpuk baris), dan sisa draft kosong lain dibersihkan supaya tab Draft
     * tidak pernah menampilkan lebih dari satu laporan yang belum disentuh.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $attributes kolom pemilik/status baris baru
     */
    protected function reserveDraftReport(string $modelClass, array $attributes): Model
    {
        $blankDrafts = $modelClass::query()
            ->where($attributes)
            ->blankDraft()
            ->latest('id')
            ->get();

        $reserved = $blankDrafts->shift();

        $blankDrafts->each(fn (Model $surplus) => $surplus->delete());

        return $reserved ?? $modelClass::create($attributes);
    }

    /**
     * Buang draft yang ternyata dibuka lalu ditinggal tanpa diisi.
     *
     * Dipanggil dari browser lewat sendBeacon, jadi permintaannya tidak bisa
     * dipercaya: pengecekan isBlankDraft() di sini yang memastikan laporan
     * berisi tidak akan pernah ikut terhapus.
     */
    protected function discardBlankDraftReport(Model $report): JsonResponse
    {
        $discarded = false;

        if ($report->isBlankDraft()) {
            $report->delete();
            $discarded = true;
        }

        return response()->json(['ok' => true, 'discarded' => $discarded]);
    }
}
