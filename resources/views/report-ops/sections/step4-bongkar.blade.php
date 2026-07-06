    <div class="box-form form-step d-none flex-column align-items-start align-self-stretch gap-10 br-10 white-bg" id="step-bongkar" style="box-shadow: 0 2px 4px 0 var(--blue-main-10);">
        <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
            <div class="title-form d-flex align-items-center gap-10">
                <span class="icon-title-form"><i class="fi fi-sr-box-open"></i></span>
                <span class="fw-600">Form Bongkar</span>
            </div>
            <div class="counter-form">Form 4 dari 7</div>
        </div>

        <div class="content-form d-flex flex-column align-items-center align-self-stretch w-100">
            <div class="step-info-note">
                <i class="fi fi-rr-info"></i>
                <span>Pilih jenis kegiatan: <strong>Bongkar Bahan Baku</strong> atau <strong>Bongkar/Muat Container</strong>. Kolom <strong>Lalu</strong> terisi otomatis dari shift sebelumnya — nilai ini masih dapat diubah manual bila perlu, dan <strong>Total</strong> akan dihitung sendiri.</span>
            </div>
            <div class="form-bongkar d-flex flex-column align-items-start align-self-stretch" style="gap: 25px;">
                <div class="tab-bongkar">
                    <a class="tab active material" id="tab-btn-bahan-baku">Bongkar Bahan Baku</a>
                    <a class="tab tab-container" id="tab-btn-container">Bongkar / Muat Container</a>
                </div>

                <!-- SUB-TAB 1: BAHAN BAKU -->
                <div id="section-bahan-baku" class="d-flex flex-column align-items-start align-self-stretch w-100" style="gap: 25px;">
                    <div class="form-grid w-100">
                        <div class="form-group"><label>Nama Kapal</label><input type="text" name="ship_name_material" value="{{ old('ship_name_material') }}" placeholder="Masukkan Nama Kapal"></div>
                        <div class="form-group"><label>Agen</label><input type="text" name="agent_material" value="{{ old('agent_material') }}" placeholder="Masukkan Nama Agen"></div>
                        <div class="form-group"><label>Dermaga</label><input type="text" name="jetty_material" value="{{ old('jetty_material') }}" placeholder="Masukkan Dermaga"></div>
                        <div class="form-group">
                            <label>Kapasitas</label>
                            <div class="input-wrapper"><input type="number" name="capacity_material" value="{{ old('capacity_material') }}" placeholder="Kapasitas" style="padding-right: 40px;"><span class="input-icon" style="font-size:11px;">Ton</span></div>
                        </div>
                    </div>

                    <div class="table-wrapper w-100 material">
                        <div class="table-input material w-100">
                            <div class="head">
                                <div class="table-column no"><span>No</span></div>
                                <div class="table-column main"><span>Jenis</span></div>
                                <div class="table-column small"><span>Sekarang</span></div>
                                <div class="table-column small"><span>Lalu</span></div>
                                <div class="table-column small"><span>Total</span></div>
                                <div class="table-column delete"><span>Hapus</span></div>
                            </div>
                            <div class="body">
                                <div class="table-column no"><span>1</span></div>
                                <div class="table-column main">
                                    <div class="table-input-wrapper"><span class="icon"><i class="fi fi-sr-marker"></i></span><input type="text" name="unloading_materials[0][raw_material_type]" placeholder="Tujuan"></div>
                                </div>
                                <div class="table-column small"><input type="number" name="unloading_materials[0][qty_current]" class="form-control-custom" placeholder="0"></div>
                                <div class="table-column small"><input type="number" name="unloading_materials[0][qty_prev]" class="form-control-custom" placeholder="0"></div>
                                <div class="table-column small"><input type="number" name="unloading_materials[0][qty_total]" class="form-control-custom" placeholder="0" readonly></div>
                                <div class="table-column delete"><button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button></div>
                            </div>
                            <button type="button" class="btn-tambah-baris"><i class="fi fi-rr-plus-small"></i> Tambah Baris</button>
                        </div>
                    </div>
                    <div class="petugas-card w-100 material">
                        <h5 class="card-title">Petugas</h5>
                        <div class="form-grid w-100">
                            <div class="form-group">
                                <label for="tally_kapal">Tally Kapal</label>
                                <input type="text" id="tally_kapal" name="tally_kapal" value="{{ old('tally_kapal') }}" placeholder="Masukkan Nama Tally Kapal">
                            </div>
                            <div class="form-group">
                                <label for="opr_forklift">Operator Forklift</label>
                                <input type="text" id="opr_forklift" name="opr_forklift" value="{{ old('opr_forklift') }}" placeholder="Nama Operator">
                            </div>
                        </div>
                        <div class="form-grid w-100">
                            <div class="form-group">
                                <label for="tally_pengiriman">Tally Pengiriman</label>
                                <input type="text" id="tally_pengiriman" name="tally_pengiriman" value="{{ old('tally_pengiriman') }}" placeholder="Masukkan Nama Tally Pengiriman">
                            </div>
                            <div class="form-group">
                                <label for="driver_petugas_bb">Driver</label>
                                <input type="text" id="driver_petugas_bb" name="driver_petugas_bb" value="{{ old('driver_petugas_bb') }}" placeholder="Masukkan Nama Driver">
                            </div>
                            <div class="form-group rentang-jam-group">
                                <label>Rentang Jam Kerja</label>
                                <div class="rentang-jam-wrapper">
                                    <div class="input-wrapper">
                                        <span class="input-icon" style="top: 8px;left: 15px; right: auto; color: var(--blue-main);"><i class="fi fi-br-clock"></i></span>
                                        <input type="text" name="material_work_start" class="time-picker-input" placeholder="00:00" style="padding: 8px 15px 8px 40px; border: none; width: 100%; outline: none; font-size: 12px; font-weight: 500; text-align: center;">
                                    </div>
                                    <i class="fi fi-rr-arrow-right" style="font-size: 12px; color: var(--dark-main);"></i>
                                    <div class="input-wrapper">
                                        <span class="input-icon" style="top:8px;left: 15px; right: auto; color: var(--red-main);"><i class="fi fi-br-clock"></i></span>
                                        <input type="text" name="material_work_end" class="time-picker-input" placeholder="00:00" style="padding: 8px 15px 8px 40px; border: none; width: 100%; outline: none; font-size: 12px; font-weight: 500; text-align: center;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-TAB 2: CONTAINER -->
                <div id="section-container" class="d-none flex-column align-items-start align-self-stretch w-100" style="gap: 25px;">
                    <div class="form-grid w-100">
                        <div class="form-group"><label>Nama Kapal</label><input type="text" name="ship_name_container" value="{{ old('ship_name_container') }}" placeholder="Masukkan Nama Kapal"></div>
                        <div class="form-group"><label>Agen</label><input type="text" name="agent_container" value="{{ old('agent_container') }}" placeholder="Masukkan Nama Agen"></div>
                        <div class="form-group"><label>Dermaga</label><input type="text" name="jetty_container" value="{{ old('jetty_container') }}" placeholder="Masukkan Dermaga"></div>
                        <div class="form-group">
                            <label>Kapasitas</label>
                            <div class="input-wrapper"><input type="number" name="capacity_container" value="{{ old('capacity_container') }}" placeholder="Kapasitas" style="padding-right: 40px;"><span class="input-icon" style="font-size:11px;">Ton</span></div>
                        </div>
                    </div>

                    <div class="table-wrapper w-100 container-content">
                        <div class="table-input w-100">
                            <div class="head">
                                <div class="table-column no"><span>No</span></div>
                                <div class="table-column main"><span>Jam</span></div>
                                <div class="table-column small"><span>Sekarang</span></div>
                                <div class="table-column small"><span>Lalu</span></div>
                                <div class="table-column small"><span>Total</span></div>
                                <div class="table-column small"><span>Ket</span></div>
                                <div class="table-column delete"><span>Hapus</span></div>
                            </div>
                            <div class="body">
                                <div class="table-column no"><span>1</span></div>
                                <div class="table-column main">
                                    <div class="table-input-wrapper"><span class="icon"><i class="fi fi-rr-clock"></i></span><input type="text" name="unloading_containers[0][time_text]" class="time-range-input" placeholder="23:00 - 04:00" autocomplete="off" inputmode="numeric" maxlength="13"></div>
                                </div>
                                <div class="table-column small"><input type="number" name="unloading_containers[0][qty_current]" class="form-control-custom" placeholder="0"></div>
                                <div class="table-column small"><input type="number" name="unloading_containers[0][qty_prev]" class="form-control-custom" placeholder="0"></div>
                                <div class="table-column small"><input type="number" name="unloading_containers[0][qty_total]" class="form-control-custom" placeholder="0" readonly></div>
                                <div class="table-column small">
                                    <input type="text" name="unloading_containers[0][status]" class="form-control-custom" placeholder="Ket" autocomplete="off" value="{{ old('unloading_containers.0.status') }}">
                                </div>
                                <div class="table-column delete"><button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button></div>
                            </div>
                            <button type="button" class="btn-tambah-baris"><i class="fi fi-rr-plus-small"></i> Tambah Baris</button>
                        </div>
                    </div>
                    <!-- Petugas Section Card Container -->
                    <div class="petugas-card w-100 container-content">
                        <h5 class="card-title">Petugas</h5>
                        <div class="form-grid w-100">
                            <div class="form-group">
                                <label for="tally_muat">Tally Muat</label>
                                <input type="text" id="tally_muat" name="tally_muat" value="{{ old('tally_muat') }}" placeholder="Masukkan Nama Tally Kapal">
                            </div>
                            <div class="form-group">
                                <label for="tally_gudang">Tally Gudang</label>
                                <input type="text" id="tally_gudang" name="tally_gudang" value="{{ old('tally_gudang') }}" placeholder="Nama Tally">
                            </div>
                            <div class="form-group">
                                <label for="driver_petugas_cont">Driver</label>
                                <input type="text" id="driver_petugas_cont" name="driver_petugas_cont" value="{{ old('driver_petugas_cont') }}" placeholder="Nama Driver">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-button d-flex justify-content-between align-items-center align-self-stretch mt-5">
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
