@php
    $reportDateValue = old('report_date', isset($report) && $report->report_date ? $report->report_date->format('Y-m-d') : now()->toDateString());
    $shiftValue = old('shift', $report->shift ?? '');
    $normalizedShiftValue = match (strtolower((string) $shiftValue)) {
        '1', 'pagi', 'shift pagi', 'shift 1' => 'Pagi',
        '2', 'siang', 'sore', 'shift siang', 'shift sore', 'shift 2' => 'Sore',
        '3', 'malam', 'shift malam', 'shift 3' => 'Malam',
        default => (string) $shiftValue,
    };
    $groupValue = strtoupper((string) old('group_name', $report->group_name ?? auth()->user()->group ?? ''));
    $timeRangeValue = old('time_range', $report->time_range ?? '');
    $receiverGroupValue = strtoupper((string) old('received_by_group', $report->received_by_group ?? ''));
@endphp

    <div class="box-form form-step d-flex flex-column align-items-start align-self-stretch" id="step-info-umum">
        <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
            <div class="title-form d-flex align-items-center gap-10">
                <span class="icon-title-form"><i class="fi fi-sr-document"></i></span>
                <span class="fw-600">Form Info Umum</span>
            </div>
            <div class="counter-form">Form 1 dari 7</div>
        </div>

        <div class="content-form d-flex flex-column align-items-center align-self-stretch w-100">
            <div class="step-info-note">
                <i class="fi fi-rr-info"></i>
                <span>Identitas laporan shift Anda. <strong>Regu Penerima</strong> adalah regu shift berikutnya yang akan menerima dan menandatangani laporan ini, wajib berbeda dari regu pengirim.</span>
            </div>
            <div class="form-info-umum d-flex align-items-start align-content-center align-self-stretch flex-wrap gap-20">
                <!-- 1. Hari / Tanggal -->
                <div class="box-input-1">
                    <div class="box-label-1">
                        <label for="tanggal">Hari / Tanggal</label>
                        <span class="text-red">*</span>
                    </div>
                    <div class="input-wrapper">
                        <input type="date" id="tanggal" name="report_date" value="{{ $reportDateValue }}" class="custom-input" onclick="if (this.showPicker) this.showPicker()" data-validation-message="Tanggal laporan wajib diisi." required>
                        <i class="fi fi-rr-calendar input-icon"></i>
                    </div>
                </div>

                <!-- 2. Shift -->
                <div class="box-input-1">
                    <div class="box-label-1">
                        <label for="shift">Shift</label>
                        <span class="text-red">*</span>
                    </div>
                    <div class="input-wrapper">
                        <select id="shift" name="shift" class="custom-input native-select" required>
                            <option value="" disabled @selected($normalizedShiftValue === '') hidden>Pilih Shift</option>
                            <option value="Pagi" @selected($normalizedShiftValue === 'Pagi')>Shift Pagi</option>
                            <option value="Sore" @selected($normalizedShiftValue === 'Sore')>Shift Sore</option>
                            <option value="Malam" @selected($normalizedShiftValue === 'Malam')>Shift Malam</option>
                        </select>
                        <i class="fi fi-rr-angle-small-down input-icon"></i>
                    </div>
                </div>

                <!-- 3. Group / Regu -->
                <div class="box-input-1">
                    <div class="box-label-1">
                        <label for="group-regu">Regu</label>
                        <span class="text-red">*</span>
                    </div>
                    <div class="input-wrapper">
                        <select id="group-regu" name="group_name" class="custom-input native-select" required>
                            <option value="" disabled @selected($groupValue === '') hidden>Pilih Group</option>
                            <option value="A" @selected($groupValue === 'A')>Group A</option>
                            <option value="B" @selected($groupValue === 'B')>Group B</option>
                            <option value="C" @selected($groupValue === 'C')>Group C</option>
                            <option value="D" @selected($groupValue === 'D')>Group D</option>
                        </select>
                        <i class="fi fi-rr-angle-small-down input-icon"></i>
                    </div>
                </div>

                <!-- 4. Jam Kerja -->
                <div class="box-input-1">
                    <div class="box-label-1">
                        <label for="jam-kerja">Jam Kerja</label>
                        <span class="text-red">*</span>
                    </div>
                    <div class="input-wrapper">
                        <select id="jam-kerja" name="time_range" class="custom-input native-select" required>
                            <option value="" disabled @selected($timeRangeValue === '') hidden>Pilih Jam Kerja</option>
                            <option value="07.00 - 15.00" @selected(in_array($timeRangeValue, ['07.00 - 15.00', '07:00 - 15:00'], true))>07.00 - 15.00</option>
                            <option value="15.00 - 23.00" @selected(in_array($timeRangeValue, ['15.00 - 23.00', '15:00 - 23:00'], true))>15.00 - 23.00</option>
                            <option value="23.00 - 07.00" @selected(in_array($timeRangeValue, ['23.00 - 07.00', '23:00 - 07:00'], true))>23.00 - 07.00</option>
                        </select>
                        <i class="fi fi-rr-angle-small-down input-icon"></i>
                    </div>
                </div>

                <!-- 5. Group / Regu Penerima -->
                <div class="box-input-1">
                    <div class="box-label-1">
                        <label for="group-penerima">Regu Penerima</label>
                        <span class="text-red">*</span>
                    </div>
                    <div class="input-wrapper">
                        <select id="group-penerima" name="received_by_group" class="custom-input native-select" required>
                            <option value="" disabled @selected($receiverGroupValue === '') hidden>Pilih Group</option>
                            <option value="A" @selected($receiverGroupValue === 'A')>Group A</option>
                            <option value="B" @selected($receiverGroupValue === 'B')>Group B</option>
                            <option value="C" @selected($receiverGroupValue === 'C')>Group C</option>
                            <option value="D" @selected($receiverGroupValue === 'D')>Group D</option>
                        </select>
                        <i class="fi fi-rr-angle-small-down input-icon"></i>
                    </div>
                    <div class="group-route-warning d-none" data-group-route-warning>
                        <i class="fi fi-rr-triangle-warning"></i>
                        <span>Group penerima harus berbeda dari group pengirim.</span>
                    </div>
                </div>
            </div>

            <div class="step-info-note" data-night-shift-hint style="margin-top:14px">
                <i class="fi fi-rr-moon-stars"></i>
                <span>Khusus <strong>Shift Malam</strong> (23.00&ndash;07.00) yang melewati tengah malam: beri tanggal saat shift <strong>dimulai</strong> (malam harinya), walau laporan baru diisi setelah lewat tengah malam. Ini mencegah satu shift tercatat ganda.</span>
            </div>

            @if (session('night_shift_adjacent') || old('confirm_adjacent_night'))
                <div class="night-shift-confirm" style="margin-top:14px;padding:14px 16px;border-radius:12px;border:1px solid var(--warning,#f0ad4e);background:var(--warning-10,#fff7e6);display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;align-items:flex-start;gap:10px">
                        <i class="fi fi-rr-triangle-warning" style="color:var(--warning,#f0ad4e);margin-top:2px"></i>
                        <span style="font-size:13px;line-height:1.5">
                            Sudah ada laporan <strong>Shift Malam</strong> regu ini di tanggal berdekatan{{ session('night_shift_adjacent') ? ' (' . session('night_shift_adjacent') . ')' : '' }}.
                            Karena shift malam melewati tengah malam, pastikan ini <strong>bukan shift yang sama</strong> yang terlanjur beda tanggal.
                        </span>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600">
                        <input type="checkbox" name="confirm_adjacent_night" value="1" @checked(old('confirm_adjacent_night')) style="width:16px;height:16px;cursor:pointer">
                        <span>Ya, ini shift malam yang berbeda &mdash; lanjutkan kirim laporan.</span>
                    </label>
                </div>
            @endif

            <div class="box-button d-flex justify-content-between align-items-center align-self-stretch mt-5">
                <a href="{{ route('report-ops.index') }}" type="button" class="btn-form cancel" style="text-decoration: none; cursor: pointer;">
                    <span class="icon"><i class="fi fi-br-cross-small"></i></span>
                    <span>Batalkan</span>
                </a>
                <button type="button" class="btn-form next btn-next-step">
                    <span>Lanjut</span>
                    <span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Default tanggal pintar untuk Shift Malam.
        // Shift Malam (23.00-07.00) melewati tengah malam. Bila petugas memilih
        // Shift Malam pada dini hari (00.00-07.59), berarti ia sedang melaporkan
        // shift yang DIMULAI kemarin malam. Konvensi: laporan diberi tanggal saat
        // shift dimulai, jadi tanggal otomatis digeser ke kemarin. Hanya berlaku
        // bila field tanggal masih berisi default hari ini (belum diubah manual),
        // dan petugas tetap bisa mengubahnya.
        (function () {
            var shiftSelect = document.getElementById('shift');
            var dateInput = document.getElementById('tanggal');
            if (!shiftSelect || !dateInput) {
                return;
            }

            function localDateString(date) {
                var y = date.getFullYear();
                var m = String(date.getMonth() + 1).padStart(2, '0');
                var d = String(date.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + d;
            }

            function maybeAdjustForNightShift() {
                if (shiftSelect.value !== 'Malam') {
                    return;
                }

                var now = new Date();
                // Dini hari: 00.00 s/d 07.59 (shift malam belum berakhir).
                if (now.getHours() >= 8) {
                    return;
                }

                // Hanya geser bila tanggal masih default hari ini (belum diubah petugas).
                if (dateInput.value !== localDateString(now)) {
                    return;
                }

                var yesterday = new Date(now.getTime());
                yesterday.setDate(yesterday.getDate() - 1);
                dateInput.value = localDateString(yesterday);
            }

            shiftSelect.addEventListener('change', maybeAdjustForNightShift);
            maybeAdjustForNightShift();
        })();
    </script>
