<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Laporan KSS</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">

    <!-- LINK BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

    <!-- LINK FLATICON UICONS -->
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>

    <style>
        :root {
            --blue-main: #2563EB;
            --blue-hover: #1D4ED8;
            --blue-active: #1E40AF;
            --blue-main-25: #2563eb40;
            --blue-main-10: #2563eb1a;
            --blue-main-5: #2563eb0d;
            --blue-main-2: rgba(37, 99, 235, 0.02);
            --blue-main-40: #2563eb66;
            --blue-bg: #E5F1FF;
            --blue-input-focus: #fafcff;
            --blue-bg-25: #E5F1FF40;

            --cyan-main: #0EA5E9;
            --cyan-hover: #0B83B5;
            --cyan-active: #09658B;
            --cyan-main-25: #0ea5e940;
            --cyan-main-10: #0ea5e91a;
            --cyan-main-5: #0ea5e90d;
            --cyan-main-40: #0ea5e966;

            --red-main: #D20000;
            --red-hover: #B80000;
            --red-active: #9F0000;
            --red-main-25: #d2000040;
            --red-main-10: #d200001a;
            --red-main-5: #d200000d;
            --red-main-40: #d2000066;
            --red-input-focus: #fff5f5;

            --success: #10B981;
            --success-hover: #0F9A6B;
            --success-active: #0E7A55;
            --success-25: #10b77f40;
            --success-10: #10b77f1a;
            --success-5: #10b77f0d;
            --success-40: #10b77f66;

            --orange-main: #F7931E;
            --orange-hover: #E67E00;
            --orange-active: #CC6F00;
            --orange-main-25: #f9731640;
            --orange-main-10: #f973161a;
            --orange-main-5: #f973160d;
            --orange-main-40: #f9731666;
            --orange-input-focus: #fef4e8;
            --orange-bg: #FEF4E8;

            --black: #000000;
            --black-25: #00000040;
            --black-10: lch(0% 0 0 / 0.102);
            --black-5: #0000000d;
            --black-40: #00000066;

            --dark-main: #0F172A;
            --dark-secondary: #334155;
            --dark-secondary-10: #3341551A;
            --dark-table-head: #0F172A;
            --muted: #94A3B8;
            --smooth-border:#E2E8F0;
            --main-bg:#F8FAFC;
            --white: #FFFFFF;
            --white-50: rgba(255, 255, 255, 0.2);
            --white-60: rgba(255, 255, 255, 0.3);
            --divider: #CBD5E1;
            --button-color: #FFFFFF;

            --btn-theme-bg: var(--white);
            --btn-theme-border: rgba(0, 0, 0, 0.1);
            --btn-theme-icon: var(--dark-main);

            /* Glass Variables */
            --glass-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 100%);
            --glass-border-light: rgba(255, 255, 255, 0.7);
            --glass-border-dark: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
            --glass-inner-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3), inset 0 2px 10px rgba(255, 255, 255, 0.4);
            --input-glass-bg: rgba(255, 255, 255, 0.5);
        }

        body.dark-mode {
            --main-bg: #0F172A;
            --white: #1E293B;
            --white-50: rgba(30, 41, 59, 0.5);
            --white-60: rgba(30, 41, 59, 0.6);
            --dark-main: #F8FAFC;
            --dark-secondary: #CBD5E1;
            --dark-secondary-10: rgba(203, 213, 225, 0.1);
            --dark-table-head: #0F172A;
            --muted: #94A3B8;
            --smooth-border: #334155;
            --divider: #334155;
            --black: #FFFFFF;
            --black-25: #ffffff40;
            --black-10: #ffffff1a;
            --black-5: #ffffff0d;
            --black-40: #ffffff66;
            --button-color: #FFFFFF;
            --btn-theme-bg: #334155;
            --btn-theme-border: rgba(255, 255, 255, 0.1);
            --btn-theme-icon: #F1F5F9;
            --blue-main: #3B82F6;
            --blue-hover: #60A5FA;
            --blue-active: #93C5FD;
            --blue-main-25: #3b82f640;
            --blue-main-10: #3b82f61a;
            --blue-main-5: #3b82f60d;
            --blue-main-2: rgba(59, 130, 246, 0.02);
            --blue-main-40: #3b82f666;
            --blue-bg: #243447;
            --blue-input-focus: #334155;
            --blue-bg-25: #24344740;
            --cyan-main: #0EA5E9;
            --cyan-hover: #38BDF8;
            --cyan-active: #7DD3FC;
            --cyan-main-25: #0ea5e940;
            --cyan-main-10: #0ea5e91a;
            --cyan-main-5: #0ea5e90d;
            --cyan-main-40: #0ea5e966;
            --red-main: #EF4444;
            --red-hover: #F87171;
            --red-active: #FCA5A5;
            --red-main-25: #ef444440;
            --red-main-10: #ef44441a;
            --red-main-5: #ef44440d;
            --red-main-40: #ef444466;
            --red-input-focus: #2a1a1a;
            --success: #10B981;
            --success-hover: #34D399;
            --success-active: #6EE7B7;
            --success-25: #10b98140;
            --success-10: #10b9811a;
            --success-5: #10b9810d;
            --success-40: #10b98166;
            --orange-main: #F97316;
            --orange-hover: #FB923C;
            --orange-active: #FDBA74;
            --orange-main-25: #f9731640;
            --orange-main-10: #f973161a;
            --orange-main-5: #f973160d;
            --orange-main-40: #f9731666;
            --orange-bg: #431407;
            --orange-input-focus: #2f2111;

            /* Dark Mode Glass Variables */
            --glass-bg: linear-gradient(135deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.4) 100%);
            --glass-border-light: rgba(255, 255, 255, 0.15);
            --glass-border-dark: rgba(0, 0, 0, 0.3);
            --glass-shadow: 0 25px 45px rgba(0, 0, 0, 0.5);
            --glass-inner-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05), inset 0 2px 10px rgba(255, 255, 255, 0.02);
            --input-glass-bg: rgba(0, 0, 0, 0.2);
        }

        .bg-main { background-color: var(--main-bg) !important; }
        .text-main { color: var(--dark-main) !important; }
        .text-secondary { color: var(--dark-secondary) !important; }
        .bg-blue { background-color: var(--blue-bg) !important; }
        .text-muted { color: var(--muted) !important; }
        .text-cyan { color: var(--cyan-main) !important; }
        .text-red { color: var(--red-main) !important; }
        .white-pure { color: var(--white) !important; }
        .white-bg { background-color: var(--white) !important; }
        .blue-bg { background-color: var(--blue-main) !important; }
        .red-bg { background-color: var(--red-main) !important; }
        .cyan-bg { background-color: var(--cyan-main) !important; }
        .orange-bg { background-color: var(--orange-main) !important; }
        .success-bg { background-color: var(--success) !important; }

        /* Font Size Utility */
        .fsize-9  { font-size: 9px !important; }
        .fsize-10 { font-size: 10px !important; }
        .fsize-11 { font-size: 11px !important; }
        .fsize-12 { font-size: 12px !important; }
        .fsize-13 { font-size: 13px !important; }
        .fsize-14 { font-size: 14px !important; }
        .fsize-16 { font-size: 16px !important; }
        .fsize-18 { font-size: 18px !important; }
        .fsize-20 { font-size: 20px !important; }
        .fsize-24 { font-size: 24px !important; }

        /* Font Weight Utility */
        .fw-300 { font-weight: 300 !important; }
        .fw-400 { font-weight: 400 !important; }
        .fw-500 { font-weight: 500 !important; }
        .fw-600 { font-weight: 600 !important; }
        .fw-700 { font-weight: 700 !important; }

        /* Gap Utility */
        .gap-2 { gap: 2px !important; }
        .gap-4  { gap: 4px !important; }
        .gap-6  { gap: 6px !important; }
        .gap-8  { gap: 8px !important; }
        .gap-10 { gap: 10px !important; }
        .gap-15 { gap: 15px !important; }
        .gap-20 { gap: 20px !important; }
        .gap-30 { gap: 30px !important; }
        .gap-40 { gap: 40px !important; }

        /* Border Radius Utility */
        .br-4   { border-radius: 4px !important; }
        .br-5   { border-radius: 5px !important; }
        .br-6   { border-radius: 6px !important; }
        .br-8   { border-radius: 8px !important; }
        .br-10  { border-radius: 10px !important; }
        .br-12  { border-radius: 12px !important; }
        .br-15  { border-radius: 15px !important; }
        .br-20  { border-radius: 20px !important; }
        .br-100 { border-radius: 100px !important; }

        .p-navbar  { padding: 15px 22px !important; }
        .p-content { padding: 0 100px !important; }
        .p-main { padding: 10px 20px !important; }
        .p-20 { padding: 20px !important; }
        .p-30 { padding: 30px !important; }
        .p-table { padding: 1px 0 !important;}
        .p-empty { padding: 60px 0 !important; }

        .size-logo { width: 108px !important; height: auto !important; }
        .divider-vertical { border-left: 1px solid var(--divider) !important; height: 30px !important; }
        .flexible { flex: 1 0 0 !important; }

        /* =========================================
           GLOBAL STYLES & LAYOUT
           ========================================= */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--main-bg);
            color: var(--dark-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            transition: background-color 0.5s ease-out, color 0.5s ease-out;
            overflow: hidden; /* Mencegah scroll karena blobs */
            position: relative;
        }

        /* Utility Classes */
        .fw-400 { font-weight: 400 !important; }
        .fw-500 { font-weight: 500 !important; }
        .fw-600 { font-weight: 600 !important; }

        /* Theme Toggle Button */
        .btn-theme {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
            background-color: var(--btn-theme-bg);
            border: 1px solid var(--btn-theme-border);
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            z-index: 100;
        }
        .btn-theme:hover { border-color: var(--blue-main); transform: scale(1.05); }
        .btn-theme i { color: var(--btn-theme-icon); font-size: 20px; transition: transform 0.4s; position: relative; top: 3px; }

        /* =========================================
           BACKGROUND ANIMATED BLOBS
           ========================================= */
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.6;
            border-radius: 50%;
            animation: floatBlob 12s infinite ease-in-out alternate;
        }

        .blob-1 {
            top: -10%;
            left: -5%;
            width: 50vw;
            height: 50vw;
            background: var(--blue-main);
            animation-delay: 0s;
        }

        .blob-2 {
            bottom: -10%;
            right: -5%;
            width: 40vw;
            height: 40vw;
            background: var(--cyan-main);
            animation-delay: -5s;
        }

        body.dark-mode .blob { opacity: 0.3; }

        @keyframes floatBlob {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(5%, 5%) scale(1.1); }
            100% { transform: translate(-5%, -5%) scale(0.9); }
        }

        /* =========================================
           LIQUID GLASS LOGIN CARD
           ========================================= */
        .login-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            z-index: 10;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(28px) saturate(150%);
            -webkit-backdrop-filter: blur(28px) saturate(150%);

            /* Border 3D edge effect */
            border-top: 1px solid var(--glass-border-light);
            border-left: 1px solid var(--glass-border-light);
            border-right: 1px solid var(--glass-border-dark);
            border-bottom: 1px solid var(--glass-border-dark);

            border-radius: 50px;
            box-shadow: var(--glass-shadow), var(--glass-inner-shadow);
            padding: 40px 35px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: all 0.5s ease;
        }

        /* Glassmorphism for Dark Mode */
        body.dark-mode .login-card {
            background: rgba(30, 41, 59, 0.45);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .login-logo {
            width: auto;
            height: 40px;
            margin-bottom: 10px;
            object-fit: contain;
        }

        .login-title { font-size: 20px; font-weight: 600; color: var(--dark-main); margin: 0; }
        .login-subtitle { font-size: 12px; color: var(--dark-secondary); margin: 0; font-weight: 400; }

        /* =========================================
           INPUTS & FORMS (PILL SHAPE)
           ========================================= */
        .form-group { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; width: 100%; }
        .form-group label { font-size: 12px; font-weight: 500; color: var(--dark-secondary); margin-left: 10px; }

        .input-wrapper { position: relative; display: flex; align-items: center; width: 100%; }

        .input-wrapper .input-icon-right {
            position: absolute;
            right: 20px;
            color: var(--muted);
            font-size: 16px;
            cursor: pointer;
            transition: color 0.3s;
            display: flex;
            align-items: center;
        }

        .input-wrapper .input-icon-right i {
            position: relative;
            top: 3px;
        }

        .input-wrapper .input-icon-right:hover { color: var(--blue-main); }

        .custom-input {
            width: 100%;
            padding: 14px 45px 14px 20px; /* Space for icon on right */
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 50px; /* Pill Shape */
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            color: var(--dark-main);
            background: rgba(255, 255, 255, 0.7);
            outline: none;
            transition: all 0.3s ease-out;
        }

        .custom-input:focus {
            background: #ffffff;
            border-color: var(--blue-main);
            box-shadow: 0 0 0 4px var(--blue-main-10);
        }

        body.dark-mode .custom-input {
            background: rgba(15, 23, 42, 0.5);
            border-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        body.dark-mode .custom-input:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--blue-main);
        }

        .custom-input::placeholder { color: var(--muted); font-weight: 400; }

        /* =========================================
           CUSTOM CHECKBOX (Ingat Saya)
           ========================================= */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
            margin-left: 10px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            display: none;
        }

        .custom-checkbox {
            width: 16px;
            height: 16px;
            border: 1px solid var(--muted);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            background-color: transparent;
        }

        .custom-checkbox i {
            color: var(--white);
            font-size: 10px;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s ease;
            position: relative;
            top: 1px;
        }

        .checkbox-wrapper input[type="checkbox"]:checked + .custom-checkbox {
            background-color: var(--blue-main);
            border-color: var(--blue-main);
        }

        .checkbox-wrapper input[type="checkbox"]:checked + .custom-checkbox i {
            opacity: 1;
            transform: scale(1);
        }

        .checkbox-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--dark-secondary);
        }

        /* =========================================
           BUTTON LOGIN (YELLOW PILL)
           ========================================= */
        .btn-login {
            display: flex;
            width: 100%;
            padding: 14px 20px;
            justify-content: center;
            align-items: center;
            gap: 8px;
            border-radius: 50px; /* Pill shape */
            border: none;
            background-color: var(--orange-main);
            color: var(--button-color); /* Dark text for contrast */
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            min-height: 50px;
        }

        .btn-login:hover {
            background-color: var(--orange-hover);
            box-shadow: 0 8px 20px var(--orange-main-active);
            transform: translateY(-2px);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px var(--orange-main-active);
        }

        .btn-login:disabled,
        .btn-login.is-loading {
            cursor: wait;
            opacity: 0.9;
            pointer-events: none;
            transform: none;
        }

        .login-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.45);
            border-top-color: var(--button-color);
            border-radius: 50%;
            animation: sk-rotate 0.7s linear infinite;
            flex: 0 0 auto;
        }

        .btn-login.is-loading .login-spinner {
            display: inline-block;
        }

        /* ============================================================
           LOADING SPINNER - Animasi rotasi saat halaman loading
           ============================================================ */
        @keyframes sk-rotate {
            to { transform: rotate(360deg); }
        }
        .sk-overlay {
            position: fixed;
            inset: 0;
            z-index: 9998;
            background-color: var(--main-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }
        .sk-overlay.sk-done {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .sk-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid var(--smooth-border);
            border-top-color: var(--blue-main);
            border-radius: 50%;
            animation: sk-rotate 0.8s linear infinite;
        }

        @keyframes authToastEnter {
            from { opacity: 0; transform: translateY(-12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes authToastAttention {
            0%, 100% { transform: translateX(0); }
            18% { transform: translateX(-5px); }
            36% { transform: translateX(5px); }
            54% { transform: translateX(-3px); }
            72% { transform: translateX(3px); }
        }

        @keyframes loginCardAttention {
            0%, 100% { transform: translateX(0); }
            15% { transform: translateX(-6px); }
            30% { transform: translateX(6px); }
            45% { transform: translateX(-4px); }
            60% { transform: translateX(4px); }
            75% { transform: translateX(-2px); }
        }

        .auth-toast-viewport {
            position: fixed;
            top: 18px;
            left: 50%;
            width: min(460px, calc(100vw - 32px));
            z-index: 10020;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            transform: translateX(-50%);
            pointer-events: none;
        }

        .auth-toast {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 24px;
            border-top: 1px solid var(--glass-border-light);
            border-left: 1px solid var(--glass-border-light);
            border-right: 1px solid var(--glass-border-dark);
            border-bottom: 1px solid var(--glass-border-dark);
            background: var(--glass-bg);
            box-shadow:
                var(--glass-shadow),
                var(--glass-inner-shadow);
            backdrop-filter: blur(28px) saturate(150%);
            -webkit-backdrop-filter: blur(28px) saturate(150%);
            color: var(--dark-main);
            pointer-events: auto;
            opacity: 0;
            animation: authToastEnter 0.32s ease-out forwards, authToastAttention 0.62s ease-in-out 0.45s 1;
        }

        .auth-toast::before {
            content: "";
            position: absolute;
            inset: 2px;
            border-radius: 22px;
            background: transparent;
            pointer-events: none;
            z-index: -1;
        }

        .auth-toast::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            box-shadow: none;
            pointer-events: none;
            z-index: -1;
        }

        body.dark-mode .auth-toast {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(30, 41, 59, 0.45);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .auth-toast.error {
            border-color: var(--red-main-25);
        }

        .auth-toast.success {
            border-color: var(--success-25);
        }

        .auth-toast-icon {
            width: 36px;
            height: 36px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            font-size: 16px;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.70),
                inset 0 -10px 20px rgba(255,255,255,0.10),
                0 8px 18px rgba(15,23,42,0.08);
        }

        .auth-toast-icon i {
            position: relative;
            top: 2px;
        }

        .auth-toast.error .auth-toast-icon {
            color: var(--red-main);
            background:
                linear-gradient(145deg, rgba(255,255,255,0.30), rgba(255,255,255,0.08)),
                rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.34);
        }

        .auth-toast.success .auth-toast-icon {
            color: var(--success);
            background:
                linear-gradient(145deg, rgba(255,255,255,0.30), rgba(255,255,255,0.08)),
                rgba(16,185,129,0.12);
            border: 1px solid rgba(16,185,129,0.34);
        }

        .auth-toast-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            min-width: 0;
        }

        .auth-toast-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark-main);
        }

        .auth-toast-text {
            font-size: 12px;
            line-height: 1.45;
            color: var(--dark-secondary);
        }

        .auth-toast-close {
            border: none;
            background: transparent;
            color: var(--muted);
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.22);
            transition: 0.2s ease;
        }

        .auth-toast-close:hover {
            background: var(--red-main-10);
            color: var(--red-main);
        }

        .login-card.has-auth-error {
            animation: loginCardAttention 0.58s ease-in-out 0.16s 1;
        }

        .custom-input.is-invalid {
            border-color: var(--red-main) !important;
            background: var(--red-input-focus);
            box-shadow: 0 0 0 4px var(--red-main-10);
        }

        @media (max-width: 480px) {
            .auth-toast-viewport {
                top: 12px;
                width: calc(100vw - 24px);
            }

            .auth-toast {
                padding: 10px 12px;
                gap: 9px;
                border-radius: 22px;
            }

            .auth-toast-icon {
                width: 34px;
                height: 34px;
                border-radius: 12px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .auth-toast,
            .login-card.has-auth-error {
                animation: none !important;
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Dark mode init lebih awal agar overlay langsung pakai warna yang benar -->
    <script>if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark-mode');</script>

    <!-- LOADING SPINNER -->
    <div class="sk-overlay" id="sk-overlay">
        <div class="sk-spinner"></div>
    </div>

    <!-- Animated Background Blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    @include('auth.layouts.theme')

    @php
        $authToastMessages = collect();

        if (session('success')) {
            $authToastMessages->push([
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => session('success'),
                'icon' => 'fi fi-rr-check',
            ]);
        }

        if (session('error')) {
            $authToastMessages->push([
                'type' => 'error',
                'title' => 'Login belum berhasil',
                'message' => session('error'),
                'icon' => 'fi fi-rr-exclamation',
            ]);
        }

        if ($errors->any()) {
            $authToastMessages->push([
                'type' => 'error',
                'title' => 'Login belum berhasil',
                'message' => $errors->first(),
                'icon' => 'fi fi-rr-exclamation',
            ]);
        }
    @endphp

    @if ($authToastMessages->isNotEmpty())
        <div class="auth-toast-viewport" aria-live="polite" aria-atomic="true">
            @foreach ($authToastMessages as $toast)
                <div class="auth-toast {{ $toast['type'] }}" data-duration="5200" role="status">
                    <div class="auth-toast-icon"><i class="{{ $toast['icon'] }}"></i></div>
                    <div class="auth-toast-copy">
                        <span class="auth-toast-title">{{ $toast['title'] }}</span>
                        <span class="auth-toast-text">{{ $toast['message'] }}</span>
                    </div>
                    <button type="button" class="auth-toast-close" aria-label="Tutup notifikasi">
                        <i class="fi fi-br-cross-small"></i>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Wrapper Content -->
    <div class="login-wrapper">
        @yield('content')
    </div>

    <script>
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

            document.querySelectorAll('.auth-toast').forEach((toast, index) => {
                const closeButton = toast.querySelector('.auth-toast-close');
                const duration = Number(toast.dataset.duration || 5200) + (index * 180);
                let timer = window.setTimeout(() => hideToast(toast), duration);

                closeButton?.addEventListener('click', () => hideToast(toast));
                toast.addEventListener('mouseenter', () => window.clearTimeout(timer));
                toast.addEventListener('mouseleave', () => {
                    timer = window.setTimeout(() => hideToast(toast), 1800);
                });
            });

            function hideToast(toast) {
                if (!toast || toast.dataset.hiding === 'true') return;
                toast.dataset.hiding = 'true';
                toast.style.transition = 'opacity 0.28s ease, transform 0.28s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-8px)';
                window.setTimeout(() => toast.remove(), 300);
            }

        });

        window.addEventListener('load', function() {
            var sk = document.getElementById('sk-overlay');
            if (sk) {
                sk.classList.add('sk-done');
                setTimeout(function() { sk.remove(); }, 600);
            }
        });
    </script>
</body>
</html>
