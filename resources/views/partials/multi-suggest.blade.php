{{--
    Autocomplete kustom multi-nilai (dipisah koma) — perilaku & tampilan sama
    dengan field petugas pada form laporan operasi.

    Cara pakai pada input:
        data-suggest="<id datalist sumber>"   wajib
        data-multi="true"                     boleh diisi lebih dari satu nama
        autocomplete="off"                    matikan saran bawaan browser
    dan JANGAN pasang atribut list=, supaya dropdown bawaan browser tidak
    menimpa dropdown ini.

    Saran difilter dari potongan teks setelah koma terakhir, jadi daftar tetap
    muncul untuk nama ke-2, ke-3, dst. Nama yang sudah dipilih di input yang
    sama otomatis disembunyikan dari daftar berikutnya. Sumber opsi dibaca live
    dari elemen <datalist>, jadi ikut ter-update bila isinya berubah.
--}}
@push('styles')
<style>
    .kss-suggest-dropdown {
        position: fixed;
        z-index: 9999;
        display: none;
        max-height: 220px;
        overflow-y: auto;
        padding: 6px;
        border: 1px solid var(--blue-main-25);
        border-radius: 10px;
        background-color: var(--white);
        box-shadow: 0 16px 34px rgba(15, 23, 42, .16);
    }
    .kss-suggest-dropdown.show { display: block; }
    .kss-suggest-option {
        display: block;
        width: 100%;
        border: none;
        border-radius: 7px;
        background: transparent;
        padding: 8px 10px;
        text-align: left;
        font-size: 12px;
        font-weight: 500;
        color: var(--dark-main);
        cursor: pointer;
        transition: .12s ease-out;
    }
    .kss-suggest-option:hover,
    .kss-suggest-option.active { background-color: var(--blue-main-10); color: var(--blue-main); }

    body.dark-mode .kss-suggest-dropdown {
        background-color: #17212F;
        border-color: rgba(255, 255, 255, .12);
        box-shadow: 0 16px 34px rgba(0, 0, 0, .45);
    }
    body.dark-mode .kss-suggest-option { color: #E2E8F0; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const DROPDOWN_ID = 'kss-suggest-dropdown';
    let activeInput = null;
    let activeIndex = -1;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        })[char]);
    }

    function optionsFrom(listId) {
        const datalist = document.getElementById(listId);
        if (!datalist) return [];
        return Array.from(datalist.querySelectorAll('option')).map((option) => option.value).filter(Boolean);
    }

    // Batas kata yang sedang diketik: dari koma terakhir sebelum kursor sampai
    // koma berikutnya. Inilah yang membuat saran tetap muncul setelah koma.
    function tokenBounds(input) {
        const value = input.value;
        const caret = input.selectionStart ?? value.length;
        const start = value.lastIndexOf(',', caret - 1) + 1;
        let end = value.indexOf(',', caret);
        if (end === -1) end = value.length;
        return { start, end };
    }

    function currentToken(input) {
        if (input.dataset.multi !== 'true') return input.value.trim();
        const { start, end } = tokenBounds(input);
        return input.value.slice(start, end).trim();
    }

    function ensureDropdown() {
        let dropdown = document.getElementById(DROPDOWN_ID);
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.id = DROPDOWN_ID;
            dropdown.className = 'kss-suggest-dropdown';
            document.body.appendChild(dropdown);
        }
        return dropdown;
    }

    function positionDropdown(input, dropdown) {
        const rect = input.getBoundingClientRect();
        dropdown.style.left = `${rect.left}px`;
        dropdown.style.top = `${rect.bottom + 4}px`;
        dropdown.style.width = `${Math.max(rect.width, 140)}px`;
    }

    function closeDropdown() {
        const dropdown = document.getElementById(DROPDOWN_ID);
        if (dropdown) dropdown.classList.remove('show');
        activeInput = null;
        activeIndex = -1;
    }

    function openFor(input) {
        const listId = input.dataset.suggest;
        if (!listId) return;

        const isMulti = input.dataset.multi === 'true';
        const options = optionsFrom(listId);
        const query = currentToken(input).toLowerCase();

        // Nama sudah lengkap & persis salah satu opsi: tak perlu menawarkan lagi.
        if (isMulti && query !== '' && options.some((option) => option.toLowerCase() === query)) {
            closeDropdown();
            return;
        }

        const chosen = isMulti
            ? input.value.split(',').map((part) => part.trim().toLowerCase()).filter(Boolean)
            : [];

        const matches = options.filter((option) => {
            const low = option.toLowerCase();
            if (isMulti && low !== query && chosen.includes(low)) return false;
            return query === '' ? true : low.includes(query);
        }).slice(0, 12);

        const dropdown = ensureDropdown();
        if (matches.length === 0) {
            dropdown.classList.remove('show');
            activeInput = null;
            activeIndex = -1;
            return;
        }

        dropdown.innerHTML = matches
            .map((match, index) => `<button type="button" class="kss-suggest-option${index === 0 ? ' active' : ''}" data-value="${escapeHtml(match)}">${escapeHtml(match)}</button>`)
            .join('');
        activeInput = input;
        activeIndex = 0;
        positionDropdown(input, dropdown);
        dropdown.classList.add('show');
    }

    function applyValue(input, value) {
        if (input.dataset.multi !== 'true') {
            input.value = value;
        } else {
            const { start, end } = tokenBounds(input);
            const before = input.value.slice(0, start).replace(/\s*$/, '');
            const after = input.value.slice(end);
            const connector = before === '' ? '' : (before.endsWith(',') ? ' ' : ', ');
            input.value = `${before}${connector}${value}${after}`.replace(/,\s*$/, '');
            const caret = `${before}${connector}${value}`.length;
            try { input.setSelectionRange(caret, caret); } catch (_) {}
        }
        // Tandai agar handler focusin tidak langsung membuka dropdown lagi.
        input.dataset.suggestApplying = 'true';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        delete input.dataset.suggestApplying;
        input.focus();
        closeDropdown();
    }

    function highlight(delta) {
        const dropdown = document.getElementById(DROPDOWN_ID);
        if (!dropdown || !dropdown.classList.contains('show')) return;
        const options = Array.from(dropdown.querySelectorAll('.kss-suggest-option'));
        if (options.length === 0) return;
        activeIndex = (activeIndex + delta + options.length) % options.length;
        options.forEach((option, index) => option.classList.toggle('active', index === activeIndex));
        options[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    document.addEventListener('focusin', function (event) {
        if (!event.target.matches?.('input[data-suggest]')) return;
        if (event.target.dataset.suggestApplying === 'true') return;
        openFor(event.target);
    });

    document.addEventListener('input', function (event) {
        if (event.target.matches?.('input[data-suggest]')) openFor(event.target);
    });

    document.addEventListener('mousedown', function (event) {
        const option = event.target.closest('.kss-suggest-option');
        if (option && activeInput) {
            event.preventDefault(); // pertahankan fokus input
            applyValue(activeInput, option.dataset.value || option.textContent.trim());
            return;
        }
        if (!event.target.closest('input[data-suggest]') && !event.target.closest('#' + DROPDOWN_ID)) {
            closeDropdown();
        }
    });

    // Capture agar Enter memilih saran lebih dulu, bukan mengirim/berpindah field.
    document.addEventListener('keydown', function (event) {
        const dropdown = document.getElementById(DROPDOWN_ID);
        if (!activeInput || !dropdown || !dropdown.classList.contains('show')) return;
        if (event.target !== activeInput) return;

        if (event.key === 'ArrowDown') { event.preventDefault(); highlight(1); }
        else if (event.key === 'ArrowUp') { event.preventDefault(); highlight(-1); }
        else if (event.key === 'Enter') {
            const active = dropdown.querySelector('.kss-suggest-option.active') || dropdown.querySelector('.kss-suggest-option');
            if (active) { event.preventDefault(); event.stopPropagation(); applyValue(activeInput, active.dataset.value); }
        } else if (event.key === 'Escape') {
            closeDropdown();
        }
    }, true);

    document.addEventListener('focusout', function (event) {
        if (!event.target.matches?.('input[data-suggest]')) return;
        setTimeout(() => {
            if (activeInput === event.target) closeDropdown();
        }, 120);
    });

    window.addEventListener('scroll', () => { if (activeInput) closeDropdown(); }, true);
    window.addEventListener('resize', () => { if (activeInput) closeDropdown(); });
})();
</script>
@endpush
