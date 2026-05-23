@once
<style>
    .kss-date-native {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    .kss-date-trigger {
        width: 100%;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid var(--smooth-border, #e2e8f0);
        border-radius: 10px;
        background: var(--white, #ffffff);
        color: var(--dark-main, var(--black, #0f172a));
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.2;
        text-align: left;
        cursor: pointer;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .kss-date-trigger.custom-input,
    .input-wrapper .kss-date-trigger.custom-input {
        min-height: 42px;
        padding: 10px 15px;
        border-color: var(--black-25, var(--smooth-border, #e2e8f0));
        border-radius: 8px;
        font-size: 13px;
        font-weight: 400;
        line-height: normal;
        box-sizing: border-box;
    }

    .kss-date-trigger.filter-input {
        min-height: 42px;
        padding: 0 12px;
    }

    .kss-date-trigger.kss-modal__input {
        padding: 0 12px;
    }

    .kss-date-trigger:hover {
        border-color: var(--blue-main-25, rgba(37, 99, 235, 0.25));
        background-color: var(--blue-main-3, rgba(37, 99, 235, 0.03));
    }

    .kss-date-trigger:focus-visible,
    .kss-date-trigger.is-open {
        outline: none;
        border-color: var(--blue-main, #2563eb);
        box-shadow: 0 0 0 3px var(--blue-main-10, rgba(37, 99, 235, 0.10));
        background-color: var(--white, #ffffff);
    }

    .form-group .input-wrapper .kss-date-trigger.custom-input {
        min-height: 0;
        padding: 8px 15px;
        border-color: var(--divider, #cbd5e1);
        border-radius: 10px;
        font-size: 12px;
    }

    .form-group .input-wrapper .kss-date-trigger.custom-input:focus-visible,
    .form-group .input-wrapper .kss-date-trigger.custom-input.is-open {
        outline: 3px solid var(--blue-main-10, rgba(37, 99, 235, 0.10));
        box-shadow: 0 0 1px 0 var(--blue-main, #2563eb);
        background-color: var(--blue-input-focus, var(--white, #ffffff));
    }

    .kss-date-trigger__main {
        min-width: 0;
        display: inline-flex;
        align-items: center;
        gap: 9px;
    }

    .kss-date-trigger__main i {
        position: relative;
        top: 1px;
        flex: 0 0 auto;
        color: var(--dark-secondary, var(--black-secondary, #334155));
    }

    .kss-date-trigger__text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .kss-date-trigger.is-placeholder .kss-date-trigger__text {
        color: var(--muted, #94a3b8);
        font-weight: 400;
    }

    .kss-date-popover {
        position: absolute;
        z-index: 12000;
        width: min(292px, calc(100vw - 24px));
        padding: 10px;
        border: 1px solid var(--smooth-border, #e2e8f0);
        border-radius: 10px;
        background: var(--white, #ffffff);
        color: var(--dark-main, var(--black, #0f172a));
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.13), 0 1px 2px rgba(15, 23, 42, 0.08);
        font-family: inherit;
    }

    .kss-date-popover--datetime {
        width: min(350px, calc(100vw - 24px));
    }

    .kss-date-popover--time {
        width: min(204px, calc(100vw - 24px));
    }

    .kss-date-picker {
        display: flex;
        align-items: stretch;
        gap: 8px;
    }

    .kss-date-calendar {
        flex: 1 1 auto;
        min-width: 0;
    }

    .kss-date-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .kss-date-month {
        font-size: 13px;
        font-weight: 600;
        color: var(--dark-main, var(--black, #0f172a));
    }

    .kss-date-nav {
        width: 28px;
        height: 28px;
        border: 1px solid var(--smooth-border, #e2e8f0);
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--white, #ffffff);
        color: var(--dark-main, var(--black, #0f172a));
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }

    .kss-date-nav:hover {
        border-color: var(--blue-main-25, rgba(37, 99, 235, 0.25));
        background: var(--blue-main-5, rgba(37, 99, 235, 0.05));
        color: var(--blue-main, #2563eb);
    }

    .kss-date-weekdays,
    .kss-date-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 2px;
    }

    .kss-date-weekdays {
        margin-bottom: 3px;
    }

    .kss-date-weekday {
        height: 21px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--muted, #94a3b8);
        font-size: 10px;
        font-weight: 600;
    }

    .kss-date-day {
        height: 28px;
        border: 0;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        color: var(--dark-main, var(--black, #0f172a));
        font-family: inherit;
        font-size: 11px;
        font-weight: 500;
        transition: background-color 0.16s ease, color 0.16s ease, box-shadow 0.16s ease;
    }

    .kss-date-day:hover {
        background: var(--blue-main-5, rgba(37, 99, 235, 0.05));
        color: var(--blue-main, #2563eb);
    }

    .kss-date-day.is-muted {
        color: var(--muted, #94a3b8);
        opacity: 0.68;
    }

    .kss-date-day.is-today {
        box-shadow: inset 0 0 0 1px var(--blue-main-25, rgba(37, 99, 235, 0.25));
    }

    .kss-date-day.is-selected {
        background: var(--blue-main, #2563eb);
        color: #ffffff;
        box-shadow: 0 8px 16px var(--blue-main-25, rgba(37, 99, 235, 0.25));
    }

    .kss-date-time {
        width: 70px;
        height: 238px;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 3px;
        padding-left: 8px;
        border-left: 1px solid var(--smooth-border, #e2e8f0);
        min-height: 0;
    }

    .kss-date-popover--time .kss-date-time {
        width: 100%;
        height: 184px;
        padding-left: 0;
        border-left: 0;
    }

    .kss-date-time-column {
        height: 100%;
        max-height: 100%;
        min-height: 0;
        overflow-y: auto;
        padding-right: 0;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .kss-date-time-column::-webkit-scrollbar {
        width: 0;
        height: 0;
        display: none;
    }

    .kss-date-time-option {
        width: 100%;
        height: 25px;
        border: 0;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        color: var(--dark-main, var(--black, #0f172a));
        font-family: inherit;
        font-size: 11px;
        font-weight: 500;
    }

    .kss-date-time-option:hover {
        background: var(--blue-main-5, rgba(37, 99, 235, 0.05));
        color: var(--blue-main, #2563eb);
    }

    .kss-date-time-option.is-selected {
        background: var(--blue-main, #2563eb);
        color: #ffffff;
    }

    .kss-date-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid var(--smooth-border, #e2e8f0);
    }

    .kss-date-action {
        min-height: 31px;
        border: 1px solid var(--smooth-border, #e2e8f0);
        border-radius: 8px;
        padding: 0 10px;
        background: var(--white, #ffffff);
        color: var(--dark-main, var(--black, #0f172a));
        font-family: inherit;
        font-size: 11px;
        font-weight: 500;
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }

    .kss-date-action:hover {
        border-color: var(--blue-main-25, rgba(37, 99, 235, 0.25));
        background: var(--blue-main-5, rgba(37, 99, 235, 0.05));
        color: var(--blue-main, #2563eb);
    }

    .kss-date-action--clear {
        background: var(--main-bg, #f8fafc);
    }

    body.dark-mode .kss-date-popover,
    body.dark-mode .kss-date-nav,
    body.dark-mode .kss-date-action,
    body.dark-mode .kss-date-trigger {
        background: var(--white, #1e293b);
        border-color: var(--smooth-border, #334155);
    }

    @media (max-width: 520px) {
        .kss-date-picker {
            flex-direction: column;
            min-height: 0;
        }

        .kss-date-time {
            width: 100%;
            height: 132px;
            max-height: 132px;
            padding-left: 0;
            padding-top: 10px;
            border-left: 0;
            border-top: 1px solid var(--smooth-border, #e2e8f0);
        }

        .kss-date-time-column {
            height: 120px;
        }
    }
</style>

<script>
    (function () {
        if (window.KssDateTimePicker) return;

        const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const weekdayNames = ['Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb', 'Mg'];
        const minuteOptions = Array.from({ length: 12 }, (_, index) => index * 5);
        let activePicker = null;
        let activePopover = null;

        const pad = value => String(value).padStart(2, '0');
        const sameDate = (a, b) => a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
        const dateKey = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

        function parseDateValue(value, mode) {
            const text = String(value || '').trim();
            if (!text) return null;

            let match = text.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2}))?/);
            if (match) {
                return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), Number(match[4] || 0), Number(match[5] || 0), 0, 0);
            }

            if (mode === 'time') {
                match = text.match(/^(\d{1,2}):(\d{2})$/);
                if (match) {
                    const now = new Date();
                    return new Date(now.getFullYear(), now.getMonth(), now.getDate(), Number(match[1]), Number(match[2]), 0, 0);
                }
            }

            return null;
        }

        function formatValue(date, mode) {
            if (!date) return '';
            if (mode === 'time') return `${pad(date.getHours())}:${pad(date.getMinutes())}`;
            if (mode === 'datetime') return `${dateKey(date)}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            return dateKey(date);
        }

        function formatDisplay(date, mode) {
            if (!date) return '';
            const day = date.getDate();
            const month = monthNames[date.getMonth()];
            const year = date.getFullYear();
            const time = `${pad(date.getHours())}:${pad(date.getMinutes())}`;

            if (mode === 'time') return time;
            if (mode === 'datetime') return `${day} ${month} ${year} ${time}`;
            return `${day} ${month} ${year}`;
        }

        function normalizedDate(date) {
            return new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0);
        }

        function monthStartForGrid(viewDate) {
            const first = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
            const offset = (first.getDay() + 6) % 7;
            const start = new Date(first);
            start.setDate(first.getDate() - offset);
            return start;
        }

        function iconForMode(mode) {
            if (mode === 'time') return 'fi fi-rr-clock';
            if (mode === 'datetime') return 'fi fi-rr-calendar-clock';
            return 'fi fi-rr-calendar';
        }

        function showToast(type, title, message) {
            const fn = window.showAdminToast || window.showManagerToast || window.showReportToast;
            if (typeof fn === 'function') fn(type, title, message, 3600);
        }

        function setPickerValue(picker, date, closeAfter = false) {
            picker.selected = date ? new Date(date) : null;
            picker.input.value = picker.selected ? formatValue(picker.selected, picker.mode) : '';
            syncTrigger(picker);
            picker.input.dispatchEvent(new Event('input', { bubbles: true }));
            picker.input.dispatchEvent(new Event('change', { bubbles: true }));
            if (closeAfter) closePicker();
            else renderPicker(picker);
        }

        function dispatchAdvance(picker) {
            if (!picker?.trigger) return;

            picker.trigger.dispatchEvent(new CustomEvent('kss-picker:advance', {
                bubbles: true,
                detail: {
                    input: picker.input,
                    trigger: picker.trigger,
                    mode: picker.mode,
                },
            }));
        }

        function syncTrigger(picker) {
            const value = parseDateValue(picker.input.value, picker.mode);
            picker.selected = value;

            if (value && picker.mode !== 'time') {
                picker.viewDate = new Date(value.getFullYear(), value.getMonth(), 1);
            }

            if (value) {
                picker.hour = value.getHours();
                picker.minute = value.getMinutes();
            }

            const text = picker.trigger.querySelector('.kss-date-trigger__text');
            const display = value ? formatDisplay(value, picker.mode) : '';
            text.textContent = display || picker.placeholder;
            picker.trigger.classList.toggle('is-placeholder', !display);
            picker.trigger.disabled = picker.input.disabled;
        }

        function createTrigger(input) {
            const mode = input.dataset.kssPicker || 'date';
            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = `kss-date-trigger ${input.dataset.triggerClass || ''}`.trim();
            trigger.setAttribute('aria-haspopup', 'dialog');
            trigger.innerHTML = `
                <span class="kss-date-trigger__main">
                    <i class="${iconForMode(mode)}"></i>
                    <span class="kss-date-trigger__text"></span>
                </span>
            `;
            input.insertAdjacentElement('afterend', trigger);
            return trigger;
        }

        function cleanupClonedTrigger(input) {
            if (input._kssPicker) return;

            input.dataset.kssPickerReady = '';
            let next = input.nextElementSibling;
            while (next && next.classList.contains('kss-date-trigger')) {
                const remove = next;
                next = next.nextElementSibling;
                remove.remove();
            }
        }

        function initInput(input) {
            cleanupClonedTrigger(input);
            if (input._kssPicker) {
                syncTrigger(input._kssPicker);
                return;
            }

            input.type = 'hidden';
            input.classList.add('kss-date-native');

            const mode = input.dataset.kssPicker || 'date';
            const selected = parseDateValue(input.value, mode);
            const now = new Date();
            const picker = {
                input,
                mode,
                selected,
                viewDate: selected ? new Date(selected.getFullYear(), selected.getMonth(), 1) : new Date(now.getFullYear(), now.getMonth(), 1),
                hour: selected ? selected.getHours() : now.getHours(),
                minute: selected ? selected.getMinutes() : 0,
                placeholder: input.dataset.placeholder || (mode === 'datetime' ? 'Pilih tanggal & waktu' : mode === 'time' ? 'Pilih jam' : 'Pilih tanggal'),
                trigger: createTrigger(input),
            };

            input._kssPicker = picker;
            picker.trigger._kssPicker = picker;
            input.dataset.kssPickerReady = 'true';
            syncTrigger(picker);

            picker.trigger.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                if (input.disabled) return;
                activePicker === picker ? closePicker() : openPicker(picker);
            });

            picker.trigger.addEventListener('keydown', event => {
                if (!['Enter', ' '].includes(event.key)) return;
                event.preventDefault();
                event.stopPropagation();

                if (input.disabled) return;

                if (event.key === 'Enter' && activePicker === picker && picker.input.value) {
                    closePicker();
                    dispatchAdvance(picker);
                    return;
                }

                activePicker === picker ? closePicker() : openPicker(picker);
            });

            input.addEventListener('change', () => syncTrigger(picker));
        }

        function currentWorkingDate(picker) {
            const base = picker.selected || new Date(picker.viewDate);
            return new Date(base.getFullYear(), base.getMonth(), base.getDate(), picker.hour, picker.minute, 0, 0);
        }

        function renderCalendar(picker) {
            const start = monthStartForGrid(picker.viewDate);
            const today = normalizedDate(new Date());
            const selected = picker.selected ? normalizedDate(picker.selected) : null;
            const days = [];

            for (let index = 0; index < 42; index += 1) {
                const date = new Date(start);
                date.setDate(start.getDate() + index);
                const muted = date.getMonth() !== picker.viewDate.getMonth();
                const isToday = sameDate(date, today);
                const isSelected = selected && sameDate(date, selected);
                days.push(`
                    <button type="button"
                            class="kss-date-day${muted ? ' is-muted' : ''}${isToday ? ' is-today' : ''}${isSelected ? ' is-selected' : ''}"
                            data-date="${dateKey(date)}">
                        ${date.getDate()}
                    </button>
                `);
            }

            return `
                <div class="kss-date-calendar">
                    <div class="kss-date-header">
                        <button type="button" class="kss-date-nav" data-action="prev-month" aria-label="Bulan sebelumnya">
                            <i class="fi fi-rr-angle-small-left"></i>
                        </button>
                        <div class="kss-date-month">${monthNames[picker.viewDate.getMonth()]} ${picker.viewDate.getFullYear()}</div>
                        <button type="button" class="kss-date-nav" data-action="next-month" aria-label="Bulan berikutnya">
                            <i class="fi fi-rr-angle-small-right"></i>
                        </button>
                    </div>
                    <div class="kss-date-weekdays">
                        ${weekdayNames.map(day => `<span class="kss-date-weekday">${day}</span>`).join('')}
                    </div>
                    <div class="kss-date-grid">${days.join('')}</div>
                </div>
            `;
        }

        function renderTimeColumn(values, selected, key) {
            return `
                <div class="kss-date-time-column" data-time-column="${key}">
                    ${values.map(value => `
                        <button type="button"
                                class="kss-date-time-option${value === selected ? ' is-selected' : ''}"
                                data-${key}="${value}">
                            ${pad(value)}
                        </button>
                    `).join('')}
                </div>
            `;
        }

        function renderTimePicker(picker) {
            const minutes = minuteOptions.includes(picker.minute)
                ? minuteOptions
                : [...minuteOptions, picker.minute].sort((a, b) => a - b);

            return `
                <div class="kss-date-time">
                    ${renderTimeColumn(Array.from({ length: 24 }, (_, index) => index), picker.hour, 'hour')}
                    ${renderTimeColumn(minutes, picker.minute, 'minute')}
                </div>
            `;
        }

        function renderPicker(picker) {
            if (!activePopover || activePicker !== picker) return;

            const hasCalendar = picker.mode !== 'time';
            const hasTime = picker.mode === 'datetime' || picker.mode === 'time';
            activePopover.className = `kss-date-popover kss-date-popover--${picker.mode}`;
            activePopover.innerHTML = `
                <div class="kss-date-picker">
                    ${hasCalendar ? renderCalendar(picker) : ''}
                    ${hasTime ? renderTimePicker(picker) : ''}
                </div>
                <div class="kss-date-footer">
                    <button type="button" class="kss-date-action" data-action="today">${picker.mode === 'time' ? 'Sekarang' : 'Hari ini'}</button>
                    <button type="button" class="kss-date-action kss-date-action--clear" data-action="clear">Hapus</button>
                </div>
            `;

            requestAnimationFrame(() => {
                activePopover.querySelector('.kss-date-time-option.is-selected')?.scrollIntoView({ block: 'center' });
                positionPicker(picker);
            });
        }

        function positionPicker(picker) {
            if (!activePopover) return;

            const rect = picker.trigger.getBoundingClientRect();
            const gap = 8;
            const width = activePopover.offsetWidth;
            const height = activePopover.offsetHeight;
            let left = rect.left + window.scrollX;
            let top = rect.bottom + gap + window.scrollY;

            if (left + width > window.scrollX + window.innerWidth - 12) {
                left = window.scrollX + window.innerWidth - width - 12;
            }

            if (left < window.scrollX + 12) {
                left = window.scrollX + 12;
            }

            if (rect.bottom + gap + height > window.innerHeight && rect.top - gap - height > 0) {
                top = rect.top - gap - height + window.scrollY;
            }

            activePopover.style.left = `${left}px`;
            activePopover.style.top = `${top}px`;
        }

        function openPicker(picker) {
            closePicker();
            activePicker = picker;
            activePopover = document.createElement('div');
            activePopover.setAttribute('role', 'dialog');
            activePopover.setAttribute('aria-label', picker.mode === 'datetime' ? 'Pilih tanggal dan waktu' : picker.mode === 'time' ? 'Pilih jam' : 'Pilih tanggal');
            document.body.appendChild(activePopover);
            picker.trigger.classList.add('is-open');
            renderPicker(picker);
        }

        function closePicker() {
            if (activePicker) activePicker.trigger.classList.remove('is-open');
            if (activePopover) activePopover.remove();
            activePicker = null;
            activePopover = null;
        }

        function handlePopoverClick(event) {
            if (!activePicker || !activePopover) return;

            const target = event.target.closest('button');
            if (!target || !activePopover.contains(target)) return;

            const action = target.dataset.action;
            if (action === 'prev-month' || action === 'next-month') {
                const delta = action === 'prev-month' ? -1 : 1;
                activePicker.viewDate = new Date(activePicker.viewDate.getFullYear(), activePicker.viewDate.getMonth() + delta, 1);
                renderPicker(activePicker);
                return;
            }

            if (action === 'today') {
                const now = new Date();
                const value = activePicker.mode === 'date'
                    ? new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0, 0)
                    : new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours(), now.getMinutes(), 0, 0);
                setPickerValue(activePicker, value, true);
                return;
            }

            if (action === 'clear') {
                setPickerValue(activePicker, null, true);
                return;
            }

            if (target.dataset.date) {
                const [year, month, day] = target.dataset.date.split('-').map(Number);
                const value = new Date(year, month - 1, day, activePicker.hour, activePicker.minute, 0, 0);
                activePicker.viewDate = new Date(year, month - 1, 1);
                setPickerValue(activePicker, value, activePicker.mode === 'date');
                return;
            }

            if (target.dataset.hour !== undefined || target.dataset.minute !== undefined) {
                if (target.dataset.hour !== undefined) activePicker.hour = Number(target.dataset.hour);
                if (target.dataset.minute !== undefined) activePicker.minute = Number(target.dataset.minute);

                if (activePicker.mode === 'time') {
                    const now = new Date();
                    setPickerValue(activePicker, new Date(now.getFullYear(), now.getMonth(), now.getDate(), activePicker.hour, activePicker.minute, 0, 0));
                } else {
                    setPickerValue(activePicker, currentWorkingDate(activePicker));
                }
            }
        }

        function initAll(root = document) {
            root.querySelectorAll('input[data-kss-picker]').forEach(initInput);
        }

        document.addEventListener('click', handlePopoverClick);

        document.addEventListener('mousedown', event => {
            if (!activePicker) return;
            if (activePopover?.contains(event.target) || activePicker.trigger.contains(event.target)) return;
            closePicker();
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closePicker();
        });

        document.addEventListener('keyup', event => {
            if (event.key !== 'Enter') return;
            if (!activePicker || !activePopover?.contains(event.target)) return;
            if (!activePicker.input.value) return;

            const picker = activePicker;
            event.preventDefault();
            closePicker();
            dispatchAdvance(picker);
        });

        document.addEventListener('submit', event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            const emptyRequired = Array.from(form.querySelectorAll('input[data-kss-picker][required]'))
                .find(input => !input.disabled && !input.value);

            if (!emptyRequired) return;

            event.preventDefault();
            const picker = emptyRequired._kssPicker;
            showToast('error', 'Tanggal belum diisi', emptyRequired.dataset.validationMessage || 'Pilih tanggal terlebih dahulu sebelum melanjutkan.');
            if (picker) openPicker(picker);
        }, true);

        window.addEventListener('resize', () => activePicker && positionPicker(activePicker));
        window.addEventListener('scroll', () => activePicker && positionPicker(activePicker), true);

        document.addEventListener('DOMContentLoaded', () => {
            initAll(document);

            const observer = new MutationObserver(records => {
                records.forEach(record => {
                    record.addedNodes.forEach(node => {
                        if (node.nodeType !== Node.ELEMENT_NODE) return;
                        if (node.matches?.('input[data-kss-picker]')) initInput(node);
                        initAll(node);
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        });

        window.KssDateTimePicker = {
            init: initAll,
            close: closePicker,
            open(target) {
                const picker = target?._kssPicker || target?.nextElementSibling?._kssPicker || target?.previousElementSibling?._kssPicker;
                if (picker) openPicker(picker);
            },
            isTrigger(target) {
                return Boolean(target?._kssPicker);
            },
            parse: parseDateValue,
            format: formatValue,
        };
    })();
</script>
@endonce
