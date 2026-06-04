<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan KSS - Safety / K3</title>

    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>

    <style>
        /* ===== DESIGN TOKENS (selaras modul Operasional) ===== */
        :root {
            --blue-main: #2563EB; --blue-hover: #1D4ED8; --blue-active: #1E40AF;
            --blue-main-25: #2563eb40; --blue-main-10: #2563eb1a; --blue-main-5: #2563eb0d;
            --blue-main-2: rgba(37, 99, 235, 0.02); --blue-main-40: #2563eb66;
            --blue-bg: #E5F1FF; --blue-input-focus: #fafcff; --blue-bg-25: #E5F1FF40;
            --cyan-main: #0EA5E9; --cyan-main-10: #0ea5e91a; --cyan-main-25: #0ea5e940;
            --red-main: #D20000; --red-hover: #B80000; --red-active: #9F0000;
            --red-main-25: #d2000040; --red-main-10: #d200001a; --red-main-5: #d200000d; --red-main-40: #d2000066; --red-input-focus: #fff5f5;
            --success: #10B981; --success-hover: #0F9A6B; --success-active: #0E7A55;
            --success-25: #10b77f40; --success-10: #10b77f1a; --success-5: #10b77f0d; --success-40: #10b77f66;
            --orange-main: #F7931E; --orange-hover: #E67E00; --orange-active: #CC6F00;
            --orange-main-25: #f9731640; --orange-main-10: #f973161a; --orange-main-5: #f973160d; --orange-main-40: #f9731666;
            --orange-input-focus: #fef4e8; --orange-bg: #FEF4E8;
            --black: #000000; --black-25: #00000040; --black-10: #0000001a; --black-5: #0000000d; --black-40: #00000066;
            --dark-main: #0F172A; --dark-secondary: #334155; --dark-secondary-10: #3341551A;
            --muted: #94A3B8; --smooth-border:#E2E8F0; --main-bg:#F8FAFC; --white: #FFFFFF;
            --divider: #CBD5E1; --button-color: #FFFFFF;
            --btn-theme-bg: var(--white); --btn-theme-border: rgba(0,0,0,0.1); --btn-theme-icon: var(--dark-main);
        }
        body.dark-mode {
            --main-bg: #0F172A; --white: #1E293B;
            --dark-main: #F8FAFC; --dark-secondary: #CBD5E1; --dark-secondary-10: rgba(203,213,225,0.1);
            --muted: #94A3B8; --smooth-border: #334155; --divider: #334155;
            --black: #FFFFFF; --black-25: #ffffff40; --black-10: #ffffff1a; --black-5: #ffffff0d; --black-40: #ffffff66;
            --button-color: #FFFFFF; --btn-theme-bg: #334155; --btn-theme-border: rgba(255,255,255,0.1); --btn-theme-icon: #F1F5F9;
            --blue-main: #3B82F6; --blue-hover: #60A5FA; --blue-active: #93C5FD;
            --blue-main-25: #3b82f640; --blue-main-10: #3b82f61a; --blue-main-5: #3b82f60d; --blue-main-2: rgba(59,130,246,0.02); --blue-main-40: #3b82f666;
            --blue-bg: #243447; --blue-input-focus: #334155; --blue-bg-25: #24344740;
            --red-main: #EF4444; --red-hover: #F87171; --red-active: #FCA5A5;
            --red-main-25: #ef444440; --red-main-10: #ef44441a; --red-main-5: #ef44440d; --red-main-40: #ef444466; --red-input-focus: #2a1a1a;
            --success: #10B981; --success-hover: #34D399; --success-10: #10b9811a; --success-25: #10b98140; --success-40: #10b98166;
            --orange-main: #F97316; --orange-hover: #FB923C; --orange-main-10: #f973161a; --orange-main-25: #f9731640; --orange-main-40: #f9731666;
            --orange-bg: #431407; --orange-input-focus: #2f2111;
        }

        /* ===== UTILITIES ===== */
        .bg-main { background-color: var(--main-bg) !important; }
        .text-main { color: var(--dark-main) !important; }
        .text-secondary { color: var(--dark-secondary) !important; }
        .bg-blue { background-color: var(--blue-bg) !important; }
        .text-muted { color: var(--muted) !important; }
        .text-cyan { color: var(--cyan-main) !important; }
        .text-red { color: var(--red-main) !important; }
        .white-pure { color: var(--button-color) !important; }
        .white-bg { background-color: var(--white) !important; }
        .blue-bg { background-color: var(--blue-main) !important; }
        .success-bg { background-color: var(--success) !important; }
        .fsize-9{font-size:9px!important}.fsize-10{font-size:10px!important}.fsize-11{font-size:11px!important}.fsize-12{font-size:12px!important}
        .fsize-13{font-size:13px!important}.fsize-14{font-size:14px!important}.fsize-16{font-size:16px!important}.fsize-18{font-size:18px!important}.fsize-20{font-size:20px!important}.fsize-24{font-size:24px!important}
        .fw-300{font-weight:300!important}.fw-400{font-weight:400!important}.fw-500{font-weight:500!important}.fw-600{font-weight:600!important}.fw-700{font-weight:700!important}
        .gap-2{gap:2px!important}.gap-4{gap:4px!important}.gap-6{gap:6px!important}.gap-8{gap:8px!important}.gap-10{gap:10px!important}.gap-15{gap:15px!important}.gap-20{gap:20px!important}.gap-30{gap:30px!important}
        .br-4{border-radius:4px!important}.br-6{border-radius:6px!important}.br-8{border-radius:8px!important}.br-10{border-radius:10px!important}.br-12{border-radius:12px!important}.br-20{border-radius:20px!important}.br-100{border-radius:100px!important}
        .p-navbar{padding:15px 22px!important}.p-content{padding:0 100px!important;transition:padding .3s}.p-main{padding:10px 20px!important}.p-20{padding:20px!important}.p-empty{padding:60px 0!important}
        .size-logo{width:108px!important;height:auto!important}.divider-vertical{border-left:1px solid var(--divider)!important;height:30px!important}.flexible{flex:1 0 0!important}
        .d-none{display:none!important}

        /* Scrollbar vertikal selalu tampil agar lebar konten tidak berubah antar-tab. */
        html { overflow-y: scroll; }
        body {
            font-family:'Poppins',sans-serif; display:flex; flex-direction:column; align-items:center; gap:30px;
            background-color:var(--main-bg); color:var(--dark-main);
            transition:background-color .3s ease-out,color .3s ease-out; margin-bottom:40px;
        }
        .content { max-width:1440px; margin:0 auto; width:100%; }
        .content-header,.main-content { background-color:var(--white); box-shadow:0 2px 4px 0 var(--blue-main-10); }
        .main-content { padding-bottom:20px!important; }
        .title-header { min-width:250px; }

        /* ===== THEME & LOGOUT BUTTONS ===== */
        .btn-theme { width:30px; height:30px; background-color:var(--btn-theme-bg); border:1px solid var(--btn-theme-border); cursor:pointer; overflow:hidden; position:relative; transition:all .3s ease; padding:0; border-radius:10px; display:flex; align-items:center; justify-content:center; }
        .btn-theme:hover { border-color:var(--blue-main); box-shadow:0 4px 12px rgba(0,0,0,.05); }
        .icon-container { display:flex; align-items:center; justify-content:center; width:100%; height:100%; }
        .icon-container i { color:var(--btn-theme-icon); font-size:16px; position:relative; top:3px; transition:transform .4s cubic-bezier(0.34,1.56,0.64,1),opacity .3s ease; }
        .prepare-from-top{transform:translateY(-150%);opacity:0}.prepare-from-bottom{transform:translateY(150%);opacity:0}
        .animate-out-up{transform:translateY(-150%)!important;opacity:0!important}.animate-out-down{transform:translateY(150%)!important;opacity:0!important}
        .btn-logout { border:none; background-color:var(--red-main-10); color:var(--red-main); border-radius:8px; display:flex; align-items:center; justify-content:center; height:30px; min-width:30px; padding:0 8px; transition:all .5s ease-out; overflow:hidden; }
        .btn-logout .icon-logout{display:flex;align-items:center;justify-content:center}.btn-logout .icon-logout i{position:relative;top:2px;flex-shrink:0;color:var(--red-main)}
        .btn-logout .text{max-width:0;opacity:0;margin-left:0;white-space:nowrap;overflow:hidden;transition:all .5s ease-out}
        .btn-logout:hover{background-color:var(--red-main-10);color:var(--red-hover);outline:1px solid var(--red-hover)}
        .btn-logout:hover .text{max-width:60px;opacity:1;margin-left:8px}
        .btn-new { color:var(--button-color); padding:10px 15px; background-color:var(--blue-main); border:none; transition:.2s ease-out; }
        .icon-new{position:relative;top:3px}
        .btn-new:hover{background-color:var(--blue-hover);transform:translateY(-3px)}

        /* ===== STICKY HEADER (liquid glass island) ===== */
        .content-header{position:relative;z-index:10}
        .content-header.is-sticky{position:fixed;top:20px;left:50%;max-width:max-content!important;width:auto!important;padding:6px 8px!important;background-color:rgba(255,255,255,.65)!important;backdrop-filter:blur(20px) saturate(180%);-webkit-backdrop-filter:blur(20px) saturate(180%);border:1px solid rgba(255,255,255,.5);border-radius:100px!important;box-shadow:0 10px 30px rgba(0,0,0,.1),inset 0 1px 0 rgba(255,255,255,.8)!important;justify-content:center!important;z-index:9999;transform:translate(-50%,-150%) scale(.9);opacity:0;pointer-events:none;transition:transform .6s cubic-bezier(0.34,1.56,0.64,1),opacity .4s ease-out}
        body.dark-mode .content-header.is-sticky{background-color:rgba(15,23,42,.65)!important;border-color:rgba(255,255,255,.15);box-shadow:0 10px 30px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.1)!important}
        .content-header.is-sticky.show-sticky{transform:translate(-50%,0) scale(1);opacity:1;pointer-events:auto}
        .content-header.is-sticky .title-header{display:none!important}
        .content-header.is-sticky .btn-new,.content-header.is-sticky .btn-draft-save{border-radius:100px!important;padding:8px 20px!important;box-shadow:0 4px 15px rgba(37,99,235,.3);width:auto;justify-content:center;gap:8px;margin:0;white-space:nowrap}
        .content-header.is-sticky .btn-new span,.content-header.is-sticky .btn-draft-save .btn-text{white-space:nowrap;font-size:13px!important}

        /* ===== DASHBOARD TABS ===== */
        .tab-content{position:relative;border-bottom:1px solid var(--divider);overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;scrollbar-color:var(--blue-main-25) transparent;flex-wrap:nowrap}
        .tab-content::-webkit-scrollbar{height:4px}.tab-content::-webkit-scrollbar-thumb{background-color:var(--blue-main-25);border-radius:10px}
        .tab-slide-indicator{position:absolute;left:0;bottom:0;width:0;height:2px;border-radius:999px;background:var(--blue-active);transform:translateX(0);transition:transform .34s cubic-bezier(.22,1,.36,1),width .34s cubic-bezier(.22,1,.36,1);pointer-events:none;z-index:0}
        .list-tab{position:relative;z-index:1;display:flex;padding:10px 0;flex-direction:column;align-items:flex-start;gap:10px;text-decoration:none;font-size:14px;color:var(--dark-secondary);border:1px solid transparent;cursor:pointer;flex-shrink:0;white-space:nowrap}
        .list-tab .list-item:hover{background-color:var(--dark-secondary-10)}
        .list-tab.active{border-bottom-color:transparent;color:var(--blue-active)}
        .list-tab.active .list-item:hover{background-color:var(--blue-main-10)}
        .list-item{display:flex;padding:2px 10px;justify-content:center;align-items:center;gap:10px;border-radius:6px}
        .icon-tab i{position:relative;top:1px;font-size:12px}
        .tab-amount{display:flex;padding:0 5px;flex-direction:column;justify-content:center;align-items:center;gap:10px;background-color:var(--orange-main);border-radius:10px;color:var(--button-color)}

        /* ===== DRAFT CARDS ===== */
        .draft-item{background-color:var(--white);box-shadow:0 0 1px 0 var(--muted);padding:20px;transition:.2s ease-out;width:100%}
        .draft-item:hover{box-shadow:0 2px 4px 0 var(--blue-main-40);transform:translateY(-2px);background-color:var(--blue-main-2)}
        .badge-draft{padding:3px 6px;gap:6px;background-color:var(--dark-secondary-10);border-radius:4px;font-size:10px}
        .badge-draft .icon-draft i{position:relative;top:2px}
        .draft-report{display:flex;justify-content:space-between;align-items:flex-end;row-gap:12px;align-self:stretch;flex-wrap:wrap}
        .draft-detail{display:flex;min-width:250px;flex-direction:column;align-items:flex-start;gap:8px;flex:1 0 0}
        .last-edit{font-style:italic}.last-edit .icon-edit i{position:relative;top:2px}
        .btn-draft-edit{display:flex;max-width:435px;padding:6px 10px;justify-content:center;align-items:center;gap:10px;flex:1 0 0;border:none;border-radius:6px;background-color:var(--blue-main);font-size:10px;color:var(--button-color);transition:.2s ease-out;text-decoration:none}
        .btn-draft-edit:hover{background-color:var(--blue-hover);transform:translateY(-2px)}
        .btn-draft-edit .icon-edit i{position:relative;top:2px}
        .btn-delete{display:flex;width:27px;height:27px;padding:6px;justify-content:center;align-items:center;border-radius:4px;background:var(--white);box-shadow:0 0 1px 0 var(--red-main);font-size:12px;border:none;color:var(--red-main);transition:.2s ease-out}
        .btn-delete:hover{background-color:var(--red-main-10);transform:translateY(-2px)}
        .btn-delete .icon-delete i{position:relative;top:2px}

        /* ===== HISTORY TABLE ===== */
        .table-responsive-wrapper{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;scrollbar-color:var(--blue-main-25) transparent}
        .table-responsive-wrapper::-webkit-scrollbar{height:6px}.table-responsive-wrapper::-webkit-scrollbar-thumb{background-color:var(--blue-main);border-radius:10px}
        .table-responsive-wrapper table{min-width:900px}
        .thead th{display:flex;padding:8px 10px;align-items:center;flex:1 0 0;font-size:12px;font-weight:400}
        th.nomor,td.nomor{display:flex;width:50px;flex:none;padding:8px 0;justify-content:center;align-items:center;text-align:center}
        th.column-1{min-width:160px}
        .tbody td{font-size:12px;font-weight:500}
        .tbody{border-radius:4px;transition:.1s ease-in-out;padding:12px 0!important}
        .tbody:hover{background-color:var(--blue-bg-25)}
        .tbody td.column-2{display:flex;min-width:160px;padding:0 10px;flex-direction:column;justify-content:center;align-items:flex-start;flex:1 0 0}
        .tbody td.column-3{display:flex;padding:0 10px;flex-direction:column;align-items:flex-start;gap:10px;flex:1 0 0}
        .status{display:inline-flex;min-height:20px;padding:2px 8px;align-items:center;gap:4px;border-radius:10px;font-size:10px;font-weight:400;line-height:1}
        .status .text{display:inline-flex;align-items:center;line-height:1}
        .icon-status{display:inline-flex;align-items:center;line-height:1}
        .icon-status i{display:block;font-size:10px;line-height:1;position:static}
        .status.approve{border:1px solid var(--success);color:var(--success);background-color:var(--success-10)}
        .status.submit{border:1px solid var(--orange-main);color:var(--orange-main);background-color:var(--orange-main-10)}
        .status.archive{border:1px solid var(--blue-main);color:var(--blue-main);background-color:var(--blue-main-10)}
        .status.draft{border:1px solid var(--dark-secondary);color:var(--dark-secondary);background-color:var(--dark-secondary-10)}
        td.aksi{display:flex;padding:0 10px;align-items:center;gap:10px;flex:1 0 0;flex-wrap:wrap}
        td.aksi .btn{display:flex;padding:6px 10px;align-items:center;gap:6px;border-radius:4px;color:var(--button-color);font-size:10px;font-weight:500;transition:.2s ease-out;text-decoration:none;white-space:nowrap}
        td.aksi .btn.see{background-color:var(--orange-main)}td.aksi .btn.see:hover{background-color:var(--orange-hover);transform:translateY(-1px)}
        td.aksi .btn.edit{background-color:var(--blue-main)}td.aksi .btn.edit:hover{background-color:var(--blue-hover);transform:translateY(-1px)}
        td.aksi .btn.export-pdf{background-color:#ef4444}td.aksi .btn.export-pdf:hover{background-color:#dc2626;transform:translateY(-1px)}
        td.aksi .btn.delete-icon{background-color:var(--red-main)}td.aksi .btn.delete-icon:hover{background-color:var(--red-hover);transform:translateY(-1px)}
        .btn i{position:relative;top:1px}
        .day-badge{display:inline-flex;min-height:20px;padding:2px 8px;align-items:center;gap:4px;border-radius:10px;font-size:10px;font-weight:500;line-height:1;background-color:var(--blue-main-10);color:var(--blue-main)}
        .day-badge i{display:block;font-size:10px;line-height:1}
        .day-badge span{display:inline-flex;align-items:center;line-height:1}

        @keyframes slideInRight{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:translateX(0)}}
        @keyframes slideInLeft{from{opacity:0;transform:translateX(-30px)}to{opacity:1;transform:translateX(0)}}
        .animate-slide-right{animation:slideInRight .3s ease-out forwards}
        .animate-slide-left{animation:slideInLeft .3s ease-out forwards}

        /* ===== REMINDER DRAFT ===== */
        @keyframes draftReminderPulse {
            0%, 100% { transform: translateY(0); box-shadow: 0 0 0 rgba(255,139,22,0); border-color: rgba(255,139,22,0.12); }
            45% { transform: translateY(-1px); box-shadow: 0 12px 28px rgba(255,139,22,0.16); border-color: rgba(255,139,22,0.28); }
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
        .reminder-draft{position:relative;display:flex;padding:10px;justify-content:space-between;align-items:center;row-gap:10px;align-self:stretch;flex-wrap:wrap;border-radius:16px;border:1px solid rgba(255,139,22,.12);background:var(--orange-main-10);overflow:hidden;animation:draftReminderPulse 2.8s ease-in-out infinite}
        .icon-reminder{display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:10px;background-color:var(--orange-main-10);color:var(--orange-main);animation:draftReminderIconWiggle 2.4s ease-in-out infinite;transform-origin:center}
        .icon-reminder i{position:relative;top:2px}
        .btn.draft-edit{position:relative;overflow:hidden;display:flex;padding:6px 12px;justify-content:center;align-items:center;gap:0;border:none;border-radius:4px;background-color:var(--orange-main);font-size:10px;font-weight:600;color:var(--button-color);transition:.3s ease-out;text-decoration:none;animation:draftReminderButtonNudge 2.4s ease-in-out infinite;cursor:pointer}
        .btn.draft-edit::after{content:"";position:absolute;inset:-40% -25%;background:linear-gradient(100deg,transparent 35%,rgba(255,255,255,.34) 50%,transparent 65%);transform:translateX(-140%) skewX(-18deg);animation:draftReminderSheen 3s ease-in-out infinite;pointer-events:none}
        .btn.draft-edit .text,.btn.draft-edit .icon-edit{position:relative;z-index:1}
        .btn.draft-edit:hover{background-color:var(--orange-hover);gap:10px;animation-play-state:paused}
        .btn.draft-edit .icon-edit{max-width:0;opacity:0;transform:translateX(-10px);transition:.3s ease-out;overflow:hidden;display:flex;align-items:center;justify-content:center}
        .btn.draft-edit:hover .icon-edit{max-width:20px;opacity:1;transform:translateX(0)}
        .btn.draft-edit .icon-edit i{position:relative;top:1px}
        .btn.close i{font-size:12px;color:var(--muted);transition:.2s ease-out}.btn.close:hover i{color:var(--red-main)}
        @media (prefers-reduced-motion: reduce){.reminder-draft,.icon-reminder,.btn.draft-edit,.btn.draft-edit::after{animation:none !important}}

        /* ===== EMPTY STATE ===== */
        .empty-state{width:100%;padding:60px 20px;color:var(--muted)}
        .icon-empty{font-size:48px;color:var(--cyan-main);line-height:1}.icon-empty i{position:relative;top:3px}
        .btn.new-report{display:flex;align-items:center;background:none;border:none;font-size:12px;color:var(--blue-main);gap:10px;transition:.2s ease-out;text-decoration:none}
        .btn.new-report:hover{color:var(--blue-hover);transform:translateY(-2px)}

        /* ===== FORM: TABS ===== */
        .tab-form{position:relative;display:flex;padding:5px;align-items:center;gap:5px 10px;align-self:stretch;flex-wrap:nowrap;border-radius:10px;background-color:var(--white);box-shadow:0 2px 4px 0 var(--blue-main-10);width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;scrollbar-width:thin;scrollbar-color:var(--blue-main-25) transparent}
        .tab-form::-webkit-scrollbar{height:6px}.tab-form::-webkit-scrollbar-thumb{background:var(--blue-main);border-radius:10px}
        .tab-form-indicator{position:absolute;left:0;top:5px;bottom:5px;width:0;border-radius:8px;background:var(--blue-main);box-shadow:0 0 4px 0 var(--blue-main-40);transform:translateX(0);transition:transform .34s cubic-bezier(.22,1,.36,1),width .34s cubic-bezier(.22,1,.36,1);pointer-events:none;z-index:0}
        .list-form-tab{position:relative;z-index:1;display:flex;min-width:130px;padding:6px 12px;justify-content:center;align-items:center;gap:8px;flex:1 0 auto;flex-shrink:0;white-space:nowrap;font-size:12px;font-weight:500;color:var(--dark-secondary);cursor:pointer;border-radius:8px;transition:.2s ease-out}
        .list-form-tab:hover{background-color:var(--blue-main-10);color:var(--blue-main)}
        .list-form-tab.active{color:var(--button-color);background:transparent;box-shadow:none}
        .list-form-tab.active:hover{background:transparent;color:var(--button-color)}
        .list-form-tab .icon-tab{position:relative;top:1px}

        /* ===== FORM: CARD CONTAINERS ===== */
        .box-form{gap:10px;border-radius:10px;box-shadow:0 2px 4px 0 var(--blue-main-10);background-color:var(--white)}
        .header-form{padding:20px 25px;border-top:4px solid var(--blue-main);border-radius:10px 10px 0 0;background-color:var(--blue-main-2)}
        .icon-title-form{font-size:14px;color:var(--blue-main);display:flex;width:30px;height:30px;padding:10px;justify-content:center;align-items:center;border-radius:6px;background-color:var(--blue-main-10);font-weight:600}
        .icon-title-form i{position:relative;top:2.5px}
        .counter-form{display:flex;min-width:90px;padding:4px 10px;justify-content:center;align-items:center;border-radius:20px;background-color:var(--white);border:1px solid var(--blue-main-10);font-size:10px;color:var(--muted)}
        .content-form{padding:20px 25px;gap:25px}

        /* ===== FORM: INPUTS ===== */
        .box-input-1{display:flex;min-width:160px;flex-direction:column;align-items:flex-start;gap:8px;flex:1 0 0}
        .box-label-1{display:flex;align-items:center;gap:5px;font-size:13px}
        input[type="date"]::-webkit-calendar-picker-indicator{opacity:0;position:absolute;right:12px;width:24px;height:24px;cursor:pointer}
        input[type="number"]::-webkit-outer-spin-button,input[type="number"]::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
        .input-wrapper{position:relative;display:flex;align-items:center;width:100%;align-self:stretch;height:100%}
        .custom-input{width:100%;padding:10px 35px 10px 15px;border:1px solid var(--black-25);border-radius:8px;font-size:13px;font-family:'Poppins',sans-serif;color:var(--dark-main);background-color:var(--white);outline:none;transition:.2s ease-out;cursor:pointer;min-height:42px}
        .custom-input:focus,.custom-input.focus-active{border-color:var(--blue-main);box-shadow:0 0 0 3px var(--blue-main-10)}
        .custom-input::placeholder{color:var(--muted)}
        select.custom-input{-webkit-appearance:none;-moz-appearance:none;appearance:none}
        select.custom-input option{background-color:var(--white);color:var(--dark-main)}
        .input-icon,.tbl-icon-dropdown{position:absolute;right:15px;top:50%;transform:translateY(-50%);color:var(--blue-main);pointer-events:none;min-height:18px;line-height:1;font-size:14px;display:flex;align-items:center;justify-content:center;z-index:5}
        .text-placeholder{color:var(--muted)!important}
        .custom-options-container{position:absolute;top:calc(100% + 5px);left:0;right:0;background:var(--white);border:1px solid var(--black-25);border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,.08);z-index:999;display:none;max-height:220px;overflow-y:auto;padding:8px 0}
        .custom-options-container.open{display:block}
        .custom-option{padding:10px 15px;font-size:13px;color:var(--dark-secondary);cursor:pointer;transition:.2s ease;font-weight:400}
        .custom-option:hover{background-color:var(--blue-main-10);color:var(--blue-main)}
        .custom-option.selected{background-color:var(--blue-main-2);color:var(--blue-main);border-left:3px solid var(--blue-main);font-weight:500}

        /* ===== TABLE CUSTOM SELECT (dropdown ala operasional) ===== */
        .tbl-select-wrapper{position:relative;display:flex;align-items:center;width:100%;align-self:stretch}
        .tbl-custom-select-trigger{width:100%;padding:8px 30px 8px 12px;border:1px solid var(--divider);border-radius:8px;font-size:12px;font-family:'Poppins',sans-serif;color:var(--dark-main);background-color:var(--white);outline:none;transition:.2s ease-out;cursor:pointer;min-height:38px;box-sizing:border-box;display:flex;align-items:center}
        .tbl-custom-select-trigger:focus,.tbl-custom-select-trigger.focus-active{border-color:var(--blue-main);box-shadow:0 0 0 3px var(--blue-main-10)}
        .tbl-custom-select-trigger.text-placeholder{color:var(--muted)}
        .tbl-select-wrapper .sel-caret{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--blue-main);pointer-events:none;font-size:11px;display:flex;align-items:center}
        .tbl-custom-options{position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--white);border:1px solid var(--black-25);border-radius:8px;z-index:100;display:none;box-shadow:0 6px 18px var(--black-10);max-height:260px;overflow-y:auto;overflow-x:hidden;box-sizing:border-box}
        .tbl-custom-options.open{display:block;animation:fadeIn .15s ease-out}
        .tbl-custom-option{padding:9px 14px;font-size:12px;color:var(--dark-main);background-color:var(--white);cursor:pointer;transition:background-color .2s,color .2s;position:relative}
        .tbl-custom-option:hover{background-color:var(--blue-main-10);color:var(--blue-main)}
        .tbl-custom-option.selected{color:var(--blue-main);background-color:var(--blue-main-2)}
        .tbl-custom-option.selected::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background-color:var(--blue-main)}
        .tbl-search{position:sticky;top:0;background:var(--white);padding:8px;border-bottom:1px solid var(--divider);z-index:1}
        .tbl-search input{width:100%;padding:7px 10px;border:1px solid var(--divider);border-radius:6px;font-size:12px;font-family:'Poppins',sans-serif;color:var(--dark-main);background:var(--white);outline:none}
        .tbl-search input:focus{border-color:var(--blue-main);box-shadow:0 0 0 2px var(--blue-main-10)}

        /* ===== FORM: TABLE COMPONENT ===== */
        .table-wrapper{width:100%;overflow-x:auto;border-radius:10px;border:1px solid var(--blue-main-40);background-color:var(--white);-webkit-overflow-scrolling:touch}
        .table-wrapper.heavy{border:1px solid var(--orange-main-40)}
        .table-input{display:flex;flex-direction:column;align-items:flex-start;align-self:stretch;min-width:880px}
        .table-input .head{display:flex;padding:12px 0;align-items:center;align-self:stretch;border-radius:9px 9px 0 0;background-color:var(--blue-main)}
        .heavy .table-input .head{background-color:var(--orange-main)}
        .table-input .head .table-column span{font-size:12px;font-weight:600;color:var(--button-color)}
        .table-input .body{display:flex;padding:12px 0;align-items:center;align-self:stretch;border-bottom:1px solid var(--divider);color:var(--dark-main)}
        .table-column{display:flex;padding:0 10px;align-items:center;gap:10px}
        .table-column.no{width:50px;text-align:center;justify-content:center;font-weight:600;font-size:13px;flex-shrink:0}
        .table-column.group{width:80px;justify-content:center;flex-shrink:0}
        .table-column.main{flex:2;min-width:200px}
        .table-column.unit{flex:1.3;min-width:170px}
        .table-column.medium{min-width:150px;flex:1 0 0}
        .table-column.absent{min-width:100px;max-width:170px;flex:1 0 0;justify-content:center}
        .table-column.radio-col{min-width:170px;max-width:230px;flex:1 0 0}
        .table-column.delete{width:60px;justify-content:center;text-align:center;flex-shrink:0}
        .table-input-wrapper{display:flex;padding:8px 12px;align-items:center;gap:8px;align-self:stretch;border-radius:8px;background-color:var(--white);border:1px solid var(--divider);width:100%;box-sizing:border-box}
        .table-input-wrapper i{font-size:12px;color:var(--blue-main);position:relative;top:0}
        .heavy .table-input-wrapper i{color:var(--orange-main)}
        .table-input-wrapper input,.table-input-wrapper textarea,.table-input-wrapper select{font-weight:500;font-size:12px;color:var(--dark-main);border:none;outline:none;width:100%;background:transparent;resize:none;font-family:'Poppins',sans-serif}
        .table-input-wrapper:focus-within{outline:3px solid var(--blue-main-10);box-shadow:0 0 1px 0 var(--blue-main);background-color:var(--blue-input-focus)}
        .table-input-wrapper select{cursor:pointer}
        .table-input-wrapper input::placeholder,.table-input-wrapper textarea::placeholder{color:var(--muted)}
        .btn-trash-row{background:none;border:none;color:var(--red-main);cursor:pointer;font-size:14px;transition:.2s;padding:5px;border-radius:4px}
        .btn-trash-row:hover{background-color:var(--red-main-10)}
        .btn-tambah-baris{display:flex;padding:12px;justify-content:center;align-items:center;gap:8px;align-self:stretch;border-radius:8px;background-color:transparent;color:var(--dark-main);font-size:12px;font-weight:600;cursor:pointer;transition:.2s;margin:15px;width:calc(100% - 30px);box-sizing:border-box;border:1.5px dashed var(--blue-main-40)}
        .btn-tambah-baris:hover{background-color:var(--blue-main-5);color:var(--blue-main)}
        .btn-tambah-baris i{font-size:14px;position:relative;top:1px}

        /* ===== FORM: RADIO TOGGLES ===== */
        .radio-group-custom{display:flex;gap:8px;width:100%}
        .radio-custom{position:relative;flex:1;display:flex}
        .radio-custom input[type="radio"]{position:absolute;opacity:0;width:0;height:0}
        .radio-custom label{display:flex;padding:9px 12px;justify-content:center;align-items:center;gap:6px;flex:1 0 0;border:1px solid var(--divider);border-radius:8px;cursor:pointer;font-size:11px;font-weight:500;color:var(--muted);background-color:var(--white);transition:all .2s ease;margin:0}
        .radio-custom label i{font-size:11px;display:flex;align-items:center}
        .radio-custom.good input[type="radio"]:checked + label{border-color:var(--success);background-color:var(--success-10);color:var(--success)}
        .radio-custom.bad input[type="radio"]:checked + label{border-color:var(--red-main);background-color:var(--red-main-10);color:var(--red-main)}

        /* ===== FORM: SUB-TAB SECTIONS (Kondisi Unit) ===== */
        .inspection-header{display:flex;justify-content:space-between;align-items:flex-end;align-self:stretch;gap:10px;flex-wrap:wrap}
        .tab-group{display:flex;max-width:620px;padding:5px;align-items:center;gap:5px 10px;flex:1 0 0;flex-wrap:wrap}
        .tab-sections{display:flex;min-width:100px;padding:6px 12px;justify-content:center;align-items:center;gap:8px;flex:1 0 0;font-size:12px;font-weight:600;border-radius:8px;cursor:pointer;transition:all .2s;color:var(--dark-secondary)}
        .tab-sections:hover{background-color:var(--blue-main-10);color:var(--blue-main)}
        .tab-sections.active{color:var(--button-color);background-color:var(--blue-main)}
        .tab-sections i{position:relative;top:1px}
        button.set-all-good{display:flex;padding:8px 14px;align-items:center;gap:8px;border-radius:8px;background-color:var(--success);border:none;font-size:12px;font-weight:600;color:var(--button-color);cursor:pointer}
        button.set-all-good:hover{background-color:var(--success-hover);box-shadow:0 0 4px 0 var(--success-40)}
        button.set-all-good i{position:relative;top:2px}
        .condition-counter{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .count-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600}
        .count-chip.ready{background-color:var(--success-10);color:var(--success)}
        .count-chip.rusak{background-color:var(--red-main-10);color:var(--red-main)}
        .count-chip i{position:relative;top:1px}

        /* ===== FORM: NAV BUTTONS ===== */
        .box-button{display:flex;justify-content:space-between;align-items:center;align-self:stretch}
        .btn-form{display:flex;width:130px;padding:12px 20px;justify-content:center;align-items:center;gap:10px;border-radius:10px;border:none;transition:.2s ease-out;font-size:14px;color:var(--dark-secondary);font-weight:500;cursor:pointer;text-decoration:none}
        .btn-form.back{background-color:var(--orange-main);color:var(--button-color);border:1px solid var(--black-10)}
        .btn-form.back:hover{background-color:var(--orange-hover)}
        .btn-form.cancel{background-color:var(--white);border:1px solid var(--black-10)}
        .btn-form.cancel:hover{background-color:var(--red-main-10);color:var(--red-hover)}
        .btn-form.next{background-color:var(--blue-main);color:var(--button-color)}
        .btn-form.next:hover{background-color:var(--blue-hover)}
        .btn-form.finish{background-color:var(--success);color:var(--button-color)}
        .btn-form.finish:hover{background-color:var(--success-hover)}
        .btn-form .icon{position:relative;top:2px;display:inline-flex;align-items:center;justify-content:center}

        /* Header simpan draft (amber) */
        .content-header .btn-draft-save{color:var(--button-color);padding:12px 24px;margin:0;background-color:var(--orange-main);border:none;border-radius:10px;transition:all .3s ease;box-shadow:0 4px 12px rgba(249,115,22,.25);white-space:nowrap;display:flex;align-items:center;gap:10px;cursor:pointer}
        .content-header .btn-draft-save:hover{background-color:var(--orange-hover);transform:translateY(-2px)}
        .content-header .btn-draft-save .icon-new{position:relative;top:1px;font-size:16px}
        .content-header.is-sticky .btn-draft-save{border-radius:100px;padding:8px 18px;width:100%;justify-content:center}
        .content-header.is-sticky .btn-draft-save span.btn-text{font-size:12px}

        /* ===== MODAL ===== */
        .modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,.6);backdrop-filter:blur(4px);display:flex;justify-content:center;align-items:center;z-index:10000;opacity:0;visibility:hidden;transition:opacity .3s ease,visibility .3s ease;padding:20px}
        .modal-overlay.show{opacity:1;visibility:visible}
        .pop-up.signed{width:400px;max-width:100%;padding:25px;border-radius:20px;background-color:var(--white);box-shadow:0 10px 30px rgba(0,0,0,.15);transform:scale(.9);transition:transform .3s cubic-bezier(0.34,1.56,0.64,1)}
        .modal-overlay.show .pop-up.signed{transform:scale(1)}
        .pop-up.detail{padding:15px;gap:12px;border-radius:12px;background-color:var(--blue-main-5);border:1px solid var(--blue-main-10)}
        .pop-up.detail.danger{background-color:var(--red-main-10);border:1px solid var(--red-main-10)}
        .icon-document{display:flex;width:40px;height:40px;align-items:center;justify-content:center;border-radius:8px;background-color:var(--white);font-size:20px;color:var(--blue-main);box-shadow:0 2px 5px rgba(0,0,0,.05)}
        .icon-document.danger{color:var(--red-main)}
        .icon-document i{position:relative;top:2px}
        .button-close{background:none;border:none;cursor:pointer;padding:0;display:flex;color:var(--muted)}
        .button-close:hover{color:var(--red-main)}
        .pop-up.footer .btn{padding:10px 18px;font-size:13px;font-weight:600;border-radius:10px;border:none;transition:.2s;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
        .pop-up.footer .btn.cancel{background-color:var(--dark-secondary-10);color:var(--dark-main)}
        .pop-up.footer .btn.confirm{background-color:var(--success);color:#fff}
        .pop-up.footer .btn.confirm:hover{background-color:var(--success-hover);transform:translateY(-2px)}
        .pop-up.footer .btn.delete-confirm{background-color:var(--red-main);color:#fff}
        .pop-up.footer .btn.delete-confirm:hover{background-color:var(--red-hover);transform:translateY(-2px)}
        .pop-up.footer .btn.edit-confirm{background-color:var(--blue-main);color:#fff}
        .pop-up.footer .btn.edit-confirm:hover{background-color:var(--blue-hover);transform:translateY(-2px)}
        .pop-up.footer .btn.confirm i,.pop-up.footer .btn.delete-confirm i,.pop-up.footer .btn.edit-confirm i{position:relative;top:2px}

        /* ===== TOAST ===== */
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

        /* ===== LOADING SPINNER ===== */
        @keyframes sk-rotate{to{transform:rotate(360deg)}}
        .sk-overlay{position:fixed;inset:0;z-index:9998;background-color:var(--main-bg);display:flex;align-items:center;justify-content:center;transition:opacity .4s ease,visibility .4s ease}
        .sk-overlay.sk-done{opacity:0;visibility:hidden;pointer-events:none}
        .sk-spinner{width:48px;height:48px;border:4px solid var(--smooth-border);border-top-color:var(--blue-main);border-radius:50%;animation:sk-rotate .8s linear infinite}

        /* ===== RESPONSIVE ===== */
        @media (max-width:1024px){.p-content{padding:0 40px!important}}
        @media (max-width:768px){
            body{gap:16px}.p-content{padding:0 16px!important}.p-navbar{padding:12px 16px!important}.size-logo{width:82px!important}
            .content-header{flex-direction:column!important;align-items:flex-start!important;gap:12px!important}
            .content-header .btn-new,.content-header .btn-draft-save{width:100%!important;justify-content:center!important}
            .content-header.is-sticky{flex-direction:row!important;align-items:center!important;gap:0!important}
            .header-form{padding:14px 16px!important;flex-wrap:wrap!important;gap:8px!important}.content-form{padding:16px!important}
            .inspection-header{flex-direction:column!important;align-items:stretch!important}.set-all-good{width:100%!important;justify-content:center!important}
            .btn-form{width:auto!important;flex:1!important;padding:10px!important;font-size:13px!important}
            /* Reminder box: tombol Lanjutkan Draft melebar, X tetap di ujung kanan (samakan dengan operasional) */
            .reminder{min-width:unset!important}
            .reminder-button{width:100%!important;justify-content:space-between!important}
            .reminder-button .btn.draft-edit{flex:1 1 auto!important}
            .draft-detail{min-width:unset!important}.draft-button{min-width:unset!important;flex:1 0 100%!important}
        }
        @media (max-width:480px){
            .p-content{padding:0 12px!important}.info-officer{display:none!important}.header-left .divider-vertical{display:none!important}
            .fsize-20{font-size:16px!important}.list-form-tab span:not(.icon-tab){display:none!important}.list-form-tab{min-width:40px!important;padding:8px 10px!important;gap:0!important}
            .counter-form{display:none!important}
            .toast-viewport{top:12px;width:calc(100vw - 24px)}
            .toast-message{padding:10px 12px;gap:9px;border-radius:22px}
            .toast-icon{width:34px;height:34px;border-radius:12px}
        }
    </style>
    @stack('styles')
</head>
<body>
    <script>if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark-mode');</script>

    <div class="sk-overlay" id="sk-overlay"><div class="sk-spinner"></div></div>

    @php
        $toastMessages = collect();
        if (session('success')) { $toastMessages->push(['type'=>'success','title'=>'Berhasil','message'=>session('success'),'icon'=>'fi fi-rr-check-circle']); }
        if (session('error')) { $toastMessages->push(['type'=>'error','title'=>'Gagal','message'=>session('error'),'icon'=>'fi fi-rr-triangle-warning']); }
        if ($errors->any()) { $toastMessages->push(['type'=>'error','title'=>'Periksa Form','message'=>$errors->first(),'icon'=>'fi fi-rr-info']); }
    @endphp

    @if ($toastMessages->isNotEmpty())
        <div class="toast-viewport" aria-live="polite" aria-atomic="true">
            @foreach ($toastMessages as $toast)
                <div class="toast-message {{ $toast['type'] }}" data-duration="4200" role="status">
                    <div class="toast-icon"><i class="{{ $toast['icon'] }}"></i></div>
                    <div class="toast-copy">
                        <span class="toast-title">{{ $toast['title'] }}</span>
                        <span class="toast-text">{{ $toast['message'] }}</span>
                    </div>
                    <button type="button" class="toast-close" aria-label="Tutup notifikasi"><i class="fi fi-br-cross-small"></i></button>
                </div>
            @endforeach
        </div>
    @endif

    @include('report-safety.layouts.header')

    @yield('content')

    @include('report-safety.layouts.footer')

    @stack('modals')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. STICKY HEADER
        const contentHeader = document.querySelector('.content-header');
        if (contentHeader) {
            const wrapper = document.createElement('div');
            wrapper.style.width = '100%'; wrapper.style.position = 'relative';
            contentHeader.parentNode.insertBefore(wrapper, contentHeader);
            wrapper.appendChild(contentHeader);
            window.addEventListener('scroll', () => {
                const rect = wrapper.getBoundingClientRect();
                if (rect.bottom < 0) {
                    if (!contentHeader.classList.contains('is-sticky')) {
                        wrapper.style.height = `${wrapper.offsetHeight}px`;
                        contentHeader.classList.add('is-sticky');
                        requestAnimationFrame(() => contentHeader.classList.add('show-sticky'));
                    }
                } else if (contentHeader.classList.contains('is-sticky')) {
                    contentHeader.classList.remove('show-sticky', 'is-sticky');
                    wrapper.style.height = 'auto';
                }
            });
        }

        // 2. DARK MODE TOGGLE
        const themeBtn = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;
        let isDark = localStorage.getItem('theme') === 'dark';
        if (isDark && themeIcon) themeIcon.className = 'fi fi-rr-moon';
        if (themeBtn && themeIcon) {
            themeBtn.addEventListener('click', () => {
                isDark = !isDark;
                themeIcon.classList.add(isDark ? 'animate-out-up' : 'animate-out-down');
                setTimeout(() => {
                    body.classList.toggle('dark-mode', isDark);
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    themeIcon.className = isDark ? 'fi fi-rr-moon' : 'fi fi-rr-sun';
                    themeIcon.classList.remove('animate-out-up', 'animate-out-down');
                    themeIcon.classList.add(isDark ? 'prepare-from-bottom' : 'prepare-from-top');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-bottom', 'prepare-from-top');
                }, 200);
            });
        }

        // 3. TOAST
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

        function bindToast(toast, index = 0) {
            const closeBtn = toast.querySelector('.toast-close');
            const duration = Number(toast.dataset.duration || 4200) + index * 180;
            let timer = null;
            const hide = () => { if (toast.classList.contains('is-hiding')) return; toast.classList.remove('show'); toast.classList.add('is-hiding'); setTimeout(() => toast.remove(), 420); };
            setTimeout(() => toast.classList.add('show'), 80 + index * 90);
            timer = setTimeout(hide, duration);
            toast.addEventListener('mouseenter', () => clearTimeout(timer));
            toast.addEventListener('mouseleave', () => { timer = setTimeout(hide, 1400); });
            closeBtn?.addEventListener('click', hide);
        }
        window.showReportToast = function (type, title, message, duration = 4200) {
            const safe = type === 'success' ? 'success' : 'error';
            const viewport = ensureToastViewport();
            const toast = document.createElement('div');
            toast.className = `toast-message ${safe}`;
            toast.dataset.duration = String(duration);
            toast.setAttribute('role', 'status');
            toast.innerHTML = `<div class="toast-icon"><i class="${safe === 'success' ? 'fi fi-rr-check-circle' : 'fi fi-rr-triangle-warning'}"></i></div><div class="toast-copy"><span class="toast-title"></span><span class="toast-text"></span></div><button type="button" class="toast-close" aria-label="Tutup notifikasi"><i class="fi fi-br-cross-small"></i></button>`;
            toast.querySelector('.toast-title').textContent = title || (safe === 'success' ? 'Berhasil' : 'Gagal');
            toast.querySelector('.toast-text').textContent = message || '';
            viewport.appendChild(toast); bindToast(toast);
        };
        document.querySelectorAll('.toast-message').forEach((t, i) => bindToast(t, i));

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

        // 4. NUMBER INPUT SAFETY
        document.addEventListener('keydown', e => { if (e.target.matches?.('input[type="number"]') && ['-','+','e','E'].includes(e.key)) e.preventDefault(); });

        // 5. CUSTOM SELECT (native-select -> custom-input)
        function initCustomSelects() {
            document.querySelectorAll('.input-wrapper').forEach(wrapper => {
                const native = wrapper.querySelector('select.native-select');
                if (!native || wrapper.querySelector('.custom-input.cs-trigger')) return;
                native.style.display = 'none';
                const trigger = document.createElement('div');
                trigger.className = 'custom-input cs-trigger d-flex align-items-center';
                trigger.tabIndex = 0;
                const span = document.createElement('span');
                const selected = native.options[native.selectedIndex];
                span.textContent = selected ? selected.text : '';
                if (!selected || selected.disabled || selected.value === '') trigger.classList.add('text-placeholder');
                trigger.appendChild(span);
                wrapper.insertBefore(trigger, native.nextSibling);
                const list = document.createElement('div');
                list.className = 'custom-options-container';
                Array.from(native.options).forEach(opt => {
                    if (opt.disabled && opt.hidden) return;
                    const div = document.createElement('div');
                    div.className = 'custom-option'; div.textContent = opt.text; div.dataset.value = opt.value;
                    if (opt.selected) div.classList.add('selected');
                    div.addEventListener('click', e => {
                        e.stopPropagation();
                        native.value = opt.value; native.dispatchEvent(new Event('change', { bubbles: true }));
                        span.textContent = opt.text; trigger.classList.remove('text-placeholder');
                        list.querySelectorAll('.custom-option').forEach(o => o.classList.remove('selected'));
                        div.classList.add('selected'); list.classList.remove('open'); trigger.classList.remove('focus-active');
                    });
                    list.appendChild(div);
                });
                wrapper.appendChild(list);
                trigger.addEventListener('click', e => {
                    e.stopPropagation();
                    document.querySelectorAll('.custom-options-container.open').forEach(c => { if (c !== list) { c.classList.remove('open'); c.previousElementSibling?.classList.remove('focus-active'); } });
                    list.classList.toggle('open'); trigger.classList.toggle('focus-active');
                });
            });
        }
        initCustomSelects();

        // Custom select untuk SELECT di dalam tabel (.tbl-select-wrapper > select.tbl-native-select),
        // styling dropdown mengikuti modul operasional. Dipanggil ulang saat baris ditambah.
        function hydrateTableSelects(root = document) {
            root.querySelectorAll('.tbl-select-wrapper').forEach(wrapper => {
                const native = wrapper.querySelector('select.tbl-native-select');
                if (!native || wrapper.querySelector('.tbl-custom-select-trigger')) return;
                native.style.display = 'none';
                const caret = wrapper.querySelector('.sel-caret');
                const trigger = document.createElement('div');
                trigger.className = 'tbl-custom-select-trigger';
                const span = document.createElement('span');
                trigger.appendChild(span);
                wrapper.insertBefore(trigger, native.nextSibling);
                const list = document.createElement('div');
                list.className = 'tbl-custom-options';
                function updateTrigger() {
                    const o = native.options[native.selectedIndex];
                    span.textContent = o ? o.text : '';
                    trigger.classList.toggle('text-placeholder', !o || o.disabled || o.value === '');
                    list.querySelectorAll('.tbl-custom-option').forEach(op => op.classList.toggle('selected', op.dataset.value === native.value));
                }
                Array.from(native.options).forEach(option => {
                    if (option.disabled && option.hidden) return;
                    const op = document.createElement('div');
                    op.className = 'tbl-custom-option';
                    op.textContent = option.text;
                    op.dataset.value = option.value;
                    op.addEventListener('click', e => {
                        e.stopPropagation();
                        native.value = op.dataset.value;
                        native.dispatchEvent(new Event('change', { bubbles: true }));
                        if (trigger.__closeTblList) trigger.__closeTblList();
                        else {
                            list.classList.remove('open');
                            trigger.classList.remove('focus-active');
                        }
                    });
                    list.appendChild(op);
                });
                // Kotak pencarian (untuk dropdown dengan banyak opsi, mis. Jenis Unit).
                let searchInput = null;
                if (wrapper.dataset.search === 'true') {
                    const searchBox = document.createElement('div');
                    searchBox.className = 'tbl-search';
                    searchInput = document.createElement('input');
                    searchInput.type = 'text';
                    searchInput.placeholder = 'Ketik untuk mencari unit...';
                    searchInput.addEventListener('click', e => e.stopPropagation());
                    searchInput.addEventListener('input', () => {
                        const q = searchInput.value.trim().toLowerCase();
                        list.querySelectorAll('.tbl-custom-option').forEach(op => {
                            op.style.display = (!q || op.textContent.toLowerCase().includes(q)) ? '' : 'none';
                        });
                    });
                    searchBox.appendChild(searchInput);
                    list.insertBefore(searchBox, list.firstChild);
                }
                wrapper.appendChild(list);
                list.__trigger = trigger;
                native.addEventListener('change', updateTrigger);

                // Posisikan dropdown sebagai fixed relatif ke trigger agar tidak
                // terpotong oleh overflow tabel (muncul penuh di atas konten lain).
                // Dropdown selalu menempel tepat di bawah field (tidak pernah ke atas).
                function positionList() {
                    const r = trigger.getBoundingClientRect();
                    list.style.position = 'fixed';
                    list.style.left = r.left + 'px';
                    list.style.width = r.width + 'px';
                    list.style.zIndex = '9998';
                    list.style.top = (r.bottom + 4) + 'px';
                }
                function closeList() {
                    list.classList.remove('open');
                    trigger.classList.remove('focus-active');
                    list.style.position = ''; list.style.top = ''; list.style.left = ''; list.style.width = ''; list.style.zIndex = '';
                    if (window.__pmlOpenTblList === list) window.__pmlOpenTblList = null;
                }
                trigger.__closeTblList = closeList;
                trigger.__reposition = positionList;

                trigger.addEventListener('click', e => {
                    e.stopPropagation();
                    const willOpen = !list.classList.contains('open');
                    document.querySelectorAll('.tbl-custom-options.open').forEach(c => {
                        const t = c.__trigger || c.previousElementSibling;
                        if (t && t.__closeTblList) t.__closeTblList();
                    });
                    if (willOpen) {
                        list.classList.add('open');
                        trigger.classList.add('focus-active');
                        positionList();
                        window.__pmlOpenTblList = list;
                        if (searchInput) {
                            searchInput.value = '';
                            list.querySelectorAll('.tbl-custom-option').forEach(op => { op.style.display = ''; });
                            setTimeout(() => searchInput.focus({ preventScroll: true }), 20);
                        }
                    } else {
                        closeList();
                    }
                });
                updateTrigger();
            });
        }
        window.__pmlHydrateSelects = hydrateTableSelects;
        hydrateTableSelects();

        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-options-container.open').forEach(c => c.classList.remove('open'));
            document.querySelectorAll('.custom-input.focus-active').forEach(t => t.classList.remove('focus-active'));
            document.querySelectorAll('.tbl-custom-options.open').forEach(c => {
                const t = c.__trigger || c.previousElementSibling;
                if (t && t.__closeTblList) t.__closeTblList(); else c.classList.remove('open');
            });
        });

        // Dropdown tabel memakai position:fixed; ikut bergerak saat halaman/area di-scroll.
        ['scroll', 'resize'].forEach(ev => window.addEventListener(ev, () => {
            const list = window.__pmlOpenTblList;
            if (list && list.classList.contains('open')) {
                const t = list.__trigger || list.previousElementSibling;
                if (t && t.__reposition) t.__reposition();
            }
        }, true));

        // 6. TIME INPUT FORMATTING (auto :)
        document.addEventListener('input', e => {
            const el = e.target;
            if (!el.matches?.('.time-picker-input')) return;
            if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward') return;
            let v = el.value.replace(/\D/g, '');
            if (v.length > 4) v = v.substring(0, 4);
            if (v.length >= 3) el.value = v.substring(0, 2) + ':' + v.substring(2);
            else el.value = v;
        });

        // 7. FORM SECTION SPA NAV (.list-form-tab + .form-step)
        const formTabs = document.querySelectorAll('.list-form-tab');
        const formSteps = document.querySelectorAll('.form-step');
        let currentStep = 0;
        function scrollToMaintenanceFormTabs() {
            const tabForm = document.querySelector('.tab-form');
            if (!tabForm) return;

            const topGap = window.innerWidth <= 768 ? 16 : 40;
            const targetTop = Math.max(0, tabForm.getBoundingClientRect().top + window.scrollY - topGap);
            window.scrollTo({ top: targetTop, behavior: 'smooth' });
        }

        window.__pmlShowStep = function (index) {
            if (!formSteps.length || index < 0 || index >= formSteps.length) return;
            formSteps.forEach(s => { s.classList.remove('d-flex'); s.classList.add('d-none'); });
            formTabs.forEach(t => t.classList.remove('active'));
            formSteps[index].classList.remove('d-none'); formSteps[index].classList.add('d-flex');
            formTabs[index]?.classList.add('active');
            formTabs[index]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            currentStep = index;
            requestAnimationFrame(scrollToMaintenanceFormTabs);
        };
        if (formTabs.length && formSteps.length) {
            formTabs.forEach((tab, i) => tab.addEventListener('click', () => window.__pmlShowStep(i)));
            document.querySelectorAll('.btn-next-step').forEach(b => b.addEventListener('click', e => { e.preventDefault(); window.__pmlShowStep(currentStep + 1); }));
            document.querySelectorAll('.btn-back-step').forEach(b => b.addEventListener('click', e => { e.preventDefault(); window.__pmlShowStep(currentStep - 1); }));
        }

        // 8. GENERIC MODAL OPEN/CLOSE (data-open-modal / data-close-modal)
        function openModal(id) { document.getElementById(id)?.classList.add('show'); }
        function closeAllModals() { document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show')); }
        window.__pmlOpenModal = openModal; window.__pmlCloseModals = closeAllModals;
        document.querySelectorAll('[data-open-modal]').forEach(btn => btn.addEventListener('click', () => openModal(btn.dataset.openModal)));
        document.querySelectorAll('[data-close-modal]').forEach(btn => btn.addEventListener('click', closeAllModals));
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', e => { if (e.target === modal) closeAllModals(); });
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAllModals(); });
    });
    </script>

    @stack('scripts')

    <script>
        window.addEventListener('load', function () {
            var sk = document.getElementById('sk-overlay');
            if (sk) { sk.classList.add('sk-done'); setTimeout(() => sk.remove(), 600); }
        });
    </script>
</body>
</html>
