{{--
    Badge sisa masa simpan draft + tombol Perpanjang.
    Draft yang tidak dilanjutkan melewati masa simpan akan dihapus otomatis,
    jadi petugas perlu tahu sisa umurnya dan bisa memperpanjang tanpa membuka form.

    Dipakai di tab Draft ketiga modul lewat:
        @include('partials.draft-expiry', [
            'report'    => $report,
            'ttlDays'   => \App\Models\DailyReport::DRAFT_TTL_DAYS,
            'extendUrl' => route('report-ops.extend-draft', $report),
        ])
--}}
@php
    $draftLastTouchedAt = $report->updated_at ?? $report->created_at;
    $draftExpiresAt = $draftLastTouchedAt ? $draftLastTouchedAt->copy()->addDays($ttlDays) : null;
    $draftRemainingMinutes = $draftExpiresAt ? now()->diffInMinutes($draftExpiresAt, false) : null;
    $draftExpiryUrgent = $draftRemainingMinutes !== null && $draftRemainingMinutes <= 60 * 24;

    if ($draftRemainingMinutes === null) {
        $draftExpiryLabel = null;
    } elseif ($draftRemainingMinutes <= 0) {
        $draftExpiryLabel = 'Draft kedaluwarsa, akan dihapus otomatis';
    } elseif ($draftRemainingMinutes < 60) {
        $draftExpiryLabel = 'Terhapus otomatis dalam '.max(1, (int) floor($draftRemainingMinutes)).' menit';
    } elseif ($draftRemainingMinutes < 60 * 24) {
        $draftExpiryLabel = 'Terhapus otomatis dalam '.(int) floor($draftRemainingMinutes / 60).' jam';
    } else {
        $draftExpiryLabel = 'Terhapus otomatis dalam '.(int) ceil($draftRemainingMinutes / (60 * 24)).' hari';
    }
@endphp

@once
    <style>
        .draft-expiry {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 4px;
        }

        .draft-expiry-text {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
            color: #92400E;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.35);
        }

        .draft-expiry.is-urgent .draft-expiry-text {
            color: var(--red-main, #D20000);
            background: rgba(210, 0, 0, 0.10);
            border-color: rgba(210, 0, 0, 0.35);
        }

        .draft-expiry-text i { position: relative; top: 1px; }

        .draft-expiry-extend { display: inline-flex; margin: 0; }

        .draft-expiry-extend button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid rgba(37, 99, 235, 0.35);
            background: rgba(37, 99, 235, 0.08);
            color: #1D4ED8;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .draft-expiry-extend button:hover { background: rgba(37, 99, 235, 0.16); }

        .draft-expiry-extend button i { position: relative; top: 1px; }
    </style>
@endonce

@if ($draftExpiryLabel)
    <div class="draft-expiry {{ $draftExpiryUrgent ? 'is-urgent' : '' }}">
        <span class="draft-expiry-text">
            <i class="fi fi-rr-hourglass-end"></i>
            {{ $draftExpiryLabel }}
        </span>
        <form method="POST" action="{{ $extendUrl }}" class="draft-expiry-extend">
            @csrf
            <button type="submit" title="Perpanjang masa simpan draft {{ $ttlDays }} hari sejak sekarang">
                <i class="fi fi-rr-rotate-right"></i>
                Perpanjang
            </button>
        </form>
    </div>
@endif
