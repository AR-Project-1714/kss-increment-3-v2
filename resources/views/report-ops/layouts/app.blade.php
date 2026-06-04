<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan KSS - Dashboard</title>

    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- LINK BOOTSTRAP 5 CSS (Terbaru) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

    <!-- LINK FLATICON UICONS (Versi 2.6.0) -->
    <!-- Regular Rounded (fi-rr-*) -->
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>

    <!-- Bold Rounded (fi-br-*) -->
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>

    <!-- Solid Rounded (fi-sr-*) -->
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>

    @stack('styles')
    <!-- Style Internal CSS -->
     <style>
        /* =========================================
           VARIABLES (COLOR PALETTE & THEME)
           ========================================= */
        :root {
            /* Blue Palette */
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

            /* Cyan Palette */
            --cyan-main: #0EA5E9;
            --cyan-hover: #0B83B5;
            --cyan-active: #09658B;
            --cyan-main-25: #0ea5e940;
            --cyan-main-10: #0ea5e91a;
            --cyan-main-5: #0ea5e90d;
            --cyan-main-40: #0ea5e966;

            /* Red Palette */
            --red-main: #D20000;
            --red-hover: #B80000;
            --red-active: #9F0000;
            --red-main-25: #d2000040;
            --red-main-10: #d200001a;
            --red-main-5: #d200000d;
            --red-main-40: #d2000066;
            --red-input-focus: #fff5f5;

            /* Success/Green Palette */
            --success: #10B981;
            --success-hover: #0F9A6B;
            --success-active: #0E7A55;
            --success-25: #10b77f40;
            --success-10: #10b77f1a;
            --success-5: #10b77f0d;
            --success-40: #10b77f66;

            /* Orange Palette */
            --orange-main: #F7931E;
            --orange-hover: #E67E00;
            --orange-active: #CC6F00;
            --orange-main-25: #f9731640;
            --orange-main-10: #f973161a;
            --orange-main-5: #f973160d;
            --orange-main-40: #f9731666;
            --orange-input-focus: #fef4e8;
            --orange-bg: #FEF4E8;

            /* Grayscale & Black */
            --black: #000000;
            --black-25: #00000040;
            --black-10: lch(0% 0 0 / 0.102);
            --black-5: #0000000d;
            --black-40: #00000066;

            /* Theme Layout Colors */
            --dark-main: #0F172A;
            --dark-secondary: #334155;
            --dark-secondary-10: #3341551A;
            --dark-table-head: #0F172A;
            --muted: #94A3B8;
            --smooth-border:#E2E8F0;
            --main-bg:#F8FAFC;
            --white: #FFFFFF;
            --white-50: rgba(255, 255, 255, 0.5);
            --white-60: rgba(255, 255, 255, 0.6);
            --divider: #CBD5E1;
            --button-color: #FFFFFF;

            /* Component Specifics */
            --btn-theme-bg: var(--white);
            --btn-theme-border: rgba(0, 0, 0, 0.1);
            --btn-theme-icon: var(--dark-main);
        }

        /* Dark Mode Variables Override */
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
        }

        /* =========================================
           UTILITY CLASSES (Colors, Fonts, Layouts)
           ========================================= */
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

        /* Font Size */
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

        /* Font Weight */
        .fw-300 { font-weight: 300 !important; }
        .fw-400 { font-weight: 400 !important; }
        .fw-500 { font-weight: 500 !important; }
        .fw-600 { font-weight: 600 !important; }
        .fw-700 { font-weight: 700 !important; }

        /* Gaps */
        .gap-2 { gap: 2px !important; }
        .gap-4  { gap: 4px !important; }
        .gap-6  { gap: 6px !important; }
        .gap-8  { gap: 8px !important; }
        .gap-10 { gap: 10px !important; }
        .gap-15 { gap: 15px !important; }
        .gap-20 { gap: 20px !important; }
        .gap-30 { gap: 30px !important; }
        .gap-40 { gap: 40px !important; }

        /* Border Radius */
        .br-4   { border-radius: 4px !important; }
        .br-5   { border-radius: 5px !important; }
        .br-6   { border-radius: 6px !important; }
        .br-8   { border-radius: 8px !important; }
        .br-10  { border-radius: 10px !important; }
        .br-12  { border-radius: 12px !important; }
        .br-15  { border-radius: 15px !important; }
        .br-20  { border-radius: 20px !important; }
        .br-100 { border-radius: 100px !important; }

        /* Paddings */
        .p-navbar  { padding: 15px 22px !important; }
        .p-content { padding: 0 100px !important; transition: padding 0.3s; }
        .p-main { padding: 10px 20px !important; }
        .p-20 { padding: 20px !important; }
        .p-30 { padding: 30px !important; }
        .p-table { padding: 1px 0 !important;}
        .p-empty { padding: 60px 0 !important; }

        /* Misc Layouts */
        .size-logo { width: 108px !important; height: auto !important; transition: width 0.3s; }
        .divider-vertical { border-left: 1px solid var(--divider) !important; height: 30px !important; }
        .flexible { flex: 1 0 0 !important; }

        /* SPA HIDE/SHOW LOGIC */
        .d-none { display: none !important; }

        /* === GLOBAL & BODY STYLES === */
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            background-color: var(--main-bg);
            color: var(--dark-main);
            transition: background-color 0.3s ease-out, color 0.3s ease-out;
            margin-bottom: 40px;
        }

        .content {
            max-width: 1440px;
            margin: 0 auto;
            width: 100%;
        }

        .content-header, .main-content {
            background-color: var(--white);
            box-shadow: 0 2px 4px 0 var(--blue-main-10);
        }

        .main-content {
            padding-bottom: 20px !important
        }

        .title-header {
            min-width: 250px;
        }

        /* =========================================
           COMPONENTS: BUTTON THEMES & LOGOUT
           ========================================= */
        .btn-theme {
            width: 30px;
            height: 30px;
            background-color: var(--btn-theme-bg);
            border: 1px solid var(--btn-theme-border);
            cursor: pointer;
            overflow: hidden;
            position: relative;
            transition: all 0.3s ease;
            padding: 0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-theme:hover {
            border-color: var(--blue-main);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .icon-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        .icon-container i {
            color: var(--btn-theme-icon);
            font-size: 16px;
            position: relative;
            top: 3px;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
        }

        /* Dark Mode Theme Button Animation States */
        .prepare-from-top { transform: translateY(-150%); opacity: 0; }
        .prepare-from-bottom { transform: translateY(150%); opacity: 0; }
        .animate-out-up { transform: translateY(-150%) !important; opacity: 0 !important; }
        .animate-out-down { transform: translateY(150%) !important; opacity: 0 !important; }

        .btn-logout {
            border: none;
            background-color: var(--red-main-10);
            color: var(--red-main);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 30px;
            min-width: 30px;
            padding: 0 8px;
            transition: all 0.5s ease-out;
            overflow: hidden;
        }
        .btn-logout .icon-logout { display: flex; align-items: center; justify-content: center; }
        .btn-logout .icon-logout i { position: relative; top: 2px; flex-shrink: 0; color: var(--red-main); }
        .btn-logout .text {
            max-width: 0; opacity: 0; margin-left: 0; white-space: nowrap;
            overflow: hidden; transition: all 0.5s ease-out;
        }
        .btn-logout:hover { background-color: var(--red-main-10); color: var(--red-hover); outline: 1px solid var(--red-hover); }
        .btn-logout:hover .text { max-width: 60px; opacity: 1; margin-left: 8px; }
        .btn-logout:active { background-color: var(--red-main-25); color: var(--red-active); outline: 1px solid var(--red-active); }

        .btn-new {
            color: var(--button-color);
            padding: 10px 15px;
            background-color: var(--blue-main);
            border: none;
            transition: .2s ease-out;
        }
        .icon-new {
            position: relative;
            top: 3px;
        }
        .btn-new:hover {
            background-color: var(--blue-hover);
            transform: translateY(-3px);
        }

        /* =========================================
           STICKY HEADER - LIQUID GLASS ISLAND
           ========================================= */
        .content-header {
            position: relative;
            z-index: 10;
        }

        .content-header.is-sticky {
            position: fixed;
            top: 20px;
            left: 50%;
            max-width: 240px !important;
            padding: 6px 8px !important;
            background-color: rgba(255, 255, 255, 0.65) !important;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 100px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
            justify-content: center !important;
            z-index: 9999;
            transform: translate(-50%, -150%) scale(0.9);
            opacity: 0;
            pointer-events: none;
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease-out;
        }

        body.dark-mode .content-header.is-sticky {
            background-color: rgba(15, 23, 42, 0.65) !important;
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
        }

        .content-header.is-sticky.show-sticky {
            transform: translate(-50%, 0) scale(1);
            opacity: 1;
            pointer-events: auto;
        }

        .content-header.is-sticky .title-header {
            display: none !important;
        }

        .content-header.is-sticky .btn-new {
            border-radius: 100px !important;
            padding: 8px 18px !important;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            width: 100%;
            justify-content: center;
            gap: 6px;
            margin: 0;
        }

        .content-header.is-sticky .btn-new span.white.pure {
            font-size: 12px !important;
        }
        .content-header.is-sticky .btn-new .icon-new {
            font-size: 13px !important;
            top: 2px !important;
        }

        .content-header.is-sticky .btn-new:hover {
            transform: scale(1.03) !important;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4) !important;
        }

        /* === TABS STYLES (MODIFIED SCROLL) === */
        .tab-content {
            position: relative;
            border-bottom: 1px solid var(--divider);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--blue-main-25) transparent;
            flex-wrap: nowrap; /* Ensures tabs do not wrap to next line */
        }

        /* Scrollbar customization for tab-content */
        .tab-content::-webkit-scrollbar {
            height: 4px; /* Thin scrollbar for cleaner UI */
        }
        .tab-content::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 10px;
        }
        .tab-content::-webkit-scrollbar-thumb {
            background-color: var(--blue-main-25);
            border-radius: 10px;
        }
        .tab-content::-webkit-scrollbar-thumb:hover {
            background-color: var(--blue-hover);
        }

        .list-tab {
            position: relative;
            z-index: 1;
            display: flex;
            padding: 10px 0;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            text-decoration: none;
            font-size: 14px;
            color: var(--dark-secondary);
            border: 1px solid transparent;
            cursor: pointer;
            flex-shrink: 0; /* Prevents tab items from shrinking */
            white-space: nowrap; /* Prevents text from breaking lines */
        }

        .list-tab .list-item:hover {
           background-color: var(--dark-secondary-10);
        }

        .list-tab.active {
            border-bottom-color: transparent;
            color: var(--blue-active);
        }

        .tab-slide-indicator {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            border-radius: 999px;
            background: var(--blue-active);
            transform: translateX(0);
            transition: transform .34s cubic-bezier(.22,1,.36,1), width .34s cubic-bezier(.22,1,.36,1);
            pointer-events: none;
            z-index: 0;
        }

        .list-tab.active .list-item:hover {
            background-color: var(--blue-main-10);
        }

        .list-item {
            display: flex;
            padding: 2px 10px;
            justify-content: center;
            align-items: center;
            gap: 10px;
            border-radius: 6px;
        }

        .icon-tab i {
            position: relative;
            top: 1px;
            font-size: 12px;
        }
        .tab-amount {
            display: flex;
            padding: 0 5px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 10px;
            background-color: var(--orange-main);
            border-radius: 10px;
            color: var(--button-color);
        }

        /* === SHIFT LABELS (Common for all tabs) === */
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

        .icon-shift i {
            font-size: 8px;
        }

        .shift.pagi {
            background-color: var(--cyan-main-10);
            color: var(--cyan-main);
        }

        .shift.sore {
            background-color: var(--orange-main-10);
            color: var(--orange-main);
        }
        .shift.malam {
            background-color: var(--blue-main-10);
            color: var(--blue-main);
        }

        /* === 1. LAPORAN MASUK STYLES === */
        .report-item {
            display: flex;
            padding: 20px;
            justify-content: space-between;
            align-items: flex-end;
            align-content: flex-end;
            row-gap: 20px;
            align-self: stretch;
            flex-wrap: wrap;
            border-radius: 10px;
            background-color: var(--white);
            box-shadow: 0 0 1px 0 var(--muted);
            transition: .2s ease-out;
            width: 100%;
        }

        .report-item:hover {
            box-shadow: 0 2px 4px 0 var(--blue-main-40);
            transform: translateY(-2px);
            background-color: var(--blue-main-2);
        }

        .report-detail {
            min-width: 250px;
        }

        .upload-time {
            font-size: 10px;
            color: var(--muted);
            font-style: italic;
            font-weight: 300;
            gap: 5px;
        }

        .icon-clock i {
            position: relative;
            top: 2px;
        }

        .report-group {
            box-shadow: 0 0 1px 0 var(--muted);
            padding: 6px 8px;
        }

        .letter-group {
            display: flex;
            width: 15px;
            height: 15px;
            padding: 5px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-radius: 50px;
            font-size: 8px;
            font-weight: 600;
        }

        .letter-group.out {
            color: var(--success);
            background-color: var(--success-10);
        }

        .letter-group.in {
            color: var(--blue-main);
            background-color: var(--blue-main-10);
        }

        .icon-arrow i {
            position: relative;
            top: 2px;
        }

        .report-button .btn {
            display: flex;
            max-width: 230px;
            padding: 6px 10px;
            justify-content: center;
            align-items: center;
            gap: 6px;
            flex: 1 0 0;
            border-radius: 6px;
            border: none;
            color: var(--button-color);
            font-size: 10px;
        }

        .report-button .btn i {
            position: relative;
            top: 1px;
        }

        .report-button .btn.see {
            background-color: var(--orange-main);
            transition: 0.2s ease-out;
        }

        .report-button .btn.see:hover {
            background-color: var(--orange-hover);
            transform: translateY(-2px);
        }

        .report-button .btn.signed {
            background-color: var(--success);
            transition: 0.2s ease-out;
        }

        .report-button .btn.signed:hover {
            background-color: var(--success-hover);
            transform: translateY(-2px);
        }

        /* === 2. DRAFT STYLES === */
        .draft-item {
            background-color: var(--white);
            box-shadow: 0 0 1px 0 var(--muted);
            padding: 20px;
            transition: .2s ease-out;
            cursor: pointer;
            width: 100%;
        }

        .draft-item:hover {
            box-shadow: 0 2px 4px 0 var(--blue-main-40);
            transform: translateY(-2px);
            background-color: var(--blue-main-2);
        }

        .badge-draft {
            padding: 3px 6px;
            gap: 6px;
            background-color: var(--dark-secondary-10);
            border-radius: 4px;
            font-size: 10px;
        }

        .badge-draft .icon-draft i {
            position: relative;
            top: 2px;
        }

        .draft-report {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            align-content: flex-end;
            row-gap: 12px;
            align-self: stretch;
            flex-wrap: wrap;
        }

        .draft-detail {
            display: flex;
            min-width: 250px;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
            flex: 1 0 0;
        }

        .last-edit {
            font-style: italic;
        }

        .last-edit .icon-edit i {
            position: relative;
            top: 2px;
        }

        .btn-draft-edit {
            display: flex;
            max-width: 435px;
            padding: 6px 10px;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex: 1 0 0;
            border: none;
            border-radius: 6px;
            background-color: var(--blue-main);
            font-size: 10px;
            color: var(--button-color);
            transition: .2s ease-out;
        }

        .btn-draft-edit:hover {
            background-color: var(--blue-hover);
            transform: translateY(-2px);
        }

        .btn-draft-edit .icon-edit i {
            position: relative;
            top: 2px;
        }

        .btn-delete {
            display: flex;
            width: 27px;
            height: 27px;
            padding: 6px;
            justify-content: center;
            align-items: center;
            gap: 10px;
            border-radius: 4px;
            background: var(--white);
            box-shadow: 0 0 1px 0 var(--red-main);
            font-size: 12px;
            border: none;
            color: var(--red-main);
            transition: .2s ease-out;
        }

        .btn-delete:hover {
            background-color: var(--red-main-10);
            transform: translateY(-2px);
        }

        .btn-delete .icon-delete i {
            position: relative;
            top: 2px;
        }

        /* === 3. RIWAYAT LAPORAN STYLES === */
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--blue-main-25) transparent;
        }

        .table-responsive-wrapper::-webkit-scrollbar {
            height: 6px;
        }
        .table-responsive-wrapper::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 10px;
        }
        .table-responsive-wrapper::-webkit-scrollbar-thumb {
            background-color: var(--blue-main);
            border-radius: 10px;
        }
        .table-responsive-wrapper::-webkit-scrollbar-thumb:hover {
            background-color: var(--blue-hover);
        }

        .table-responsive-wrapper table {
            min-width: 1000px;
        }
        .thead th {
            display: flex;
            padding: 8px 10px;
            align-items: center;
            flex: 1 0 0;
            font-size: 12px;
            font-weight: 400;
        }
        th.nomor, td.nomor {
            display: flex;
            width: 50px;
            flex: none;
            padding: 8px 0;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        th.column-1 {
            min-width: 160px;
        }

        .tbody td {
            font-size: 12px;
            font-weight: 500;
        }
        .tbody {
            border-radius: 4px;
            transition: .1s ease-in-out;
            padding: 12px 0px !important;
        }

        .tbody:hover {
            background-color: var(--blue-bg-25);
        }

        .tbody td.column-2 {
            display: flex;
            min-width: 160px;
            padding: 0 10px;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            flex: 1 0 0;
        }
        .tbody td.column-3 {
            display: flex;
            padding: 0 10px;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            flex: 1 0 0;
        }

        .status {
            display: flex;
            padding: 2px 8px;
            align-items: center;
            gap: 4px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 400;
        }
        .icon-status i {
            font-size: 10px;
            position: relative;
            top: 1px;
        }
        .status.approve {
            border: 1px solid var(--success);
            color: var(--success);
            background-color: var(--success-10);
        }
        .status.confirm {
            border: 1px solid var(--cyan-main);
            color: var(--cyan-main);
            background-color: var(--cyan-main-10);
        }
        .status.submit {
            border: 1px solid var(--orange-main);
            color: var(--orange-main);
            background-color: var(--orange-main-10);
        }
        .status.draft {
            border: 1px solid var(--dark-secondary);
            color: var(--dark-secondary);
            background-color: var(--dark-secondary-10);
        }
        .status.archive {
            border: 1px solid var(--blue-main);
            color: var(--blue-main);
            background-color: var(--blue-main-10);
        }

        td.aksi {
            display: flex;
            padding: 0 10px;
            align-items: center;
            align-content: center;
            gap: 10px;
            flex: 1 0 0;
            flex-wrap: wrap;
        }

        td.aksi .btn {
            display: flex;
            padding: 6px 10px;
            align-items: center;
            gap: 6px;
            border: 4px;
            color: var(--button-color);
            font-size: 10px;
            font-weight: 500;
            transition: .2s ease-out;
        }

        td.aksi .btn.see {
            background-color: var(--orange-main);
        }
        td.aksi .btn.see:hover {
            background-color: var(--orange-hover);
            transform: translateY(-1px);
        }
        td.aksi .btn.edit {
            background-color: var(--blue-main);
        }
        td.aksi .btn.edit:hover {
            background-color: var(--blue-hover);
            transform: translateY(-1px);
        }
        .btn i {
            position: relative;
            top: 1px;
        }

        /* === ANIMASI TAB === */
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .animate-slide-right {
            animation: slideInRight 0.3s ease-out forwards;
        }

        .animate-slide-left {
            animation: slideInLeft 0.3s ease-out forwards;
        }

        @keyframes draftReminderPulse {
            0%, 100% {
                transform: translateY(0);
                box-shadow: 0 0 0 rgba(255, 139, 22, 0);
                border-color: rgba(255, 139, 22, 0.12);
            }
            45% {
                transform: translateY(-1px);
                box-shadow: 0 12px 28px rgba(255, 139, 22, 0.16);
                border-color: rgba(255, 139, 22, 0.28);
            }
        }

        @keyframes draftReminderIconWiggle {
            0%, 72%, 100% { transform: rotate(0deg) scale(1); }
            78% { transform: rotate(-8deg) scale(1.04); }
            84% { transform: rotate(8deg) scale(1.04); }
            90% { transform: rotate(-5deg) scale(1.02); }
            96% { transform: rotate(4deg) scale(1.01); }
        }

        @keyframes draftReminderButtonNudge {
            0%, 68%, 100% { transform: translateX(0); }
            74% { transform: translateX(3px); }
            80% { transform: translateX(-2px); }
            86% { transform: translateX(2px); }
            92% { transform: translateX(0); }
        }

        @keyframes draftReminderSheen {
            0%, 58% { transform: translateX(-140%) skewX(-18deg); }
            82%, 100% { transform: translateX(140%) skewX(-18deg); }
        }

        /* Reminder Draft */
        .reminder-draft {
            position: relative;
            display: flex;
            padding: 10px;
            justify-content: space-between;
            align-items: center;
            align-content: center;
            row-gap: 10px;
            align-self: stretch;
            flex-wrap: wrap;
            border-radius: 16px;
            border: 1px solid rgba(255, 139, 22, 0.12);
            background: var(--orange-main-10);
            overflow: hidden;
            animation: draftReminderPulse 2.8s ease-in-out infinite;
        }

        .icon-reminder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 10px;
            background-color: var(--orange-main-10);
            color: var(--orange-main);
            animation: draftReminderIconWiggle 2.4s ease-in-out infinite;
            transform-origin: center;
        }

        .icon-reminder i {
            position: relative;
            top: 2px;
        }

        /* === BTN DRAFT EDIT (Di Dalam Kotak Reminder) === */
        .btn.draft-edit {
            display: flex;
            padding: 6px 12px;
            justify-content: center;
            align-items: center;
            gap: 0px; /* Diubah menjadi 0 secara default untuk animasi */
            border-radius: 4px;
            background-color: var(--orange-main);
            font-size: 10px;
            font-weight: 600;
            color: var(--button-color);
            transition: .3s ease-out;
            position: relative;
            overflow: hidden;
            animation: draftReminderButtonNudge 2.4s ease-in-out infinite;
        }

        .btn.draft-edit::after {
            content: "";
            position: absolute;
            inset: -40% -25%;
            background: linear-gradient(100deg, transparent 35%, rgba(255, 255, 255, 0.34) 50%, transparent 65%);
            transform: translateX(-140%) skewX(-18deg);
            animation: draftReminderSheen 3s ease-in-out infinite;
            pointer-events: none;
        }

        .btn.draft-edit .text,
        .btn.draft-edit .icon-edit {
            position: relative;
            z-index: 1;
        }

        .btn.draft-edit:hover {
            background-color: var(--orange-hover);
            gap: 10px; /* Munculkan gap saat di hover */
            animation-play-state: paused;
        }

        /* Animasi Icon pada Tombol di dalam Reminder */
        .btn.draft-edit .icon-edit {
            max-width: 0;
            opacity: 0;
            transform: translateX(-10px); /* Efek icon bergeser dari sebelah kiri */
            transition: .3s ease-out;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn.draft-edit:hover .icon-edit {
            max-width: 20px;
            opacity: 1;
            transform: translateX(0);
        }

        .btn.close i{
            font-size: 12px;
            color: var(--muted);
            transition: .2s ease-out;
        }
        .btn.close:hover i {
            color: var(--red-main);
        }

        @media (prefers-reduced-motion: reduce) {
            .reminder-draft,
            .icon-reminder,
            .btn.draft-edit,
            .btn.draft-edit::after {
                animation: none !important;
            }
        }

        /* =========================================
           MODAL & POP UP (Konfirmasi Tanda Tangan)
           ========================================= */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;

            /* Hidden State Default */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .pop-up.signed {
            width: 350px;
            padding: 20px;
            gap: 30px;
            flex-shrink: 0;
            border-radius: 20px;
            background-color: var(--white);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            color: var(--dark-main);

            /* Animasi Skala Pop up */
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-overlay.show .pop-up.signed {
            transform: scale(1);
        }

        .button-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
        }

        .button-close:hover .icon-close i {
            color: var(--red-main);
        }

        .pop-up.detail {
            padding: 10px;
            gap: 10px;
            border-radius: 10px;
            background-color: var(--cyan-main-10);
            box-shadow:  0 0 1px 0 var(--cyan-main);
        }

        .icon-document {
            display: flex;
            width: 34px;
            height: 34px;
            padding: 6px;
            align-items: center;
            border-radius: 4px;
            background-color: var(--white);
            box-shadow: 0 0 1px 0 var(--cyan-main);
            font-size: 22px;
            color: var(--blue-main);
            justify-content: center;
        }
        .icon-document i {
            position: relative;
            top: 3px;
        }

        .signature-box {
            padding: 10px;
            gap: 10px;
            border-radius: 10px;
            background-color: var(--white);
            box-shadow: 0 0 1px 0 var(--muted);
        }

        .img-sign {
            display: flex;
            width: 100px;
            height: 60px;
            justify-content: center;
            align-items: center;
            gap: 10px;
            align-self: stretch;
            border-radius: 4px;
            background-color: var(--white);
            box-shadow: 0 0 1px 0 var(--muted);
            object-fit: contain;
        }

        .verified {
            display: flex;
            padding: 2px 6px;
            justify-content: center;
            align-items: center;
            gap: 6px;
            border-radius: 10px;
            background: var(--success-10);
            font-weight: 600;
            color: var(--success);
        }

        .icon-verified {
            font-size: 12px;
            position: relative;
            top: 1px;
        }

        .icon-notes {
            font-size: 12px;
            position: relative;
            top: 5px;
        }

        .pop-up.footer .btn {
            display: flex;
            padding: 8px 12px;
            justify-content: center;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            transition: all 0.2s ease;
        }

        .pop-up.footer .btn.cancel {
            background-color: transparent;
            color: var(--dark-main);
        }

        .pop-up.footer .btn.cancel:hover {
            background-color: var(--dark-secondary-10);
        }

        .pop-up.footer .btn.confirm {
            background-color: var(--success);
            color: #ffffff;
        }

        .pop-up.footer .btn.confirm i {
            position: relative;
            top: 2px;
        }

        .pop-up.footer .btn.confirm:hover {
            background-color: var(--success-hover);
            transform: translateY(-2px);
        }

        /* === NEW BUTTONS FOR DELETE & EDIT POPUP === */
        .pop-up.footer .btn.delete-confirm {
            background-color: var(--red-main);
            color: #ffffff;
        }
        .pop-up.footer .btn.delete-confirm:hover {
            background-color: var(--red-hover);
            transform: translateY(-2px);
        }

        .pop-up.footer .btn.edit-confirm {
            background-color: var(--blue-main);
            color: #ffffff;
        }
        .pop-up.footer .btn.edit-confirm:hover {
            background-color: var(--blue-hover);
            transform: translateY(-2px);
        }

        .pop-up.detail.danger {
            background-color: var(--red-main-10);
            box-shadow: 0 0 1px 0 var(--red-main);
        }
        .icon-document.danger {
            color: var(--red-main);
            box-shadow: 0 0 1px 0 var(--red-main);
        }

        /* CREATE FORM CSS */
        /* =========================================
           MAIN NAVIGATION TABS (TABS FORM)
           ========================================= */
        .tab-form {
            position: relative;
            display: flex; padding: 5px; align-items: center; align-content: center;
            gap: 5px 10px; align-self: stretch; flex-wrap: nowrap; border-radius: 10px;
            background-color: var(--white); box-shadow: 0 2px 4px 0 var(--blue-main-10);
            width: 100%; overflow-x: auto; overflow-y: hidden;
            -webkit-overflow-scrolling: touch; scrollbar-width: thin; scrollbar-color: var(--blue-main-25) transparent;
        }
        .tab-form::-webkit-scrollbar { height: 6px; }
        .tab-form::-webkit-scrollbar-track { background: transparent; border-radius: 10px; }
        .tab-form::-webkit-scrollbar-thumb { background: var(--blue-main); border-radius: 10px; }
        .tab-form::-webkit-scrollbar-thumb:hover { background: var(--blue-hover); }

        .list-form-tab {
            position: relative;
            z-index: 1;
            display: flex; min-width: 130px; padding: 6px 12px; justify-content: center; align-items: center;
            gap: 8px; flex: 1 0 auto; flex-shrink: 0; white-space: nowrap; font-size: 12px; font-weight: 500;
            color: var(--dark-secondary); cursor: pointer; border-radius: 8px; transition: .2s ease-out;
        }
        .list-form-tab:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
        .list-form-tab.active { color: var(--button-color); background: transparent; box-shadow: none; }
        .list-form-tab.active:hover { background: transparent; color: var(--button-color); }
        .list-form-tab .icon-tab { position: relative; top: 1px; }

        .tab-form-indicator {
            position: absolute;
            left: 0;
            top: 5px;
            bottom: 5px;
            width: 0;
            border-radius: 8px;
            background: var(--blue-main);
            box-shadow: 0 0 4px 0 var(--blue-main-40);
            transform: translateX(0);
            transition: transform .34s cubic-bezier(.22,1,.36,1), width .34s cubic-bezier(.22,1,.36,1);
            pointer-events: none;
            z-index: 0;
        }

        /* =========================================
           FORM CONTAINERS & HEADERS
           ========================================= */
        .box-form {
            gap: 10px; border-radius: 10px; box-shadow: 0 2px 4px 0 var(--blue-main-10); background-color: var(--white);
        }
        .header-form {
            padding: 20px 25px; border-top: 4px solid var(--blue-main);
            border-radius: 10px 10px 0 0; background-color: var(--blue-main-2);
        }
        .icon-title-form {
            font-size: 14px; color: var(--blue-main); display: flex; width: 30px; height: 30px;
            padding: 10px; justify-content: center; align-items: center; border-radius: 6px; background-color: var(--blue-main-10); font-weight: 600;
        }
        .icon-title-form i { position: relative; top: 2.5px; }
        .counter-form {
            display: flex; width: 90px; padding: 4px 10px; justify-content: center; align-items: center;
            border-radius: 20px; background-color: var(--white); border: 1px solid var(--blue-main-10); font-size: 10px; color: var(--muted);
        }
        .content-form { padding: 20px 25px; gap: 25px; border: 0 0 10px 10px; }

        /* =========================================
           FORM INPUTS & DROPDOWNS
           ========================================= */
        .box-input-1 { display: flex; min-width: 160px; flex-direction: column; align-items: flex-start; gap: 8px; flex: 1 0 0; }
        .box-label-1 { display: flex; align-items: center; gap: 5px; font-size: 13px; }
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator { display: none; -webkit-appearance: none; color: var(--muted); }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        /* Field pengganti OP.7 yang terisi otomatis (No.Forklift, Area, Masuk, Keluar) */
        input.is-auto-filled { background-color: var(--blue-main-5); cursor: not-allowed; }

        .input-wrapper { position: relative; display: flex; align-items: center; width: 100%; align-self: stretch; height: 100%;}

        .custom-input {
            width: 100%; padding: 10px 35px 10px 15px; border: 1px solid var(--black-25); border-radius: 8px;
            font-size: 13px; font-family: 'Poppins', sans-serif; color: var(--dark-main); background-color: var(--white);
            outline: none; transition: .2s ease-out; cursor: pointer; min-height: 42px;
        }
        .custom-input:focus, .custom-input.focus-active { border-color: var(--blue-main); box-shadow: 0 0 0 3px var(--blue-main-10); }
        .custom-input::placeholder { color: var(--muted); }

        .form-info-umum input[type="date"].custom-input {
            cursor: pointer;
        }

        select.custom-input { -webkit-appearance: none; -moz-appearance: none; appearance: none; }
        select.custom-input option { background-color: var(--white); color: var(--dark-main); font-family: 'Poppins', sans-serif; padding: 10px; }
        select.custom-input option:disabled { color: var(--muted); }

        .box-input-1.route-invalid .custom-input {
            border-color: var(--red-main) !important;
            box-shadow: 0 0 0 3px var(--red-main-10) !important;
        }
        .group-route-warning {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            color: var(--red-main);
            font-size: 11px;
            font-weight: 500;
            line-height: 1.35;
        }
        .group-route-warning.d-none {
            display: none !important;
        }
        .group-route-warning i {
            position: relative;
            top: 1px;
            flex: 0 0 auto;
        }

        .input-icon, .tbl-icon-dropdown {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--blue-main); pointer-events: none;
            min-height: 18px; line-height: 1; font-size: 14px; display: flex; align-items: center; justify-content: center; z-index: 5;
        }
        .input-icon i, .tbl-icon-dropdown i { line-height: 1; position: static; }
        .text-placeholder { color: var(--muted) !important; }

        /* Dropdown Table Custom Select */
        .tbl-select-wrapper { position: relative; display: flex; align-items: center; width: 100%; align-self: stretch; height: 100%; }
        .tbl-custom-select-trigger {
            width: 100%; padding: 10px 35px 10px 15px; border: 1px solid var(--divider); border-radius: 8px;
            font-size: 12px; font-family: 'Poppins', sans-serif; color: var(--dark-main); background-color: var(--white);
            outline: none; transition: .2s ease-out; cursor: pointer; min-height: 41px; box-sizing: border-box;
        }
        .tbl-custom-select-trigger:focus, .tbl-custom-select-trigger.focus-active {
            border-color: var(--blue-main); box-shadow: 0 0 0 3px var(--blue-main-10);
        }
        .tbl-custom-select-trigger::placeholder { color: var(--muted); }

        /* Container Custom Options List */
        .tbl-custom-options {
            position: absolute; top: 100%; left: 0; right: 0; background: var(--white); border: 1px solid var(--black-25);
            border-radius: 8px; margin-top: 4px; z-index: 100; display: none; box-shadow: 0 4px 12px var(--black-10);
            overflow: hidden; box-sizing: border-box;
        }
        .tbl-custom-options.open { display: block; }
        .tbl-custom-option {
            padding: 10px 15px; font-size: 12px; color: var(--dark-main); background-color: var(--white);
            cursor: pointer; transition: background-color 0.2s, color 0.2s; position: relative;
        }
        .tbl-custom-option:hover { background-color: var(--blue-bg); color: var(--blue-main); }
        .tbl-custom-option.selected { color: var(--blue-main); background-color: var(--white); }
        .tbl-custom-option.selected::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background-color: var(--blue-main);
        }

        .custom-options-container {
            position: absolute; top: calc(100% + 5px); left: 0; right: 0; background: var(--white);
            border: 1px solid var(--black-25); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            z-index: 999; display: none; max-height: 200px; overflow-y: auto; padding: 8px 0;
        }
        .custom-options-container.open { display: block; animation: fadeIn .2s ease-out; }
        .custom-option {
            padding: 10px 15px; font-size: 13px; font-family: 'Poppins', sans-serif; color: var(--dark-secondary);
            cursor: pointer; transition: background-color 0.2s ease, color 0.2s ease; font-weight: 400;
        }
        .custom-option:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
        .custom-option.selected { background-color: var(--blue-main-2); color: var(--blue-main); border-left: 3px solid var(--blue-main); font-weight: 500; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* General Inputs (Forms grids) */
        .form-grid { display: flex; align-items: center; align-content: center; gap: 20px; align-self: stretch; flex-wrap: wrap; }
        .form-group { display: flex; min-width: 160px; flex-direction: column; align-items: flex-start; gap: 5px; flex: 1 0 0; color: var(--dark-main); }
        .form-group label { font-size: 11px; }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="time"],
        .form-group input[type="datetime-local"] {
            display: flex; padding: 8px 15px; border: 1px solid var(--divider); background-color: var(--white);
            color: var(--dark-main); border-radius: 10px; font-size: 12px; align-self: stretch; align-items: center;
            width: 100%; box-sizing: border-box; font-family: 'Poppins', sans-serif;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group input[type="time"]:focus,
        .form-group input[type="datetime-local"]:focus {
            outline: 3px solid var(--blue-main-10); box-shadow: 0 0 1px 0 var(--blue-main); background-color: var(--blue-input-focus);
        }

        /* Time pickers icons */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator,
        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            position: absolute; right: 12px; width: 24px; height: 24px; opacity: 0; cursor: pointer; z-index: 10;
        }

        .form-info-umum input[type="date"].custom-input::-webkit-calendar-picker-indicator {
            display: block;
            position: absolute;
            right: 12px;
            width: 24px;
            height: 24px;
            opacity: 0;
            cursor: pointer;
            z-index: 10;
        }

        /* =========================================
           ACTIVITIES & TABS BONGKAR
           ========================================= */
        /* Deretan tab kegiatan + tombol +/- harus mengikuti lebar form induk
           dan turun ke baris baru bila penuh (anti-overflow di desktop & mobile). */
        .tab-activity {
            width: 100%;
            max-width: 100%;
            flex-wrap: wrap;
            row-gap: 10px;
        }

        .btn-activity {
            display: flex; width: 120px; padding: 6px 10px; justify-content: center; align-items: center;
            gap: 8px; border-radius: 8px; background-color: var(--white); border: 1px solid var(--blue-main-25);
            font-size: 12px; font-weight: 600; color: var(--dark-secondary); transition: .1s ease-out; cursor: pointer;
            flex: 0 0 auto;
        }

        /* Grup tombol +/- tetap utuh sebagai satu kesatuan saat tab membungkus baris. */
        .plus-minus-tab { flex: 0 0 auto; }
        .btn-activity:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
        .btn-activity.active { background-color: var(--blue-main); box-shadow: 0 0 4px 0 var(--blue-main-40); color: var(--button-color); }

        .plus-minus-tab .btn {
            display: flex; width: 30px; height: 30px; justify-content: center; align-items: center;
            border-radius: 8px; color: var(--dark-secondary); transition: .1s ease-out; font-size: 18px; cursor: pointer;
        }
        .plus-minus-tab .btn i { position: relative; top: 3px; }
        .plus-minus-tab .btn.add { background-color: var(--blue-main-5); border: 1px solid var(--blue-main-25); }
        .plus-minus-tab .btn.add:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
        .plus-minus-tab .btn.remove { background-color: var(--red-main-5); border: 1px solid var(--red-main-25); }
        .plus-minus-tab .btn.remove:hover { background-color: var(--red-main-10); color: var(--red-main); }

        .tab-bongkar {
            display: flex; padding: 5px; align-items: center; align-content: center; align-self: stretch;
            flex-wrap: wrap; gap: 5px 10px; border-radius: 13px; background-color: var(--blue-main-2); border: 1px solid var(--blue-main-25);
        }
        .tab-bongkar .tab {
            display: flex; min-width: 250px; padding: 8px 12px; justify-content: center; align-items: center;
            gap: 8px; flex: 1 0 0; border-radius: 8px; text-decoration: none; color: var(--dark-secondary); font-size: 12px; font-weight: 600; cursor: pointer; transition: .2s;
        }
        .tab-bongkar .tab.active.material, .tab-bongkar .tab.active.material:hover { color: var(--button-color); background-color: var(--blue-main); }
        .tab-bongkar .tab.material:hover { color: var(--blue-main); background-color: var(--blue-main-10); }
        .tab-bongkar .tab.active.tab-container, .tab-bongkar .tab.active.tab-container:hover { color: var(--button-color); background-color: var(--orange-main); }
        .tab-bongkar .tab.tab-container:hover { color: var(--orange-main); background-color: var(--orange-main-10); }

        /* =========================================
           SUMMARY CARDS
           ========================================= */
        .form-card { display: flex; min-width: 300px; flex-direction: column; align-items: flex-start; flex: 1 0 0; border-radius: 10px; background-color: var(--white); }
        .form-card.deliv { border: 1px solid var(--blue-main-40); }
        .form-card.load { border: 1px solid var(--orange-main-40); }
        .form-card.damage { border: 1px solid var(--red-main-40); }

        .form-card-head { display: flex; padding: 10px; justify-content: space-between; align-items: center; align-self: stretch; border-radius: 10px 10px 0 0; font-weight: 600; }
        .deliv .form-card-head { background-color: var(--blue-main-10); border-bottom: 1px solid var(--blue-main-40); color: var(--blue-main); }
        .load .form-card-head { background-color: var(--orange-main-10); border-bottom: 1px solid var(--orange-main-40); color: var(--orange-main); }
        .damage .form-card-head { background-color: var(--red-main-10); border-bottom: 1px solid var(--red-main-40); color: var(--red-main); }

        .form-card-head .title { display: flex; align-items: center; gap: 10px; flex: 1 0 0; font-size: 12px; }
        .form-card-head .title .box-icon { display: flex; width: 25px; height: 25px; padding: 10px; justify-content: center; align-items: center; border-radius: 6px; background: var(--white); }
        .box-icon i { position: relative; top: 2px; }

        .form-card-content { display: flex; padding: 10px; align-items: flex-end; gap: 5px; align-self: stretch; }
        .card-form-group { display: flex; flex-direction: column; align-items: flex-start; gap: 5px; flex: 1 0 0; }
        .card-form-group label { font-size: 11px; }
        .card-form-group input[type="number"] {
            width: 100%; box-sizing: border-box; min-width: 0; padding: 8px 0; text-align: center;
            border-radius: 10px; border: 1px solid var(--divider); font-size: 12px; font-weight: 500; align-self: stretch; background-color: var(--white); color: var(--dark-main);
        }
        .deliv .card-form-group input[type="number"]:focus { outline: 3px solid var(--blue-main-10); box-shadow: 0 0 1px 0 var(--blue-main); background-color: var(--blue-input-focus); }
        .load .card-form-group input[type="number"]:focus { outline: 3px solid var(--orange-main-10); box-shadow: 0 0 1px 0 var(--orange-main); background-color: var(--orange-input-focus); }
        .damage .card-form-group input[type="number"]:focus { outline: 3px solid var(--red-main-10); box-shadow: 0 0 1px 0 var(--red-main); background-color: var(--red-input-focus); }

        /* Saat lebar tidak cukup untuk tiga kartu sejajar, stack penuh agar
           Pengiriman & Pemuatan selebar Kerusakan (tidak jadi 2+1 yang timpang). */
        @media (max-width: 960px) {
            .summary-section .form-card {
                flex: 1 0 100% !important;
                min-width: 100% !important;
            }
        }

        /* =========================================
           TIMESHEET COMPONENT
           ========================================= */
        .timesheet-section {
            align-items: stretch !important;
        }

        .timesheet-card {
            display: flex; min-width: min(380px, 100%); padding: 15px; flex-direction: column; align-items: flex-start;
            gap: 15px; flex: 1 1 380px; align-self: stretch; min-height: 350px; background-color: var(--main-bg); border-radius: 10px; border: 1px solid var(--divider);
        }
        .timesheet-card.w-100 { flex-basis: 100%; min-width: 100%; }

        @media (max-width: 1024px) {
            .timesheet-section { gap: 16px !important; }
            .timesheet-card {
                flex: 1 1 100% !important;
                min-width: 100% !important;
                flex-basis: 100% !important;
            }
        }
        .timesheet-card-header { display: flex; padding-bottom: 10px; align-items: center; gap: 15px; align-self: stretch; }

        .timesheet-icon { display: flex; width: 35px; height: 35px; padding: 10px; justify-content: center; align-items: center; gap: 10px; aspect-ratio: 1/1; border-radius: 4px; color: var(--button-color); font-size: 14px; }
        .timesheet-icon i { position: relative; top: 2px; }
        .timesheet-content { display: flex; flex: 1 1 auto; flex-direction: column; align-items: flex-start; gap: 15px; align-self: stretch; }

        .timesheet-input { display: flex; padding: 5px; align-items: center; align-content: center; gap: 8px; align-self: stretch; flex-wrap: wrap; border-radius: 15px; background-color: var(--white); border: 1px solid var(--divider); }
        .timesheet-input.is-invalid { border-color: var(--red-main); box-shadow: 0 0 0 3px var(--red-main-10); }
        .timesheet-input-wrapper { display: flex; padding: 8px 12px; align-items: center; gap: 10px; border-radius: 10px; background-color: var(--white); border: 1px solid var(--divider); font-size: 12px; transition: .2s ease-out; cursor: text; }
        .timesheet-input-wrapper.is-invalid,
        .cob-wrapper.is-invalid { border-color: var(--red-main) !important; box-shadow: 0 0 0 3px var(--red-main-10) !important; }
        .timesheet-input-wrapper input.time-picker-input { border: none; width: 45px; font-weight: 600; color: var(--dark-main); background: transparent; padding: 0; text-align: center; font-size: 12px; outline: none; }
        .timesheet-input-wrapper input.time-picker-input::placeholder { color: var(--muted); font-weight: 400; }
        .timesheet-input-wrapper .icon { color: var(--muted); transition: .2s; display: flex; align-items: center; }
        .timesheet-input input.activity-input { display: flex; min-width: 140px; padding: 8px 15px; align-items: center; gap: 15px; flex: 1 0 0; border-radius: 10px; border: 1px solid transparent; font-size: 12px; font-weight: 500; background-color: var(--white); color: var(--dark-main); }
        .timesheet-input input.activity-input.is-invalid { border-color: var(--red-main); box-shadow: 0 0 0 3px var(--red-main-10); }
        .timesheet-input input.activity-input:hover { outline: 1px solid var(--divider); }
        .btn-add-activity { display: flex; padding: 8px 12px; align-items: center; gap: 10px; border-radius: 10px; color: var(--button-color); font-size: 12px; font-weight: 500; border: none; transition: .2s ease-out; cursor: pointer; }
        .btn-add-activity .icon i  { font-size: 10px; }

        /* Timesheet Variants (Deliv / Load) */
        .deliv .timesheet-card-header { border-bottom: 2px solid var(--blue-main-40); }
        .deliv .timesheet-icon { background-color: var(--blue-main); }
        .deliv .timesheet-input-wrapper:focus-within { border-color: var(--blue-main); box-shadow: 0 0 0 3px var(--blue-main-10); background-color: var(--white); }
        .deliv .timesheet-input-wrapper:focus-within .icon { color: var(--blue-main); }
        .deliv .timesheet-input input.activity-input:focus { outline:1px solid var(--blue-main); box-shadow: 0 0 0 3px var(--blue-main-10); background-color: var(--white); }
        .deliv .btn-add-activity { background: var(--blue-main); }
        .deliv .btn-add-activity:hover { background-color: var(--blue-hover); }
        .deliv .timeline-item:not(:last-child)::after { background-color: var(--blue-main-25); }
        .deliv .timeline-item .dot i { color: var(--blue-main); }
        .deliv .timeline-item .content:hover { outline: 3px solid var(--blue-main-10); }
        .deliv .clock { color: var(--blue-main); }

        .load .timesheet-card-header { border-bottom: 2px solid var(--orange-main-40); }
        .load .timesheet-icon { background-color: var(--orange-main); }
        .load .timesheet-input-wrapper:focus-within { border-color: var(--orange-main); box-shadow: 0 0 0 3px var(--orange-main-10); background-color: var(--white); }
        .load .timesheet-input-wrapper:focus-within .icon { color: var(--orange-main); }
        .load .timesheet-input input.activity-input:focus { outline:1px solid var(--orange-main); box-shadow: 0 0 0 3px var(--orange-main-10); background-color: var(--white); }
        .load .btn-add-activity { background: var(--orange-main); }
        .load .btn-add-activity:hover { background-color: var(--orange-hover); }
        .load .timeline-item:not(:last-child)::after { background-color: var(--orange-main-25); }
        .load .timeline-item .dot i { color: var(--orange-main); }
        .load .timeline-item .content:hover { outline: 3px solid var(--orange-main-10); }
        .load .clock { color: var(--orange-main); }

        .timesheet-personnel-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 16px;
            align-self: stretch;
            padding-top: 14px;
            border-top: 1px solid var(--divider);
        }
        .timesheet-personnel-grid .form-group {
            min-width: 0;
            flex: none;
        }
        .timesheet-personnel-grid .form-group--full {
            grid-column: 1 / -1;
        }
        .load .timesheet-personnel-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
        .load .timesheet-personnel-grid .form-group:nth-child(1),
        .load .timesheet-personnel-grid .form-group:nth-child(2),
        .load .timesheet-personnel-grid .form-group:nth-child(3) {
            grid-column: span 2;
        }
        .load .timesheet-personnel-grid .form-group:nth-child(4) {
            grid-column: span 3;
        }
        .load .timesheet-personnel-grid .form-group:nth-child(5) {
            grid-column: span 3;
        }
        .timesheet-personnel-grid .form-group label {
            color: var(--dark-secondary);
            font-weight: 600;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }
        .timesheet-personnel-grid .input-wrapper input[type="text"] {
            padding-left: 40px;
        }
        .personnel-input-icon {
            position: absolute;
            left: 15px;
            top: calc(50% + 1px);
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 13px;
            line-height: 1;
            pointer-events: none;
            transform: translateY(-50%);
        }
        .personnel-input-icon i { position: relative; top: 1px; }
        .deliv .timesheet-personnel-grid .personnel-input-icon { color: var(--blue-main); }
        .load .timesheet-personnel-grid .personnel-input-icon { color: var(--orange-main); }
        .load .timesheet-personnel-grid .form-group input[type="text"]:focus {
            outline: 3px solid var(--orange-main-10);
            box-shadow: 0 0 1px 0 var(--orange-main);
            background-color: var(--orange-input-focus);
        }

        @media (max-width: 560px) {
            .timesheet-personnel-grid {
                grid-template-columns: 1fr;
            }

            .load .timesheet-personnel-grid {
                grid-template-columns: 1fr;
            }

            .timesheet-personnel-grid .form-group--full {
                grid-column: auto;
            }

            .load .timesheet-personnel-grid .form-group:nth-child(4),
            .load .timesheet-personnel-grid .form-group:nth-child(5) {
                grid-column: auto;
            }

            .load .timesheet-personnel-grid .form-group:nth-child(1),
            .load .timesheet-personnel-grid .form-group:nth-child(2),
            .load .timesheet-personnel-grid .form-group:nth-child(3) {
                grid-column: auto;
            }
        }

        /* Timeline Items */
        .timeline-section { display: flex; min-height: 220px; flex: 1 1 auto; flex-direction: column; align-items: flex-start; gap: 15px; align-self: stretch; }
        .timeline-section:empty { min-height: 0; }
        .timeline-item { display: flex; align-items: center; gap: 10px; align-self: stretch; position: relative; }
        .timeline-item:not(:last-child)::after { content: ''; position: absolute; top: 50%; left: 7px; width: 2px; height: calc(100% + 15px); transform: translateX(-50%); z-index: 0; }
        .timeline-item .dot { display: flex; width: 14px; height: 14px; justify-content: center; align-items: center; background-color: var(--main-bg, #ffffff); position: relative; z-index: 1; }
        .timeline-item .dot i { font-size: 8px; }
        .timeline-item .content { display: flex; padding: 6px 12px; justify-content: center; align-items: center; gap: 10px; flex: 1 0 0; border-radius: 10px; background-color: var(--white); border: 1px solid var(--smooth-border, #e5e5e5); transition: .2s ease-out; overflow: hidden; }
        .timeline-item .content .btn-trash  { font-size: 12px; color: var(--red-main); background: none; border: none; cursor: pointer; opacity: 0; pointer-events: none; transform: translateX(20px); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .timeline-item .content:hover .btn-trash { opacity: 1; pointer-events: auto; transform: translateX(0); }
        .btn-trash i { position: relative; top: 1px; }

        .timeline-item .content .btn-edit { font-size: 12px; color: var(--muted); background: none; border: none; cursor: pointer; opacity: 0; pointer-events: none; transform: translateX(20px); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); padding: 0; }
        .timeline-item .content:hover .btn-edit { opacity: 1; pointer-events: auto; transform: translateX(0); }
        .timeline-item .content .btn-edit:hover { color: var(--blue-main); }
        .btn-edit i { position: relative; top: 1px; }

        .timesheet-input.is-editing { box-shadow: 0 0 0 3px var(--blue-main-10); border-color: var(--blue-main) !important; }
        .deliv .timesheet-input.is-editing { box-shadow: 0 0 0 3px var(--blue-main-10); border-color: var(--blue-main) !important; }
        .load .timesheet-input.is-editing { box-shadow: 0 0 0 3px var(--orange-main-10); border-color: var(--orange-main) !important; }
        .timesheet-input.is-editing .btn-add-activity { background: var(--blue-main); }
        .deliv .timesheet-input.is-editing .btn-add-activity { background: var(--blue-main); }
        .load .timesheet-input.is-editing .btn-add-activity { background: var(--orange-main); }

        /* Muat Curah Custom */
        .cob-wrapper { display: flex; padding: 8px 12px; align-items: center; gap: 10px; border-radius: 10px; background-color: var(--white); border: 1px solid var(--divider); transition: .2s ease-out; }
        .cob-wrapper:focus-within { border-color: var(--blue-main); box-shadow: 0 0 0 3px var(--blue-main-10); }
        .cob-wrapper input { border: none; width: 65px; font-weight: 500; color: var(--dark-secondary); background: transparent; padding: 0; text-align: center; font-size: 12px; outline: none; }
        .cob-wrapper .cob-unit { font-size: 11px; font-weight: 500; color: var(--dark-main); }
        .cob-wrapper .cob-label { font-size: 12px; font-weight: 600; color: var(--dark-main); }

        /* =========================================
           TABLE COMPONENT
           ========================================= */
        .table-wrapper { width: 100%; overflow-x: auto; border-radius: 10px; border: 1px solid var(--blue-main-40); background-color: var(--white); -webkit-overflow-scrolling: touch; }
        .table-wrapper.material { border: 1px solid var(--blue-main-40); }
        .table-wrapper.container-content { border: 1px solid var(--orange-main-40) !important; }
        .table-wrapper.red { border: 1px solid var(--red-main-40) !important; }

        .table-input { display: flex; flex-direction: column; align-items: flex-start; align-self: stretch; min-width: 820px; }
        .table-input .head { display: flex; padding: 12px 0; align-items: center; align-self: stretch; border-radius: 9px 9px 0 0; }
        .material .table-input .head { background-color: var(--blue-main); }
        .container-content .table-input .head { background-color: var(--orange-main); }
        .table-input .head .table-column span { font-size: 12px; font-weight: 600; color: var(--button-color); }

        .table-input .body { display: flex; padding: 12px 0; align-items: center; align-self: stretch; border-bottom: 1px solid var(--divider); color: var(--dark-main); }

        /* Table Columns Alignments */
        .table-column { display: flex; padding: 0 10px; align-items: center; }
        .table-column.no { width: 50px; text-align: center; justify-content: center; font-weight: 600; font-size: 13px;}
        .table-column.main { flex: 2; min-width: 250px; }
        .table-column.small { flex: 1; min-width: 120px; justify-content: center; }
        .table-column.small input { color: var(--dark-main); }
        .table-column.delete { width: 60px; justify-content: center; text-align: center; }
        /* Kolom No & Hapus tidak boleh menyusut agar baris (mis. nomor 2 digit
           seperti "10") tetap sejajar dengan baris lain saat tabel di-scroll. */
        .table-column.no, .table-column.delete { flex-shrink: 0; }

        .table-column.medium { display: flex; min-width: 150px; padding: 0 10px; align-items: center; gap: 10px; flex: 1 0 0; }
        .table-column.double { display: flex; max-width: 280px; padding: 0 10px; flex-direction: column; justify-content: center; align-items: center; gap: 10px; flex: 1 0 0; }
        .table-column.triple { display: flex; padding: 0 10px; flex-direction: column; justify-content: center; align-items: center; gap: 10px; flex: 1 0 0; }
        .table-column.double .column-detail, .table-column.triple .column-detail { display: flex; align-items: flex-start; gap: 10px; align-self: stretch; }
        .table-column.double .column-detail span, .table-column.triple .column-detail span { flex: 1 0 0; text-align: center; }

        .table-column.input-double { display: flex; max-width: 280px; padding: 0 10px; align-items: flex-start; gap: 10px; flex: 1 0 0; }
        .table-column.input-triple { display: flex; padding: 0 10px; align-items: flex-start; gap: 10px; flex: 1 0 0; }

        /* Specific Columns Formats */
        .table-column.amount, .table-column.absent { display: flex; min-width: 100px; max-width: 180px; padding: 0 10px; align-items: center; gap: 10px; flex: 1 0 0; }
        .table-column.absent, .table-column.ket { justify-content: center; }
        .head .table-column.absent span { text-align: center; }
        .table-column.absent i.blue { color: var(--blue-main); }
        .table-column.absent i.red { color: var(--red-main); }
        .table-column.absent input { text-align: center; }

        /* Mobile: tabel karyawan (Shift, Relief/Lembur, OP.7 & Pengganti, Lain-lain)
           dilebarkan mengikuti kolom agar header sejajar dengan isi dan latar
           header (mis. OP.7 Pengganti yang merah) tidak terpotong saat di-scroll. */
        @media (max-width: 920px) {
            .tab-content-karyawan .table-input { min-width: max-content; }
        }

        /* =========================================
           OP.7 EXCHANGE ARROWS (animated)
           Panah biru turun & merah naik bergerak bergantian untuk
           menggambarkan pertukaran operator -> pengganti.
           ========================================= */
        .exchange { gap: 30px; padding: 4px 0; }

        .exchange-arrow {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            font-size: 22px;
            transition: transform .25s ease;
        }

        .exchange-arrow i { position: relative; display: block; }
        .exchange-arrow.down { color: var(--blue-main); background-color: var(--blue-main-10); box-shadow: 0 6px 16px var(--blue-main-10); }
        .exchange-arrow.up   { color: var(--red-main);  background-color: var(--red-main-10);  box-shadow: 0 6px 16px var(--red-main-10); }

        /* Cincin pulsa yang melebar keluar */
        .exchange-arrow::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid currentColor;
            opacity: 0;
            animation: exchangeRing 2.4s ease-out infinite;
        }
        .exchange-arrow.up::before { animation-delay: 1.2s; }

        /* Ikon memantul ke arah masing-masing (berlawanan fase) */
        .exchange-arrow.down i { animation: exchangeBobDown 1.8s ease-in-out infinite; }
        .exchange-arrow.up i   { animation: exchangeBobUp 1.8s ease-in-out infinite; }

        .exchange-arrow:hover { transform: translateY(-2px) scale(1.06); }

        @keyframes exchangeRing {
            0%   { transform: scale(.85); opacity: .5; }
            70%  { opacity: 0; }
            100% { transform: scale(1.55); opacity: 0; }
        }
        @keyframes exchangeBobDown {
            0%, 100% { transform: translateY(-5px); opacity: .6; }
            50%      { transform: translateY(5px);  opacity: 1; }
        }
        @keyframes exchangeBobUp {
            0%, 100% { transform: translateY(5px);  opacity: .6; }
            50%      { transform: translateY(-5px); opacity: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .exchange-arrow::before,
            .exchange-arrow.down i,
            .exchange-arrow.up i { animation: none !important; }
            .exchange-arrow i { opacity: 1; }
        }

        .table-divide { display: flex; padding: 10px 20px; align-items: center; align-self: stretch; background-color: var(--blue-main-25, #cfe2ff); font-size: 12px; font-weight: 600; }

        /* Inner Inputs within tables */
        .table-input-wrapper {
            display: flex; padding: 8px 15px; align-items: center; gap: 10px; align-self: stretch;
            border-radius: 8px; background-color: var(--white); border: 1px solid var(--divider); width: 100%; box-sizing: border-box;
        }
        .table-input-wrapper .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transform: translateY(2px);
        }
        .table-input-wrapper i { font-size: 12px; color: var(--blue-main); position: relative; top: 0px; line-height: 1; }
        .container-content .table-input-wrapper i { color: var(--orange-main) !important; }
        .table-input-wrapper input { font-weight: 500; font-size: 12px; color: var(--dark-main); border: none; outline: none; width: 100%; background: transparent; }
        .table-input-wrapper:focus-within { outline: 3px solid var(--blue-main-10); box-shadow: 0 0 1px 0 var(--blue-main); background-color: var(--blue-input-focus); }

        .table-column input.form-control-custom {
            display: flex; padding: 8px 15px; align-items: center; align-self: stretch; border-radius: 8px;
            background-color: var(--white); border: 1px solid var(--divider); width: 100%; font-size: 12px; outline: none; box-sizing: border-box;
        }
        .table-column input.form-control-custom:focus { border-color: var(--blue-main); box-shadow: 0 0 0 2px var(--blue-main-10); }
        .material .table-column input.form-control-custom:disabled { background-color: var(--blue-bg); color: var(--blue-main); border-color: var(--divider); }
        .container-content .table-column input.form-control-custom:disabled { background-color: var(--orange-main-10); color: var(--orange-main); border-color: var(--divider); }

        .table-column .custom-input { min-height: auto; padding: 8px 30px 8px 15px; font-size: 12px; border-color: var(--divider); font-weight: 500; }
        .table-column .input-icon { font-size: 14px; right: 12px; color: var(--dark-main); }
        .table-column .custom-option { font-size: 12px; padding: 8px 15px; }

        .btn-trash-row { background: none; border: none; color: var(--red-main); cursor: pointer; font-size: 14px; transition: .2s; padding: 5px; border-radius: 4px; }
        .btn-trash-row:hover { background-color: var(--red-main-10); }

        .btn-tambah-baris {
            display: flex; padding: 12px; justify-content: center; align-items: center; gap: 8px; align-self: stretch;
            border-radius: 8px; background-color: transparent; color: var(--dark-main); font-size: 12px; font-weight: 600;
            cursor: pointer; transition: .2s; margin: 15px; width: calc(100% - 30px); box-sizing: border-box;
        }
        .btn-tambah-baris i { font-size: 14px; position: relative; top: 1px; }
        .material .btn-tambah-baris:hover { background-color: var(--blue-main-5); border-color: var(--blue-hover); color: var(--blue-main); }
        .container-content .btn-tambah-baris:hover { background-color: var(--orange-main-5); border-color: var(--orange-hover); color: var(--orange-main); }
        .btn-tambah-baris.red:hover { background-color: var(--red-main-5); border-color: var(--red-hover); color: var(--red-main); }
        .material .btn-tambah-baris { border: 1.5px dashed var(--blue-main-40); }
        .container-content .btn-tambah-baris { border: 1.5px dashed var(--orange-main-40); }
        .btn-tambah-baris.red {border: 1.5px dashed var(--red-main-40);}

        /* Tab groups inside table containers */
        .tab-group { display: flex; max-width: 620px; padding: 5px; align-items: center; align-content: center; gap: 5px 10px; flex: 1 0 0; flex-wrap: wrap; }
        .tab-sections {
            display: flex; min-width: 100px; padding: 6px 12px; justify-content: center; align-items: center;
            gap: 8px; flex: 1 0 0; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: all 0.2s;
        }
        .tab-sections:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
        .tab-sections.active { color: var(--button-color, #fff); background-color: var(--blue-main, #0d6efd); box-shadow: 0 0 4px 0 var(--blue-main-40); }
        .tab-sections.active:hover { color: var(--button-color, #fff); background-color: var(--blue-main, #0d6efd); }
        .tab-sections i { position: relative; top: 1px; }

        /* Custom Radio Buttons */
        .table-column.radio { display: flex; max-width: 250px; padding: 0 10px; align-items: center; gap: 10px; flex: 1 0 0; }
        .table-column.radio span { text-align: center; flex: 1 0 0; }

        .radio-group-custom { display: flex; gap: 8px; width: 100%; }
        .radio-custom { position: relative; flex: 1; display: flex; }
        .radio-custom input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }

        .radio-custom label {
            display: flex; padding: 11px 15px; justify-content: center; align-items: center; gap: 10px; flex: 1 0 0;
            border: 1px solid var(--divider, #dee2e6); border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 500;
            color: var(--muted, #6c757d); background-color: var(--white, #ffffff); transition: all 0.2s ease-in-out; margin: 0;
        }
        .radio-custom label i { font-size: 12px; display: flex; align-items: center; }

        /* Style saat "Baik" dipilih (Hijau) */
        .radio-custom.baik input[type="radio"]:checked + label { border-color: var(--success, #198754); background-color: var(--success-10, #d1e7dd); color: var(--success, #198754); }
        /* Style saat "Rusak" dipilih (Merah) */
        .radio-custom.rusak input[type="radio"]:checked + label { border-color: var(--red-main, #dc3545); background-color: var(--red-main-10, #f8d7da); color: var(--red-main, #dc3545); }

        /* =========================================
           PETUGAS CARDS
           ========================================= */
        .petugas-card { display: flex; padding: 25px; flex-direction: column; align-items: flex-start; gap: 20px; align-self: stretch; border-radius: 10px; }
        .petugas-card.material { border: 1px solid var(--blue-main-40); background-color: var(--main-bg); }
        .petugas-card.container-content  { border: 1px solid var(--orange-main-40); background-color: var(--orange-main-5); }
        .petugas-card .card-title { font-size: 14px; font-weight: 700; color: var(--dark-main); margin: 0; }

        .rentang-jam-wrapper { display: flex; align-items: center; gap: 15px; width: 100%; }
        .rentang-jam-wrapper .input-wrapper { flex: 1; border: 1px solid var(--divider); border-radius: 10px; overflow: hidden; background: var(--white); }
        .rentang-jam-wrapper .input-wrapper:focus-within { border-color: var(--blue-main); box-shadow: 0 0 0 2px var(--blue-main-10); }
        .rentang-jam-wrapper .input-icon {
            top: 50% !important;
            left: 15px !important;
            right: auto !important;
            transform: translateY(-50%) !important;
        }
        .rentang-jam-wrapper .input-icon i {
            position: static !important;
            line-height: 1;
        }

        button.set-all-good { display: flex; padding: 6px 12px; align-items: center; gap: 10px; border-radius: 8px; background-color: var(--success); border: none; font-size: 12px; font-weight: 600; color: var(--button-color); }
        button.set-all-good:hover { background-color: var(--success-hover); box-shadow: 0 0 4px 0 var(--success-40); }
        button.set-all-good:active { background-color: var(--success-active); }
        button.set-all-good i { position: relative; top: 2px; }

        /* Modal / Popup Styles (Berdasarkan Dashboard) */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px);
            display: flex; justify-content: center; align-items: center;
            z-index: 10000; opacity: 0; visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .pop-up.signed {
            width: 380px; padding: 25px; border-radius: 20px;
            background-color: var(--white); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .modal-overlay.show .pop-up.signed { transform: scale(1); }
        .pop-up.detail {
            padding: 15px; gap: 12px; border-radius: 12px;
            background-color: var(--blue-main-5); border: 1px solid var(--blue-main-10);
        }
        .icon-document {
            display: flex; width: 40px; height: 40px; align-items: center; justify-content: center;
            border-radius: 8px; background-color: var(--white); font-size: 20px; color: var(--blue-main);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .pop-up.footer .btn {
            padding: 10px 20px; font-size: 13px; font-weight: 600; border-radius: 10px; border: none; transition: 0.2s; cursor: pointer;
        }
        .pop-up.footer .btn.cancel { background-color: var(--dark-secondary-10); color: var(--dark-main); }
        .pop-up.footer .btn.confirm { background-color: var(--success); color: #fff; }

        /* =========================================
           RESPONSIVE DESIGN (BREAKPOINTS)
           ========================================= */

        /* TABLET (≤ 1024px) */
        @media (max-width: 1024px) {
            .p-content { padding: 0 40px !important; }
        }

        /* MOBILE (≤ 768px) */
        @media (max-width: 768px) {
            body { gap: 16px; }
            .p-content { padding: 0 16px !important; }
            .p-navbar { padding: 12px 16px !important; }
            .size-logo { width: 82px !important; }
            .header-right { gap: 10px !important; }

            /* Content header: allow btn-new to stretch full width when wrapping */
            .content-header .btn-new,
            a.btn-new { width: 100% !important; justify-content: center !important; }
            .title-header { min-width: unset !important; }

            /* Reminder box: tombol Lanjutkan Draft melebar, X tetap di ujung kanan */
            .reminder { min-width: unset !important; }
            .reminder-button { width: 100% !important; justify-content: space-between !important; }
            .reminder-button .btn.draft-edit { flex: 1 1 auto !important; }

            /* Report items: tombol aksi turun ke baris sendiri (full width) */
            .report-detail { min-width: unset !important; flex: 1 0 100% !important; }
            .report-button { min-width: unset !important; flex: 1 0 100% !important; justify-content: flex-start !important; }
            .report-button .btn { max-width: unset !important; }
            .report-group { flex-wrap: wrap; }

            /* Draft items: tombol aksi turun ke baris sendiri */
            .draft-detail { min-width: unset !important; }
            .draft-button { min-width: unset !important; flex: 1 0 100% !important; justify-content: flex-start !important; }
            .btn-draft-edit { max-width: unset !important; }

            /* Modal */
            .pop-up.signed { width: calc(100vw - 40px) !important; max-width: 420px; }
        }

        /* SMALL MOBILE (≤ 480px) */
        @media (max-width: 480px) {
            .p-content { padding: 0 12px !important; }
            .p-navbar { padding: 10px 12px !important; }
            .size-logo { width: 68px !important; }

            /* Hide greeting on tiny screens for a clean header */
            .info-officer { display: none !important; }
            .header-left .divider-vertical { display: none !important; }

            /* Shrink page title */
            .fsize-20 { font-size: 16px !important; }

            /* Report action buttons: stack vertically */
            .report-button { flex-direction: column !important; }
            .report-button .btn { width: 100% !important; max-width: 100% !important; }

            /* Draft action buttons: stack vertically */
            .draft-button { flex-direction: column !important; align-items: stretch !important; }
            .btn-draft-edit { width: 100% !important; flex: unset !important; }
            .btn-delete { width: 100% !important; height: 32px !important; }

            /* Modal tighter on small screens */
            .pop-up.signed { width: calc(100vw - 24px) !important; padding: 16px !important; border-radius: 16px !important; }
        }

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
            color: var(--dark-main);
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
                rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.34);
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
            color: var(--dark-main);
        }

        .toast-text {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            font-weight: 400;
            line-height: 1.35;
            color: var(--dark-secondary);
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
            color: var(--dark-main);
            background-color: var(--black-5);
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
     </style>

    @include('components.kss-datetime-picker')

</head>
<body>
    <!-- Dark mode init lebih awal agar overlay langsung pakai warna yang benar -->
    <script>if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark-mode');</script>

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

    @include('report-ops.layouts.header')

    @yield('content')

    @include('report-ops.layouts.footer')

    @stack('modals')

    <!-- LINK BOOTSTRAP 5 JS BUNDLE -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    {{-- Javascript Interaktif --}}
    <script>
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

                // ==========================================
                // 2B. TOAST MESSAGE
                // ==========================================
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

                window.showReportToast = function(type, title, message, duration = 4200) {
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

                const toastMessages = document.querySelectorAll('.toast-message');
                toastMessages.forEach((toast, index) => bindToastMessage(toast, index));

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
                const timeInputs = document.querySelectorAll('.time-picker-input');
                timeInputs.forEach(input => {
                    input.addEventListener('input', function(e) {
                        if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward') return;
                        let val = this.value.replace(/\D/g, '');
                        if (val.length > 4) val = val.substring(0, 4);
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
    </script>

    @stack('scripts')

    {{-- Loading Spinner js --}}
    <script>
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
