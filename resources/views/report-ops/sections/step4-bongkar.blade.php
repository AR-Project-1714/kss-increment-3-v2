    <div class="box-form form-step d-none flex-column align-items-start align-self-stretch gap-10 br-10 white-bg" id="step-bongkar" style="box-shadow: 0 2px 4px 0 var(--blue-main-10);">
        <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
            <div class="title-form d-flex align-items-center gap-10">
                <span class="icon-title-form"><i class="fi fi-sr-box-open"></i></span>
                <span class="fw-600">Form Bongkar</span>
                <x-form-info-popover id="info-form-ops-bongkar" label="Informasi Form Bongkar">
                    Pilih jenis kegiatan: <strong>Bongkar Bahan Baku</strong> atau <strong>Bongkar/Muat Container</strong>. Gunakan tab <strong>Kegiatan</strong> bila menangani lebih dari satu kapal/kegiatan. Bongkar bahan baku dicatat per <strong>kelompok kemasan</strong>. Pilih kemasan pada tiap kelompok, lalu isi jenis bahan dan jumlah <strong>Bag</strong>; konversi ke Ton dihitung sistem sesuai kemasan yang dipilih. Gunakan <strong>Tambah Kelompok Kemasan</strong> bila satu kapal membongkar lebih dari satu jenis kemasan, dan hapus kelompok yang tidak terpakai. Bila kemasannya belum ada pada daftar, pilih <strong>Tambah Kemasan Baru</strong> di bagian bawah dropdown lalu isi nama dan perbandingan Bag ke Ton-nya; kemasan itu berlaku untuk laporan ini saja. Kolom <strong>Lalu</strong> terisi otomatis dari shift sebelumnya dan <strong>Akumulasi</strong> dihitung sistem. Pada tabel container, kolom <strong>Empty / Full</strong> wajib diisi untuk setiap baris yang ada jumlahnya: <strong>Empty</strong> berarti bongkar, <strong>Full</strong> berarti muat. Isian itulah yang menentukan baris masuk ke <strong>Bongkar Container</strong> atau <strong>Muat Container</strong> pada laporan kinerja.
                </x-form-info-popover>
            </div>
            <div class="counter-form">Form 5 dari 8</div>
        </div>

        <div class="content-form d-flex flex-column align-items-center align-self-stretch w-100">
            <div class="form-bongkar d-flex flex-column align-items-start align-self-stretch" style="gap: 25px;">
                <div class="tab-group tab-group-bongkar" id="bongkar-tabs-group">
                    <a class="tab-sections active" id="tab-btn-bahan-baku">
                        <span class="icon"><i class="fi fi-rr-box-open"></i></span>
                        <span>Bongkar Bahan Baku</span>
                    </a>
                    <a class="tab-sections tab-container" id="tab-btn-container">
                        <span class="icon"><i class="fi fi-rr-truck-container"></i></span>
                        <span>Bongkar Container</span>
                    </a>
                </div>

                <!-- SUB-TAB 1: BAHAN BAKU -->
                <div id="section-bahan-baku" class="d-flex flex-column align-items-start align-self-stretch w-100" style="gap: 15px;">
                    <div class="form-bongkar-activity-wrapper w-100">
                        <div class="tab-activity d-flex align-items-center gap-10">
                            <button type="button" class="btn-activity active">Kegiatan 1</button>
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

                    <div class="form-grid w-100">
                        <div class="form-group"><label>Nama Kapal</label><input type="hidden" name="ship_operation_material_id_1" value="{{ old('ship_operation_material_id_1') }}"><input type="text" name="ship_name_material_1" value="{{ old('ship_name_material_1') }}" placeholder="Masukkan Nama Kapal"></div>
                        <div class="form-group"><label>Agen</label><input type="text" name="agent_material_1" value="{{ old('agent_material_1') }}" placeholder="Masukkan Nama Agen"></div>
                    </div>
                    <div class="form-grid w-100">
                        <div class="form-group"><label>Dermaga</label><input type="text" name="jetty_material_1" value="{{ old('jetty_material_1') }}" placeholder="Masukkan Dermaga"></div>
                        <div class="form-group">
                            <label>Kapasitas</label>
                            <div class="input-wrapper"><input type="number" name="capacity_material_1" value="{{ old('capacity_material_1') }}" placeholder="Kapasitas" style="padding-right: 40px;"><span class="input-icon" style="font-size:11px;">Ton</span></div>
                        </div>
                    </div>

                    <div class="ship-operation-status">
                        <span class="ship-operation-status-label">
                            Status pekerjaan kapal
                            <span class="status-info-icon" tabindex="0" role="button" aria-label="Info status pekerjaan kapal">
                                <i class="fi fi-rr-info"></i>
                                <span class="status-info-tip" role="tooltip">Pilih "Masih Berjalan" agar kapal ini muncul sebagai saran otomatis pada laporan shift berikutnya. Pilih "Selesai" bila pekerjaan sudah rampung, saran ini akan hilang dari daftar.</span>
                            </span>
                        </span>
                        <div class="ship-operation-status-options">
                            <label>
                                <input type="radio" name="ship_operation_material_status_1" value="active" {{ old('ship_operation_material_status_1') === 'active' ? 'checked' : '' }}>
                                <span>Masih Berjalan</span>
                            </label>
                            <label>
                                <input type="radio" name="ship_operation_material_status_1" value="completed" {{ old('ship_operation_material_status_1') === 'completed' ? 'checked' : '' }}>
                                <span>Selesai</span>
                            </label>
                        </div>
                    </div>

                    @php
                        $materialPackageOptions = \App\Support\MaterialPackaging::active();
                        $materialPackageDefaults = \App\Support\MaterialPackaging::defaults();
                        $materialPackageFamily = static fn (string $code): string => str_starts_with($code, 'jumbo_') ? 'jumbo' : 'bag';
                    @endphp

                    <div class="material-package-ledger w-100" data-material-package-ledger data-material-package-defaults="{{ collect($materialPackageDefaults)->pluck('code')->implode(',') }}">
                        @foreach ($materialPackageDefaults as $index => $package)
                            <section class="material-package-group" data-material-package-group data-material-package-family="{{ $materialPackageFamily($package['code']) }}" data-material-package-code="{{ $package['code'] }}" data-material-package-type="{{ $package['label'] }}" data-material-tonnage-factor="{{ $package['tonPerBag'] }}" aria-label="Kelompok kemasan bahan baku">
                                {{-- Kepala kelompok merangkap pemicu buka/tutup, mengikuti pola
                                     akordeon lokasi pada Inspeksi K3. Kontrol di dalamnya
                                     (pemilih kemasan, tombol hapus) ditandai data-noprop agar
                                     kliknya tidak ikut menutup kelompok. --}}
                                <div class="material-package-group__header" data-material-package-head>
                                    <button type="button" class="material-package-group__toggle" data-material-package-toggle data-noprop aria-expanded="true" aria-label="Sembunyikan rincian kemasan">
                                        <i class="fi fi-rr-angle-small-down" aria-hidden="true"></i>
                                    </button>
                                    <div class="material-package-group__identity">
                                        <span class="material-package-group__icon" aria-hidden="true"><i class="fi fi-sr-box"></i></span>
                                        <div class="material-package-group__picker" data-noprop>
                                            <div class="input-wrapper material-package-group__field">
                                                {{-- Bukan `native-select`: dropdown ini isinya berubah-ubah
                                                     (kemasan tambahan, kemasan yang sudah dipakai kelompok
                                                     lain), jadi kontrol kustomnya disusun ulang sendiri
                                                     dengan anatomi yang sama seperti dropdown lain. --}}
                                                <select class="custom-input material-package-native-select" data-material-package-select aria-label="Jenis kemasan">
                                                    @foreach ($materialPackageOptions as $option)
                                                        <option value="{{ $option['code'] }}"
                                                            data-package-family="{{ $materialPackageFamily($option['code']) }}"
                                                            data-package-label="{{ $option['label'] }}"
                                                            data-package-factor="{{ $option['tonPerBag'] }}"
                                                            data-package-hint="{{ $option['hint'] }}"
                                                            @selected($option['code'] === $package['code'])>Kemasan {{ $option['label'] }}</option>
                                                    @endforeach
                                                    {{-- Jalan keluar untuk kemasan yang belum pernah tercatat:
                                                         petugas mendaftarkannya sendiri lewat pop-up, berlaku
                                                         untuk laporan yang sedang diisi saja. --}}
                                                    <option value="__new__" data-material-package-new>+ Tambah Kemasan Baru…</option>
                                                </select>
                                                <i class="fi fi-rr-angle-small-down input-icon"></i>
                                            </div>
                                            <span class="material-package-group__hint" data-material-package-hint>{{ $package['hint'] }}</span>
                                        </div>
                                    </div>
                                    {{-- Ringkasan hanya muncul ketika kelompok ditutup: saat terbuka,
                                         angkanya sudah ada pada baris Subtotal di bawah tabel. --}}
                                    <div class="material-package-group__summary" data-material-package-summary aria-live="polite">
                                        <span class="material-package-group__rows">
                                            <i class="fi fi-rr-list-check" aria-hidden="true"></i>
                                            <b data-material-package-rowcount>1</b> bahan
                                        </span>
                                        <span class="material-package-group__figure">
                                            <small>Sekarang</small>
                                            <span><b data-material-summary-bag>0</b> Bag</span>
                                            <i aria-hidden="true">/</i>
                                            <span><b data-material-summary-tonnage>0</b> Ton</span>
                                        </span>
                                    </div>
                                    <button type="button" class="material-package-group__remove" data-material-package-remove data-noprop aria-label="Hapus kelompok kemasan ini" title="Hapus kelompok kemasan ini">
                                        <i class="fi fi-rr-trash" aria-hidden="true"></i>
                                    </button>
                                </div>

                                <div class="material-package-group__body" data-material-package-body>
                                <div class="table-wrapper w-100 material">
                                    <div class="table-input material w-100">
                                        <div class="head">
                                            <div class="table-column no"><span>No</span></div>
                                            <div class="table-column main"><span>Jenis Bahan Baku</span></div>
                                            <div class="table-column small"><span>Sekarang</span></div>
                                            <div class="table-column small"><span>Lalu</span></div>
                                            <div class="table-column small"><span>Akumulasi</span></div>
                                            <div class="table-column delete"><span>Hapus</span></div>
                                        </div>
                                        <div class="body">
                                            {{-- Kode kemasan adalah penentu konversi di server; label ikut
                                                 dikirim hanya sebagai teks pencarian laporan. --}}
                                            <input type="hidden" name="unloading_materials_1[{{ $index }}][packaging_code]" value="{{ $package['code'] }}">
                                            <input type="hidden" name="unloading_materials_1[{{ $index }}][packaging_type]" value="{{ $package['label'] }}">
                                            {{-- Terisi hanya untuk kemasan tambahan; kemasan katalog selalu
                                                 memakai faktor dari server. --}}
                                            <input type="hidden" name="unloading_materials_1[{{ $index }}][packaging_factor]" value="">
                                            <div class="table-column no"><span>1</span></div>
                                            <div class="table-column main">
                                                <div class="table-input-wrapper"><span class="icon"><i class="fi fi-sr-marker"></i></span><input type="text" name="unloading_materials_1[{{ $index }}][raw_material_type]" maxlength="150" placeholder="Masukkan jenis bahan baku"></div>
                                            </div>
                                            @foreach (['current' => 'Sekarang', 'prev' => 'Lalu', 'total' => 'Akumulasi'] as $field => $fieldLabel)
                                                <div class="table-column small">
                                                    <div class="quantity-measure">
                                                        <div class="quantity-input">
                                                            <input type="number" min="0" step="1" name="unloading_materials_1[{{ $index }}][qty_{{ $field }}]" class="form-control-custom" placeholder="0" inputmode="numeric" aria-label="{{ $fieldLabel }} dalam Bag" @readonly($field === 'total')>
                                                            <span class="quantity-input__unit">Bag</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <div class="table-column delete"><button type="button" class="btn-trash-row" aria-label="Hapus baris bahan baku"><i class="fi fi-rr-trash"></i></button></div>
                                        </div>
                                        <button type="button" class="btn-tambah-baris"><i class="fi fi-rr-plus-small"></i> Tambah Bahan</button>
                                    </div>
                                </div>
                                <div class="material-package-scroll-hint" aria-hidden="true"><i class="fi fi-rr-arrow-small-right"></i> Geser tabel untuk melihat seluruh kolom</div>

                                <div class="material-package-subtotal" aria-live="polite">
                                    <span class="material-package-subtotal__label">Subtotal <span data-material-package-title>{{ $package['label'] }}</span></span>
                                    @foreach (['current' => 'Sekarang', 'previous' => 'Lalu', 'total' => 'Akumulasi'] as $subtotalKey => $subtotalLabel)
                                        <span>
                                            <small>{{ $subtotalLabel }}</small>
                                            {{-- Angka dan satuannya dipasangkan agar keduanya tidak pernah
                                                 terpisah baris saat subtotal dibungkus di layar sempit.
                                                 Kemasan 25 Kg menghasilkan jumlah Bag berdigit banyak. --}}
                                            <span class="material-package-subtotal__value">
                                                <span class="material-package-subtotal__pair"><strong data-material-subtotal="{{ $subtotalKey }}">0</strong><em>Bag</em></span>
                                                <i aria-hidden="true">/</i>
                                                <span class="material-package-subtotal__pair"><b data-material-subtotal-tonnage="{{ $subtotalKey }}">0</b><em>Ton</em></span>
                                            </span>
                                        </span>
                                    @endforeach
                                </div>
                                </div>
                            </section>
                        @endforeach

                        {{-- Menambah kelompok, bukan menambah jenis kemasan. Jenis baru
                             didaftarkan lewat pilihan terakhir pada dropdown kemasan. --}}
                        <button type="button" class="material-package-add" data-material-package-add>
                            <span class="icon" aria-hidden="true"><i class="fi fi-rr-plus-small"></i></span>
                            <span>Tambah Kelompok Kemasan</span>
                        </button>
                    </div>
                    <div class="petugas-card w-100 material">
                        <h5 class="card-title">Petugas</h5>
                        <div class="form-grid w-100">
                            <div class="form-group">
                                <label for="tally_kapal_1">Tally Kapal</label>
                                <input type="text" id="tally_kapal_1" name="tally_kapal_1" value="{{ old('tally_kapal_1') }}" placeholder="Masukkan Nama Tally Kapal">
                            </div>
                            <div class="form-group">
                                <label for="tally_pengiriman_1">Tally Pengiriman</label>
                                <input type="text" id="tally_pengiriman_1" name="tally_pengiriman_1" value="{{ old('tally_pengiriman_1') }}" placeholder="Masukkan Nama Tally Pengiriman">
                            </div>
                        </div>
                        <div class="form-grid w-100">
                            <div class="form-group">
                                <label for="opr_forklift_1">Operator Forklift</label>
                                <input type="text" id="opr_forklift_1" name="opr_forklift_1" value="{{ old('opr_forklift_1') }}" placeholder="Nama Operator">
                            </div>
                            <div class="form-group">
                                <label for="no_forklift_bb_1">Nomor Forklift</label>
                                <input type="text" id="no_forklift_bb_1" name="no_forklift_bb_1" value="{{ old('no_forklift_bb_1') }}" placeholder="Nomor Forklift">
                            </div>
                        </div>
                        <div class="form-grid w-100">
                            <div class="form-group">
                                <label for="driver_petugas_bb_1">Driver</label>
                                <input type="text" id="driver_petugas_bb_1" name="driver_petugas_bb_1" value="{{ old('driver_petugas_bb_1') }}" placeholder="Masukkan Nama Driver">
                            </div>
                            <div class="form-group">
                                <label for="truck_petugas_bb_1">No Truck</label>
                                <input type="text" id="truck_petugas_bb_1" name="truck_petugas_bb_1" value="{{ old('truck_petugas_bb_1') }}" placeholder="Nomor Truck">
                            </div>
                        </div>
                        <div class="form-grid w-100">
                            <div class="form-group rentang-jam-group">
                                <label>Rentang Jam Kerja</label>
                                <div class="rentang-jam-wrapper">
                                    <div class="input-wrapper">
                                        <span class="input-icon" style="top: 8px;left: 15px; right: auto; color: var(--blue-main);"><i class="fi fi-br-clock"></i></span>
                                        <input type="text" name="material_work_start_1" class="time-picker-input" placeholder="00:00" style="padding: 8px 15px 8px 40px; border: none; width: 100%; outline: none; font-size: 12px; font-weight: 500; text-align: center;">
                                    </div>
                                    <i class="fi fi-rr-arrow-right" style="font-size: 12px; color: var(--dark-main);"></i>
                                    <div class="input-wrapper">
                                        <span class="input-icon" style="top:8px;left: 15px; right: auto; color: var(--red-main);"><i class="fi fi-br-clock"></i></span>
                                        <input type="text" name="material_work_end_1" class="time-picker-input" placeholder="00:00" style="padding: 8px 15px 8px 40px; border: none; width: 100%; outline: none; font-size: 12px; font-weight: 500; text-align: center;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="activity-pane-end d-none" aria-hidden="true"></div>
                </div>

                <!-- SUB-TAB 2: CONTAINER -->
                <div id="section-container" class="d-none flex-column align-items-start align-self-stretch w-100" style="gap: 15px;">
                    <div class="form-bongkar-activity-wrapper w-100">
                        <div class="tab-activity d-flex align-items-center gap-10">
                            <button type="button" class="btn-activity active">Kegiatan 1</button>
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

                    <div class="form-grid w-100">
                        <div class="form-group"><label>Nama Kapal</label><input type="hidden" name="ship_operation_container_id_1" value="{{ old('ship_operation_container_id_1') }}"><input type="text" name="ship_name_container_1" value="{{ old('ship_name_container_1') }}" placeholder="Masukkan Nama Kapal"></div>
                        <div class="form-group"><label>Agen</label><input type="text" name="agent_container_1" value="{{ old('agent_container_1') }}" placeholder="Masukkan Nama Agen"></div>
                    </div>
                    <div class="form-grid w-100">
                        <div class="form-group"><label>Dermaga</label><input type="text" name="jetty_container_1" value="{{ old('jetty_container_1') }}" placeholder="Masukkan Dermaga"></div>
                        <div class="form-group container-capacity-group">
                            <label>Kapasitas</label>
                            <div class="container-capacity-fields">
                                <div class="container-capacity-field">
                                    <span class="capacity-label">Empty =</span>
                                    <div class="input-wrapper">
                                        <input type="number" name="capacity_container_1" value="{{ old('capacity_container_1') }}" placeholder="0" style="padding-right: 48px;">
                                        <span class="input-icon" style="font-size:11px;">Teus</span>
                                    </div>
                                </div>
                                <span class="capacity-separator">/</span>
                                <div class="container-capacity-field">
                                    <span class="capacity-label">Full =</span>
                                    <div class="input-wrapper">
                                        <input type="number" name="capacity_full_container_1" value="{{ old('capacity_full_container_1') }}" placeholder="0" style="padding-right: 48px;">
                                        <span class="input-icon" style="font-size:11px;">Teus</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ship-operation-status">
                        <span class="ship-operation-status-label">
                            Status pekerjaan kapal
                            <span class="status-info-icon" tabindex="0" role="button" aria-label="Info status pekerjaan kapal">
                                <i class="fi fi-rr-info"></i>
                                <span class="status-info-tip" role="tooltip">Pilih "Masih Berjalan" agar kapal ini muncul sebagai saran otomatis pada laporan shift berikutnya. Pilih "Selesai" bila pekerjaan sudah rampung, saran ini akan hilang dari daftar.</span>
                            </span>
                        </span>
                        <div class="ship-operation-status-options">
                            <label>
                                <input type="radio" name="ship_operation_container_status_1" value="active" {{ old('ship_operation_container_status_1') === 'active' ? 'checked' : '' }}>
                                <span>Masih Berjalan</span>
                            </label>
                            <label>
                                <input type="radio" name="ship_operation_container_status_1" value="completed" {{ old('ship_operation_container_status_1') === 'completed' ? 'checked' : '' }}>
                                <span>Selesai</span>
                            </label>
                        </div>
                    </div>

                    <div class="table-wrapper w-100 container-content">
                        <div class="table-input w-100">
                            <div class="head">
                                <div class="table-column no"><span>No</span></div>
                                <div class="table-column main"><span>Jam</span></div>
                                <div class="table-column small"><span>Sekarang <small>Teus</small></span></div>
                                <div class="table-column small"><span>Lalu <small>Teus</small></span></div>
                                <div class="table-column small"><span>Akumulasi <small>Teus</small></span></div>
                                <div class="table-column small"><span>Keterangan</span></div>
                                <div class="table-column delete"><span>Hapus</span></div>
                            </div>
                            <div class="body">
                                <div class="table-column no"><span>1</span></div>
                                <div class="table-column main">
                                    <div class="table-input-wrapper"><span class="icon"><i class="fi fi-rr-clock"></i></span><input type="text" name="unloading_containers_1[0][time_text]" class="time-range-input" placeholder="00:00 - 00:00" autocomplete="off" inputmode="numeric" maxlength="13"></div>
                                </div>
                                @foreach (['current' => 'Sekarang', 'prev' => 'Lalu', 'total' => 'Akumulasi'] as $field => $fieldLabel)
                                    <div class="table-column small">
                                        <div class="quantity-input quantity-input--teus">
                                            <input type="number" min="0" step="1" name="unloading_containers_1[0][qty_{{ $field }}]" class="form-control-custom" placeholder="0" inputmode="numeric" aria-label="{{ $fieldLabel }} container dalam Teus" @readonly($field === 'total')>
                                            <span class="quantity-input__unit">Teus</span>
                                        </div>
                                    </div>
                                @endforeach
                                {{-- Isian ini yang memisahkan baris menjadi kegiatan
                                     Bongkar Container atau Muat Container di laporan
                                     manajer: Empty berarti bongkar, Full berarti muat.
                                     Tetap boleh diketik bebas, tetapi isinya
                                     diseragamkan di server (ContainerStatusNormalizer)
                                     supaya ejaan seperti "Container empty" tidak lagi
                                     jatuh di luar kedua kegiatan. --}}
                                <div class="table-column small">
                                    <input type="text" name="unloading_containers_1[0][status]" class="form-control-custom" list="container-status-options" placeholder="Empty / Full" autocomplete="off" value="{{ old('unloading_containers_1.0.status') }}">
                                </div>
                                <div class="table-column delete"><button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button></div>
                            </div>
                            <button type="button" class="btn-tambah-baris"><i class="fi fi-rr-plus-small"></i> Tambah Baris</button>
                        </div>
                    </div>

                    {{-- Saran cepat penanda container. Empty = bongkar, Full = muat. --}}
                    <datalist id="container-status-options">
                        <option value="Empty"></option>
                        <option value="Full"></option>
                    </datalist>

                    <!-- Petugas Section Card Container -->
                    <div class="petugas-card w-100 container-content">
                        <h5 class="card-title">Petugas</h5>
                        <div class="form-grid w-100">
                            <div class="form-group">
                                <label for="tally_muat_1">Tally Muat</label>
                                <input type="text" id="tally_muat_1" name="tally_muat_1" value="{{ old('tally_muat_1') }}" placeholder="Masukkan Nama Tally Kapal">
                            </div>
                            <div class="form-group">
                                <label for="tally_gudang_1">Tally Gudang</label>
                                <input type="text" id="tally_gudang_1" name="tally_gudang_1" value="{{ old('tally_gudang_1') }}" placeholder="Nama Tally">
                            </div>
                        </div>
                        <div class="form-grid w-100">
                            <div class="form-group">
                                <label for="driver_petugas_cont_1">Driver</label>
                                <input type="text" id="driver_petugas_cont_1" name="driver_petugas_cont_1" value="{{ old('driver_petugas_cont_1') }}" placeholder="Nama Driver">
                            </div>
                            <div class="form-group">
                                <label for="truck_petugas_cont_1">No Truck</label>
                                <input type="text" id="truck_petugas_cont_1" name="truck_petugas_cont_1" value="{{ old('truck_petugas_cont_1') }}" placeholder="Nomor Truck">
                            </div>
                        </div>
                    </div>

                    <div class="activity-pane-end d-none" aria-hidden="true"></div>
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
