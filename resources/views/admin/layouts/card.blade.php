{{--
    Komponen kartu seksi (section card) yang dapat digunakan ulang.

    Cara pakai:
        @component('admin.layouts.card', ['title' => 'Daftar Pengguna'])
            ... toolbar + tabel ...
        @endcomponent

    Param opsional:
        $title    : judul kartu (string)
        $titleId  : id untuk elemen judul (mis. untuk diubah via JS)
--}}
<div class="section-card">
    <div class="archive-body">
        @isset($title)
            <span class="section-card__title" @isset($titleId) id="{{ $titleId }}" @endisset>{{ $title }}</span>
        @endisset

        {{ $slot }}
    </div>
</div>
