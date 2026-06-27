    <div class="box-form form-step d-none flex-column align-items-start align-self-stretch gap-10 br-10 white-bg" id="step-muat-curah" style="box-shadow: 0 2px 4px 0 var(--blue-main-10);">
        <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
            <div class="title-form d-flex align-items-center gap-10">
                <span class="icon-title-form"><i class="fi fi-sr-truck-loading"></i></span>
                <span class="fw-600">Form Pemuatan Curah Urea</span>
            </div>
            <div class="counter-form">Form 3 dari 7</div>
        </div>

        <div class="content-form d-flex flex-column align-items-center align-self-stretch w-100">
            <div class="step-info-note">
                <i class="fi fi-rr-info"></i>
                <span>Catat pemuatan urea curah per kapal. Isi <strong>Laporan Harian</strong> tiap jam beserta <strong>COB</strong> (jumlah muat, dalam ton). Tandai status <strong>Selesai</strong> bila pekerjaan kapal sudah rampung.</span>
            </div>
            <div class="form-muat-curah d-flex flex-column align-items-start align-self-stretch" style="gap: 25px;">
                <div class="tab-activity d-flex align-items-center gap-10">
                    <button type="button" class="btn-activity active">Kegiatan 1</button>
                    <div class="plus-minus-tab d-flex align-items-center" style="gap: 8px;">
                        <button type="button" class="btn add"><span class="icon"><i class="fi fi-rr-plus-small"></i></span></button>
                        <button type="button" class="btn remove"><span class="icon"><i class="fi fi-rr-minus-small"></i></span></button>
                    </div>
                </div>
            </div>

            <div class="shipment-details d-flex flex-column align-items-start align-self-stretch gap-15">
                <div class="form-grid">
                    <div class="form-group"><label>Nama Kapal</label><input type="hidden" name="ship_operation_urea_id_1" value="{{ old('ship_operation_urea_id_1') }}"><input type="text" name="ship_name_urea_1" value="{{ old('ship_name_urea_1') }}" placeholder="Masukkan Nama Kapal"></div>
                    <div class="form-group"><label>Dermaga</label><input type="text" name="jetty_urea_1" value="{{ old('jetty_urea_1') }}" placeholder="Lokasi Dermaga"></div>
                    <div class="form-group"><label>Tujuan</label><input type="text" name="destination_urea_1" value="{{ old('destination_urea_1') }}" placeholder="Tujuan Pengiriman"></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label>Agen</label><input type="text" name="agent_urea_1" value="{{ old('agent_urea_1') }}" placeholder="Masukkan Nama Agen"></div>
                    <div class="form-group"><label>Petugas PBM</label><input type="text" name="stevedoring_urea_1" value="{{ old('stevedoring_urea_1') }}" placeholder="Nama Petugas"></div>
                    <div class="form-group"><label>Jenis Urea</label><input type="text" name="commodity_urea_1" value="{{ old('commodity_urea_1') }}" placeholder="Jenis Urea"></div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Kapasitas</label>
                        <div class="input-wrapper"><input type="number" name="capacity_urea_1" value="{{ old('capacity_urea_1') }}" placeholder="Masukkan Kapasitas" style="padding-right: 40px;"><span class="input-icon" style="font-size: 11px;">Ton</span></div>
                    </div>
                    <div class="form-group">
                        <label>Tiba / Sandar</label>
                        <div class="input-wrapper"><input type="hidden" name="berthing_time_urea_1" value="{{ old('berthing_time_urea_1') }}" class="datetime-picker-input" data-kss-picker="datetime" data-trigger-class="custom-input" data-placeholder="Pilih tanggal & jam" autocomplete="off"></div>
                    </div>
                    <div class="form-group">
                        <label>Mulai Muat</label>
                        <div class="input-wrapper"><input type="hidden" name="start_loading_time_urea_1" value="{{ old('start_loading_time_urea_1') }}" class="datetime-picker-input" data-kss-picker="datetime" data-trigger-class="custom-input" data-placeholder="Pilih tanggal & jam" autocomplete="off"></div>
                    </div>
                </div>

                <div class="ship-operation-status">
                    <span class="ship-operation-status-label">Status pekerjaan kapal</span>
                    <div class="ship-operation-status-options">
                        <label>
                            <input type="radio" name="ship_operation_urea_status_1" value="active" {{ old('ship_operation_urea_status_1', 'active') === 'active' ? 'checked' : '' }}>
                            <span>Masih Berjalan</span>
                        </label>
                        <label>
                            <input type="radio" name="ship_operation_urea_status_1" value="completed" {{ old('ship_operation_urea_status_1') === 'completed' ? 'checked' : '' }}>
                            <span>Selesai</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="timesheet-section d-flex align-items-stretch align-content-center align-self-stretch flex-wrap" style="gap: 20px 30px;">
                <div class="timesheet-card deliv w-100">
                    <div class="timesheet-card-header">
                        <span class="timesheet-icon"><i class="fi fi-rr-document"></i></span>
                        <span class="fsize-14 fw-600">Laporan Harian</span>
                    </div>
                    <div class="timesheet-content">
                        <div class="timesheet-input cob-line d-flex flex-wrap w-100" style="gap: 15px;">
                            <div class="timesheet-input-wrapper">
                                <input type="text" name="bulk_logs[1][0][time]" placeholder="00:00" class="time-picker-input" autocomplete="off" inputmode="numeric" maxlength="5">
                                <span class="icon"><i class="fi fi-rr-clock"></i></span>
                            </div>
                            <input class="activity-input flex-grow-1" name="bulk_logs[1][0][activity]" type="text" placeholder="Ketik Aktivitas...">
                            <div class="cob-wrapper">
                                <span class="cob-label">COB:</span>
                                <input type="number" name="bulk_logs[1][0][cob]" placeholder="0" min="0" step="0.01" inputmode="decimal">
                                <span class="cob-unit">Ton</span>
                            </div>
                            <button type="button" class="btn-add-activity">
                                <span class="icon"><i class="fi fi-rr-plus"></i></span>
                                <span>Tambah</span>
                            </button>
                        </div>
                        <div class="timeline-section w-100 mt-3"></div>
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
