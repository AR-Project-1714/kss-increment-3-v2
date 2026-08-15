(function () {
    'use strict';

    function initAccountSettings() {
        var passwordModal = document.querySelector('[data-account-modal]');
        var passwordForm = document.querySelector('[data-account-form]');
        var helpModal = document.querySelector('[data-account-help-modal]');
        var photoModal = document.querySelector('[data-account-photo-modal]');
        var photoForm = document.querySelector('[data-account-photo-form]');
        if (!photoModal || !photoForm || photoModal.dataset.initialized === 'true') return;
        photoModal.dataset.initialized = 'true';

        var passwordPanel = passwordModal?.querySelector('.kss-account-modal__panel');
        var passwordSubmit = passwordForm?.querySelector('[data-account-submit]');
        var passwordSubmitLabel = passwordForm?.querySelector('[data-account-submit-label]');
        var passwordAlert = passwordForm?.querySelector('[data-account-alert]');
        var currentInput = passwordForm?.querySelector('[name="current_password"]');
        var passwordInput = passwordForm?.querySelector('[name="password"]');
        var confirmationInput = passwordForm?.querySelector('[name="password_confirmation"]');
        var helpPanel = helpModal?.querySelector('.kss-account-modal__panel');

        var photoPanel = photoModal.querySelector('.kss-account-modal__panel');
        var photoInput = photoForm.querySelector('[data-account-photo-input]');
        var photoDropzone = photoForm.querySelector('[data-account-photo-dropzone]');
        var photoPreview = photoForm.querySelector('[data-account-photo-preview]');
        var photoPreviewFallback = photoForm.querySelector('[data-account-photo-preview-fallback]');
        var photoFileInfo = photoForm.querySelector('[data-account-photo-file-info]');
        var photoAlert = photoForm.querySelector('[data-account-photo-alert]');
        var photoSubmit = photoForm.querySelector('[data-account-photo-submit]');
        var photoSubmitLabel = photoForm.querySelector('[data-account-photo-submit-label]');
        var photoProgress = photoForm.querySelector('[data-account-photo-progress]');
        var photoProgressLabel = photoForm.querySelector('[data-account-photo-progress-label]');
        var photoProgressValue = photoForm.querySelector('[data-account-photo-progress-value]');
        var photoProgressTrack = photoForm.querySelector('[data-account-photo-progress-track]');
        var photoProgressBar = photoForm.querySelector('[data-account-photo-progress-bar]');
        var photoDeleteOpen = photoForm.querySelector('[data-account-photo-delete-open]');
        var photoDeleteConfirm = photoForm.querySelector('[data-account-photo-delete-confirm]');
        var photoDeleteCancel = photoForm.querySelector('[data-account-photo-delete-cancel]');
        var photoDeleteButton = photoForm.querySelector('[data-account-photo-delete-confirm-button]');
        var photoDeleteLabel = photoForm.querySelector('[data-account-photo-delete-label]');

        var passwordLastFocus = null;
        var helpLastFocus = null;
        var photoLastFocus = null;
        var passwordSubmitting = false;
        var photoUploading = false;
        var photoDeleting = false;
        var selectedPhoto = null;
        var previewObjectUrl = null;
        var currentPhotoUrl = photoPreview && !photoPreview.hidden ? photoPreview.src : '';
        var hoverCapable = window.matchMedia('(hover: hover) and (pointer: fine)');
        var allowedPhotoTypes = ['image/jpeg', 'image/png', 'image/webp'];
        var maximumPhotoSize = 10 * 1024 * 1024;

        function syncThemeControls() {
            var isDark = document.body.classList.contains('dark-mode');
            document.documentElement.classList.toggle('kss-dark-theme', isDark);

            document.querySelectorAll('[data-account-theme-toggle]').forEach(function (control) {
                control.checked = isDark;
                control.setAttribute('aria-label', isDark ? 'Nonaktifkan mode gelap' : 'Aktifkan mode gelap');
                var status = control.closest('.kss-account__theme-row')?.querySelector('[data-account-theme-status]');
                if (status) status.textContent = isDark ? 'Aktif' : 'Nonaktif';
            });
        }

        function setTheme(isDark) {
            document.body.classList.toggle('dark-mode', isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            syncThemeControls();
            document.dispatchEvent(new CustomEvent('kss:theme-change', { detail: { dark: isDark } }));
        }

        function setBodyModalState() {
            var hasOpenModal = document.querySelector('.kss-account-modal.is-open');
            document.body.classList.toggle('kss-account-modal-open', Boolean(hasOpenModal));
        }

        /* Dua cara membuka dengan umur yang berbeda:
           - hover  → sementara, tertutup begitu kursor meninggalkan area;
           - klik   → "dipaku" (pinned), hanya tertutup oleh klik di luar,
                      Escape, atau overlay lain yang mengambil alih.
           Status paku disimpan di root.dataset.pinned supaya setMenuOpen tetap
           menjadi satu-satunya jalan mengubah keadaan. */
        function setMenuOpen(root, open, focusFirstItem, pinned) {
            var trigger = root?.querySelector('[data-account-trigger]');
            var popover = root?.querySelector('[data-account-popover]');
            if (!trigger || !popover) return;

            root.dataset.pinned = open && pinned ? 'true' : 'false';
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            popover.setAttribute('aria-hidden', open ? 'false' : 'true');
            popover.classList.toggle('is-open', open);

            if (open) notifyOverlayOpened();

            if (open && focusFirstItem) {
                window.setTimeout(function () {
                    popover.querySelector('[data-account-initial-focus], [role="menuitem"], button, input')?.focus();
                }, 20);
            }
        }

        function isPinned(root) {
            return root?.dataset.pinned === 'true';
        }

        function closeMenus(exceptRoot) {
            document.querySelectorAll('[data-account-root]').forEach(function (root) {
                if (root === exceptRoot) return;
                setMenuOpen(root, false, false, false);
            });
        }

        /* Kontrak lepas-kait dengan overlay lain di navbar (mis. pusat
           notifikasi): siapa pun yang membuka memberi kabar, yang lain menutup.
           Keduanya tidak boleh terbuka bersamaan. */
        function notifyOverlayOpened() {
            document.dispatchEvent(new CustomEvent('kss:overlay-open', {
                detail: { source: 'account' },
            }));
        }

        document.addEventListener('kss:overlay-open', function (event) {
            if (event.detail?.source === 'account') return;
            closeMenus();
        });

        function showToast(type, title, message) {
            if (window.kssToast) window.kssToast(type, title, message);
        }

        function clearPasswordError(name) {
            var input = passwordForm.querySelector('[data-account-input="' + name + '"]');
            var error = passwordForm.querySelector('[data-account-error="' + name + '"]');
            input?.closest('.kss-account-field')?.classList.remove('is-invalid');
            input?.removeAttribute('aria-invalid');
            if (error) error.textContent = '';
        }

        function clearPasswordErrors() {
            ['current_password', 'password', 'password_confirmation'].forEach(clearPasswordError);
            passwordAlert.hidden = true;
            passwordAlert.textContent = '';
        }

        function setPasswordError(name, message) {
            var input = passwordForm.querySelector('[data-account-input="' + name + '"]');
            var error = passwordForm.querySelector('[data-account-error="' + name + '"]');
            input?.closest('.kss-account-field')?.classList.add('is-invalid');
            input?.setAttribute('aria-invalid', 'true');
            if (error) error.textContent = message;
        }

        function setPasswordCheck(name, valid) {
            var check = passwordForm.querySelector('[data-password-check="' + name + '"]');
            if (!check) return;
            check.classList.toggle('is-valid', valid);
            var icon = check.querySelector('i');
            if (icon) icon.className = valid ? 'fi fi-rr-check-circle' : 'fi fi-rr-circle-small';
        }

        function updatePasswordState() {
            if (!passwordForm || !currentInput || !passwordInput || !confirmationInput || !passwordSubmit) return;
            var current = currentInput.value;
            var password = passwordInput.value;
            var confirmation = confirmationInput.value;
            var hasLength = password.length >= 8;
            var isDifferent = current.length > 0 && password.length > 0 && current !== password;
            var isMatching = confirmation.length > 0 && password === confirmation;

            setPasswordCheck('length', hasLength);
            setPasswordCheck('different', isDifferent);
            setPasswordCheck('matching', isMatching);
            passwordSubmit.disabled = passwordSubmitting || !(current && hasLength && isDifferent && isMatching);
        }

        function resetPasswordForm() {
            if (!passwordForm) return;
            passwordForm.reset();
            clearPasswordErrors();
            passwordForm.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                var input = button.closest('.kss-account-field__control')?.querySelector('input');
                var icon = button.querySelector('i');
                if (input) input.type = 'password';
                button.setAttribute('aria-pressed', 'false');
                if (icon) icon.className = 'fi fi-rr-eye';
            });
            updatePasswordState();
        }

        function openPasswordModal(opener) {
            if (!passwordModal || !passwordForm || !currentInput) return;
            closeMenus();
            photoModal.classList.remove('is-open');
            photoModal.setAttribute('aria-hidden', 'true');
            if (helpModal) {
                helpModal.classList.remove('is-open');
                helpModal.setAttribute('aria-hidden', 'true');
            }
            passwordLastFocus = opener || document.activeElement;
            resetPasswordForm();
            passwordModal.classList.add('is-open');
            passwordModal.setAttribute('aria-hidden', 'false');
            setBodyModalState();
            window.setTimeout(function () { currentInput.focus(); }, 30);
        }

        function closePasswordModal() {
            if (!passwordModal) return;
            if (passwordSubmitting) return;
            passwordModal.classList.remove('is-open');
            passwordModal.setAttribute('aria-hidden', 'true');
            resetPasswordForm();
            setBodyModalState();
            if (passwordLastFocus && document.contains(passwordLastFocus)) passwordLastFocus.focus();
        }

        function setPasswordSubmitting(active) {
            if (!passwordForm || !passwordSubmit || !passwordSubmitLabel) return;
            passwordSubmitting = active;
            passwordSubmit.classList.toggle('is-loading', active);
            passwordSubmitLabel.textContent = active ? 'Menyimpan...' : 'Simpan Password';
            var icon = passwordSubmit.querySelector('i');
            if (icon) icon.className = active ? 'fi fi-rr-spinner' : 'fi fi-rr-disk';
            passwordForm.querySelectorAll('input, button').forEach(function (control) {
                if (control !== passwordSubmit) control.disabled = active;
            });
            updatePasswordState();
        }

        function openHelpModal(opener) {
            if (!helpModal || !helpPanel) return;
            closeMenus();
            photoModal.classList.remove('is-open');
            photoModal.setAttribute('aria-hidden', 'true');
            if (passwordModal) {
                passwordModal.classList.remove('is-open');
                passwordModal.setAttribute('aria-hidden', 'true');
            }
            helpLastFocus = opener || document.activeElement;
            helpModal.classList.add('is-open');
            helpModal.setAttribute('aria-hidden', 'false');
            setBodyModalState();
            window.setTimeout(function () {
                helpModal.querySelector('[data-account-help-primary], [data-account-help-close]')?.focus();
            }, 30);
        }

        function closeHelpModal() {
            if (!helpModal) return;
            helpModal.classList.remove('is-open');
            helpModal.setAttribute('aria-hidden', 'true');
            setBodyModalState();
            if (helpLastFocus && document.contains(helpLastFocus)) helpLastFocus.focus();
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            return (bytes / (1024 * 1024)).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' MB';
        }

        function setPhotoAlert(message) {
            photoAlert.textContent = message || '';
            photoAlert.hidden = !message;
        }

        function setPhotoProgress(value, indeterminate) {
            var normalized = Math.max(0, Math.min(100, Math.round(value || 0)));
            photoProgress.classList.toggle('is-indeterminate', Boolean(indeterminate));
            photoProgressValue.textContent = indeterminate ? 'Memproses' : normalized + '%';
            photoProgressTrack.setAttribute('aria-valuenow', String(normalized));
            photoProgressTrack.setAttribute('aria-valuetext', indeterminate ? 'Sedang memproses upload' : normalized + ' persen');
            photoProgressBar.style.width = indeterminate ? '' : normalized + '%';
        }

        function showCurrentPhoto() {
            if (currentPhotoUrl) {
                photoPreview.src = currentPhotoUrl;
                photoPreview.hidden = false;
                photoPreviewFallback.hidden = true;
            } else {
                photoPreview.removeAttribute('src');
                photoPreview.hidden = true;
                photoPreviewFallback.hidden = false;
            }
        }

        function releasePreviewUrl() {
            if (!previewObjectUrl) return;
            URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = null;
        }

        function resetPhotoForm() {
            releasePreviewUrl();
            selectedPhoto = null;
            photoForm.reset();
            setPhotoAlert('');
            photoDropzone.classList.remove('is-dragging');
            photoFileInfo.textContent = 'Belum ada foto baru yang dipilih.';
            photoProgress.hidden = true;
            photoProgressLabel.textContent = 'Mengunggah foto...';
            setPhotoProgress(0, false);
            photoSubmit.disabled = true;
            setPhotoDeleteConfirm(false);
            showCurrentPhoto();
        }

        function setPhotoDeleteConfirm(active) {
            photoDeleteConfirm.hidden = !active;
            photoDeleteOpen.hidden = active || !currentPhotoUrl;
            photoForm.querySelectorAll('[data-account-photo-close], [data-account-photo-submit]').forEach(function (button) {
                button.hidden = active;
            });
            if (active) window.setTimeout(function () { photoDeleteCancel.focus(); }, 0);
        }

        function selectPhoto(file) {
            setPhotoAlert('');
            if (!file) return;

            selectedPhoto = null;
            releasePreviewUrl();
            showCurrentPhoto();
            photoFileInfo.textContent = file.name + ' · ' + formatFileSize(file.size);
            photoSubmit.disabled = true;

            if (!allowedPhotoTypes.includes(file.type)) {
                setPhotoAlert('Format foto tidak didukung. Pilih file JPG, PNG, atau WebP.');
                return;
            }

            if (file.size > maximumPhotoSize) {
                setPhotoAlert('Ukuran foto melebihi 10 MB. Pilih foto yang lebih kecil.');
                return;
            }

            selectedPhoto = file;
            previewObjectUrl = URL.createObjectURL(file);
            photoPreview.src = previewObjectUrl;
            photoPreview.hidden = false;
            photoPreviewFallback.hidden = true;
            photoSubmit.disabled = false;
        }

        function openPhotoModal(opener) {
            closeMenus();
            if (passwordModal) {
                passwordModal.classList.remove('is-open');
                passwordModal.setAttribute('aria-hidden', 'true');
            }
            if (helpModal) {
                helpModal.classList.remove('is-open');
                helpModal.setAttribute('aria-hidden', 'true');
            }
            photoLastFocus = opener || document.activeElement;
            resetPhotoForm();
            photoModal.classList.add('is-open');
            photoModal.setAttribute('aria-hidden', 'false');
            setBodyModalState();
            window.setTimeout(function () { photoDropzone.focus(); }, 30);
        }

        function closePhotoModal() {
            if (photoUploading || photoDeleting) return;
            photoModal.classList.remove('is-open');
            photoModal.setAttribute('aria-hidden', 'true');
            resetPhotoForm();
            setBodyModalState();
            if (photoLastFocus && document.contains(photoLastFocus)) photoLastFocus.focus();
        }

        function setPhotoUploading(active) {
            photoUploading = active;
            photoDropzone.disabled = active;
            photoInput.disabled = active;
            photoForm.querySelectorAll('[data-account-photo-close]').forEach(function (button) {
                button.disabled = active;
            });
            photoSubmit.disabled = active || !selectedPhoto;
            photoSubmit.classList.toggle('is-loading', active);
            photoSubmitLabel.textContent = active ? 'Mengunggah...' : 'Simpan Foto';
            var icon = photoSubmit.querySelector('i');
            if (icon) icon.className = active ? 'fi fi-rr-spinner' : 'fi fi-rr-cloud-upload-alt';
            photoProgress.hidden = !active;
        }

        function setPhotoDeleting(active) {
            photoDeleting = active;
            photoDeleteCancel.disabled = active;
            photoDeleteButton.disabled = active;
            photoDeleteButton.classList.toggle('is-loading', active);
            photoDeleteLabel.textContent = active ? 'Menghapus...' : 'Ya, Hapus';
            var icon = photoDeleteButton.querySelector('i');
            if (icon) icon.className = active ? 'fi fi-rr-spinner' : 'fi fi-rr-trash';
        }

        function updateAccountAvatars(photoUrl) {
            var versionedPhotoUrl = photoUrl + (photoUrl.includes('?') ? '&' : '?') + 'v=' + Date.now();
            currentPhotoUrl = versionedPhotoUrl;
            document.querySelectorAll('[data-account-avatar-image]').forEach(function (image) {
                image.src = versionedPhotoUrl;
                image.hidden = false;
            });
            document.querySelectorAll('[data-account-avatar-fallback]').forEach(function (fallback) {
                fallback.hidden = true;
            });
            document.querySelectorAll('[data-account-photo-label]').forEach(function (label) {
                label.textContent = 'Ganti Foto Profil';
            });
        }

        function clearAccountAvatars() {
            currentPhotoUrl = '';
            document.querySelectorAll('[data-account-avatar-image]').forEach(function (image) {
                image.removeAttribute('src');
                image.hidden = true;
            });
            document.querySelectorAll('[data-account-avatar-fallback]').forEach(function (fallback) {
                fallback.hidden = false;
            });
            document.querySelectorAll('[data-account-photo-label]').forEach(function (label) {
                label.textContent = 'Tambah Foto Profil';
            });
            photoDeleteOpen.hidden = true;
            showCurrentPhoto();
        }

        function finishPhotoUpload(callback, startedAt) {
            var minimumVisibleTime = 450;
            window.setTimeout(callback, Math.max(0, minimumVisibleTime - (Date.now() - startedAt)));
        }

        document.querySelectorAll('[data-account-root]').forEach(function (root) {
            var trigger = root.querySelector('[data-account-trigger]');
            var popover = root.querySelector('[data-account-popover]');
            var passwordOpen = root.querySelector('[data-account-open]');
            var helpOpen = root.querySelector('[data-account-help-open]');
            var photoOpen = root.querySelector('[data-account-photo-open]');
            var themeToggle = root.querySelector('[data-account-theme-toggle]');
            var hoverCloseTimer = null;

            // Klik memaku popover; klik lagi pada pemicunya melepas dan menutup.
            trigger?.addEventListener('click', function () {
                var willOpen = !isPinned(root);
                closeMenus(root);
                setMenuOpen(root, willOpen, willOpen, willOpen);
            });

            root.addEventListener('mouseenter', function () {
                if (!hoverCapable.matches) return;
                window.clearTimeout(hoverCloseTimer);
                if (isPinned(root)) return;
                closeMenus(root);
                setMenuOpen(root, true, false, false);
            });

            root.addEventListener('mouseleave', function () {
                if (!hoverCapable.matches || isPinned(root)) return;
                hoverCloseTimer = window.setTimeout(function () {
                    if (!isPinned(root)) setMenuOpen(root, false, false, false);
                }, 180);
            });

            root.addEventListener('focusin', function () { window.clearTimeout(hoverCloseTimer); });
            root.addEventListener('focusout', function (event) {
                if (root.contains(event.relatedTarget) || isPinned(root)) return;
                window.setTimeout(function () {
                    if (!isPinned(root)) setMenuOpen(root, false, false, false);
                }, 0);
            });

            passwordOpen?.addEventListener('click', function () { openPasswordModal(passwordOpen); });
            helpOpen?.addEventListener('click', function () { openHelpModal(helpOpen); });
            photoOpen?.addEventListener('click', function () { openPhotoModal(photoOpen); });
            themeToggle?.addEventListener('change', function () { setTheme(themeToggle.checked); });
        });

        syncThemeControls();
        window.addEventListener('storage', function (event) {
            if (event.key === 'theme') setTheme(event.newValue === 'dark');
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('[data-account-root]')) closeMenus();
        });

        if (passwordModal && passwordForm) {
            passwordModal.querySelectorAll('[data-account-modal-close]').forEach(function (button) {
                button.addEventListener('click', closePasswordModal);
            });

            passwordModal.addEventListener('mousedown', function (event) {
                if (event.target === passwordModal) closePasswordModal();
            });

            passwordModal.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var input = button.closest('.kss-account-field__control')?.querySelector('input');
                    if (!input) return;
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    button.setAttribute('aria-pressed', show ? 'true' : 'false');
                    button.setAttribute('aria-label', (show ? 'Sembunyikan ' : 'Tampilkan ') + input.closest('.kss-account-field').querySelector('label').textContent.toLowerCase());
                    var icon = button.querySelector('i');
                    if (icon) icon.className = show ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye';
                    input.focus();
                });
            });

            passwordForm.querySelectorAll('[data-account-input]').forEach(function (input) {
                input.addEventListener('input', function () {
                    clearPasswordError(input.dataset.accountInput);
                    if (input.name === 'password' || input.name === 'password_confirmation') clearPasswordError('password_confirmation');
                    updatePasswordState();
                });
            });

            passwordForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (passwordSubmit.disabled || passwordSubmitting) return;
                clearPasswordErrors();
                setPasswordSubmitting(true);

                fetch(passwordForm.action, {
                    method: 'POST',
                    body: new FormData(passwordForm),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (payload) {
                        if (!response.ok) throw { status: response.status, payload: payload };
                        return payload;
                    });
                }).then(function (payload) {
                    setPasswordSubmitting(false);
                    closePasswordModal();
                    showToast('success', 'Password Diperbarui', payload.message || 'Password berhasil diperbarui.');
                }).catch(function (error) {
                    setPasswordSubmitting(false);
                    var errors = error.payload?.errors || {};
                    var firstInput = null;

                    Object.keys(errors).forEach(function (name) {
                        var targetName = name === 'password' && String(errors[name][0]).toLowerCase().includes('konfirmasi')
                            ? 'password_confirmation'
                            : name;
                        setPasswordError(targetName, errors[name][0]);
                        firstInput = firstInput || passwordForm.querySelector('[name="' + targetName + '"]');
                    });

                    if (!Object.keys(errors).length) {
                        passwordAlert.textContent = error.status === 429
                            ? 'Terlalu banyak percobaan. Tunggu sebentar lalu coba kembali.'
                            : 'Password belum dapat diperbarui. Periksa koneksi lalu coba kembali.';
                        passwordAlert.hidden = false;
                    }
                    (firstInput || passwordAlert).focus?.();
                });
            });
        }

        if (helpModal) {
            helpModal.querySelectorAll('[data-account-help-close]').forEach(function (button) {
                button.addEventListener('click', closeHelpModal);
            });

            helpModal.addEventListener('mousedown', function (event) {
                if (event.target === helpModal) closeHelpModal();
            });
        }

        photoModal.querySelectorAll('[data-account-photo-close]').forEach(function (button) {
            button.addEventListener('click', closePhotoModal);
        });

        photoModal.addEventListener('mousedown', function (event) {
            if (event.target === photoModal) closePhotoModal();
        });

        photoDropzone.addEventListener('click', function () { photoInput.click(); });
        photoInput.addEventListener('change', function () { selectPhoto(photoInput.files?.[0]); });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            photoDropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                if (!photoUploading) photoDropzone.classList.add('is-dragging');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            photoDropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                photoDropzone.classList.remove('is-dragging');
            });
        });

        photoDropzone.addEventListener('drop', function (event) {
            if (photoUploading) return;
            selectPhoto(event.dataTransfer?.files?.[0]);
        });

        photoDeleteOpen.addEventListener('click', function () {
            if (!currentPhotoUrl || photoUploading || photoDeleting) return;
            setPhotoDeleteConfirm(true);
        });
        photoDeleteCancel.addEventListener('click', function () { setPhotoDeleteConfirm(false); });
        photoDeleteButton.addEventListener('click', function () {
            if (!currentPhotoUrl || photoDeleting) return;
            setPhotoAlert('');
            setPhotoDeleting(true);

            fetch(photoForm.dataset.accountPhotoDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': photoForm.querySelector('[name="_token"]').value
                }
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (payload) {
                    if (!response.ok) throw { status: response.status, payload: payload };
                    return payload;
                });
            }).then(function (payload) {
                setPhotoDeleting(false);
                clearAccountAvatars();
                closePhotoModal();
                showToast('success', 'Foto Profil Dihapus', payload.message || 'Foto profil berhasil dihapus.');
            }).catch(function (error) {
                setPhotoDeleting(false);
                var message = error.status === 429
                    ? 'Terlalu banyak percobaan. Tunggu sebentar lalu coba kembali.'
                    : 'Foto belum dapat dihapus. Periksa koneksi lalu coba kembali.';
                setPhotoAlert(message);
                photoDeleteButton.focus();
            });
        });

        photoPreview.addEventListener('error', function () {
            photoPreview.hidden = true;
            photoPreviewFallback.hidden = false;
            if (selectedPhoto) {
                setPhotoAlert('Foto tidak dapat dipreview. Pilih file gambar lain.');
                photoSubmit.disabled = true;
            }
        });

        photoForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!selectedPhoto || photoUploading) return;

            setPhotoAlert('');
            setPhotoUploading(true);
            setPhotoProgress(0, false);
            var startedAt = Date.now();
            var formData = new FormData(photoForm);
            formData.set('profile_photo', selectedPhoto, selectedPhoto.name);
            var request = new XMLHttpRequest();
            request.open('POST', photoForm.action, true);
            request.timeout = 60000;
            request.setRequestHeader('Accept', 'application/json');
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            request.upload.addEventListener('progress', function (progressEvent) {
                if (progressEvent.lengthComputable) {
                    setPhotoProgress((progressEvent.loaded / progressEvent.total) * 100, false);
                } else {
                    setPhotoProgress(0, true);
                }
            });

            request.addEventListener('load', function () {
                var payload = {};
                try { payload = JSON.parse(request.responseText || '{}'); } catch (error) { payload = {}; }

                if (request.status >= 200 && request.status < 300) {
                    setPhotoProgress(100, false);
                    photoProgressLabel.textContent = 'Upload selesai';
                    finishPhotoUpload(function () {
                        setPhotoUploading(false);
                        updateAccountAvatars(payload.photo_url);
                        closePhotoModal();
                        showToast('success', 'Foto Profil Diperbarui', payload.message || 'Foto profil berhasil diperbarui.');
                    }, startedAt);
                    return;
                }

                finishPhotoUpload(function () {
                    setPhotoUploading(false);
                    photoProgress.hidden = true;
                    var message = payload.errors?.profile_photo?.[0]
                        || (request.status === 429
                            ? 'Terlalu banyak percobaan. Tunggu sebentar lalu coba kembali.'
                            : 'Foto belum dapat diunggah. Periksa file lalu coba kembali.');
                    setPhotoAlert(message);
                    photoDropzone.focus();
                }, startedAt);
            });

            function handleNetworkFailure(message) {
                finishPhotoUpload(function () {
                    setPhotoUploading(false);
                    photoProgress.hidden = true;
                    setPhotoAlert(message);
                    photoDropzone.focus();
                }, startedAt);
            }

            request.addEventListener('error', function () {
                handleNetworkFailure('Koneksi terputus saat mengunggah. Periksa koneksi lalu coba kembali.');
            });
            request.addEventListener('timeout', function () {
                handleNetworkFailure('Upload memerlukan waktu terlalu lama. Silakan coba kembali.');
            });
            request.send(formData);
        });

        function trapFocus(event, modal, panel) {
            if (!modal || !panel || event.key !== 'Tab' || !modal.classList.contains('is-open')) return false;
            var focusable = Array.from(panel.querySelectorAll('button:not(:disabled), input:not(:disabled), [tabindex]:not([tabindex="-1"])'));
            if (!focusable.length) return false;
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
            return true;
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                if (photoModal.classList.contains('is-open')) closePhotoModal();
                else if (passwordModal?.classList.contains('is-open')) closePasswordModal();
                else if (helpModal?.classList.contains('is-open')) closeHelpModal();
                else closeMenus();
                return;
            }
            if (trapFocus(event, photoModal, photoPanel)) return;
            if (trapFocus(event, passwordModal, passwordPanel)) return;
            trapFocus(event, helpModal, helpPanel);
        });

        if (passwordForm) updatePasswordState();
        resetPhotoForm();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAccountSettings);
    else initAccountSettings();
})();
