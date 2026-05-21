    <div class="box-form form-step d-none flex-column align-items-start align-self-stretch gap-10 br-10 white-bg" id="step-karyawan" style="box-shadow: 0 2px 4px 0 var(--blue-main-10);">
        <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
            <div class="title-form d-flex align-items-center gap-10">
                <span class="icon-title-form"><i class="fi fi-sr-employee-man"></i></span><span class="fw-600">Form Karyawan</span>
            </div>
            <div class="counter-form">Form 7 dari 7</div>
        </div>

        <div class="content-form d-flex flex-column align-items-center align-self-stretch w-100">

            <!-- SUB TABS KARYAWAN -->
            <div class="inspection-header d-flex justify-content-between align-items-end align-self-stretch">
                <div class="tab-group" id="karyawan-tabs-group">
                    <div class="tab-sections active" data-target="section-shift" style="cursor: pointer;">
                        <span>Karyawan Shift</span>
                    </div>
                    <div class="tab-sections" data-target="section-lembur" style="cursor: pointer;">
                        <span>Relief & Lembur</span>
                    </div>
                    <div class="tab-sections" data-target="section-op7" style="cursor: pointer;">
                        <span>OP.7 & Pengganti</span>
                    </div>
                    <div class="tab-sections" data-target="section-lain" style="cursor: pointer;">
                        <span>Lain-lain</span>
                    </div>
                </div>
            </div>

            <!-- 1. SECTION KARYAWAN SHIFT -->
            <div id="section-shift" class="tab-content-karyawan d-flex flex-column align-items-center align-self-stretch w-100 gap-10">
                <div class="table-wrapper w-100 material">
                    <div class="table-input material w-100">
                        <div class="head">
                            <div class="table-column no"><span>No</span></div>
                            <div class="table-column main"><span>Nama Karyawan Shift</span></div>
                            <div class="table-column absent"><span>Masuk</span></div>
                            <div class="table-column absent"><span>Pulang</span></div>
                            <div class="table-column absent"><span>Keterangan</span></div>
                            <div class="table-column delete"><span>Hapus</span></div>
                        </div>

                        <div class="body">
                            <div class="table-column no"><span>1</span></div>
                            <div class="table-column main">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-user-time"></i></span>
                                    <input type="text" name="employee_shift_logs[0][name]" placeholder="Nama Karyawan" value="Trailer KSS-01">
                                </div>
                            </div>
                            <div class="table-column absent">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-rr-time-quarter-past blue"></i></span>
                                    <input type="text" name="employee_shift_logs[0][time_in]" class="time-picker-input" placeholder="00:00" >
                                </div>
                            </div>
                            <div class="table-column absent">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-rr-time-check red"></i></span>
                                    <input type="text" name="employee_shift_logs[0][time_out]" class="time-picker-input" placeholder="00:00" >
                                </div>
                            </div>

                            <div class="table-column absent" style="overflow: visible;">
                                <div class="tbl-select-wrapper w-100">
                                    <select name="employee_shift_logs[0][description]" class="tbl-native-select">
                                        <option value="" disabled selected hidden>Pilih...</option>
                                        <option value="Sakit">Sakit</option>
                                        <option value="Cuti">Cuti</option>
                                        <option value="Tidak Masuk">Tidak Masuk</option>
                                    </select>
                                    <span class="icon tbl-icon-dropdown"><i class="fi fi-rr-angle-small-down"></i></span>
                                </div>
                            </div>

                            <div class="table-column delete">
                                <button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button>
                            </div>
                        </div>

                        <button type="button" class="btn-tambah-baris">
                            <i class="fi fi-rr-plus-small"></i> Tambah Baris
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. SECTION RELIEF & LEMBUR -->
            <div id="section-lembur" class="tab-content-karyawan d-none flex-column align-items-center align-self-stretch w-100 gap-10">
                <div class="table-wrapper w-100 material">
                    <div class="table-input material w-100">
                        <div class="head">
                            <div class="table-column no"><span>No</span></div>
                            <div class="table-column main"><span>Nama Karyawan Relief</span></div>
                            <div class="table-column no"><span>No</span></div>
                            <div class="table-column main"><span>Nama Karyawan Lembur</span></div>
                        </div>

                        <div class="body">
                            <div class="table-column no"><span>1</span></div>
                            <div class="table-column main">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-user-helmet-safety"></i></span>
                                    <input type="text" name="relief_logs[0][name]" placeholder="Nama Karyawan Relief" value="Sabarudin">
                                </div>
                            </div>
                            <div class="table-column no"><span>1</span></div>
                            <div class="table-column main">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-user-hard-work"></i></span>
                                    <input type="text" name="overtime_logs[0][name]" placeholder="Nama Karyawan Lembur" value="Nurul Huda">
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn-tambah-baris">
                            <i class="fi fi-rr-plus-small"></i> Tambah Baris
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. SECTION OP.7 & PENGGANTI -->
            <div id="section-op7" class="tab-content-karyawan d-none flex-column align-items-center align-self-stretch w-100 gap-10">
                <!-- Tabel OP.7 -->
                <div class="table-wrapper w-100 material">
                    <div class="table-input material w-100">
                        <div class="head">
                            <div class="table-column no"><span>No</span></div>
                            <div class="table-column main"><span>Nama Karyawan OP.7</span></div>
                            <div class="table-column medium"><span>No.Forklift</span></div>
                            <div class="table-column medium"><span>Area Kerja</span></div>
                            <div class="table-column absent"><span>Masuk</span></div>
                            <div class="table-column absent"><span>Keluar</span></div>
                            <div class="table-column absent"><span>Keterangan</span></div>
                            <div class="table-column delete"><span>Hapus</span></div>
                        </div>

                        <div class="body">
                            <div class="table-column no"><span>1</span></div>
                            <div class="table-column main">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-user-helmet-safety"></i></span>
                                    <input type="text" name="op7_logs[0][name]" placeholder="Nama Karyawan OP.7" value="Sabarudin">
                                </div>
                            </div>
                            <div class="table-column medium">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-forklift"></i></span>
                                    <input type="text" name="op7_logs[0][no_forklift_]" placeholder="No. Forklift">
                                </div>
                            </div>
                            <div class="table-column medium">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-land-location"></i></span>
                                    <input type="text" name="op7_logs[0][work_area]" placeholder="Area">
                                </div>
                            </div>
                            <div class="table-column absent">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-rr-time-quarter-past blue"></i></span>
                                    <input type="text" name="op7_logs[0][time_in]" class="time-picker-input" placeholder="00:00" >
                                </div>
                            </div>
                            <div class="table-column absent">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-rr-time-check red"></i></span>
                                    <input type="text" name="op7_logs[0][time_out]" class="time-picker-input" placeholder="00:00" >
                                </div>
                            </div>

                            <div class="table-column absent" style="overflow: visible;">
                                <div class="tbl-select-wrapper w-100">
                                    <select name="op7_logs[0][description]" class="tbl-native-select">
                                        <option value="" disabled selected hidden>Pilih...</option>
                                        <option value="Sakit">Sakit</option>
                                        <option value="Cuti">Cuti</option>
                                        <option value="Tidak Masuk">Tidak Masuk</option>
                                    </select>
                                    <span class="icon tbl-icon-dropdown"><i class="fi fi-rr-angle-small-down"></i></span>
                                </div>
                            </div>

                            <div class="table-column delete">
                                <button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button>
                            </div>
                        </div>

                        <button type="button" class="btn-tambah-baris">
                            <i class="fi fi-rr-plus-small"></i> Tambah Baris
                        </button>
                    </div>
                </div>

                <div class="exchange d-flex justify-content-center align-items-center align-self-stretch gap-20" style="font-size: 30px;">
                    <span class="icon" style="color: var(--blue-main);"><i class="fi fi-rr-arrow-down"></i></span>
                    <span class="icon" style="color: var(--red-main);"><i class="fi fi-rr-arrow-up"></i></span>
                </div>

                <!-- Tabel Pengganti OP.7 -->
                <div class="table-wrapper w-100 material red">
                    <div class="table-input material w-100">
                        <div class="title-pengganti d-flex justify-content-center align-items-center align-self-stretch" style="padding: 10px 0;">
                            <span style="font-size: 14px; font-weight: 500; color: var(--red-main);">Daftar Pengganti Operator yang Tidak Masuk</span>
                        </div>
                        <div class="head" style="border-radius: 0px !important; background-color: var(--red-main);">
                            <div class="table-column no"><span>No</span></div>
                            <div class="table-column main"><span>Nama Karyawan Pengganti</span></div>
                            <div class="table-column medium"><span>No.Forklift</span></div>
                            <div class="table-column medium"><span>Area Kerja</span></div>
                            <div class="table-column absent"><span>Masuk</span></div>
                            <div class="table-column absent"><span>Keluar</span></div>
                            <div class="table-column absent"><span>Keterangan</span></div>
                            <div class="table-column delete"><span>Hapus</span></div>
                        </div>

                        <div class="body">
                            <div class="table-column no"><span>1</span></div>
                            <div class="table-column main">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-user-helmet-safety text-red"></i></span>
                                    <input type="text" name="replacement_logs[0][name]" placeholder="Nama Karyawan Pengganti" value="Sabarudin">
                                </div>
                            </div>
                            <div class="table-column medium">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-forklift text-red"></i></span>
                                    <input type="text" name="replacement_logs[0][no_forklift_]" placeholder="No. Forklift">
                                </div>
                            </div>
                            <div class="table-column medium">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-land-location text-red"></i></span>
                                    <input type="text" name="replacement_logs[0][work_area]" placeholder="Area">
                                </div>
                            </div>
                            <div class="table-column absent">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-rr-time-quarter-past blue"></i></span>
                                    <input type="text" name="replacement_logs[0][time_in]" class="time-picker-input" placeholder="00:00" >
                                </div>
                            </div>
                            <div class="table-column absent">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-rr-time-check red"></i></span>
                                    <input type="text" name="replacement_logs[0][time_out]" class="time-picker-input" placeholder="00:00" >
                                </div>
                            </div>

                            <div class="table-column absent" style="overflow: visible;">
                                <div class="tbl-select-wrapper w-100">
                                    <select name="replacement_logs[0][description]" class="tbl-native-select">
                                        <option value="" disabled selected hidden>Pilih...</option>
                                        <option value="Sakit">Sakit</option>
                                        <option value="Cuti">Cuti</option>
                                        <option value="Tidak Masuk">Tidak Masuk</option>
                                    </select>
                                    <span class="icon tbl-icon-dropdown"><i class="fi fi-rr-angle-small-down"></i></span>
                                </div>
                            </div>

                            <div class="table-column delete">
                                <button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button>
                            </div>
                        </div>

                        <button type="button" class="btn-tambah-baris red">
                            <i class="fi fi-rr-plus-small"></i> Tambah Baris
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. SECTION LAIN-LAIN -->
            <div id="section-lain" class="tab-content-karyawan d-none flex-column align-items-center align-self-stretch w-100 gap-10">
                <div class="table-wrapper w-100 material">
                    <div class="table-input material w-100">
                        <div class="head">
                            <div class="table-column no"><span>No</span></div>
                            <div class="table-column main"><span>Personil</span></div>
                            <div class="table-column main"><span>Kegiatan Lain</span></div>
                            <div class="table-column absent"><span>Masuk</span></div>
                            <div class="table-column absent"><span>Pulang</span></div>
                            <div class="table-column delete"><span>Hapus</span></div>
                        </div>

                        <div class="body">
                            <div class="table-column no"><span>1</span></div>
                            <div class="table-column main">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-user-time"></i></span>
                                    <input type="text" name="other_activity_logs[0][name]" placeholder="Nama Personil" value="Nurul Huda">
                                </div>
                            </div>
                            <div class="table-column main">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-sr-tools"></i></span>
                                    <input type="text" name="other_activity_logs[0][description]" placeholder="Kegiatan Lain......">
                                </div>
                            </div>
                            <div class="table-column absent">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-rr-time-quarter-past blue"></i></span>
                                    <input type="text" name="other_activity_logs[0][time_in]" class="time-picker-input" placeholder="00:00" >
                                </div>
                            </div>
                            <div class="table-column absent">
                                <div class="table-input-wrapper">
                                    <span class="icon"><i class="fi fi-rr-time-check red"></i></span>
                                    <input type="text" name="other_activity_logs[0][time_out]" class="time-picker-input" placeholder="00:00" >
                                </div>
                            </div>
                            <div class="table-column delete">
                                <button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button>
                            </div>
                        </div>

                        <button type="button" class="btn-tambah-baris">
                            <i class="fi fi-rr-plus-small"></i> Tambah Baris
                        </button>
                    </div>
                </div>
            </div>

            <!-- BUTTON NAVIGASI BAWAH -->
            <div class="box-button d-flex justify-content-between align-items-center align-self-stretch w-100" style="padding-top: 15px;">
                <button class="btn-form back btn-back-step" type="button">
                    <span class="icon"><i class="fi fi-rr-arrow-small-left"></i></span>
                    <span>Kembali</span>
                </button>
                <!-- Tombol Selesai yang memicu Modal -->
                <button id="btnOpenConfirm" class="btn-form finish" type="button">
                    <span>Selesai</span>
                    <span class="icon"><i class="fi fi-rr-check"></i></span>
                </button>
            </div>

        </div>
    </div>
