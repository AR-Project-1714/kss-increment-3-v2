    <div class="box-form form-step d-none flex-column align-items-start align-self-stretch gap-10 br-10 white-bg" id="step-cek-unit" style="box-shadow: 0 2px 4px 0 var(--blue-main-10);">
        <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
            <div class="title-form d-flex align-items-center gap-10">
                <span class="icon-title-form"><i class="fi fi-sr-pulse"></i></span><span class="fw-600">Form Cek Unit</span>
            </div>
            <div class="counter-form">Form 6 dari 7</div>
        </div>
        <div class="content-form d-flex flex-column align-items-center align-self-stretch w-100">
            <div class="step-info-note">
                <i class="fi fi-rr-info"></i>
                <span>Periksa kondisi <strong>Unit Kendaraan</strong>, <strong>Inventaris</strong>, dan <strong>Lingkungan Shelter</strong> saat terima dan saat diserahkan. Klik <strong>Set Semua Baik</strong> untuk menandai sekaligus, lalu ubah item yang bermasalah.</span>
            </div>

            <!-- SUB TAB NAVIGATION (Kendaraan, Inventaris, Lingkungan) -->
            <div class="inspection-header d-flex justify-content-between align-items-end align-self-stretch mb-2">
                <div class="tab-group unit">
                    <div class="tab-sections active" id="subtab-unit">
                        <span class="icon"><i class="fi fi-rr-truck-side"></i></span>
                        <span>Unit Kendaraan</span>
                    </div>
                    <div class="tab-sections" id="subtab-inventaris">
                        <span class="icon"><i class="fi fi-rr-box"></i></span>
                        <span>Inventaris</span>
                    </div>
                    <div class="tab-sections" id="subtab-lingkungan">
                        <span class="icon"><i class="fi fi-rr-home"></i></span>
                        <span>Lingkungan Shelter</span>
                    </div>
                </div>
                <button class="set-all-good" type="button">
                    <span class="icon"><i class="fi fi-rr-check"></i></span>
                    Set Semua Baik
                </button>
            </div>

            <!-- 1. BAGIAN UNIT KENDARAAN -->
            <div class="table-wrapper w-100 material" id="section-unit">
                <div class="table-input material w-100">
                    <!-- Table Head -->
                    <div class="head">
                        <div class="table-column no"><span>No</span></div>
                        <div class="table-column main"><span>Nama Unit</span></div>
                        <div class="table-column amount"><span>BBM (Liter)</span></div>
                        <div class="table-column radio"><span>Kondisi Terima</span></div>
                        <div class="table-column radio"><span>Kondisi Diserahkan</span></div>
                    </div>

                    <!-- Table Row 1 -->
                    <div class="body">
                        <div class="table-column no">
                            <span>1</span>
                        </div>
                        <div class="table-column main">
                            <div class="table-input-wrapper">
                                <span class="icon"><i class="fi fi-sr-truck-side"></i></span>
                                <input type="text" name="unit_logs[0][item_name]" placeholder="Trailer KSS-01" value="Trailer KSS-01">
                            </div>
                        </div>
                        <div class="table-column amount">
                            <div class="table-input-wrapper">
                                <span class="icon"><i class="fi fi-rr-gas-pump text-muted"></i></span>
                                <input type="number" name="unit_logs[0][fuel_level]" placeholder="0">
                                <span class="fsize-12 text-muted">L</span>
                            </div>
                        </div>

                        <div class="table-column radio">
                            <div class="radio-group-custom">
                                <div class="radio-custom baik">
                                    <input type="radio" name="unit_logs[0][condition_received]" id="unit_terima_baik_1" value="Baik" checked>
                                    <label for="unit_terima_baik_1"><i class="fi fi-rr-check"></i> Baik</label>
                                </div>
                                <div class="radio-custom rusak">
                                    <input type="radio" name="unit_logs[0][condition_received]" id="unit_terima_rusak_1" value="Rusak">
                                    <label for="unit_terima_rusak_1"><i class="fi fi-rr-cross-small"></i> Rusak</label>
                                </div>
                            </div>
                        </div>

                        <div class="table-column radio">
                            <div class="radio-group-custom">
                                <div class="radio-custom baik">
                                    <input type="radio" name="unit_logs[0][condition_handed_over]" id="unit_serah_baik_1" value="Baik" checked>
                                    <label for="unit_serah_baik_1"><i class="fi fi-rr-check"></i> Baik</label>
                                </div>
                                <div class="radio-custom rusak">
                                    <input type="radio" name="unit_logs[0][condition_handed_over]" id="unit_serah_rusak_1" value="Rusak">
                                    <label for="unit_serah_rusak_1"><i class="fi fi-rr-cross-small"></i> Rusak</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-tambah-baris">
                        <i class="fi fi-rr-plus-small"></i> Tambah Baris
                    </button>
                </div>
            </div>

            <!-- 2. BAGIAN INVENTARIS (Disembunyikan secara default) -->
            <div class="table-wrapper w-100 material d-none" id="section-inventaris">
                <div class="table-input material w-100">
                    <div class="head">
                        <div class="table-column no"><span>No</span></div>
                        <div class="table-column main"><span>Nama Barang</span></div>
                        <div class="table-column amount"><span>Jumlah Barang</span></div>
                        <div class="table-column radio"><span>Kondisi Terima</span></div>
                        <div class="table-column radio"><span>Kondisi Diserahkan</span></div>
                    </div>

                    <div class="body">
                        <div class="table-column no"><span>1</span></div>
                        <div class="table-column main">
                            <div class="table-input-wrapper">
                                <span class="icon"><i class="fi fi-sr-box"></i></span>
                                <input type="text" name="inventory_logs[0][item_name]" placeholder="HT (Handy Talky)" value="HT (Handy Talky)">
                            </div>
                        </div>
                        <div class="table-column amount">
                            <div class="table-input-wrapper">
                                <span class="icon"><i class="fi fi-rr-supplier-alt text-muted"></i></span>
                                <input type="number" name="inventory_logs[0][quantity]" placeholder="0" value="2">
                                <span class="fsize-12 text-muted">Pcs</span>
                            </div>
                        </div>

                        <div class="table-column radio">
                            <div class="radio-group-custom">
                                <div class="radio-custom baik">
                                    <input type="radio" name="inventory_logs[0][condition_received]" id="inv_terima_baik_1" value="Baik" checked>
                                    <label for="inv_terima_baik_1"><i class="fi fi-rr-check"></i> Baik</label>
                                </div>
                                <div class="radio-custom rusak">
                                    <input type="radio" name="inventory_logs[0][condition_received]" id="inv_terima_rusak_1" value="Rusak">
                                    <label for="inv_terima_rusak_1"><i class="fi fi-rr-cross-small"></i> Rusak</label>
                                </div>
                            </div>
                        </div>

                        <div class="table-column radio">
                            <div class="radio-group-custom">
                                <div class="radio-custom baik">
                                    <input type="radio" name="inventory_logs[0][condition_handed_over]" id="inv_serah_baik_1" value="Baik" checked>
                                    <label for="inv_serah_baik_1"><i class="fi fi-rr-check"></i> Baik</label>
                                </div>
                                <div class="radio-custom rusak">
                                    <input type="radio" name="inventory_logs[0][condition_handed_over]" id="inv_serah_rusak_1" value="Rusak">
                                    <label for="inv_serah_rusak_1"><i class="fi fi-rr-cross-small"></i> Rusak</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-tambah-baris">
                        <i class="fi fi-rr-plus-small"></i> Tambah Baris
                    </button>
                </div>
            </div>

            <!-- 3. BAGIAN LINGKUNGAN SHELTER (Disembunyikan secara default) -->
            {{--
                Baris diisi otomatis oleh JS dari master "Data Lingkungan Operasi"
                (dikelola admin), dikelompokkan per kategori. Nama item bisa diedit,
                baris bisa ditambah/dihapus per laporan. Lihat renderShelterRows()
                di partials/report-form.blade.php.
            --}}
            <div class="table-wrapper w-100 material d-none" id="section-lingkungan">
                <div class="table-input material w-100">
                    <div class="head">
                        <div class="table-column no"><span>No</span></div>
                        <div class="table-column main"><span>Item</span></div>
                        <div class="table-column radio"><span>Kondisi Terima</span></div>
                        <div class="table-column radio"><span>Kondisi Diserahkan</span></div>
                        <div class="table-column delete"><span>Hapus</span></div>
                    </div>

                    <button type="button" class="btn-tambah-baris"><i class="fi fi-rr-plus-small"></i> Tambah Baris</button>
                </div>
            </div>

            <!-- BUTTON NAVIGASI BAWAH (Selalu Tampil) -->
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
