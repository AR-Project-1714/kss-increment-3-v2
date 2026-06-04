<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSS Admin — Dashboard</title>

    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

    <!-- Flaticon UICONS -->
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-solid-rounded/css/uicons-solid-rounded.css">

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
            background-color: rgba(210, 0, 0, 0.16);
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

        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 900;
            padding: 0;
            border: 0;
            background-color: rgba(15, 23, 42, 0.48);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.24s ease, visibility 0.24s ease;
        }

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
            padding: 20px 30px 0;
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
           SECTION CARD (Laporan Masuk)
           ============================================= */
        .section-card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(37,99,235,0.07);
            /* overflow: hidden; Dihapus agar box-shadow hover pada report item tidak terpotong */
            transition: background-color 0.3s ease;
        }

        .section-card__header {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
            padding: 15px 20px;
            background-color: var(--blue-main-3);
            border-top: 3px solid var(--blue-main);
            border-top-left-radius: 10px; /* Ditambahkan agar sudut header tetap membulat */
            border-top-right-radius: 10px;
        }

        .section-card__title {
            font-size: 16px;
            font-weight: 600;
            color: var(--black);
        }

        .section-card__badge {
            width: 18px;
            height: 18px;
            border-radius: 50px;
            background-color: var(--orange-main);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            color: #fff;
            flex-shrink: 0;
        }

        .section-card__subtitle {
            font-size: 10px;
            font-weight: 400;
            color: var(--black-secondary);
            flex: 1;
        }

        .section-card__body {
            padding: 20px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* =============================================
           REPORT ITEM (Flexbox Based)
           ============================================= */
        .report-item {
            display: flex;
            padding: 16px;
            justify-content: space-between;
            align-items: flex-end;
            align-content: flex-end;
            row-gap: 16px;
            align-self: stretch;
            flex-wrap: wrap;
            border-radius: 10px;
            background-color: var(--white);
            box-shadow: 0 0 1px 0 var(--muted);
            transition: .2s ease-out;
            width: 100%;
        }

        .report-item:hover {
            box-shadow: 0 2px 4px 0 var(--blue-main-25);
            transform: translateY(-2px);
            background-color: var(--blue-main-3);
        }

        .report-detail { min-width: 250px; }

        /* Shift Badges */
        .shift {
            display: flex;
            padding: 3px 6px;
            justify-content: center;
            align-items: center;
            gap: 4px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 400;
        }

        .icon-shift { display: inline-flex; align-items: center; justify-content: center; }
        .icon-shift i { font-size: 8px; line-height: 1; }

        .shift.pagi { background-color: var(--cyan-main-10); color: var(--cyan-main); }
        .shift.sore { background-color: var(--orange-main-10); color: var(--orange-main); }
        .shift.malam { background-color: var(--blue-main-10); color: var(--blue-main); }

        /* Category Badges */
        .category {
            display: flex;
            padding: 3px 6px;
            justify-content: center;
            align-items: center;
            gap: 4px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }

        .category .icon-cat { display: inline-flex; align-items: center; justify-content: center; }
        .category i { font-size: 8px; line-height: 1; }

        .category.operasional  { background-color: var(--blue-main-10);   color: var(--blue-main); }
        .category.pemeliharaan { background-color: var(--orange-main-10); color: var(--orange-main); }
        .category.safety       { background-color: var(--success-10);     color: var(--success); }

        /* Report Tabs */
        .report-tabs {
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 0 20px;
            border-bottom: 1px solid var(--smooth-border);
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-inline: contain;
        }

        .report-tabs::-webkit-scrollbar { display: none; }

        .report-tab {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            flex: 0 0 auto;
            gap: 6px;
            padding: 12px 2px;
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
            transition: color 0.2s ease, border-color 0.2s ease;
        }

        .report-tab:hover { color: var(--black-secondary); }

        .report-tab.active {
            color: var(--blue-main);
            border-bottom-color: transparent;
            font-weight: 600;
        }

        .tab-slide-indicator {
            position: absolute;
            left: 0;
            bottom: -1px;
            width: 0;
            height: 2px;
            border-radius: 999px;
            background: var(--blue-main);
            transform: translateX(0);
            transition: transform .34s cubic-bezier(.22,1,.36,1), width .34s cubic-bezier(.22,1,.36,1);
            pointer-events: none;
            z-index: 0;
        }

        .report-tab__count {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 50px;
            background-color: var(--orange-main);
            color: #fff;
            font-size: 9px;
            font-weight: 600;
        }

        /* Upload Time */
        .upload-time {
            font-size: 10px;
            color: var(--muted);
            font-style: italic;
            font-weight: 300;
        }

        .icon-clock i { position: relative; top: 2px; }

        /* Report Title */
        .report-title {
            gap: 4px;
        }

        .report-title .title { font-size: 16px; font-weight: 600; color: var(--black); line-height: 1.2; }
        .report-title .id { font-size: 10px; color: var(--muted); line-height: 1.35; }

        /* Regu Groups */
        .report-group {
            box-shadow: 0 0 1px 0 var(--muted);
            padding: 6px 10px;
            border-radius: 20px;
            background-color: var(--white);
            display: inline-flex;
            align-items: center;
        }

        .letter-group {
            display: flex;
            width: 16px;
            height: 16px;
            padding: 5px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-radius: 50px;
            font-size: 8px;
            font-weight: 600;
        }

        .letter-group.out { color: var(--success); background-color: var(--success-10); }
        .letter-group.in { color: var(--blue-main); background-color: var(--blue-main-10); }

        .icon-arrow i { position: relative; top: 1px; color: var(--muted); }

        /* Report Actions */
        .report-button .btn {
            display: flex;
            max-width: 230px;
            padding: 7px 12px;
            justify-content: center;
            align-items: center;
            gap: 6px;
            flex: 1 0 0;
            border-radius: 6px;
            border: none;
            color: #fff;
            font-size: 10px;
            font-weight: 500;
            cursor: pointer;
        }

        .report-button .btn i { position: relative; top: 1px; }

        .report-button .btn.see { background-color: var(--orange-main); transition: 0.2s ease-out; }
        .report-button .btn.see:hover { background-color: var(--orange-hover); transform: translateY(-2px); }

        .report-button .btn.signed { background-color: var(--success); transition: 0.2s ease-out; }
        .report-button .btn.signed:hover { background-color: var(--success-hover); transform: translateY(-2px); }

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
           MODAL OVERLAY
           ============================================= */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0,0,0,0.55);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.show { opacity: 1; visibility: visible; }

        .modal-box {
            width: 360px;
            padding: 22px;
            background-color: var(--white);
            border-radius: 18px;
            box-shadow: 0 12px 36px rgba(0,0,0,0.18);
            display: flex;
            flex-direction: column;
            gap: 18px;
            transform: scale(0.90);
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), background-color 0.3s ease;
        }

        .modal-overlay.show .modal-box { transform: scale(1); }

        .modal-box__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-box__title {
            font-size: 14px;
            font-weight: 600;
            color: var(--black);
        }

        .btn-modal-close {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 11px;
            color: var(--muted);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s ease;
        }

        .btn-modal-close i { position: relative; top: 1px; }
        .btn-modal-close:hover { color: var(--red-main); }

        .modal-box__doc-detail {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background-color: var(--cyan-main-10);
            border-radius: 8px;
            border: 1px solid var(--cyan-main-20);
        }

        .modal-box__doc-icon {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--white);
            border-radius: 6px;
            box-shadow: 0 0 0 1px rgba(14,165,233,0.3);
            font-size: 20px;
            color: var(--blue-main);
            flex-shrink: 0;
        }

        .modal-box__doc-icon i { position: relative; top: 3px; }
        .modal-box__doc-title  { font-size: 13px; font-weight: 600; color: var(--black); }
        .modal-box__doc-sub    { font-size: 10px; font-weight: 300; color: var(--black-secondary); }

        .modal-box__section-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--black-secondary);
            letter-spacing: 0.5px;
        }

        .modal-box__signature {
            display: flex;
            gap: 12px;
            padding: 12px;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 0 0 1px var(--smooth-border);
        }

        .sign-img-placeholder {
            width: 90px;
            height: 56px;
            border-radius: 4px;
            background-color: var(--main-bg);
            box-shadow: 0 0 0 1px var(--smooth-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: var(--muted);
            flex-shrink: 0;
        }

        .sign-officer__name { font-size: 13px; font-weight: 600; color: var(--black); }
        .sign-officer__id   { font-size: 10px; color: var(--muted); }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            background-color: var(--success-10);
            border-radius: 50px;
            font-size: 9px;
            font-weight: 600;
            color: var(--success);
        }

        .verified-badge i { font-size: 10px; position: relative; top: 1px; }

        .modal-box__note {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 9px;
            color: var(--muted);
        }

        .modal-box__note i { font-size: 11px; position: relative; top: 3px; flex-shrink: 0; }

        .modal-box__footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
        }

        .btn-modal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-modal i { position: relative; top: 1px; }

        .btn-modal--cancel { background: none; color: var(--black); }
        .btn-modal--cancel:hover { background-color: rgba(51,65,85,0.10); }

        .btn-modal--confirm { background-color: var(--success); color: #fff; }
        .btn-modal--confirm:hover { background-color: var(--success-hover); transform: translateY(-2px); }

        /* =============================================
           ARSIP — TOOLBAR & FILTERS
           ============================================= */
        .archive-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .archive-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border: 1px solid var(--smooth-border);
            border-radius: 50px;
            background-color: var(--main-bg);
            flex: 1 1 380px;
            max-width: 460px;
        }

        .search-box i { color: var(--muted); font-size: 13px; position: relative; top: 1px; }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            font-family: inherit;
            font-size: 12px;
            color: var(--black);
            width: 100%;
        }

        .search-box input::placeholder { color: var(--muted); }

        .archive-toolbar__actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .tool-select,
        .filter-input {
            font-family: inherit;
            font-size: 12px;
            color: var(--black);
            background-color: var(--white);
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .tool-select:focus,
        .filter-input:focus { border-color: var(--blue-main); }

        .btn-tool {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            background-color: var(--white);
            color: var(--black-secondary);
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-tool i { position: relative; top: 1px; }
        .btn-tool:hover { background-color: var(--blue-main-5); border-color: var(--blue-main-25); color: var(--blue-main); }

        .btn-tool--primary {
            background-color: var(--blue-main);
            border-color: var(--blue-main);
            color: #fff;
        }
        .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }

        .btn-tool--active {
            background-color: var(--blue-main-10);
            border-color: var(--blue-main);
            color: var(--blue-main);
        }

        .archive-filters {
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            animation: filterSlideDown 0.3s ease;
        }

        .archive-filters.collapsed { display: none; }

        @keyframes filterSlideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .filter-field { display: flex; flex-direction: column; gap: 4px; }
        .filter-field label { font-size: 10px; font-weight: 500; color: var(--black-secondary); }
        .filter-field .filter-input { min-width: 150px; }

        .btn-reset {
            padding: 8px 16px;
            border: 1px solid var(--red-main);
            border-radius: 8px;
            background-color: transparent;
            color: var(--red-main);
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s ease;
        }
        .btn-reset:hover { background-color: var(--red-main-10); }

        /* Custom dropdown (Regu, Shift & toolbar sort) */
        .filter-select-wrapper { position: relative; min-width: 150px; }
        .toolbar-sort-wrapper { min-width: 120px; }

        .filter-select-trigger {
            display: flex;
            align-items: center;
            padding-right: 34px;
            cursor: pointer;
        }
        .filter-select-trigger.focus-active {
            border-color: var(--blue-main);
            box-shadow: 0 0 0 3px var(--blue-main-10);
        }

        .select-arrow {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--blue-main);
            font-size: 14px;
            pointer-events: none;
            display: flex;
            transition: transform 0.2s ease;
        }
        .filter-select-trigger.focus-active ~ .select-arrow { transform: translateY(-50%) rotate(180deg); }

        .filter-select-options {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            background-color: var(--white);
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            z-index: 999;
            display: none;
            max-height: 200px;
            overflow-y: auto;
            padding: 6px 0;
        }
        .filter-select-options.open { display: block; animation: fadeIn 0.2s ease-out; }

        .filter-select-option {
            padding: 9px 14px;
            font-size: 12px;
            color: var(--black-secondary);
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .filter-select-option:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
        .filter-select-option.selected {
            background-color: var(--blue-main-5);
            color: var(--blue-main);
            border-left: 3px solid var(--blue-main);
            font-weight: 500;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* =============================================
           ARSIP — TABLE
           ============================================= */
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--blue-main-25) transparent;
        }

        .table-responsive-wrapper::-webkit-scrollbar { height: 6px; }
        .table-responsive-wrapper::-webkit-scrollbar-track { background: transparent; border-radius: 10px; }
        .table-responsive-wrapper::-webkit-scrollbar-thumb { background-color: var(--blue-main-25); border-radius: 10px; }
        .table-responsive-wrapper::-webkit-scrollbar-thumb:hover { background-color: var(--blue-main-40); }

        .table-responsive-wrapper table { min-width: 1000px; width: 100%; }

        .thead {
            background-color: var(--blue-main-5);
            border-radius: 6px;
        }

        .thead th {
            display: flex;
            padding: 10px;
            align-items: center;
            flex: 1 0 0;
            font-size: 12px;
            font-weight: 500;
            color: var(--black-secondary);
        }

        .thead th.nomor { width: 50px; flex: none; justify-content: center; padding: 10px 0; }
        .thead th.column-1 { min-width: 160px; }
        .thead th.aksi { min-width: 230px; }

        .tbody {
            border-bottom: 1px solid var(--smooth-border);
            transition: background-color 0.15s ease-in-out;
        }
        .tbody:hover { background-color: var(--blue-main-3); }

        .tbody td {
            display: flex;
            align-items: center;
            padding: 0 10px;
            flex: 1 0 0;
            font-size: 12px;
            font-weight: 500;
            color: var(--black);
        }

        .tbody td.nomor { width: 50px; flex: none; justify-content: center; padding: 12px 0; color: var(--black-secondary); }

        .tbody td.column-2 {
            min-width: 160px;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            gap: 2px;
        }

        .tbody td.column-1 { min-width: 160px; }

        .tbody td.column-3 {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .tbody td.aksi { gap: 8px; flex-wrap: nowrap; min-width: 230px; }

        /* Status badges */
        .status {
            display: flex;
            padding: 3px 8px;
            align-items: center;
            gap: 5px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
        }

        .status-dot { width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; flex-shrink: 0; }
        .status.approve { border: 1px solid var(--success);     color: var(--success);     background-color: var(--success-10); }
        .status.confirm { border: 1px solid var(--cyan-main);   color: var(--cyan-main);   background-color: var(--cyan-main-10); }
        .status.submit  { border: 1px solid var(--orange-main); color: var(--orange-main); background-color: var(--orange-main-10); }
        .status.archive { border: 1px solid var(--blue-main);   color: var(--blue-main);   background-color: var(--blue-main-10); }

        /* Action buttons */
        td.aksi .btn-act {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 10px;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-family: inherit;
            font-size: 10px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: 0.2s ease-out;
        }

        td.aksi .btn-act i { position: relative; top: 1px; }
        td.aksi .btn-act.download { background-color: var(--blue-main); }
        td.aksi .btn-act.download:hover { background-color: var(--blue-hover); transform: translateY(-1px); }
        td.aksi .btn-act.view { background-color: var(--orange-main); width: 30px; padding: 7px; }
        td.aksi .btn-act.view:hover { background-color: var(--orange-hover); transform: translateY(-1px); }
        td.aksi .btn-act.delete { background-color: var(--red-main); width: 30px; padding: 7px; }
        td.aksi .btn-act.delete:hover { background-color: var(--red-hover); transform: translateY(-1px); }

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

        /* Inline spinner untuk tombol (konfirmasi TTD & download) */
        .btn-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.45);
            border-top-color: #fff;
            border-radius: 50%;
            animation: sk-rotate 0.7s linear infinite;
            vertical-align: -2px;
            margin-right: 2px;
        }

        .btn-modal--confirm:disabled,
        .btn-act.is-loading {
            opacity: 0.85;
            cursor: progress;
            pointer-events: none;
        }

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
            border-top: 1px solid rgba(255, 255, 255, 0.7);
            border-left: 1px solid rgba(255, 255, 255, 0.7);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 100%);
            color: var(--black);
            box-shadow:
                0 25px 45px rgba(0, 0, 0, 0.1),
                inset 0 0 0 1px rgba(255, 255, 255, 0.3),
                inset 0 2px 10px rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(28px) saturate(150%);
            -webkit-backdrop-filter: blur(28px) saturate(150%);
            opacity: 0;
            transform: translateY(-140%) scale(0.98);
            transition: transform 0.48s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.28s ease-out;
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

        .toast-message::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            box-shadow: none;
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
            background: rgba(255, 255, 255, 0.22);
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .toast-close:hover {
            color: var(--black);
            background-color: rgba(51,65,85,0.10);
        }

        body.dark-mode .toast-message {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(30, 41, 59, 0.45);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
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

        @media (max-width: 900px) {
            body {
                display: block;
                height: 100dvh;
                min-height: 100dvh;
            }

            body.sidebar-mobile-open {
                overflow: hidden;
            }

            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                z-index: 1000;
                width: min(82vw, 286px);
                min-width: 0;
                max-width: 286px;
                height: 100dvh;
                padding: 18px 16px;
                border-right: 1px solid var(--smooth-border);
                box-shadow: 18px 0 40px rgba(15, 23, 42, 0.16);
                transform: translateX(-105%);
                transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease;
            }

            body.sidebar-mobile-open .sidebar {
                transform: translateX(0);
            }

            body.sidebar-mobile-open .sidebar-backdrop {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }

            body.sidebar-collapsed .sidebar {
                width: min(82vw, 286px);
                min-width: 0;
                padding: 18px 16px;
            }

            body.sidebar-collapsed .sidebar__logo-text,
            body.sidebar-collapsed .sidebar__section-label,
            body.sidebar-collapsed .sidebar__nav-item .nav-label,
            body.sidebar-collapsed .sidebar__logout .nav-label {
                opacity: 1;
                max-width: 170px;
            }

            body.sidebar-collapsed .sidebar__nav-item .nav-chevron {
                opacity: 1;
            }

            body.sidebar-collapsed .sidebar__nav-item[data-tooltip]::after,
            body.sidebar-collapsed .sidebar__logout[data-tooltip]::after {
                display: none;
            }

            .main-wrapper {
                width: 100%;
                height: 100dvh;
                min-height: 100dvh;
            }

            .navbar-top {
                padding: 12px 16px;
                position: sticky;
                top: 0;
                z-index: 80;
            }

            .navbar-top__left {
                min-width: 0;
                gap: 12px;
            }

            .greeting {
                min-width: 0;
            }

            .navbar-top__user-name,
            .navbar-top__user-role {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 56vw;
            }

            .btn-sidebar-toggle {
                width: 36px;
                height: 36px;
                border-radius: 8px;
                font-size: 18px;
            }

            body.sidebar-collapsed .btn-sidebar-toggle i {
                transform: none;
            }

            body.sidebar-mobile-open .btn-sidebar-toggle i {
                transform: rotate(90deg);
            }

            .page-content {
                padding: 16px 16px 0;
                gap: 14px;
            }

            .page-title {
                font-size: 18px;
            }

            .stats-row {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .stat-card {
                min-width: 0;
                padding: 12px;
            }

            .stat-card__value {
                font-size: 22px;
            }

            .section-card__header {
                padding: 14px 16px;
            }

            .section-card__body,
            .archive-body {
                padding: 16px;
            }

            .report-tabs {
                gap: 14px;
                padding: 0 16px;
            }

            .report-item {
                align-items: stretch;
                padding: 14px;
            }

            .report-button {
                min-width: 100% !important;
                width: 100%;
            }

            .report-button .btn {
                max-width: none;
                min-height: 36px;
            }

            .archive-toolbar {
                align-items: stretch;
            }

            .search-box {
                flex-basis: 100%;
                max-width: none;
                width: 100%;
            }

            .archive-toolbar__actions,
            .archive-toolbar__right {
                width: 100%;
                justify-content: flex-start;
            }

            .filter-select-wrapper,
            .toolbar-sort-wrapper {
                min-width: 140px;
                flex: 1 1 140px;
            }
        }

        @media (max-width: 560px) {
            .navbar-top {
                padding: 10px 12px;
            }

            .navbar-top__user-name {
                max-width: 48vw;
                font-size: 11px;
            }

            .navbar-top__user-role {
                max-width: 48vw;
            }

            .page-content {
                padding: 14px 12px 0;
            }

            .stats-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .stat-card {
                min-height: 76px;
                padding: 10px;
                gap: 8px;
            }

            .stat-card__label {
                font-size: 9px;
                line-height: 1.35;
            }

            .stat-card__value {
                font-size: 20px;
            }

            .stat-card__icon {
                width: 26px;
                height: 26px;
                font-size: 11px;
            }

            .report-tabs {
                gap: 16px;
                padding: 0 12px;
                scroll-padding-inline: 12px;
            }

            .report-tab {
                scroll-snap-align: start;
            }

            .stat-card__row {
                gap: 8px;
            }

            .section-card {
                border-radius: 8px;
            }

            .section-card__title {
                font-size: 15px;
            }

            .section-card__body,
            .archive-body {
                padding: 14px 12px;
            }

            .report-time {
                gap: 8px !important;
            }

            .report-title .title {
                font-size: 14px;
            }

            .report-group {
                max-width: 100%;
                flex-wrap: wrap;
                border-radius: 10px;
            }

            .report-button {
                flex-direction: column;
            }

            .report-button .btn {
                width: 100%;
                flex: none;
            }

            .archive-toolbar__actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                align-items: stretch;
            }

            .archive-toolbar__actions .btn-reset {
                grid-column: 1 / -1;
                text-align: center;
            }

            .btn-tool,
            .btn-reset,
            .filter-input,
            .filter-select-trigger {
                width: 100%;
                justify-content: center;
            }

            .archive-filters {
                width: 100%;
                justify-content: stretch;
            }

            .filter-field {
                width: 100%;
            }
        }

        @media (max-width: 360px) {
            .stats-row {
                grid-template-columns: 1fr;
            }

            .stat-card {
                min-height: auto;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
    </style>
    @include('components.kss-datetime-picker')
    @stack('styles')
</head>

<body>

    <div class="sk-overlay" id="sk-overlay">
        <div class="sk-spinner"></div>
    </div>

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
                        <i class="fi fi-br-cross-small"></i>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    <!-- ==========================================
                    SIDEBAR
    ========================================== -->
    @include('manajer.layouts.sidebar')
    <button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Tutup sidebar"></button>

    <!-- ==========================================
         MAIN WRAPPER
    ========================================== -->
    <div class="main-wrapper">

        <!-- TOP NAVBAR -->
        @include('manajer.layouts.navbar')

        <!-- PAGE CONTENT -->
        @yield('content')

        <!-- FOOTER -->
        @include('manajer.layouts.footer')

    </div>

    @stack('modals')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

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
                    document.body.prepend(viewport);
                }

                return viewport;
            }

            function bindToastMessage(toast, index = 0) {
                const closeToast = toast.querySelector('.toast-close');
                const duration = Number(toast.dataset.duration || 4200) + (index * 180);
                let hideTimer = null;

                function hideToast() {
                    if (!toast || toast.classList.contains('is-hiding')) return;

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

            window.showManagerToast = function(type, title, message, duration = 4200) {
                const safeType = type === 'success' ? 'success' : 'error';
                const toast = document.createElement('div');
                const viewport = ensureToastViewport();
                const iconClass = safeType === 'success' ? 'fi fi-rr-check-circle' : 'fi fi-rr-triangle-warning';

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
                        <i class="fi fi-br-cross-small"></i>
                    </button>
                `;

                toast.querySelector('.toast-title').textContent = title || (safeType === 'success' ? 'Berhasil' : 'Gagal');
                toast.querySelector('.toast-text').textContent = message || '';
                viewport.appendChild(toast);
                bindToastMessage(toast);
            };

            document.querySelectorAll('.toast-message').forEach((toast, index) => bindToastMessage(toast, index));

            function initSlidingTabIndicators() {
                [
                    { containerSelector: '.report-tabs', itemSelector: '.report-tab', indicatorClass: 'tab-slide-indicator' },
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
            // 1. SIDEBAR TOGGLE
            // ==========================================
            const btnSidebarToggle = document.getElementById('btnSidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
            const mobileSidebarQuery = window.matchMedia('(max-width: 900px)');
            let isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

            function applySidebarState() {
                if (mobileSidebarQuery.matches) {
                    body.classList.remove('sidebar-collapsed');
                    return;
                }

                body.classList.toggle('sidebar-collapsed', isSidebarCollapsed);
            }

            function closeMobileSidebar() {
                body.classList.remove('sidebar-mobile-open');
                btnSidebarToggle?.setAttribute('aria-expanded', 'false');
                sidebar?.setAttribute('aria-hidden', mobileSidebarQuery.matches ? 'true' : 'false');

                if (mobileSidebarQuery.matches && sidebarToggleIcon) {
                    sidebarToggleIcon.className = 'fi fi-rr-menu-burger';
                }
            }

            function openMobileSidebar() {
                body.classList.add('sidebar-mobile-open');
                btnSidebarToggle?.setAttribute('aria-expanded', 'true');
                sidebar?.setAttribute('aria-hidden', 'false');

                if (sidebarToggleIcon) {
                    sidebarToggleIcon.className = 'fi fi-br-cross';
                }
            }

            function syncSidebarMode() {
                applySidebarState();

                if (mobileSidebarQuery.matches) {
                    closeMobileSidebar();
                    btnSidebarToggle?.setAttribute('title', 'Buka Menu');
                    btnSidebarToggle?.setAttribute('aria-label', 'Buka menu navigasi');
                } else {
                    body.classList.remove('sidebar-mobile-open');
                    sidebar?.setAttribute('aria-hidden', 'false');
                    btnSidebarToggle?.setAttribute('aria-expanded', String(!isSidebarCollapsed));
                    btnSidebarToggle?.setAttribute('title', 'Toggle Sidebar');
                    btnSidebarToggle?.setAttribute('aria-label', 'Toggle sidebar');

                    if (sidebarToggleIcon) {
                        sidebarToggleIcon.className = 'fi fi-sr-angle-double-small-left';
                    }
                }
            }

            syncSidebarMode();

            if (btnSidebarToggle) {
                btnSidebarToggle.addEventListener('click', function () {
                    if (mobileSidebarQuery.matches) {
                        if (body.classList.contains('sidebar-mobile-open')) {
                            closeMobileSidebar();
                        } else {
                            openMobileSidebar();
                        }

                        return;
                    }

                    isSidebarCollapsed = !isSidebarCollapsed;
                    applySidebarState();
                    btnSidebarToggle.setAttribute('aria-expanded', String(!isSidebarCollapsed));
                    localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);
                });
            }

            sidebarBackdrop?.addEventListener('click', closeMobileSidebar);

            sidebar?.querySelectorAll('a.sidebar__nav-item, .sidebar__logout').forEach(item => {
                item.addEventListener('click', () => {
                    if (mobileSidebarQuery.matches) closeMobileSidebar();
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && body.classList.contains('sidebar-mobile-open')) {
                    closeMobileSidebar();
                }
            });

            if (typeof mobileSidebarQuery.addEventListener === 'function') {
                mobileSidebarQuery.addEventListener('change', syncSidebarMode);
            } else if (typeof mobileSidebarQuery.addListener === 'function') {
                mobileSidebarQuery.addListener(syncSidebarMode);
            }

            // ==========================================
            // 2. SUBMENU TOGGLE (Data Master)
            // ==========================================
            document.querySelectorAll('.js-submenu-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Find the submenu wrapper immediately after this toggle's parent block
                    const wrapper = toggle.nextElementSibling;
                    if (!wrapper || !wrapper.classList.contains('sidebar__submenu-wrapper')) return;

                    const isOpen = wrapper.classList.contains('open');
                    wrapper.classList.toggle('open', !isOpen);
                    toggle.classList.toggle('submenu-open', !isOpen);
                });
            });

            // ==========================================
            // 3. DARK MODE TOGGLE
            // ==========================================
            const btnTheme = document.getElementById('btnTheme');
            const themeIcon = document.getElementById('themeIcon');
            let isDarkMode = localStorage.getItem('theme') === 'dark';

            function enableDarkMode(animate) {
                body.classList.add('dark-mode');
                if (animate) {
                    themeIcon.className = 'fi fi-rr-moon';
                    themeIcon.classList.add('prepare-from-bottom');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-bottom');
                } else {
                    themeIcon.className = 'fi fi-rr-moon';
                }
            }

            function disableDarkMode(animate) {
                body.classList.remove('dark-mode');
                if (animate) {
                    themeIcon.className = 'fi fi-rr-sun';
                    themeIcon.classList.add('prepare-from-top');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-top');
                } else {
                    themeIcon.className = 'fi fi-rr-sun';
                }
            }

            if (isDarkMode) enableDarkMode(false);

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

            // ==========================================
            // 4. MODAL LOGIC
            // ==========================================

            // Open modal
            document.querySelectorAll('.js-open-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const targetId = btn.getAttribute('data-modal');
                    const modal = document.getElementById(targetId);
                    if (modal) modal.classList.add('show');
                });
            });

            // Close modal — button
            document.querySelectorAll('.js-close-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const targetId = btn.getAttribute('data-modal');
                    const modal = document.getElementById(targetId);
                    if (modal) modal.classList.remove('show');
                });
            });

            // Close modal — backdrop click
            document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) overlay.classList.remove('show');
                });
                const box = overlay.querySelector('.modal-box');
                if (box) box.addEventListener('click', function (e) { e.stopPropagation(); });
            });

            // Close modal — Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.show').forEach(function (m) {
                        m.classList.remove('show');
                    });
                }
            });

            // ==========================================
            // 4B. LOADING STATE (Konfirmasi TTD & Download)
            // ==========================================

            // Saat form konfirmasi (TTD/arsip, hapus) dikirim: tampilkan spinner di
            // tombol konfirmasi & cegah klik ganda. Halaman reload setelah redirect,
            // jadi tombol kembali normal dengan sendirinya.
            document.addEventListener('submit', function (e) {
                const confirmBtn = e.target.querySelector?.('.btn-modal--confirm');
                if (!confirmBtn || confirmBtn.dataset.loading === 'true') return;

                confirmBtn.dataset.loading = 'true';
                confirmBtn.innerHTML = '<span class="btn-spinner"></span> Memproses...';
                confirmBtn.disabled = true;
            });

            // Download laporan: ambil berkas lewat fetch agar spinner berhenti tepat
            // saat unduhan selesai (bukan lagi timer perkiraan).
            const filenameFromDisposition = (disposition) => {
                if (!disposition) return '';
                const match = disposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i);
                if (!match) return '';
                try { return decodeURIComponent(match[1]); } catch (_) { return match[1]; }
            };

            document.addEventListener('click', async function (e) {
                const link = e.target.closest?.('a.btn-act.download');
                if (!link || link.dataset.loading === 'true') return;
                e.preventDefault();

                const url = link.getAttribute('href');
                if (!url || url === '#') return;

                link.dataset.loading = 'true';
                link.dataset.label = link.innerHTML;
                link.classList.add('is-loading');
                link.innerHTML = '<span class="btn-spinner"></span> Menyiapkan...';

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('Gagal mengunduh berkas.');

                    const blob = await response.blob();
                    const filename = filenameFromDisposition(response.headers.get('Content-Disposition'));
                    const objectUrl = URL.createObjectURL(blob);
                    const anchor = document.createElement('a');
                    anchor.href = objectUrl;
                    anchor.download = filename || '';
                    document.body.appendChild(anchor);
                    anchor.click();
                    anchor.remove();
                    window.setTimeout(() => URL.revokeObjectURL(objectUrl), 10000);
                } catch (error) {
                    window.location.href = url;
                } finally {
                    link.innerHTML = link.dataset.label;
                    link.classList.remove('is-loading');
                    link.dataset.loading = 'false';
                }
            });

            // ==========================================
            // 5. TAB FILTER (Laporan Masuk)
            // ==========================================
            const reportTabs = document.querySelectorAll('.report-tab');
            const reportItems = document.querySelectorAll('.report-item');

            function filterReports(filter) {
                reportItems.forEach(function (item) {
                    const match = filter === 'all' || item.getAttribute('data-category') === filter;
                    item.style.display = match ? '' : 'none';
                });
            }

            reportTabs.forEach(function (tab) {
                const filter = tab.getAttribute('data-filter');
                const countEl = tab.querySelector('.report-tab__count');
                if (countEl) {
                    const reportCount = filter === 'all'
                        ? reportItems.length
                        : document.querySelectorAll('.report-item[data-category="' + filter + '"]').length;
                    countEl.textContent = reportCount > 0 ? reportCount : '';
                    countEl.hidden = reportCount <= 0;
                }
                tab.addEventListener('click', function () {
                    reportTabs.forEach(function (t) { t.classList.remove('active'); });
                    tab.classList.add('active');
                    filterReports(filter);
                });
            });

            // ==========================================
            // 6. FILTER TOGGLE (Arsip)
            // ==========================================
            const btnFilter = document.getElementById('btnFilter');
            const archiveFilters = document.getElementById('archiveFilters');
            if (btnFilter && archiveFilters) {
                btnFilter.addEventListener('click', function () {
                    const isOpen = !archiveFilters.classList.toggle('collapsed');
                    btnFilter.classList.toggle('btn-tool--active', isOpen);
                });
            }

            // ==========================================
            // 7. CUSTOM DROPDOWN (Regu, Shift & sort)
            // ==========================================
            document.querySelectorAll('.filter-select-wrapper').forEach(function (wrapper) {
                const select = wrapper.querySelector('select');
                if (!select) return;
                select.style.display = 'none';

                const trigger = document.createElement('div');
                trigger.className = 'filter-input filter-select-trigger';
                const label = document.createElement('span');
                label.textContent = select.options[select.selectedIndex].text;
                trigger.appendChild(label);
                wrapper.insertBefore(trigger, select.nextSibling);

                const list = document.createElement('div');
                list.className = 'filter-select-options';
                Array.from(select.options).forEach(function (opt, i) {
                    const item = document.createElement('div');
                    item.className = 'filter-select-option';
                    item.textContent = opt.text;
                    if (i === select.selectedIndex) item.classList.add('selected');
                    item.addEventListener('click', function (e) {
                        e.stopPropagation();
                        select.value = opt.value;
                        select.dispatchEvent(new Event('change'));
                        label.textContent = opt.text;
                        list.querySelectorAll('.filter-select-option').forEach(function (o) { o.classList.remove('selected'); });
                        item.classList.add('selected');
                        list.classList.remove('open');
                        trigger.classList.remove('focus-active');
                    });
                    list.appendChild(item);
                });
                wrapper.appendChild(list);

                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    document.querySelectorAll('.filter-select-options.open').forEach(function (c) {
                        if (c !== list) {
                            c.classList.remove('open');
                            const t = c.parentElement.querySelector('.filter-select-trigger');
                            if (t) t.classList.remove('focus-active');
                        }
                    });
                    list.classList.toggle('open');
                    trigger.classList.toggle('focus-active');
                });
            });

            document.addEventListener('click', function () {
                document.querySelectorAll('.filter-select-options.open').forEach(function (c) { c.classList.remove('open'); });
                document.querySelectorAll('.filter-select-trigger.focus-active').forEach(function (t) { t.classList.remove('focus-active'); });
            });

        });
    </script>
    @stack('scripts')

    <script>
        window.addEventListener('load', function () {
            var sk = document.getElementById('sk-overlay');
            if (sk) {
                sk.classList.add('sk-done');
                setTimeout(function () { sk.remove(); }, 600);
            }
        });
    </script>

</body>
</html>
