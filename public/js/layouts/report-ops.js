document.addEventListener('DOMContentLoaded', function() {

                // ==========================================
                // 1. STICKY HEADER
                // ==========================================
                const contentHeader = document.querySelector('.content-header');
                if (contentHeader) {
                    const headerWrapper = document.createElement('div');
                    headerWrapper.className = 'header-wrapper';
                    headerWrapper.style.width = '100%';
                    headerWrapper.style.position = 'relative';
                    contentHeader.parentNode.insertBefore(headerWrapper, contentHeader);
                    headerWrapper.appendChild(contentHeader);

                    window.addEventListener('scroll', () => {
                        const wrapperRect = headerWrapper.getBoundingClientRect();
                        if (wrapperRect.bottom < 0) {
                            if (!contentHeader.classList.contains('is-sticky')) {
                                headerWrapper.style.height = `${headerWrapper.offsetHeight}px`;
                                contentHeader.classList.add('is-sticky');
                                requestAnimationFrame(() => contentHeader.classList.add('show-sticky'));
                            }
                        } else {
                            if (contentHeader.classList.contains('is-sticky')) {
                                contentHeader.classList.remove('show-sticky');
                                contentHeader.classList.remove('is-sticky');
                                headerWrapper.style.height = 'auto';
                            }
                        }
                    });
                }

                // ==========================================
                // 2. DARK MODE TOGGLE LOGIC
                // ==========================================
                const themeBtn = document.getElementById('themeToggle');
                const themeIcon = document.getElementById('themeIcon');
                const body = document.body;
                let isDarkMode = localStorage.getItem('theme') === 'dark';

                if (isDarkMode) enableDarkMode(false);

                if(themeBtn && themeIcon) {
                    themeBtn.addEventListener('click', () => {
                        isDarkMode = !isDarkMode;
                        if (isDarkMode) {
                            themeIcon.classList.add('animate-out-up');
                            setTimeout(() => {
                                enableDarkMode(true);
                                localStorage.setItem('theme', 'dark');
                            }, 200);
                        } else {
                            themeIcon.classList.add('animate-out-down');
                            setTimeout(() => {
                                disableDarkMode(true);
                                localStorage.setItem('theme', 'light');
                            }, 200);
                        }
                    });
                }

                function enableDarkMode(animate) {
                    body.classList.add('dark-mode');
                    if(animate && themeIcon) {
                        themeIcon.className = 'fi fi-rr-moon';
                        themeIcon.classList.remove('animate-out-up', 'animate-out-down');
                        themeIcon.style.transition = 'none';
                        themeIcon.classList.add('prepare-from-bottom');
                        void themeIcon.offsetWidth;
                        themeIcon.style.transition = '';
                        themeIcon.classList.remove('prepare-from-bottom');
                    } else if (themeIcon) {
                        themeIcon.className = 'fi fi-rr-moon';
                    }
                }

                function disableDarkMode(animate) {
                    body.classList.remove('dark-mode');
                    if(animate && themeIcon) {
                        themeIcon.className = 'fi fi-rr-sun';
                        themeIcon.classList.remove('animate-out-up', 'animate-out-down');
                        themeIcon.style.transition = 'none';
                        themeIcon.classList.add('prepare-from-top');
                        void themeIcon.offsetWidth;
                        themeIcon.style.transition = '';
                        themeIcon.classList.remove('prepare-from-top');
                    } else if (themeIcon) {
                        themeIcon.className = 'fi fi-rr-sun';
                    }
                }

                // 2B. TOAST MESSAGE — ditangani komponen bersama (partials/toast.blade.php).
                //     window.showReportToast tersedia sebagai alias dari window.kssToast.

                function initSlidingTabIndicators() {
                    [
                        { containerSelector: '.tab-content', itemSelector: '.list-tab', indicatorClass: 'tab-slide-indicator' },
                        { containerSelector: '.tab-form', itemSelector: '.list-form-tab', indicatorClass: 'tab-form-indicator' },
                    ].forEach(config => {
                        document.querySelectorAll(config.containerSelector).forEach(container => {
                            let indicator = container.querySelector(`.${config.indicatorClass}`);
                            if (!indicator) {
                                indicator = document.createElement('div');
                                indicator.className = config.indicatorClass;
                                container.appendChild(indicator);
                            }

                            const updateIndicator = () => {
                                const active = container.querySelector(`${config.itemSelector}.active`);
                                if (!active) {
                                    indicator.style.opacity = '0';
                                    return;
                                }

                                indicator.style.opacity = '1';
                                indicator.style.width = `${active.offsetWidth}px`;
                                indicator.style.transform = `translateX(${active.offsetLeft}px)`;
                            };

                            requestAnimationFrame(updateIndicator);

                            if (container.dataset.slidingIndicatorBound === 'true') return;

                            container.dataset.slidingIndicatorBound = 'true';
                            const observer = new MutationObserver(() => requestAnimationFrame(updateIndicator));
                            observer.observe(container, { subtree: true, attributes: true, attributeFilter: ['class'] });
                            container.addEventListener('scroll', () => requestAnimationFrame(updateIndicator), { passive: true });
                            window.addEventListener('resize', () => requestAnimationFrame(updateIndicator));
                            document.fonts?.ready?.then(() => requestAnimationFrame(updateIndicator));
                        });
                    });
                }

                initSlidingTabIndicators();
                window.syncTabIndicators = initSlidingTabIndicators;

                // ==========================================
                // 2C. NUMBER INPUT SAFETY
                // ==========================================
                function normalizeNumberInput(input) {
                    if (!input || input.type !== 'number') return;

                    input.min = '0';
                    input.setAttribute('inputmode', 'decimal');
                    // Satuan ton boleh desimal; tanpa ini step default = 1 dan browser menolak angka koma.
                    if (!input.hasAttribute('step')) {
                        input.setAttribute('step', 'any');
                    }

                    if (input.value === '') return;

                    const normalized = Number(input.value);
                    if (!Number.isFinite(normalized) || normalized < 0) {
                        input.value = '0';
                    }
                }

                function prepareNonNegativeNumberInputs(root = document) {
                    root.querySelectorAll?.('input[type="number"]').forEach(normalizeNumberInput);
                }

                window.normalizeReportNumberInputs = function () {
                    prepareNonNegativeNumberInputs(document);
                };

                prepareNonNegativeNumberInputs();

                const numberInputObserver = new MutationObserver(records => {
                    records.forEach(record => {
                        record.addedNodes.forEach(node => {
                            if (node.nodeType !== Node.ELEMENT_NODE) return;

                            if (node.matches?.('input[type="number"]')) {
                                normalizeNumberInput(node);
                            }

                            prepareNonNegativeNumberInputs(node);
                        });
                    });
                });

                numberInputObserver.observe(document.body, { childList: true, subtree: true });

                document.addEventListener('keydown', event => {
                    if (!event.target.matches?.('input[type="number"]')) return;

                    if (['-', '+', 'e', 'E'].includes(event.key)) {
                        event.preventDefault();
                    }
                });

                document.addEventListener('input', event => {
                    if (event.target.matches?.('input[type="number"]')) {
                        normalizeNumberInput(event.target);
                    }
                });

                document.addEventListener('change', event => {
                    if (event.target.matches?.('input[type="number"]')) {
                        normalizeNumberInput(event.target);
                    }
                });

                document.addEventListener('wheel', event => {
                    if (event.target.matches?.('input[type="number"]') && document.activeElement === event.target) {
                        event.preventDefault();
                    }
                }, { passive: false });

                // ==========================================
                // 3. GLOBAL MODAL POP UP LOGIC
                // ==========================================
                const modalOverlays = document.querySelectorAll('.modal-overlay');
                const closeBtns = document.querySelectorAll('.btn-close-modal');

                // Trigger Elements Script 1
                const triggerBtnsSignature = document.querySelectorAll('.btn.signed.popup-trigger');
                const triggerBtnsEdit = document.querySelectorAll('.popup-trigger-edit');
                const triggerBtnsDelete = document.querySelectorAll('.popup-trigger-delete');
                const triggerBtnsEditHistory = document.querySelectorAll('.popup-trigger-edit-history');

                // Trigger Elements Script 2 (Confirm Submit)
                const btnOpenConfirm = document.getElementById('btnOpenConfirm');
                const finishModal = document.getElementById('finishModal');
                const btnFinalSubmit = document.getElementById('btnFinalSubmit');
                const mainForm = document.getElementById('mainReportForm');
                const finishReceiverLabel = document.querySelector('[data-finish-receiver-label]');

                // Modals Script 1
                const signatureModal = document.getElementById('signatureModal');
                const editDraftModal = document.getElementById('editDraftModal');
                const deleteDraftModal = document.getElementById('deleteDraftModal');
                const editHistoryModal = document.getElementById('editHistoryModal');

                // Bind Event Buka Modal Script 1
                if(signatureModal) triggerBtnsSignature.forEach(btn => btn.addEventListener('click', () => signatureModal.classList.add('show')));
                if(editDraftModal) triggerBtnsEdit.forEach(btn => btn.addEventListener('click', () => editDraftModal.classList.add('show')));
                if(deleteDraftModal) triggerBtnsDelete.forEach(btn => btn.addEventListener('click', () => deleteDraftModal.classList.add('show')));
                if(editHistoryModal) triggerBtnsEditHistory.forEach(btn => btn.addEventListener('click', () => editHistoryModal.classList.add('show')));

                function updateFinishReceiverLabel() {
                    if (!finishReceiverLabel) return;

                    const receiver = mainForm?.querySelector('[name="received_by_group"]') || document.querySelector('[name="received_by_group"]');
                    const receiverGroup = String(receiver?.value || '').trim().toUpperCase();
                    finishReceiverLabel.textContent = receiverGroup ? `Regu ${receiverGroup}` : 'regu penerima yang dipilih';
                }

                document.querySelector('[name="received_by_group"]')?.addEventListener('change', updateFinishReceiverLabel);
                updateFinishReceiverLabel();

                // Bind Event Buka Modal Script 2
                if(btnOpenConfirm && finishModal) {
                    btnOpenConfirm.addEventListener('click', () => {
                        updateFinishReceiverLabel();
                        finishModal.classList.add('show');
                    });
                }

                // Fungsi Tutup Semua Modal
                function closeAllModals() {
                    modalOverlays.forEach(modal => modal.classList.remove('show'));
                }

                // Bind Event Tutup
                closeBtns.forEach(btn => btn.addEventListener('click', closeAllModals));
                modalOverlays.forEach(modal => {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) closeAllModals();
                    });
                    const popupContent = modal.querySelector('.pop-up');
                    if(popupContent) popupContent.addEventListener('click', (e) => e.stopPropagation());
                });

                // Logika Submit Form (Script 2)
                if(btnFinalSubmit && mainForm) {
                    const finalSubmitOriginalHtml = btnFinalSubmit.innerHTML;
                    let isFinalSubmitting = false;

                    function resetFinalSubmitButton() {
                        isFinalSubmitting = false;
                        btnFinalSubmit.innerHTML = finalSubmitOriginalHtml;
                        btnFinalSubmit.style.opacity = '';
                        btnFinalSubmit.disabled = false;
                        btnFinalSubmit.removeAttribute('aria-busy');
                    }

                    function formPrototypeMethod(methodName) {
                        return window.HTMLFormElement?.prototype?.[methodName];
                    }

                    function formIsValid(targetForm) {
                        const checkValidity = formPrototypeMethod('checkValidity');

                        if (typeof checkValidity === 'function') {
                            return checkValidity.call(targetForm);
                        }

                        return typeof targetForm.checkValidity === 'function'
                            ? targetForm.checkValidity()
                            : true;
                    }

                    function submitFormSafely(targetForm) {
                        const requestSubmit = formPrototypeMethod('requestSubmit');
                        const submit = formPrototypeMethod('submit');

                        if (typeof requestSubmit === 'function') {
                            requestSubmit.call(targetForm);
                            return;
                        }

                        if (typeof submit === 'function') {
                            submit.call(targetForm);
                            return;
                        }

                        if (typeof targetForm.submit === 'function') {
                            targetForm.submit();
                        }
                    }

                    function showStepByControl(control) {
                        const step = control?.closest('.form-step');
                        if (!step) return;

                        const allSteps = Array.from(document.querySelectorAll('.form-step'));
                        const allTabs = Array.from(document.querySelectorAll('.list-form-tab'));
                        const targetIndex = allSteps.indexOf(step);

                        if (targetIndex < 0) return;

                        allSteps.forEach(item => {
                            item.classList.remove('d-flex');
                            item.classList.add('d-none');
                        });
                        allTabs.forEach(tab => tab.classList.remove('active'));

                        step.classList.remove('d-none');
                        step.classList.add('d-flex');
                        allTabs[targetIndex]?.classList.add('active');
                        currentStepIndex = targetIndex;
                    }

                    function invalidControlLabel(control) {
                        if (!control) return 'field wajib';

                        const label = control.id ? document.querySelector(`label[for="${control.id}"]`) : null;
                        const fallbackLabel = control.closest('.form-group, .box-input-1')?.querySelector('label');
                        const text = (label || fallbackLabel)?.textContent?.trim();

                        return text || control.getAttribute('name') || 'field wajib';
                    }

                    function focusInvalidControl(control) {
                        showStepByControl(control);
                        closeAllModals();

                        window.setTimeout(() => {
                            const visualTarget = control.tagName === 'SELECT'
                                ? control.nextElementSibling || control.closest('.input-wrapper') || control
                                : control;

                            visualTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });

                            if (control.tagName === 'SELECT' && control.nextElementSibling) {
                                control.nextElementSibling.classList.add('focus-active');
                            } else {
                                control.focus({ preventScroll: true });
                            }

                            const customMessage = control.validationMessage || '';
                            window.showReportToast?.(
                                'error',
                                customMessage ? 'Data belum valid' : 'Data belum lengkap',
                                customMessage || `Lengkapi ${invalidControlLabel(control)} sebelum mengirim laporan.`
                            );
                        }, 120);
                    }

                    btnFinalSubmit.addEventListener('click', () => {
                        if (isFinalSubmitting) return;

                        window.normalizeReportNumberInputs?.();

                        const reportStatus = document.getElementById('reportStatus');
                        if (reportStatus) reportStatus.value = 'submitted';

                        if (typeof window.validateReportGroupRoute === 'function') {
                            window.validateReportGroupRoute({ enforce: true });
                        }

                        if (!formIsValid(mainForm)) {
                            const invalidControl = mainForm.querySelector(':invalid');

                            if (invalidControl) {
                                focusInvalidControl(invalidControl);
                            } else {
                                closeAllModals();
                                window.showReportToast?.('error', 'Data belum lengkap', 'Periksa kembali data laporan sebelum mengirim.');
                            }

                            resetFinalSubmitButton();
                            return;
                        }

                        isFinalSubmitting = true;
                        btnFinalSubmit.innerHTML = 'Mengirim...';
                        btnFinalSubmit.style.opacity = '0.7';
                        btnFinalSubmit.disabled = true;
                        btnFinalSubmit.setAttribute('aria-busy', 'true');

                        try {
                            submitFormSafely(mainForm);
                        } catch (error) {
                            resetFinalSubmitButton();
                            window.showReportToast?.('error', 'Gagal mengirim', 'Form belum bisa dikirim. Periksa kembali data laporan.');
                        }
                    });

                    mainForm.addEventListener('invalid', resetFinalSubmitButton, true);
                }

                // ==========================================
                // 4. SPA ROUTING (MAIN TABS NAVIGATION)
                // ==========================================
                const tabs = document.querySelectorAll('.list-form-tab');
                const steps = document.querySelectorAll('.form-step');
                const btnNextList = document.querySelectorAll('.btn-next-step');
                const btnBackList = document.querySelectorAll('.btn-back-step');

                let currentStepIndex = 0;

                if(tabs.length > 0 && steps.length > 0) {
                    function scrollToFormTabs() {
                        const tabForm = document.querySelector('.tab-form');
                        if (!tabForm) return;

                        const topGap = window.innerWidth <= 768 ? 16 : 40;
                        const targetTop = Math.max(0, tabForm.getBoundingClientRect().top + window.scrollY - topGap);
                        window.scrollTo({ top: targetTop, behavior: 'smooth' });
                    }

                    function showStep(index) {
                        if(index < 0 || index >= steps.length) return;

                        steps.forEach(step => {
                            step.classList.remove('d-flex');
                            step.classList.add('d-none');
                        });
                        tabs.forEach(tab => tab.classList.remove('active'));

                        steps[index].classList.remove('d-none');
                        steps[index].classList.add('d-flex');
                        tabs[index].classList.add('active');

                        tabs[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                        currentStepIndex = index;
                        requestAnimationFrame(scrollToFormTabs);
                    }

                    tabs.forEach((tab, index) => tab.addEventListener('click', () => showStep(index)));
                    btnNextList.forEach(btn => btn.addEventListener('click', (e) => { e.preventDefault(); showStep(currentStepIndex + 1); }));
                    btnBackList.forEach(btn => btn.addEventListener('click', (e) => { e.preventDefault(); showStep(currentStepIndex - 1); }));
                }

                // ==========================================
                // 5. ALL LOGIC TABS (Laporan, Bongkar, Karyawan, dll)
                // ==========================================

                // A. Laporan, Draft, Riwayat, Diterima
                const tabLaporan = document.getElementById('tab-laporan');
                const tabDraft = document.getElementById('tab-draft');
                const tabRiwayat = document.getElementById('tab-riwayat');
                const tabDiterima = document.getElementById('tab-diterima');
                const contentLaporan = document.getElementById('content-laporan');
                const contentDraft = document.getElementById('content-draft');
                const contentRiwayat = document.getElementById('content-riwayat');
                const contentDiterima = document.getElementById('content-diterima');
                let currentTabIndex = 0;
                if (tabDraft?.classList.contains('active')) currentTabIndex = 1;
                if (tabRiwayat?.classList.contains('active')) currentTabIndex = 2;
                if (tabDiterima?.classList.contains('active')) currentTabIndex = 3;

                function switchMainTab(newTab, newContent, newIndex, isFlex) {
                    if (currentTabIndex === newIndex) return;
                    const animClass = newIndex > currentTabIndex ? 'animate-slide-right' : 'animate-slide-left';

                    if(contentLaporan) { contentLaporan.classList.add('d-none'); contentLaporan.classList.remove('d-flex', 'animate-slide-right', 'animate-slide-left'); }
                    if(contentDraft) { contentDraft.classList.add('d-none'); contentDraft.classList.remove('d-flex', 'animate-slide-right', 'animate-slide-left'); }
                    if(contentRiwayat) { contentRiwayat.classList.add('d-none'); contentRiwayat.classList.remove('animate-slide-right', 'animate-slide-left'); }
                    if(contentDiterima) { contentDiterima.classList.add('d-none'); contentDiterima.classList.remove('animate-slide-right', 'animate-slide-left'); }

                    if(tabLaporan) tabLaporan.classList.remove('active');
                    if(tabDraft) tabDraft.classList.remove('active');
                    if(tabRiwayat) tabRiwayat.classList.remove('active');
                    if(tabDiterima) tabDiterima.classList.remove('active');

                    newContent.classList.remove('d-none');
                    if (isFlex) newContent.classList.add('d-flex');
                    newContent.classList.add(animClass);
                    newTab.classList.add('active');
                    currentTabIndex = newIndex;
                }

                if(tabLaporan) tabLaporan.addEventListener('click', (e) => { e.preventDefault(); switchMainTab(tabLaporan, contentLaporan, 0, true); });
                if(tabDraft) tabDraft.addEventListener('click', (e) => { e.preventDefault(); switchMainTab(tabDraft, contentDraft, 1, true); });
                if(tabRiwayat) tabRiwayat.addEventListener('click', (e) => { e.preventDefault(); switchMainTab(tabRiwayat, contentRiwayat, 2, false); });
                if(tabDiterima) tabDiterima.addEventListener('click', (e) => { e.preventDefault(); switchMainTab(tabDiterima, contentDiterima, 3, false); });

                // B. Sub Bongkar (Bahan Baku vs Container)
                const tabBtnBahanBaku = document.getElementById('tab-btn-bahan-baku');
                const tabBtnContainer = document.getElementById('tab-btn-container');
                const sectionBahanBaku = document.getElementById('section-bahan-baku');
                const sectionContainer = document.getElementById('section-container');

                if (tabBtnBahanBaku && tabBtnContainer) {
                    tabBtnBahanBaku.addEventListener('click', function() {
                        tabBtnBahanBaku.classList.add('active'); tabBtnContainer.classList.remove('active');
                        sectionBahanBaku.classList.remove('d-none'); sectionBahanBaku.classList.add('d-flex');
                        sectionContainer.classList.remove('d-flex'); sectionContainer.classList.add('d-none');
                    });
                    tabBtnContainer.addEventListener('click', function() {
                        tabBtnContainer.classList.add('active'); tabBtnBahanBaku.classList.remove('active');
                        sectionContainer.classList.remove('d-none'); sectionContainer.classList.add('d-flex');
                        sectionBahanBaku.classList.remove('d-flex'); sectionBahanBaku.classList.add('d-none');
                    });
                }

                // C. Form Cek Unit (Kendaraan, Inventaris, Lingkungan)
                const tabUnit = document.getElementById('subtab-unit');
                const tabInventaris = document.getElementById('subtab-inventaris');
                const tabLingkungan = document.getElementById('subtab-lingkungan');
                const sectionUnit = document.getElementById('section-unit');
                const sectionInventaris = document.getElementById('section-inventaris');
                const sectionLingkungan = document.getElementById('section-lingkungan');

                if(tabUnit && tabInventaris && tabLingkungan) {
                    function switchSubTabCekUnit(activeTab, activeSection) {
                        [tabUnit, tabInventaris, tabLingkungan].forEach(t => t.classList.remove('active'));
                        [sectionUnit, sectionInventaris, sectionLingkungan].forEach(s => s.classList.add('d-none'));
                        activeTab.classList.add('active');
                        activeSection.classList.remove('d-none');
                    }
                    tabUnit.addEventListener('click', () => switchSubTabCekUnit(tabUnit, sectionUnit));
                    tabInventaris.addEventListener('click', () => switchSubTabCekUnit(tabInventaris, sectionInventaris));
                    tabLingkungan.addEventListener('click', () => switchSubTabCekUnit(tabLingkungan, sectionLingkungan));
                }

                // D. Form Karyawan
                const karyawanTabs = document.querySelectorAll('#karyawan-tabs-group .tab-sections');
                const karyawanContents = document.querySelectorAll('#step-karyawan .tab-content-karyawan');

                if(karyawanTabs.length > 0) {
                    karyawanTabs.forEach(tab => {
                        tab.addEventListener('click', function() {
                            karyawanTabs.forEach(t => t.classList.remove('active'));
                            karyawanContents.forEach(content => { content.classList.remove('d-flex'); content.classList.add('d-none'); });
                            this.classList.add('active');
                            const targetContent = document.getElementById(this.getAttribute('data-target'));
                            if(targetContent) { targetContent.classList.remove('d-none'); targetContent.classList.add('d-flex'); }
                        });
                    });
                }

                // ==========================================
                // 6. CUSTOM DROPDOWN SELECT & TABLE SELECT
                // ==========================================
                function initCustomSelects(wrapperSelector, selectClass, triggerClass, optionsContainerClass, optionClass) {
                    const wrappers = document.querySelectorAll(wrapperSelector);
                    wrappers.forEach(wrapper => {
                        const nativeSelect = wrapper.querySelector(`select.${selectClass}`);
                        if (!nativeSelect) return;

                        nativeSelect.style.display = "none";
                        const triggerBox = document.createElement("div");
                        triggerBox.className = `${triggerClass} d-flex align-items-center`;
                        triggerBox.tabIndex = 0;
                        triggerBox.setAttribute('role', 'button');

                        const selectedOption = nativeSelect.options[nativeSelect.selectedIndex];
                        const textSpan = document.createElement("span");
                        textSpan.textContent = selectedOption ? selectedOption.text : '';

                        if (selectedOption && (selectedOption.disabled || selectedOption.value === "")) triggerBox.classList.add("text-placeholder");
                        triggerBox.appendChild(textSpan);
                        wrapper.insertBefore(triggerBox, nativeSelect.nextSibling);

                        const optionsContainer = document.createElement("div");
                        optionsContainer.className = optionsContainerClass;

                        Array.from(nativeSelect.options).forEach(option => {
                            if (option.disabled && option.hidden) return;
                            const optDiv = document.createElement("div");
                            optDiv.className = optionClass;
                            optDiv.textContent = option.text;
                            optDiv.dataset.value = option.value;
                            if (option.selected) optDiv.classList.add("selected");

                            optDiv.addEventListener("click", function(e) {
                                e.stopPropagation();
                                nativeSelect.value = this.dataset.value;
                                nativeSelect.dispatchEvent(new Event("change"));
                                textSpan.textContent = this.textContent;
                                triggerBox.classList.remove("text-placeholder");
                                optionsContainer.querySelectorAll(`.${optionClass}`).forEach(o => o.classList.remove("selected"));
                                this.classList.add("selected");
                                optionsContainer.classList.remove("open");
                                triggerBox.classList.remove("focus-active");
                            });
                            optionsContainer.appendChild(optDiv);
                        });

                        wrapper.appendChild(optionsContainer);

                        triggerBox.addEventListener("click", function(e) {
                            e.stopPropagation();
                            document.querySelectorAll(`.${optionsContainerClass}.open`).forEach(cont => {
                                if (cont !== optionsContainer) {
                                    cont.classList.remove("open");
                                    cont.previousElementSibling.classList.remove("focus-active");
                                }
                            });
                            optionsContainer.classList.toggle("open");
                            this.classList.toggle("focus-active");
                        });

                        triggerBox.addEventListener("keydown", function(e) {
                            if (!['Enter', ' '].includes(e.key)) return;
                            e.preventDefault();
                            e.stopPropagation();
                            this.click();
                        });
                    });
                }

                initCustomSelects(".input-wrapper", "native-select", "custom-input", "custom-options-container", "custom-option");
                initCustomSelects(".tbl-select-wrapper", "tbl-native-select", "tbl-custom-select-trigger", "tbl-custom-options", "tbl-custom-option");

                document.addEventListener("click", function() {
                    document.querySelectorAll(".custom-options-container, .tbl-custom-options").forEach(cont => cont.classList.remove("open"));
                    document.querySelectorAll(".custom-input.focus-active, .tbl-custom-select-trigger.focus-active").forEach(trig => trig.classList.remove("focus-active"));
                });

                function isReportControlVisible(control) {
                    if (!control || control.disabled) return false;
                    if (control.closest('.d-none, [hidden]')) return false;
                    return Boolean(control.offsetWidth || control.offsetHeight || control.getClientRects().length);
                }

                function reportKeyboardControls() {
                    const formScope = document.getElementById('mainReportForm') || document;
                    return Array.from(formScope.querySelectorAll([
                        'input:not([type="hidden"]):not([type="radio"]):not([type="checkbox"])',
                        'textarea',
                        'button.kss-date-trigger',
                        '.input-wrapper > div.custom-input[tabindex="0"]',
                    ].join(','))).filter(isReportControlVisible);
                }

                function focusReportControl(control) {
                    if (!control) return;

                    control.focus({ preventScroll: true });
                    control.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

                    if (control.classList.contains('kss-date-trigger')) {
                        window.setTimeout(() => window.KssDateTimePicker?.open(control), 40);
                    }
                }

                function focusNextReportControl(currentControl) {
                    const controls = reportKeyboardControls();
                    const currentIndex = controls.indexOf(currentControl);
                    const nextControl = controls[currentIndex + 1];

                    if (nextControl) {
                        focusReportControl(nextControl);
                    }
                }

                document.addEventListener('kss-picker:advance', event => {
                    focusNextReportControl(event.detail?.trigger || event.target);
                });

                document.addEventListener('keydown', event => {
                    if (event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) return;
                    if (event.target.closest?.('.kss-date-popover, .flatpickr-calendar, .modal-overlay.show')) return;
                    if (event.target.matches?.('textarea, button.kss-date-trigger')) return;
                    if (!event.target.closest?.('#mainReportForm')) return;
                    if (!event.target.matches?.('input, select, .custom-input, .tbl-custom-select-trigger')) return;

                    event.preventDefault();
                    focusNextReportControl(event.target);
                });

                // ==========================================
                // 7. INPUT TIMESHEET & WAKTU
                // ==========================================
                // Jam tidak boleh melebihi 24:00; kelebihannya dibungkus ke jam nyata
                // (mis. ketik "40:00" -> otomatis jadi "16:00", 40 - 24 = 16).
                function wrapTimeHourDigits(val) {
                    if (val.length < 2) return val;
                    const wrappedHour = String(Number(val.substring(0, 2)) % 24).padStart(2, '0');
                    return wrappedHour + val.substring(2);
                }

                const timeInputs = document.querySelectorAll('.time-picker-input');
                timeInputs.forEach(input => {
                    input.addEventListener('input', function(e) {
                        if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward') return;
                        let val = this.value.replace(/\D/g, '');
                        if (val.length > 4) val = val.substring(0, 4);
                        val = wrapTimeHourDigits(val);
                        if (val.length >= 3) this.value = val.substring(0, 2) + ':' + val.substring(2);
                        else if (val.length === 2) this.value = val + ':';
                        else this.value = val;
                    });
                });

                if(typeof flatpickr !== 'undefined') {
                    flatpickr(".time-picker-input", {
                        enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, allowInput: true, minuteIncrement: 1
                    });
                }

                document.querySelectorAll('.timesheet-input-wrapper').forEach(wrapper => {
                    wrapper.addEventListener('click', () => {
                        const inputTime = wrapper.querySelector('input');
                        if (inputTime) inputTime.focus();
                    });
                });

            });
