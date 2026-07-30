<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Satu permintaan bundel ZIP arsip yang dikerjakan di latar.
 *
 * Baris dibuat saat pengguna menekan "siapkan di latar", diperbarui oleh
 * App\Jobs\BuildArchiveBundle selagi berkas dirakit, lalu dibersihkan
 * perintah archive:prune-bundles setelah kedaluwarsa.
 */
class ArchiveBundle extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    /**
     * Disk penyimpanan berkas bundel — 'local' (storage/app/private) supaya
     * ZIP tidak bisa diambil langsung lewat URL publik.
     */
    public const DISK = 'local';

    public const DIRECTORY = 'archive-bundles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'refs' => 'array',
            'total_reports' => 'integer',
            'processed_reports' => 'integer',
            'skipped_reports' => 'integer',
            'file_size' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function absolutePath(): ?string
    {
        return $this->file_path ? Storage::disk(self::DISK)->path($this->file_path) : null;
    }

    /**
     * Persen progres untuk bilah kemajuan di UI.
     */
    public function progressPercent(): int
    {
        if ($this->isReady()) {
            return 100;
        }

        if ($this->total_reports < 1) {
            return 0;
        }

        return (int) min(99, floor(($this->processed_reports / $this->total_reports) * 100));
    }

    /**
     * Hapus berkas ZIP-nya (kalau ada) lalu baris ini.
     */
    public function purge(): void
    {
        if ($this->file_path && Storage::disk(self::DISK)->exists($this->file_path)) {
            Storage::disk(self::DISK)->delete($this->file_path);
        }

        $this->delete();
    }
}
