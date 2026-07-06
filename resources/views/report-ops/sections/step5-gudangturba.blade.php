    <div class="box-form form-step d-none flex-column align-items-start align-self-stretch gap-10 br-10 white-bg" id="step-gudang-turba" style="box-shadow: 0 2px 4px 0 var(--blue-main-10);">
        <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
            <div class="title-form d-flex align-items-center gap-10">
                <span class="icon-title-form"><i class="fi fi-sr-warehouse-alt"></i></span><span class="fw-600">Form Tracking</span>
            </div>
            <div class="counter-form">Form 5 dari 7</div>
        </div>
        <div class="content-form d-flex flex-column align-items-center align-self-stretch w-100">
            <div class="step-info-note">
                <i class="fi fi-rr-info"></i>
                <span>Lacak distribusi barang keluar per truck — lengkapi nomor <strong>DO/SO</strong>, marking, dan jumlah terkirim. Kolom <strong>Total</strong> dihitung otomatis (Sekarang + Lalu). Tambah baris untuk tiap truck.</span>
            </div>

            <div class="d-flex flex-column align-items-start align-self-stretch w-100" style="gap: 12px;">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="turba_tally_gudang">Tally Gudang Kirim</label>
                        <input type="text" id="turba_tally_gudang" name="tally_gudang_names" value="{{ old('tally_gudang_names') }}" placeholder="Nama Tally Gudang Kirim">
                    </div>
                    <div class="form-group">
                        <label for="turba_tally_gudang_terima">Tally Gudang Terima</label>
                        <input type="text" id="turba_tally_gudang_terima" name="turba_tally_gudang_terima" value="{{ old('turba_tally_gudang_terima') }}" placeholder="Nama Tally Gudang Terima">
                    </div>
                    <div class="form-group">
                        <label for="turba_operator_forklift">Operator Forklift</label>
                        <input type="text" id="turba_operator_forklift" name="turba_forklift_operator" value="{{ old('turba_forklift_operator') }}" placeholder="Nama Operator Forklift">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="turba_fl_no">Nomor Forklift</label>
                        <input type="text" id="turba_fl_no" name="turba_fl_no" value="{{ old('turba_fl_no') }}" placeholder="Nomor Forklift" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="turba_driver">Driver</label>
                        <input type="text" id="turba_driver" name="turba_driver_names" value="{{ old('turba_driver_names') }}" placeholder="Nama Driver">
                    </div>
                    <div class="form-group">
                        <label for="turba_trl_no">Nomor Trailer</label>
                        <input type="text" id="turba_trl_no" name="turba_trl_no" value="{{ old('turba_trl_no') }}" placeholder="Nomor Trailer" autocomplete="off">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group rentang-jam-group">
                        <label>Rentang Jam Kerja</label>
                        <div class="rentang-jam-wrapper">
                            <div class="input-wrapper">
                                <span class="input-icon" style="top: 8px;left: 15px; right: auto; color: var(--blue-main);"><i class="fi fi-br-clock"></i></span>
                                <input type="text" name="turba_work_start" class="time-picker-input" placeholder="00:00" style="padding: 8px 15px 8px 40px; border: none; width: 100%; outline: none; font-size: 12px; font-weight: 500; text-align: center;">
                            </div>
                            <i class="fi fi-rr-arrow-right" style="font-size: 12px; color: var(--dark-main);"></i>
                            <div class="input-wrapper">
                                <span class="input-icon" style="top:8px;left: 15px; right: auto; color: var(--red-main);"><i class="fi fi-br-clock"></i></span>
                                <input type="text" name="turba_work_end" class="time-picker-input" placeholder="00:00" style="padding: 8px 15px 8px 40px; border: none; width: 100%; outline: none; font-size: 12px; font-weight: 500; text-align: center;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Input Material Ber-wrapper Scroll -->
            <div class="table-wrapper w-100 material">
                <div class="table-input material w-100">
                    <!-- Table Head -->
                    <div class="head">
                        <div class="table-column no"><span>No</span></div>
                        <div class="table-column medium"><span>Nama Truck</span></div>
                        <div class="table-column double">
                            <span>DO / SO</span>
                            <div class="column-detail">
                                <span>Nomor</span>
                                <span>Kapasitas</span>
                            </div>
                        </div>
                        <div class="table-column medium justify-content-center"><span>Jenis Marking</span></div>
                        <div class="table-column triple">
                            <span>Jumlah Terkirim</span>
                            <div class="column-detail">
                                <span>Sekarang</span>
                                <span>Lalu</span>
                                <span>Total</span>
                            </div>
                        </div>
                        <div class="table-column delete"><span>Hapus</span></div>
                    </div>

                    <!-- Table Row 1 -->
                    <div class="body">
                        <div class="table-column no">
                            <span>1</span>
                        </div>
                        <div class="table-column medium">
                            <div class="table-input-wrapper">
                                <span class="icon"><i class="fi fi-sr-truck-side"></i></span>
                                <input type="text" name="turba_deliveries[0][truck_name]" placeholder="Tujuan Pengiriman">
                            </div>
                        </div>
                        <div class="table-column input-double">
                            <input type="text" name="turba_deliveries[0][do_so_number]" class="form-control-custom" placeholder="Nomor">
                            <input type="number" name="turba_deliveries[0][capacity]" class="form-control-custom" placeholder="0">
                        </div>
                        <div class="table-column medium">
                            <input type="text" name="turba_deliveries[0][marking_type]" class="form-control-custom" placeholder="Marking">
                        </div>
                        <div class="table-column input-triple">
                            <input type="number" name="turba_deliveries[0][qty_current]" class="form-control-custom" placeholder="0">
                            <input type="number" name="turba_deliveries[0][qty_prev]" class="form-control-custom" placeholder="0">
                            <input type="number" name="turba_deliveries[0][qty_accumulated]" class="form-control-custom" placeholder="0" readonly>
                        </div>
                        <div class="table-column delete">
                            <button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button>
                        </div>
                    </div>

                    <!-- Tambah Baris Button -->
                    <button type="button" class="btn-tambah-baris">
                        <i class="fi fi-rr-plus-small"></i> Tambah Baris
                    </button>
                </div>
            </div>

            <div class="box-button d-flex justify-content-between align-items-center align-self-stretch w-100" style="padding-top: 15px;">
                <button class="btn-form back btn-back-step" type="button">
                    <span class="icon"><i class="fi fi-rr-arrow-small-left"></i></span>
                    <span>Kembali</span>
                </button>
                <button class="btn-form next btn-next-step" type="button">
                    <span>Lanjut</span>
                    <span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span>
                </button>
            </div>
        </div>
    </div>
