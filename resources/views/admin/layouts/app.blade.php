<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KSS Admin')</title>

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">

    <!-- Flaticon UICONS -->
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-solid-rounded/css/uicons-solid-rounded.css">

    {{-- =====================================================
         SHARED SHELL CSS (dipakai semua halaman admin)
         ===================================================== --}}
    <style>
        /* =============================================
           CSS VARIABLES — Light Mode
           ============================================= */
        :root {
            /* Brand Blues */
            --blue-main:      #2563EB;
            --blue-hover:     #1D4ED8;
            --blue-active:    #1E40AF;
            --blue-bg:        #E5F1FF;
            --blue-main-40:   rgba(37,99,235,0.40);
            --blue-main-25:   rgba(37,99,235,0.25);
            --blue-main-10:   rgba(37,99,235,0.10);
            --blue-main-5:    rgba(37,99,235,0.05);
            --blue-main-3:    rgba(37,99,235,0.03);

            /* Cyan */
            --cyan-main:    #0EA5E9;
            --cyan-main-10: rgba(14,165,233,0.10);
            --cyan-main-20: rgba(14,165,233,0.20);

            /* Success / Green */
            --success:       #10B981;
            --success-hover: #0F9A6B;
            --success-10:    rgba(16,185,129,0.10);

            /* Orange */
            --orange-main:  #F7931E;
            --orange-hover: #E67E00;
            --orange-main-10: rgba(247,147,30,0.10);

            /* Red */
            --red-main:     #D20000;
            --red-hover:    #B80000;
            --red-main-10:  rgba(210,0,0,0.10);
            --red-main-5:   rgba(210,0,0,0.05);

            /* Grayscale */
            --black:           #0F172A;
            --black-secondary: #334155;
            --muted:           #94A3B8;
            --smooth-border:   #E2E8F0;
            --divider:         #CBD5E1;
            --main-bg:         #F8FAFC;
            --white:           #FFFFFF;
            --white-pure:      #FFFFFF;

            /* Theme button */
            --btn-theme-bg:     var(--white);
            --btn-theme-icon:   var(--black);
            --btn-theme-border: rgba(0,0,0,0.10);

            /* Layout */
            --sidebar-width:     234px;
            --sidebar-collapsed: 60px;
            --sidebar-transition: 0.32s cubic-bezier(0.4,0,0.2,1);
        }

        /* =============================================
           CSS VARIABLES — Dark Mode
           ============================================= */
        body.dark-mode {
            --main-bg:         #0F172A;
            --white:           #1E293B;
            --black:           #F8FAFC;
            --black-secondary: #CBD5E1;
            --muted:           #64748B;
            --smooth-border:   #334155;
            --divider:         #334155;

            --blue-main:     #3B82F6;
            --blue-hover:    #60A5FA;
            --blue-active:   #93C5FD;
            --blue-bg:       #1E293B;
            --blue-main-40:  rgba(59,130,246,0.40);
            --blue-main-25:  rgba(59,130,246,0.25);
            --blue-main-10:  rgba(59,130,246,0.10);
            --blue-main-5:   rgba(59,130,246,0.05);
            --blue-main-3:   rgba(59,130,246,0.03);

            --red-main:    #EF4444;
            --red-hover:   #F87171;
            --red-main-10: rgba(239,68,68,0.10);
            --red-main-5:  rgba(239,68,68,0.05);

            --btn-theme-bg:     #334155;
            --btn-theme-icon:   #F1F5F9;
            --btn-theme-border: rgba(255,255,255,0.10);
        }

        /* =============================================
           RESET & BASE
           ============================================= */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--main-bg);
            color: var(--black);
            display: flex;
            height: 100vh;
            overflow: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        a { text-decoration: none; }

        /* =============================================
           UTILITIES
           ============================================= */
        .fsize-8  { font-size: 8px !important; }
        .fsize-9  { font-size: 9px !important; }
        .fsize-10 { font-size: 10px !important; }
        .fsize-11 { font-size: 11px !important; }
        .fsize-12 { font-size: 12px !important; }
        .fsize-14 { font-size: 14px !important; }
        .fsize-16 { font-size: 16px !important; }
        .fsize-20 { font-size: 20px !important; }
        .fsize-24 { font-size: 24px !important; }

        .fw-300 { font-weight: 300 !important; }
        .fw-400 { font-weight: 400 !important; }
        .fw-500 { font-weight: 500 !important; }
        .fw-600 { font-weight: 600 !important; }
        .fw-700 { font-weight: 700 !important; }

        .text-muted-custom     { color: var(--muted) !important; }
        .text-secondary-custom { color: var(--black-secondary) !important; }
        .text-blue             { color: var(--blue-main) !important; }
        .text-success          { color: var(--success) !important; }
        .text-orange           { color: var(--orange-main) !important; }
        .text-red              { color: var(--red-main) !important; }
        .text-white            { color: var(--white-pure) !important; }

        .gap-6  { gap: 6px !important; }
        .gap-8  { gap: 8px !important; }
        .gap-10 { gap: 10px !important; }
        .flexible { flex: 1 0 0 !important; }

        /* Theme toggle animation helpers */
        .prepare-from-top    { transform: translateY(-150%); opacity: 0; }
        .prepare-from-bottom { transform: translateY(150%);  opacity: 0; }
        .animate-out-up      { transform: translateY(-150%) !important; opacity: 0 !important; }
        .animate-out-down    { transform: translateY(150%)  !important; opacity: 0 !important; }

        /* =============================================
           SIDEBAR
           ============================================= */
        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--white);
            border-right: 1px solid var(--smooth-border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 16px;
            overflow: hidden;
            flex-shrink: 0;
            transition:
                width var(--sidebar-transition),
                min-width var(--sidebar-transition),
                padding var(--sidebar-transition),
                background-color 0.3s ease;
            position: relative;
            z-index: 100;
        }

        body.sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed);
            min-width: var(--sidebar-collapsed);
            padding: 20px 10px;
        }

        /* ---- Logo ---- */
        .sidebar__logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 4px;
            margin-bottom: 8px;
            overflow: hidden;
            white-space: nowrap;
        }

        .sidebar__logo-icon {
            width: 32px;
            height: 32px;
            background-color: var(--blue-main);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 15px;
            flex-shrink: 0;
        }

        .sidebar__logo-icon i { position: relative; top: 1px; }

        .sidebar__logo-text {
            font-size: 14px;
            font-weight: 700;
            color: var(--blue-main);
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.2s ease, max-width var(--sidebar-transition);
            max-width: 150px;
        }

        body.sidebar-collapsed .sidebar__logo-text {
            opacity: 0;
            max-width: 0;
        }

        /* ---- Navigation ---- */
        .sidebar__nav {
            display: flex;
            flex-direction: column;
            flex: 1;
            margin-top: 6px;
            gap: 0;
            overflow-y: auto;
            scrollbar-width: none;
        }

        .sidebar__nav::-webkit-scrollbar { display: none; }

        .sidebar__section {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 10px 0;
            border-bottom: 1px solid var(--smooth-border);
        }

        .sidebar__section:last-child { border-bottom: none; }

        .sidebar__section-label {
            font-size: 9px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 0.6px;
            text-transform: uppercase;
            padding: 0 10px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.15s ease;
        }

        body.sidebar-collapsed .sidebar__section-label { opacity: 0; }

        /* ---- Nav Item ---- */
        .sidebar__nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--muted);
            font-size: 12px;
            font-weight: 400;
            white-space: nowrap;
            position: relative;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sidebar__nav-item:hover {
            background-color: var(--blue-main-5);
            color: var(--black-secondary);
        }

        .sidebar__nav-item.active {
            background-color: var(--blue-main-10);
            color: var(--blue-active);
            font-weight: 500;
        }

        .sidebar__nav-item .nav-icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
        }

        .sidebar__nav-item .nav-icon i { position: relative; top: 1px; }

        .sidebar__nav-item .nav-label {
            flex: 1;
            overflow: hidden;
            transition: opacity 0.15s ease, max-width var(--sidebar-transition);
            max-width: 160px;
        }

        body.sidebar-collapsed .sidebar__nav-item .nav-label {
            opacity: 0;
            max-width: 0;
        }

        .sidebar__nav-item .nav-chevron {
            font-size: 10px;
            transition: transform 0.25s ease, opacity 0.15s ease;
            display: flex;
            align-items: center;
        }

        .sidebar__nav-item .nav-chevron i { position: relative; top: 1px; }

        .sidebar__nav-item.submenu-open .nav-chevron { transform: rotate(180deg); }

        body.sidebar-collapsed .sidebar__nav-item .nav-chevron { opacity: 0; }

        /* Open submenu parent highlight */
        .sidebar__nav-item.submenu-open {
            background-color: var(--blue-main-5);
            color: var(--black-secondary);
        }

        /* ---- Collapsed Tooltip ---- */
        body.sidebar-collapsed .sidebar__nav-item[data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--black);
            color: var(--white-pure);
            font-size: 11px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        body.sidebar-collapsed .sidebar__nav-item[data-tooltip]:hover::after { opacity: 1; }

        /* ---- Submenu ---- */
        .sidebar__submenu-wrapper {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height 0.3s ease, opacity 0.3s ease;
        }

        .sidebar__submenu-wrapper.open {
            max-height: 200px;
            opacity: 1;
        }

        body.sidebar-collapsed .sidebar__submenu-wrapper { display: none; }

        .sidebar__submenu {
            display: flex;
            gap: 0;
            padding: 4px 0 4px 14px;
        }

        .sidebar__submenu-line {
            width: 1px;
            background-color: var(--smooth-border);
            flex-shrink: 0;
            border-radius: 1px;
        }

        .sidebar__submenu-items {
            display: flex;
            flex-direction: column;
            gap: 1px;
            flex: 1;
        }

        .sidebar__submenu-item {
            display: block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 400;
            color: var(--muted);
            cursor: pointer;
            white-space: nowrap;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sidebar__submenu-item:hover {
            background-color: var(--blue-main-5);
            color: var(--black-secondary);
        }

        .sidebar__submenu-item.active { color: var(--blue-active); }

        /* ---- Logout Footer ---- */
        .sidebar__footer {
            border-top: 1px solid var(--smooth-border);
            padding-top: 12px;
        }

        .sidebar__logout {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--red-main);
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            position: relative;
            background-color: transparent;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.16s ease, box-shadow 0.2s ease;
        }

        .sidebar__logout:hover {
            background-color: var(--red-main-10);
            color: var(--red-hover);
            box-shadow: inset 0 0 0 1px var(--red-main-10);
        }

        .sidebar__logout:active {
            background-color: rgba(210,0,0,0.16);
            color: var(--red-hover);
            transform: scale(0.98);
            box-shadow: inset 0 0 0 1px var(--red-main-10);
        }

        .sidebar__logout:focus-visible {
            outline: none;
            background-color: var(--red-main-10);
            box-shadow: 0 0 0 3px var(--red-main-10), inset 0 0 0 1px var(--red-main-10);
        }

        .sidebar__logout .nav-icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
        }

        .sidebar__logout .nav-icon i { position: relative; top: 1px; }

        .sidebar__logout .nav-label {
            overflow: hidden;
            transition: opacity 0.15s ease, max-width var(--sidebar-transition);
            max-width: 120px;
        }

        body.sidebar-collapsed .sidebar__logout .nav-label {
            opacity: 0;
            max-width: 0;
        }

        body.sidebar-collapsed .sidebar__logout[data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--black);
            color: var(--white-pure);
            font-size: 11px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 9999;
        }

        body.sidebar-collapsed .sidebar__logout[data-tooltip]:hover::after { opacity: 1; }

        /* =============================================
           MAIN WRAPPER
           ============================================= */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            min-width: 0;
        }

        /* =============================================
           TOP NAVBAR
           ============================================= */
        .navbar-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 22px;
            background-color: var(--white);
            border-bottom: 1px solid var(--smooth-border);
            flex-shrink: 0;
            transition: background-color 0.3s ease;
        }

        .navbar-top__left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Sidebar toggle */
        .btn-sidebar-toggle {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--white);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: var(--black-secondary);
            border: .5px solid var(--smooth-border);
            transition: background-color 0.2s ease, color 0.2s ease;
            flex-shrink: 0;
            font-size: 16px;
        }

        .btn-sidebar-toggle:hover { background-color: var(--blue-main-10); color: var(--blue-main); }

        .btn-sidebar-toggle i {
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transition: transform var(--sidebar-transition);
        }

        body.sidebar-collapsed .btn-sidebar-toggle i { transform: rotate(180deg); }

        .navbar-top__user-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--black);
        }

        .navbar-top__user-role {
            font-size: 9px;
            font-weight: 300;
            color: var(--black-secondary);
        }

        .navbar-top__right { display: flex; align-items: center; gap: 12px; }

        /* Theme toggle */
        .btn-theme {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--btn-theme-bg);
            border: .5px solid var(--btn-theme-border);
            border-radius: 8px;
            cursor: pointer;
            color: var(--btn-theme-icon);
            font-size: 16px;
            overflow: hidden;
            position: relative;
            transition: border-color 0.2s ease, background-color 0.3s ease;
        }

        .btn-theme:hover { border-color: var(--blue-main); }

        .btn-theme i {
            position: relative;
            top: 3px;
            transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease;
        }

        /* =============================================
           PAGE CONTENT SCROLL AREA
           ============================================= */
        .page-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px 30px 24px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            scrollbar-width: thin;
            scrollbar-color: var(--blue-main-25) transparent;
        }

        .page-content::-webkit-scrollbar { width: 4px; }
        .page-content::-webkit-scrollbar-track { background: transparent; }
        .page-content::-webkit-scrollbar-thumb { background-color: var(--blue-main-25); border-radius: 10px; }
        .page-content::-webkit-scrollbar-thumb:hover { background-color: var(--blue-main-40); }

        .toast-viewport {
            position: fixed;
            top: 18px;
            left: 50%;
            z-index: 10050;
            width: min(460px, calc(100vw - 32px));
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            transform: translateX(-50%);
            pointer-events: none;
        }

        .toast-message {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 24px;
            border-top: 1px solid rgba(255,255,255,0.70);
            border-left: 1px solid rgba(255,255,255,0.70);
            border-right: 1px solid rgba(255,255,255,0.20);
            border-bottom: 1px solid rgba(255,255,255,0.20);
            background: linear-gradient(135deg, rgba(255,255,255,0.42) 0%, rgba(255,255,255,0.14) 100%);
            color: var(--black);
            box-shadow:
                0 25px 45px rgba(15,23,42,0.12),
                inset 0 0 0 1px rgba(255,255,255,0.30),
                inset 0 2px 10px rgba(255,255,255,0.36);
            backdrop-filter: blur(28px) saturate(150%);
            -webkit-backdrop-filter: blur(28px) saturate(150%);
            opacity: 0;
            transform: translateY(-140%) scale(0.98);
            transition: transform 0.48s cubic-bezier(0.34,1.56,0.64,1), opacity 0.28s ease-out;
            pointer-events: auto;
        }

        .toast-message::before {
            content: "";
            position: absolute;
            inset: 2px;
            border-radius: 22px;
            background: transparent;
            pointer-events: none;
            z-index: -1;
        }

        .toast-message.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .toast-message.is-hiding {
            opacity: 0;
            transform: translateY(-140%) scale(0.98);
            transition: transform 0.36s ease-in, opacity 0.28s ease-in;
        }

        .toast-icon {
            width: 36px;
            height: 36px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.70),
                inset 0 -10px 20px rgba(255,255,255,0.10),
                0 8px 18px rgba(15,23,42,0.08);
        }

        .toast-icon i,
        .toast-close i {
            position: relative;
            top: 2px;
        }

        .toast-message.success .toast-icon {
            color: var(--success);
            background:
                linear-gradient(145deg, rgba(255,255,255,0.30), rgba(255,255,255,0.08)),
                rgba(16,185,129,0.12);
            border: 1px solid rgba(16,185,129,0.34);
        }

        .toast-message.error .toast-icon {
            color: var(--red-main);
            background:
                linear-gradient(145deg, rgba(255,255,255,0.30), rgba(255,255,255,0.08)),
                rgba(210,0,0,0.12);
            border: 1px solid rgba(210,0,0,0.34);
        }

        .toast-copy {
            min-width: 0;
            flex: 1 1 auto;
        }

        .toast-title {
            display: block;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.25;
            color: var(--black);
        }

        .toast-text {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            font-weight: 400;
            line-height: 1.35;
            color: var(--black-secondary);
        }

        .toast-close {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            color: var(--muted);
            background: rgba(255,255,255,0.24);
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .toast-close:hover {
            color: var(--black);
            background-color: rgba(51,65,85,0.10);
        }

        body.dark-mode .toast-message {
            border-color: rgba(255,255,255,0.10);
            background: rgba(30,41,59,0.45);
            box-shadow: 0 20px 40px rgba(0,0,0,0.40);
        }

        @media (max-width: 480px) {
            .toast-viewport {
                top: 12px;
                width: calc(100vw - 24px);
            }

            .toast-message {
                padding: 10px 12px;
                gap: 9px;
                border-radius: 22px;
            }

            .toast-icon {
                width: 34px;
                height: 34px;
                border-radius: 12px;
            }
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--black);
            line-height: 1.2;
        }

        .page-header {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .page-subtitle {
            font-size: 12px;
            font-weight: 300;
            color: var(--black-secondary);
        }

        .admin-pagination {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .admin-pagination__info {
            color: var(--muted);
            font-size: 11px;
            font-weight: 500;
        }

        .admin-pagination__list {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .admin-pagination__link,
        .admin-pagination__disabled {
            display: inline-flex;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--blue-main-10);
            border-radius: 8px;
            background-color: var(--white);
            color: var(--blue-main);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            line-height: 1;
            transition: .2s ease-out;
        }

        .admin-pagination__link i,
        .admin-pagination__disabled i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            line-height: 1;
            width: 1em;
            height: 1em;
            position: relative;
            top: 1px;
        }

        .admin-pagination__link:hover {
            background-color: var(--blue-main-10);
            border-color: var(--blue-main-25);
            transform: translateY(-1px);
        }

        .admin-pagination__link.active {
            background-color: var(--blue-main);
            border-color: var(--blue-main);
            color: #ffffff;
            box-shadow: 0 8px 18px var(--blue-main-25);
        }

        .admin-pagination__disabled {
            color: var(--muted);
            opacity: .55;
            cursor: not-allowed;
        }

        /* =============================================
           STATS CARDS
           ============================================= */
        .stats-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .stat-card {
            flex: 1 1 160px;
            background-color: var(--white);
            border-radius: 10px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(37,99,235,0.07);
            transition: background-color 0.3s ease;
        }

        .stat-card__label {
            font-size: 10px;
            font-weight: 400;
            color: var(--black-secondary);
        }

        .stat-card__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card__value {
            font-size: 24px;
            font-weight: 700;
            color: var(--black);
            line-height: 1;
        }

        .stat-card__value--success {
            color: var(--success) !important;
            font-size: 20px !important;
        }

        .stat-card__icon {
            width: 28px;
            height: 28px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }

        .stat-card__icon i { position: relative; top: 1px; }

        .stat-card__icon--blue   { background-color: var(--blue-main-10);   color: var(--blue-main); }
        .stat-card__icon--orange { background-color: var(--orange-main-10); color: var(--orange-main); }
        .stat-card__icon--green  { background-color: var(--success-10);     color: var(--success); }
        .stat-card__icon--cyan   { background-color: var(--cyan-main-10);   color: var(--cyan-main); }

        /* =============================================
           PAGE FOOTER
           ============================================= */
        .page-footer {
            text-align: center;
            font-size: 11px;
            font-weight: 300;
            color: var(--muted);
            padding: 8px 20px;
            flex-shrink: 0;
            letter-spacing: 0.2px;
        }

        /* =============================================
           MODAL & POPUP
           ============================================= */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(15,23,42,0.56);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            overflow-y: auto;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.show { opacity: 1; visibility: visible; }

        .modal-box {
            width: min(460px, calc(100vw - 32px));
            max-height: calc(100vh - 36px);
            background-color: var(--white);
            border: 1px solid var(--smooth-border);
            border-radius: 10px;
            box-shadow: 0 18px 50px rgba(15,23,42,0.22);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: translateY(14px) scale(0.96);
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), background-color 0.3s ease;
        }

        .modal-overlay.show .modal-box { transform: translateY(0) scale(1); }

        .modal-box--sm { width: min(420px, calc(100vw - 32px)); }
        .modal-box--wide { width: min(720px, calc(100vw - 32px)); }

        .modal-box > form {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .kss-modal__header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 18px 20px 14px;
            border-bottom: 1px solid var(--smooth-border);
        }

        .kss-modal__icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
            background-color: var(--blue-main-10);
            color: var(--blue-main);
        }

        .kss-modal__icon i { position: relative; top: 2px; }
        .kss-modal__icon--danger { background-color: var(--red-main-10); color: var(--red-main); }
        .kss-modal__icon--success { background-color: var(--success-10); color: var(--success); }
        .kss-modal__icon--warning { background-color: var(--orange-main-10); color: var(--orange-main); }

        .kss-modal__heading { flex: 1; min-width: 0; }

        .kss-modal__title {
            font-size: 15px;
            font-weight: 700;
            color: var(--black);
            line-height: 1.35;
        }

        .kss-modal__subtitle {
            font-size: 11px;
            font-weight: 400;
            color: var(--muted);
            line-height: 1.5;
            margin-top: 3px;
        }

        .kss-modal__close {
            width: 30px;
            height: 30px;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            background-color: var(--white);
            color: var(--muted);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s ease;
            flex-shrink: 0;
        }

        .kss-modal__close:hover {
            color: var(--red-main);
            background-color: var(--red-main-5);
            border-color: var(--red-main-10);
        }

        .kss-modal__close i { position: relative; top: 1px; }

        .kss-modal__body {
            padding: 18px 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .kss-modal__message {
            font-size: 12px;
            color: var(--black-secondary);
            line-height: 1.6;
        }

        .kss-modal__summary {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            background-color: var(--main-bg);
            border: 1px solid var(--smooth-border);
            font-size: 11px;
            color: var(--black-secondary);
        }

        .kss-modal__summary i {
            color: var(--blue-main);
            position: relative;
            top: 1px;
        }

        .kss-modal__grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .kss-modal__field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .kss-modal__field--full { grid-column: 1 / -1; }

        .kss-modal__field label {
            font-size: 10px;
            font-weight: 600;
            color: var(--black-secondary);
        }

        .kss-modal__input,
        .kss-modal__select-trigger,
        .kss-modal__textarea {
            width: 100%;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            background-color: var(--main-bg);
            color: var(--black);
            font-family: inherit;
            font-size: 12px;
            outline: none;
            padding: 9px 12px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .kss-modal__select-wrapper {
            position: relative;
            width: 100%;
        }

        .kss-modal__native-select {
            display: none;
        }

        .kss-modal__select-trigger {
            min-height: 39px;
            padding-right: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .kss-modal__select-trigger.text-placeholder {
            color: var(--muted);
        }

        .kss-modal__select-trigger.is-disabled {
            cursor: not-allowed;
            opacity: 0.78;
            background-color: var(--main-bg);
        }

        .kss-modal__select-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--blue-main);
            font-size: 14px;
            display: flex;
            pointer-events: none;
            transition: transform 0.2s ease;
        }

        .kss-modal__select-trigger.focus-active ~ .kss-modal__select-icon {
            transform: translateY(-50%) rotate(180deg);
        }

        .kss-modal__select-options {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            z-index: 10020;
            display: none;
            max-height: 190px;
            overflow-y: auto;
            padding: 6px 0;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            background-color: var(--white);
            box-shadow: 0 8px 24px rgba(15,23,42,0.12);
        }

        .kss-modal__select-options.open {
            display: block;
            animation: fadeIn 0.2s ease-out;
        }

        .kss-modal__select-option {
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 400;
            color: var(--black-secondary);
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .kss-modal__select-option:hover {
            background-color: var(--blue-main-10);
            color: var(--blue-main);
        }

        .kss-modal__select-option.selected {
            background-color: var(--blue-main-5);
            color: var(--blue-main);
            border-left: 3px solid var(--blue-main);
            font-weight: 600;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .kss-modal__textarea {
            min-height: 84px;
            resize: vertical;
            line-height: 1.5;
        }

        .kss-modal__input:focus,
        .kss-modal__select-trigger:focus,
        .kss-modal__select-trigger.focus-active,
        .kss-modal__textarea:focus {
            background-color: var(--white);
            border-color: var(--blue-main);
            box-shadow: 0 0 0 3px var(--blue-main-10);
        }

        .kss-modal__footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px 18px;
            border-top: 1px solid var(--smooth-border);
            background-color: var(--main-bg);
        }

        .kss-modal__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 36px;
            padding: 8px 14px;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            background-color: var(--white);
            color: var(--black-secondary);
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .kss-modal__button i { position: relative; top: 1px; }

        .kss-modal__button:hover {
            background-color: var(--blue-main-5);
            border-color: var(--blue-main-25);
            color: var(--blue-main);
        }

        .kss-modal__button--primary {
            background-color: var(--blue-main);
            border-color: var(--blue-main);
            color: #fff;
        }

        .kss-modal__button--primary:hover {
            background-color: var(--blue-hover);
            border-color: var(--blue-hover);
            color: #fff;
        }

        .kss-modal__button--danger {
            background-color: var(--red-main);
            border-color: var(--red-main);
            color: #fff;
        }

        .kss-modal__button--danger:hover {
            background-color: var(--red-hover);
            border-color: var(--red-hover);
            color: #fff;
        }

        @media (max-width: 640px) {
            .modal-overlay { align-items: flex-end; padding: 12px; }
            .modal-box { width: 100%; max-height: calc(100vh - 24px); }
            .kss-modal__grid { grid-template-columns: 1fr; }
            .kss-modal__field--full { grid-column: auto; }
            .kss-modal__footer { flex-direction: column-reverse; }
            .kss-modal__button { width: 100%; }
        }
    </style>

    {{-- CSS khusus per halaman (di-push dari masing-masing view) --}}
    @stack('styles')
</head>

<body>
    {{-- Terapkan dark mode sebelum render agar tidak flicker --}}
    <script>if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');</script>

    @php
        $toastMessages = collect();

        if (session('success')) {
            $toastMessages->push([
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => session('success'),
                'icon' => 'fi fi-rr-check-circle',
            ]);
        }

        if (session('error')) {
            $toastMessages->push([
                'type' => 'error',
                'title' => 'Gagal',
                'message' => session('error'),
                'icon' => 'fi fi-rr-triangle-warning',
            ]);
        }

        if ($errors->any()) {
            $toastMessages->push([
                'type' => 'error',
                'title' => 'Periksa Form',
                'message' => $errors->first(),
                'icon' => 'fi fi-rr-info',
            ]);
        }
    @endphp

    @if ($toastMessages->isNotEmpty())
        <div class="toast-viewport" aria-live="polite" aria-atomic="true">
            @foreach ($toastMessages as $toast)
                <div class="toast-message {{ $toast['type'] }}" data-duration="4200" role="status">
                    <div class="toast-icon">
                        <i class="{{ $toast['icon'] }}"></i>
                    </div>
                    <div class="toast-copy">
                        <span class="toast-title">{{ $toast['title'] }}</span>
                        <span class="toast-text">{{ $toast['message'] }}</span>
                    </div>
                    <button type="button" class="toast-close" aria-label="Tutup notifikasi">
                        <i class="fi fi-rr-cross-small"></i>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- SIDEBAR (partial) --}}
    @include('admin.layouts.sidebar', ['active' => trim($__env->yieldContent('active'))])

    <div class="main-wrapper">

        {{-- TOP NAVBAR (partial) --}}
        @include('admin.layouts.navbar')

        {{-- PAGE CONTENT --}}
        <main class="page-content">
            @yield('content')
        </main>

        {{-- FOOTER (partial) --}}
        @include('admin.layouts.footer')

    </div>

    <div class="modal-overlay" id="adminConfirmModal" aria-hidden="true">
        <div class="modal-box modal-box--sm" role="dialog" aria-modal="true" aria-labelledby="adminConfirmTitle">
            <div class="kss-modal__header">
                <div class="kss-modal__icon kss-modal__icon--warning" id="adminConfirmIconWrap">
                    <i class="fi fi-rr-triangle-warning" id="adminConfirmIcon"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="adminConfirmTitle">Konfirmasi tindakan</div>
                    <div class="kss-modal__subtitle" id="adminConfirmSubtitle">Pastikan data yang dipilih sudah benar.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <div class="kss-modal__body">
                <div class="kss-modal__message" id="adminConfirmMessage">
                    Tindakan ini memerlukan konfirmasi sebelum dilanjutkan.
                </div>
                <div class="kss-modal__summary" id="adminConfirmSummary" hidden>
                    <i class="fi fi-rr-info"></i>
                    <span id="adminConfirmSummaryText"></span>
                </div>
            </div>
            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="button" class="kss-modal__button kss-modal__button--primary" id="adminConfirmAction">
                    Lanjutkan
                </button>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    {{-- =====================================================
         SHARED JS (sidebar toggle, submenu, dark mode)
         ===================================================== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;

            function ensureToastViewport() {
                let viewport = document.querySelector('.toast-viewport');
                if (!viewport) {
                    viewport = document.createElement('div');
                    viewport.className = 'toast-viewport';
                    viewport.setAttribute('aria-live', 'polite');
                    viewport.setAttribute('aria-atomic', 'true');
                    document.body.appendChild(viewport);
                }

                return viewport;
            }

            function bindToastMessage(toast, index = 0) {
                if (!toast || toast.dataset.bound === 'true') return;

                toast.dataset.bound = 'true';
                const closeToast = toast.querySelector('.toast-close');
                const duration = Number(toast.dataset.duration) || 4200;
                let hideTimer = null;

                function hideToast() {
                    if (!toast.isConnected) return;

                    toast.classList.remove('show');
                    toast.classList.add('is-hiding');
                    window.setTimeout(() => toast.remove(), 420);
                }

                window.setTimeout(() => toast.classList.add('show'), 80 + (index * 90));
                hideTimer = window.setTimeout(hideToast, duration);

                toast.addEventListener('mouseenter', () => {
                    if (hideTimer) window.clearTimeout(hideTimer);
                });

                toast.addEventListener('mouseleave', () => {
                    hideTimer = window.setTimeout(hideToast, 1400);
                });

                if (closeToast) {
                    closeToast.addEventListener('click', hideToast);
                }
            }

            window.showAdminToast = function (type, title, message, duration = 4200) {
                const safeType = type === 'success' ? 'success' : 'error';
                const iconClass = safeType === 'success' ? 'fi fi-rr-check-circle' : 'fi fi-rr-triangle-warning';
                const toast = document.createElement('div');
                const viewport = ensureToastViewport();

                toast.className = `toast-message ${safeType}`;
                toast.dataset.duration = String(duration);
                toast.setAttribute('role', 'status');
                toast.innerHTML = `
                    <div class="toast-icon"><i class="${iconClass}"></i></div>
                    <div class="toast-copy">
                        <span class="toast-title"></span>
                        <span class="toast-text"></span>
                    </div>
                    <button type="button" class="toast-close" aria-label="Tutup notifikasi">
                        <i class="fi fi-rr-cross-small"></i>
                    </button>
                `;

                toast.querySelector('.toast-title').textContent = title || (safeType === 'success' ? 'Berhasil' : 'Gagal');
                toast.querySelector('.toast-text').textContent = message || '';
                viewport.appendChild(toast);
                bindToastMessage(toast);
            };

            document.querySelectorAll('.toast-message').forEach((toast, index) => bindToastMessage(toast, index));

            // 1. SIDEBAR TOGGLE
            const btnSidebarToggle = document.getElementById('btnSidebarToggle');
            let isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            const applySidebarState = () => body.classList.toggle('sidebar-collapsed', isSidebarCollapsed);
            applySidebarState();
            if (btnSidebarToggle) {
                btnSidebarToggle.addEventListener('click', function () {
                    isSidebarCollapsed = !isSidebarCollapsed;
                    applySidebarState();
                    localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);
                });
            }

            // 2. SUBMENU TOGGLE (Data Master)
            document.querySelectorAll('.js-submenu-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    const wrapper = toggle.nextElementSibling;
                    if (!wrapper || !wrapper.classList.contains('sidebar__submenu-wrapper')) return;
                    const isOpen = wrapper.classList.contains('open');
                    wrapper.classList.toggle('open', !isOpen);
                    toggle.classList.toggle('submenu-open', !isOpen);
                });
            });

            // 3. DARK MODE TOGGLE
            const btnTheme = document.getElementById('btnTheme');
            const themeIcon = document.getElementById('themeIcon');
            let isDarkMode = localStorage.getItem('theme') === 'dark';

            function enableDarkMode(animate) {
                body.classList.add('dark-mode');
                themeIcon.className = 'fi fi-rr-moon';
                if (animate) {
                    themeIcon.classList.add('prepare-from-bottom');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-bottom');
                }
            }

            function disableDarkMode(animate) {
                body.classList.remove('dark-mode');
                themeIcon.className = 'fi fi-rr-sun';
                if (animate) {
                    themeIcon.classList.add('prepare-from-top');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-top');
                }
            }

            if (themeIcon) themeIcon.className = isDarkMode ? 'fi fi-rr-moon' : 'fi fi-rr-sun';

            if (btnTheme) {
                btnTheme.addEventListener('click', function () {
                    isDarkMode = !isDarkMode;
                    if (isDarkMode) {
                        themeIcon.classList.add('animate-out-up');
                        setTimeout(function () {
                            themeIcon.classList.remove('animate-out-up');
                            enableDarkMode(true);
                            localStorage.setItem('theme', 'dark');
                        }, 200);
                    } else {
                        themeIcon.classList.add('animate-out-down');
                        setTimeout(function () {
                            themeIcon.classList.remove('animate-out-down');
                            disableDarkMode(true);
                            localStorage.setItem('theme', 'light');
                        }, 200);
                    }
                });
            }

            // 4. ADMIN MODAL HELPERS
            const confirmModal = document.getElementById('adminConfirmModal');
            const confirmTitle = document.getElementById('adminConfirmTitle');
            const confirmSubtitle = document.getElementById('adminConfirmSubtitle');
            const confirmMessage = document.getElementById('adminConfirmMessage');
            const confirmSummary = document.getElementById('adminConfirmSummary');
            const confirmSummaryText = document.getElementById('adminConfirmSummaryText');
            const confirmAction = document.getElementById('adminConfirmAction');
            const confirmIconWrap = document.getElementById('adminConfirmIconWrap');
            const confirmIcon = document.getElementById('adminConfirmIcon');
            let activeConfirmTrigger = null;

            function modalById(modal) {
                if (!modal) return null;
                if (typeof modal === 'string') return document.getElementById(modal);
                return modal;
            }

            function focusFirstField(modal) {
                const focusable = modal.querySelector('[data-modal-focus], input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), .kss-modal__select-trigger:not(.is-disabled), button');
                if (focusable) window.setTimeout(() => focusable.focus({ preventScroll: true }), 80);
            }

            function syncModalSelect(select) {
                if (!select) return;
                const wrapper = select.closest('.kss-modal__select-wrapper');
                const trigger = wrapper?.querySelector('.kss-modal__select-trigger');
                const label = trigger?.querySelector('span');
                const selectedOption = select.options[select.selectedIndex];

                if (label) label.textContent = selectedOption ? selectedOption.text : '';
                if (trigger) {
                    trigger.classList.toggle('text-placeholder', !selectedOption || selectedOption.disabled || selectedOption.value === '');
                    trigger.classList.toggle('is-disabled', select.disabled);
                    trigger.setAttribute('aria-disabled', select.disabled ? 'true' : 'false');
                    trigger.tabIndex = select.disabled ? -1 : 0;
                }

                wrapper?.querySelectorAll('.kss-modal__select-option').forEach(option => {
                    option.classList.toggle('selected', option.dataset.value === select.value);
                });
            }

            function initModalSelects(root = document) {
                root.querySelectorAll('.kss-modal__select-wrapper').forEach(function (wrapper) {
                    const select = wrapper.querySelector('select.kss-modal__native-select');
                    if (!select) return;

                    if (wrapper.dataset.selectReady === 'true') {
                        syncModalSelect(select);
                        return;
                    }

                    wrapper.dataset.selectReady = 'true';

                    const trigger = document.createElement('div');
                    trigger.className = 'kss-modal__select-trigger';
                    trigger.setAttribute('role', 'button');
                    trigger.setAttribute('tabindex', '0');
                    trigger.innerHTML = '<span></span>';
                    wrapper.insertBefore(trigger, select.nextSibling);

                    if (!wrapper.querySelector('.kss-modal__select-icon')) {
                        const icon = document.createElement('i');
                        icon.className = 'fi fi-rr-angle-small-down kss-modal__select-icon';
                        wrapper.appendChild(icon);
                    }

                    const optionsContainer = document.createElement('div');
                    optionsContainer.className = 'kss-modal__select-options';
                    wrapper.appendChild(optionsContainer);

                    Array.from(select.options).forEach(function (option) {
                        if (option.disabled && option.hidden) return;
                        const optionButton = document.createElement('div');
                        optionButton.className = 'kss-modal__select-option';
                        optionButton.textContent = option.text;
                        optionButton.dataset.value = option.value;
                        optionButton.addEventListener('click', function (e) {
                            e.stopPropagation();
                            if (select.disabled) return;
                            select.value = optionButton.dataset.value;
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                            syncModalSelect(select);
                            optionsContainer.classList.remove('open');
                            trigger.classList.remove('focus-active');
                        });
                        optionsContainer.appendChild(optionButton);
                    });

                    function toggleOptions(e) {
                        e.stopPropagation();
                        if (select.disabled) return;
                        document.querySelectorAll('.kss-modal__select-options.open').forEach(function (container) {
                            if (container !== optionsContainer) {
                                container.classList.remove('open');
                                container.closest('.kss-modal__select-wrapper')?.querySelector('.kss-modal__select-trigger')?.classList.remove('focus-active');
                            }
                        });
                        optionsContainer.classList.toggle('open');
                        trigger.classList.toggle('focus-active');
                    }

                    trigger.addEventListener('click', toggleOptions);
                    trigger.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            toggleOptions(e);
                        }
                    });
                    select.addEventListener('change', () => syncModalSelect(select));
                    syncModalSelect(select);
                });
            }

            function openModal(modal) {
                const target = modalById(modal);
                if (!target) return;
                target.classList.add('show');
                target.setAttribute('aria-hidden', 'false');
                body.classList.add('modal-open');
                focusFirstField(target);
            }

            function closeModal(modal) {
                const target = modalById(modal);
                if (!target) return;
                target.classList.remove('show');
                target.setAttribute('aria-hidden', 'true');
                if (!document.querySelector('.modal-overlay.show')) {
                    body.classList.remove('modal-open');
                }
            }

            function configureConfirm(trigger) {
                if (!confirmModal || !trigger) return;

                const tone = trigger.dataset.confirmTone || 'primary';
                const iconClass = trigger.dataset.confirmIcon || (tone === 'danger' ? 'fi fi-rr-trash' : 'fi fi-rr-triangle-warning');

                confirmTitle.textContent = trigger.dataset.confirmTitle || 'Konfirmasi tindakan';
                confirmSubtitle.textContent = trigger.dataset.confirmSubtitle || 'Pastikan data yang dipilih sudah benar.';
                confirmMessage.textContent = trigger.dataset.confirmMessage || 'Tindakan ini memerlukan konfirmasi sebelum dilanjutkan.';
                confirmAction.textContent = trigger.dataset.confirmLabel || 'Lanjutkan';
                confirmIcon.className = iconClass;

                confirmIconWrap.className = 'kss-modal__icon';
                if (tone === 'danger') confirmIconWrap.classList.add('kss-modal__icon--danger');
                else if (tone === 'success') confirmIconWrap.classList.add('kss-modal__icon--success');
                else if (tone === 'warning') confirmIconWrap.classList.add('kss-modal__icon--warning');

                confirmAction.className = 'kss-modal__button';
                confirmAction.classList.add(tone === 'danger' ? 'kss-modal__button--danger' : 'kss-modal__button--primary');

                if (trigger.dataset.confirmSummary) {
                    confirmSummary.hidden = false;
                    confirmSummaryText.textContent = trigger.dataset.confirmSummary;
                } else {
                    confirmSummary.hidden = true;
                    confirmSummaryText.textContent = '';
                }
            }

            window.KssAdminModal = {
                open: openModal,
                close: closeModal,
                initSelects: initModalSelects,
                syncSelects: function (root = document) {
                    root.querySelectorAll('select.kss-modal__native-select').forEach(syncModalSelect);
                },
                confirm: function (trigger) {
                    activeConfirmTrigger = trigger;
                    configureConfirm(trigger);
                    openModal(confirmModal);
                }
            };

            document.addEventListener('click', function (e) {
                const closeTrigger = e.target.closest('[data-modal-close]');
                if (closeTrigger) {
                    e.preventDefault();
                    closeModal(closeTrigger.closest('.modal-overlay'));
                    return;
                }

                const modalOpenTrigger = e.target.closest('[data-modal-target]');
                if (modalOpenTrigger) {
                    e.preventDefault();
                    openModal(modalOpenTrigger.dataset.modalTarget);
                    return;
                }

                const confirmTrigger = e.target.closest('[data-confirm]');
                if (confirmTrigger) {
                    e.preventDefault();
                    window.KssAdminModal.confirm(confirmTrigger);
                }
            });

            initModalSelects(document);

            document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) closeModal(overlay);
                });
            });

            document.addEventListener('click', function () {
                document.querySelectorAll('.kss-modal__select-options.open').forEach(container => container.classList.remove('open'));
                document.querySelectorAll('.kss-modal__select-trigger.focus-active').forEach(trigger => trigger.classList.remove('focus-active'));
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                const opened = Array.from(document.querySelectorAll('.modal-overlay.show')).pop();
                if (opened) closeModal(opened);
            });

            document.addEventListener('submit', function (e) {
                const form = e.target.closest('[data-preview-submit]');
                if (!form) return;
                e.preventDefault();
                closeModal(form.closest('.modal-overlay'));
            });

            if (confirmAction) {
                confirmAction.addEventListener('click', function () {
                    const redirect = activeConfirmTrigger?.dataset.confirmRedirect;
                    const submitForm = activeConfirmTrigger?.dataset.confirmSubmit === 'true';
                    const form = submitForm ? activeConfirmTrigger?.closest('form') : null;
                    closeModal(confirmModal);
                    if (form) {
                        if (typeof form.requestSubmit === 'function') form.requestSubmit();
                        else form.submit();
                    }
                    if (redirect) window.location.href = redirect;
                    activeConfirmTrigger = null;
                });
            }
        });
    </script>

    {{-- JS khusus per halaman --}}
    @stack('scripts')
</body>
</html>
