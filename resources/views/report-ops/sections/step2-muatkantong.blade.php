    <div class="box-form form-step d-none flex-column align-items-start align-self-stretch gap-10 br-10 white-bg" id="step-muat-kantong" style="box-shadow: 0 2px 4px 0 var(--blue-main-10); gap: 10px;">
        <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
            <div class="title-form d-flex align-items-center gap-10">
                <span class="icon-title-form"><i class="fi fi-sr-bag-seedling"></i></span>
                <span class="fw-600">Form Muat Kantong</span>
            </div>
            <div class="counter-form">Form 2 dari 7</div>
        </div>

        <div class="content-form d-flex flex-column align-items-center align-self-stretch w-100">
            <div class="form-muat-kantong d-flex flex-column align-items-start align-self-stretch">
                <div class="tab-activity d-flex align-items-center gap-10">
                    <button type="button" class="btn-activity active">Kegiatan 1</button>
                    <button type="button" class="btn-activity">Kegiatan 2</button>
                    <div class="plus-minus-tab d-flex align-items-center" style="gap: 8px;">
                        <button type="button" class="btn add">
                            <span class="icon"><i class="fi fi-rr-plus-small"></i></span>
                        </button>
                        <button type="button" class="btn remove">
                            <span class="icon"><i class="fi fi-rr-minus-small"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="shipment-details d-flex flex-column align-items-start align-self-stretch gap-15">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama_kapal_kantong">Nama Kapal</label>
                        <input type="hidden" name="ship_operation_id_1" value="{{ old('ship_operation_id_1') }}">
                        <input type="text" id="nama_kapal_kantong" name="ship_name_1" value="{{ old('ship_name_1') }}" placeholder="Masukkan Nama Kapal">
                    </div>
                    <div class="form-group">
                        <label for="agen_kantong">Agen</label>
                        <input type="text" id="agen_kantong" name="agent_1" value="{{ old('agent_1') }}" placeholder="Masukkan Nama Agen">
                    </div>
                    <div class="form-group">
                        <label for="dermaga_kantong">Dermaga</label>
                        <input type="text" id="dermaga_kantong" name="jetty_1" value="{{ old('jetty_1') }}" placeholder="Lokasi Dermaga">
                    </div>
                    <div class="form-group">
                        <label for="tujuan_kantong">Tujuan</label>
                        <input type="text" id="tujuan_kantong" name="destination_1" value="{{ old('destination_1') }}" placeholder="Tujuan Pengiriman">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="kapasitas_kantong">Kapasitas</label>
                        <div class="input-wrapper">
                            <input type="number" id="kapasitas_kantong" name="capacity_1" value="{{ old('capacity_1') }}" placeholder="Masukkan Kapasitas" style="padding-right: 40px;">
                            <span class="input-icon" style="color: var(--dark-main); font-weight: 500; font-size: 11px;">Ton</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="nomor_wo">Nomor WO</label>
                        <input type="text" id="nomor_wo" name="wo_number_1" value="{{ old('wo_number_1') }}" placeholder="Nomor WO">
                    </div>
                    <div class="form-group">
                        <label for="jenis_kargo">Jenis Kargo</label>
                        <input type="text" id="jenis_kargo" name="cargo_type_1" value="{{ old('cargo_type_1') }}" placeholder="Pilih Jenis Kargo">
                    </div>
                    <div class="form-group">
                        <label for="marking">Marking</label>
                        <input type="text" id="marking" name="marking_1" value="{{ old('marking_1') }}" placeholder="Masukkan Marking">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="tiba_sandar_kantong">Tiba / Sandar</label>
                        <div class="input-wrapper">
                            <input type="hidden" id="tiba_sandar_kantong" name="arrival_time_1" value="{{ old('arrival_time_1') }}" class="datetime-picker-input" data-kss-picker="datetime" data-trigger-class="custom-input" data-placeholder="Pilih tanggal & jam" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="gang_operasi">Gang Operasi</label>
                        <input type="text" id="gang_operasi" name="operating_gang_1" value="{{ old('operating_gang_1') }}" placeholder="Gang">
                    </div>
                    <div class="form-group">
                        <label for="jumlah_tkbm">Jumlah TKBM</label>
                        <div class="input-wrapper">
                            <input type="number" id="jumlah_tkbm" name="tkbm_count_1" value="{{ old('tkbm_count_1') }}" placeholder="Jumlah TKBM" style="padding-right: 35px;">
                            <span class="input-icon"><i class="fi fi-rr-user"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mandor_foreman">Mandor / Foreman</label>
                        <input type="text" id="mandor_foreman" name="foreman_1" value="{{ old('foreman_1') }}" placeholder="Masukkan Nama Mandor">
                    </div>
                </div>

                <div class="ship-operation-status">
                    <span class="ship-operation-status-label">Status pekerjaan kapal</span>
                    <div class="ship-operation-status-options">
                        <label>
                            <input type="radio" name="ship_operation_status_1" value="active" {{ old('ship_operation_status_1', 'active') === 'active' ? 'checked' : '' }}>
                            <span>Masih Berjalan</span>
                        </label>
                        <label>
                            <input type="radio" name="ship_operation_status_1" value="completed" {{ old('ship_operation_status_1') === 'completed' ? 'checked' : '' }}>
                            <span>Selesai</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="summary-section d-flex align-items-center align-content-center align-self-stretch flex-wrap" style="gap: 25px;">
                <div class="form-card deliv">
                    <div class="form-card-head">
                        <div class="title">
                            <span class="box-icon"><i class="fi fi-rr-truck-container"></i></span>
                            <span style="color: var(--dark-main);">Pengiriman</span>
                        </div>
                        <span class="accumulated">0</span>
                    </div>
                    <div class="form-card-content">
                        <div class="card-form-group"><label>Sekarang</label><input type="number" name="qty_delivery_current_1" value="{{ old('qty_delivery_current_1') }}" placeholder="0"></div>
                        <span class="icon" style="color: var(--muted);"><i class="fi fi-rr-plus-small" style="position: relative; top: -2px;"></i></span>
                        <div class="card-form-group"><label>Lalu</label><input type="number" name="qty_delivery_prev_1" value="{{ old('qty_delivery_prev_1') }}" placeholder="0" readonly></div>
                    </div>
                </div>
                <div class="form-card load">
                    <div class="form-card-head">
                        <div class="title">
                            <span class="box-icon"><i class="fi fi-rr-truck-loading"></i></span>
                            <span style="color: var(--dark-main);">Pemuatan</span>
                        </div>
                        <span class="accumulated">0</span>
                    </div>
                    <div class="form-card-content">
                        <div class="card-form-group"><label>Sekarang</label><input type="number" name="qty_loading_current_1" value="{{ old('qty_loading_current_1') }}" placeholder="0"></div>
                        <span class="icon" style="color: var(--muted);"><i class="fi fi-rr-plus-small" style="position: relative; top: -2px;"></i></span>
                        <div class="card-form-group"><label>Lalu</label><input type="number" name="qty_loading_prev_1" value="{{ old('qty_loading_prev_1') }}" placeholder="0" readonly></div>
                    </div>
                </div>
                <div class="form-card damage">
                    <div class="form-card-head">
                        <div class="title">
                            <span class="box-icon"><i class="fi fi-rr-damage"></i></span>
                            <span style="color: var(--dark-main);">Kerusakan</span>
                        </div>
                        <span class="accumulated">0</span>
                    </div>
                    <div class="form-card-content">
                        <div class="card-form-group"><label>Sekarang</label><input type="number" name="qty_damage_current_1" value="{{ old('qty_damage_current_1') }}" placeholder="0"></div>
                        <span class="icon" style="color: var(--muted);"><i class="fi fi-rr-plus-small" style="position: relative; top: -2px;"></i></span>
                        <div class="card-form-group"><label>Lalu</label><input type="number" name="qty_damage_prev_1" value="{{ old('qty_damage_prev_1') }}" placeholder="0" readonly></div>
                    </div>
                </div>
            </div>

            <div class="timesheet-section d-flex align-items-stretch align-content-center align-self-stretch flex-wrap" style="gap: 20px 30px;">
                <!-- Timesheet Pengiriman -->
                <div class="timesheet-card deliv">
                    <div class="timesheet-card-header">
                        <span class="timesheet-icon"><i class="fi fi-sr-ship"></i></span>
                        <span class="fsize-14 fw-600">Timesheet Pengiriman</span>
                    </div>
                    <div class="timesheet-content">
                        <div class="timesheet-input">
                            <div class="timesheet-input-wrapper">
                                <input type="text" name="timesheets[1][delivery][0][time]" placeholder="00:00" class="time-picker-input" autocomplete="off" inputmode="numeric" maxlength="5">
                                <span class="icon" style="position: relative; top: 1px;"><i class="fi fi-rr-clock"></i></span>
                            </div>
                            <input class="activity-input" name="timesheets[1][delivery][0][activity]" type="text" placeholder="Ketik Aktivitas.....">
                            <button type="button" class="btn-add-activity">
                                <span class="icon"><i class="fi fi-rr-plus"></i></span>
                                <span>Tambah</span>
                            </button>
                        </div>
                        <div class="timeline-section"></div>
                    </div>
                    <div class="timesheet-personnel-grid">
                        <div class="form-group form-group--full">
                            <label for="tally_warehouse_1">Tally Gudang</label>
                            <div class="input-wrapper">
                                <span class="personnel-input-icon"><i class="fi fi-sr-user-time"></i></span>
                                <input type="text" id="tally_warehouse_1" name="tally_warehouse_1" value="{{ old('tally_warehouse_1') }}" placeholder="Nama tally gudang">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="driver_name_1">Driver</label>
                            <div class="input-wrapper">
                                <span class="personnel-input-icon"><i class="fi fi-sr-user-helmet-safety"></i></span>
                                <input type="text" id="driver_name_1" name="driver_name_1" value="{{ old('driver_name_1') }}" placeholder="Nama driver">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="truck_number_1">No Truck</label>
                            <div class="input-wrapper">
                                <span class="personnel-input-icon"><i class="fi fi-sr-truck-side"></i></span>
                                <input type="text" id="truck_number_1" name="truck_number_1" value="{{ old('truck_number_1') }}" placeholder="Nomor truck">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timesheet Pemuatan -->
                <div class="timesheet-card load">
                    <div class="timesheet-card-header">
                        <span class="timesheet-icon"><i class="fi fi-sr-truck-loading"></i></span>
                        <span class="fsize-14 fw-600">Timesheet Pemuatan</span>
                    </div>
                    <div class="timesheet-content">
                        <div class="timesheet-input">
                            <div class="timesheet-input-wrapper">
                                <input type="text" name="timesheets[1][loading][0][time]" placeholder="00:00" class="time-picker-input" autocomplete="off" inputmode="numeric" maxlength="5">
                                <span class="icon" style="position: relative; top: 1px;"><i class="fi fi-rr-clock"></i></span>
                            </div>
                            <input class="activity-input" name="timesheets[1][loading][0][activity]" type="text" placeholder="Ketik Aktivitas.....">
                            <button type="button" class="btn-add-activity">
                                <span class="icon"><i class="fi fi-rr-plus"></i></span>
                                <span>Tambah</span>
                            </button>
                        </div>
                        <div class="timeline-section"></div>
                    </div>
                    <div class="timesheet-personnel-grid">
                        <div class="form-group">
                            <label for="tally_ship_1">Tally Kapal</label>
                            <div class="input-wrapper">
                                <span class="personnel-input-icon"><i class="fi fi-sr-ship"></i></span>
                                <input type="text" id="tally_ship_1" name="tally_ship_1" value="{{ old('tally_ship_1') }}" placeholder="Nama tally kapal">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="operator_ship_1">Operator</label>
                            <div class="input-wrapper">
                                <span class="personnel-input-icon"><i class="fi fi-sr-user-helmet-safety"></i></span>
                                <input type="text" id="operator_ship_1" name="operator_ship_1" value="{{ old('operator_ship_1') }}" placeholder="Nama operator">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="forklift_ship_1">Forklift No.</label>
                            <div class="input-wrapper">
                                <span class="personnel-input-icon"><i class="fi fi-sr-forklift"></i></span>
                                <input type="text" id="forklift_ship_1" name="forklift_ship_1" value="{{ old('forklift_ship_1') }}" placeholder="Nomor forklift">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="operator_warehouse_1">Operator Gudang</label>
                            <div class="input-wrapper">
                                <span class="personnel-input-icon"><i class="fi fi-sr-user-time"></i></span>
                                <input type="text" id="operator_warehouse_1" name="operator_warehouse_1" value="{{ old('operator_warehouse_1') }}" placeholder="Nama operator gudang">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="forklift_warehouse_1">Forklift No.</label>
                            <div class="input-wrapper">
                                <span class="personnel-input-icon"><i class="fi fi-sr-forklift"></i></span>
                                <input type="text" id="forklift_warehouse_1" name="forklift_warehouse_1" value="{{ old('forklift_warehouse_1') }}" placeholder="Nomor forklift">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-button d-flex justify-content-between align-items-center align-self-stretch mt-4">
                <button type="button" class="btn-form back btn-back-step">
                    <span class="icon"><i class="fi fi-rr-arrow-small-left"></i></span>
                    <span>Kembali</span>
                </button>
                <button type="button" class="btn-form next btn-next-step">
                    <span>Lanjut</span>
                    <span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span>
                </button>
            </div>
        </div>
    </div>
