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
                        <label for="group-regu">Group / Regu</label>
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
                        <label for="group-penerima">Group / Regu Penerima</label>
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
