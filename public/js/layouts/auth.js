document.addEventListener("DOMContentLoaded", function() {

            // ==========================================
            // 1. TOGGLE PASSWORD VISIBILITY LOGIC
            // ==========================================
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Ganti icon mata
                const icon = this.querySelector('i');
                if (type === 'text') {
                    icon.classList.remove('fi-rr-eye-crossed');
                    icon.classList.add('fi-rr-eye');
                } else {
                    icon.classList.remove('fi-rr-eye');
                    icon.classList.add('fi-rr-eye-crossed');
                }
            });


            // ==========================================
            // 1b. PERINGATAN CAPS LOCK
            // ==========================================
            // CapsLock hanya bisa dideteksi lewat event keyboard (getModifierState),
            // jadi peringatan muncul setelah pengguna menekan tombol di kolom
            // username/password dan otomatis hilang saat dimatikan atau kolom blur.
            const capsHint = document.getElementById('capsHint');
            if (capsHint) {
                const watched = [
                    document.getElementById('password'),
                    document.getElementById('username'),
                ].filter(Boolean);

                const updateCaps = function (event) {
                    const on = typeof event.getModifierState === 'function'
                        && event.getModifierState('CapsLock');
                    const focused = watched.indexOf(document.activeElement) !== -1;
                    capsHint.classList.toggle('is-visible', !!on && focused);
                };

                watched.forEach(function (input) {
                    input.addEventListener('keydown', updateCaps);
                    input.addEventListener('keyup', updateCaps);
                    input.addEventListener('blur', function () {
                        capsHint.classList.remove('is-visible');
                    });
                });
            }


            // ==========================================
            // 2. DARK MODE TOGGLE LOGIC
            // ==========================================
            const themeBtn = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const body = document.body;

            // Cek local storage theme
            let isDarkMode = localStorage.getItem('theme') === 'dark';
            if (isDarkMode) enableDarkMode(false);

            if(themeBtn && themeIcon) {
                themeBtn.addEventListener('click', () => {
                    isDarkMode = !isDarkMode;
                    if (isDarkMode) {
                        themeIcon.style.transform = 'rotate(180deg)';
                        setTimeout(() => {
                            enableDarkMode(true);
                            localStorage.setItem('theme', 'dark');
                        }, 150);
                    } else {
                        themeIcon.style.transform = 'rotate(-180deg)';
                        setTimeout(() => {
                            disableDarkMode(true);
                            localStorage.setItem('theme', 'light');
                        }, 150);
                    }
                });
            }

            function enableDarkMode(animate) {
                body.classList.add('dark-mode');
                themeIcon.className = 'fi fi-rr-moon';
                if(animate) themeIcon.style.transform = 'rotate(0deg)';
            }

            function disableDarkMode(animate) {
                body.classList.remove('dark-mode');
                themeIcon.className = 'fi fi-rr-sun';
                if(animate) themeIcon.style.transform = 'rotate(0deg)';
            }

            // ==========================================
            // 3. LOGIN BUTTON LOADING STATE
            // ==========================================
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const loginButtonText = loginButton.querySelector('.login-button-text');
            const firstInvalidInput = document.querySelector('.custom-input.is-invalid');

            loginForm.addEventListener('submit', function(event) {
                if (loginButton.classList.contains('is-loading')) {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();
                loginButton.classList.add('is-loading');
                loginButton.disabled = true;
                loginButton.setAttribute('aria-busy', 'true');
                loginButtonText.textContent = 'Verifikasi...';

                setTimeout(function() {
                    loginForm.submit();
                }, 700);
            });

            firstInvalidInput?.focus({ preventScroll: true });

            // Toast notifikasi ditangani komponen bersama (partials/toast.blade.php).
        });

        window.addEventListener('load', function() {
            var sk = document.getElementById('sk-overlay');
            if (sk) {
                sk.classList.add('sk-done');
                setTimeout(function() { sk.remove(); }, 600);
            }
        });
