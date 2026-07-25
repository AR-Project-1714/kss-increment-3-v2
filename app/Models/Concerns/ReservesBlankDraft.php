<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Draft laporan direservasi (baris dibuat) begitu form baru dibuka, supaya
 * laporan punya ID sejak awal dan setiap penyimpanan berikutnya menimpa baris
 * yang sama — bukan membuat duplikat.
 *
 * Konsekuensinya ada baris "kosong": sudah direservasi tapi belum disentuh
 * petugas. Trait ini mendefinisikan apa arti kosong, agar baris seperti itu
 * bisa dipakai ulang saat form dibuka lagi dan dibuang saat form ditinggal.
 *
 * Model pemakai wajib mendefinisikan dua konstanta:
 *   BLANK_DRAFT_RELATIONS — relasi detail; ada isinya berarti sudah dikerjakan.
 *   BLANK_DRAFT_COLUMNS   — kolom induk yang terisi berarti sudah dikerjakan.
 */
trait ReservesBlankDraft
{
    /**
     * Batasi query ke draft yang belum disentuh sama sekali.
     */
    public function scopeBlankDraft(Builder $query): Builder
    {
        foreach (static::BLANK_DRAFT_RELATIONS as $relation) {
            $query->whereDoesntHave($relation);
        }

        foreach (static::BLANK_DRAFT_COLUMNS as $column) {
            $query->whereNull($column);
        }

        return $query;
    }

    /**
     * Apakah baris ini masih hasil reservasi yang belum diisi apa pun?
     *
     * Dipakai sebagai penjaga sebelum menghapus: permintaan "buang draft
     * kosong" dari browser tidak pernah boleh menghapus pekerjaan nyata.
     */
    public function isBlankDraft(): bool
    {
        foreach (static::BLANK_DRAFT_COLUMNS as $column) {
            if (filled($this->getAttribute($column))) {
                return false;
            }
        }

        foreach (static::BLANK_DRAFT_RELATIONS as $relation) {
            if ($this->{$relation}()->exists()) {
                return false;
            }
        }

        return true;
    }
}
