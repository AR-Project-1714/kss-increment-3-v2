@extends('auth.layouts.app')

@section('content')
        <form class="login-card {{ $errors->any() ? 'has-auth-error' : '' }}" id="loginForm" action="{{ route('login.authenticate') }}" method="POST">
            @csrf

            <!-- Header Logo & Title -->
            <div class="login-header">
                <img src="{{ asset('assets/KSS.png') }}" alt="Logo KSS" class="login-logo" onerror="this.style.display='none'; this.insertAdjacentHTML('afterend', '<h2 style=\'color: var(--blue-main); font-weight: 800; margin-bottom: 10px;\'>KSS</h2>');">
                <h1 class="login-title">Masukkan Data Akun</h1>
                <p class="login-subtitle">Sistem Manajemen Dokumen Operasional</p>
            </div>

            <!-- Form Inputs -->
            <div class="d-flex flex-column" style="gap: 18px;">

                <!-- Username -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="custom-input @error('username') is-invalid @enderror" placeholder="Masukkan username" value="{{ old('username') }}" required autocomplete="username" @error('username') aria-invalid="true" @enderror>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="custom-input @error('password') is-invalid @enderror" placeholder="Masukkan password" required autocomplete="current-password" @error('password') aria-invalid="true" @enderror>
                        <span class="input-icon-right" id="togglePassword" title="Lihat Password"><i class="fi fi-rr-eye-crossed"></i></span>
                        <div class="caps-hint" id="capsHint" role="alert" aria-live="polite">
                            <i class="fi fi-rr-shield-exclamation"></i>
                            <span>Caps Lock aktif</span>
                        </div>
                    </div>
                </div>

                <!-- Ingat Saya Checkbox -->
                <label class="checkbox-wrapper">
                    <input type="checkbox" id="rememberMe" name="remember" value="1" @checked(old('remember'))>
                    <div class="custom-checkbox">
                        <i class="fi fi-br-check"></i>
                    </div>
                    <span class="checkbox-label">Ingat Saya</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-login" id="loginButton">
                <span class="login-spinner" aria-hidden="true"></span>
                <span class="login-button-text">Masuk</span>
            </button>

        </form>
@endsection
