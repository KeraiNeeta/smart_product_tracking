<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111c2e">
    <title>@yield('title', 'Dashboard') | Shop Guard</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #172235;
            --muted: #718096;
            --line: #e8edf3;
            --surface: #fff;
            --canvas: #f5f7fb;
            --nav: #111c2e;
            --nav-muted: #9aa8bd;
            --accent: #19a889;
            --accent-dark: #12866d;
            --shadow: 0 8px 24px rgba(22, 34, 53, .045);
        }
        * { box-sizing: border-box; }
        body { min-width: 320px; margin: 0; background: var(--canvas); color: var(--ink); font: 14px/1.5 Inter, "Segoe UI", Arial, sans-serif; }
        a { color: inherit; }
        .app-shell { min-height: 100vh; }
        .sidebar {
            position: fixed; inset: 0 auto 0 0; z-index: 20; display: flex; width: 258px;
            flex-direction: column; padding: 22px 14px 16px; background: var(--nav); color: #fff;
        }
        .brand { display: flex; align-items: center; gap: 12px; min-height: 50px; padding: 0 11px 20px; color: #fff; text-decoration: none; }
        .brand-mark { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 12px; background: var(--accent); color: #fff; font-size: 15px; font-weight: 800; letter-spacing: -.5px; }
        .brand-copy { display: flex; flex-direction: column; line-height: 1.2; }
        .brand-copy strong { font-size: 14px; letter-spacing: .1px; }
        .brand-copy small { margin-top: 4px; color: var(--nav-muted); font-size: 11px; }
        .nav-scroll { flex: 1; overflow-y: auto; padding: 5px 0 12px; }
        .nav-label { margin: 17px 11px 7px; color: #77869c; font-size: 10px; font-weight: 700; letter-spacing: 1.25px; text-transform: uppercase; }
        .nav-link { position: relative; display: flex; align-items: center; gap: 11px; min-height: 41px; margin: 2px 0; padding: 9px 11px; border-radius: 8px; color: #c3cddd; font-size: 13px; text-decoration: none; transition: background .16s ease, color .16s ease; }
        .nav-link:hover { background: rgba(255,255,255,.07); color: #fff; }
        .nav-link.active { background: rgba(25,168,137,.16); color: #fff; font-weight: 600; }
        .nav-link.active::before { position: absolute; top: 9px; bottom: 9px; left: 0; width: 3px; border-radius: 0 3px 3px 0; background: #46d3af; content: ""; }
        .nav-icon { display: grid; width: 20px; color: #98a8bf; font-size: 12px; font-weight: 700; place-items: center; }
        .nav-link.active .nav-icon { color: #62ddbd; }
        .sidebar-foot { padding: 14px 11px 3px; border-top: 1px solid rgba(255,255,255,.09); color: #9aa8bd; font-size: 11px; }
        .workspace { min-height: 100vh; margin-left: 258px; }
        .topbar { position: sticky; top: 0; z-index: 10; display: flex; min-height: 74px; align-items: center; justify-content: space-between; gap: 20px; padding: 12px 34px; border-bottom: 1px solid var(--line); background: rgba(255,255,255,.94); backdrop-filter: blur(12px); }
        .topbar-title { min-width: 0; }
        .breadcrumb { margin-bottom: 2px; color: var(--muted); font-size: 11px; letter-spacing: .2px; }
        .topbar h1 { overflow: hidden; margin: 0; color: #233148; font-size: 17px; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
        .topbar-actions { display: flex; align-items: center; gap: 14px; }
        .admin-profile { display: flex; align-items: center; gap: 10px; }
        .admin-profile-link { color: inherit; text-decoration: none; }
        .admin-profile-link:hover .avatar { background: #d2eee5; }
        .avatar { display: grid; width: 36px; height: 36px; flex: 0 0 auto; place-items: center; border-radius: 50%; background: #e2f5f0; color: #14836c; font-size: 12px; font-weight: 800; }
        .admin-copy { display: flex; flex-direction: column; line-height: 1.25; }
        .admin-copy strong { max-width: 180px; overflow: hidden; color: #29374b; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .admin-copy small { margin-top: 3px; color: var(--muted); font-size: 10px; }
        .logout-form { margin: 0; }
        main { width: 100%; max-width: 1600px; margin: 0 auto; padding: 30px 34px 48px; }
        .page-heading { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 22px; }
        .page-heading h1 { margin: 0; color: #1b293d; font-size: clamp(22px, 2.2vw, 28px); font-weight: 700; letter-spacing: -.7px; line-height: 1.25; }
        .page-heading h2 { margin: 0; color: #1b293d; font-size: 20px; font-weight: 680; letter-spacing: -.35px; }
        .page-heading p { margin: 7px 0 0; }
        .panel { overflow-x: auto; padding: 22px; border: 1px solid #edf0f5; border-radius: 12px; background: var(--surface); box-shadow: var(--shadow); }
        .button, button { display: inline-flex; min-height: 38px; align-items: center; justify-content: center; gap: 7px; padding: 9px 14px; border: 1px solid var(--accent); border-radius: 7px; background: var(--accent); color: #fff; cursor: pointer; font: inherit; font-size: 13px; font-weight: 600; line-height: 1.2; text-decoration: none; transition: background .15s ease, border-color .15s ease, transform .15s ease; }
        .button:hover, button:hover { border-color: var(--accent-dark); background: var(--accent-dark); color: #fff; }
        .button:active, button:active { transform: translateY(1px); }
        .button.secondary, button.secondary { border-color: #e2e7ef; background: #fff; color: #47566c; }
        .button.secondary:hover, button.secondary:hover { border-color: #ccd5e1; background: #f8fafc; }
        .button.danger, button.danger { border-color: #f0c5c5; background: #fff; color: #b4232e; }
        .button.danger:hover, button.danger:hover { border-color: #b4232e; background: #b4232e; color: #fff; }
        .actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .actions form { margin: 0; }
        .actions .button, .actions button { min-height: 32px; padding: 7px 10px; font-size: 12px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th, td { padding: 13px 14px; border-bottom: 1px solid #edf0f4; text-align: left; vertical-align: middle; }
        th { background: #f8fafc; color: #738096; font-size: 10px; font-weight: 700; letter-spacing: .75px; text-transform: uppercase; white-space: nowrap; }
        th:first-child { border-radius: 7px 0 0 7px; }
        th:last-child { border-radius: 0 7px 7px 0; }
        td { color: #354258; font-size: 12px; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: #fbfcfe; }
        .muted { color: var(--muted); }
        td .muted { margin-top: 3px; font-size: 11px; }
        code { padding: 3px 6px; border-radius: 5px; background: #f1f4f8; color: #49586e; font-size: 11px; }
        .badge { display: inline-flex; align-items: center; padding: 4px 9px; border: 1px solid transparent; border-radius: 999px; background: #eef2f7; color: #58677d; font-size: 10px; font-weight: 700; line-height: 1.3; white-space: nowrap; }
        .badge.active, .badge.resolved, .badge.delivered { background: #e4f7ef; color: #13734f; }
        .badge.inactive, .badge.failed, .badge.retired { background: #fff0f0; color: #a6323b; }
        .badge.open, .badge.pending { background: #fff5df; color: #94620d; }
        .badge.acknowledged { background: #eaf1ff; color: #315ea8; }
        .field { margin-bottom: 18px; }
        label { display: block; margin-bottom: 7px; color: #3f4d61; font-size: 12px; font-weight: 650; }
        input, select, textarea { width: 100%; max-width: 620px; min-height: 41px; padding: 10px 12px; border: 1px solid #dce2ea; border-radius: 7px; outline: 0; background: #fff; color: #263449; font: inherit; font-size: 13px; transition: border .15s ease, box-shadow .15s ease; }
        input:focus, select:focus, textarea:focus { border-color: #59baa4; box-shadow: 0 0 0 3px rgba(25,168,137,.12); }
        textarea { min-height: 110px; resize: vertical; }
        input[type="checkbox"], input[type="radio"] { width: auto; min-height: 0; accent-color: var(--accent); }
        .error, .alert-error { color: #b4232e; }
        .alert { margin-bottom: 20px; padding: 13px 16px; border: 1px solid #bfe9d8; border-radius: 9px; background: #edfaf4; color: #176c50; font-size: 13px; }
        .alert-error { border-color: #f2c8c8; background: #fff1f1; color: #a52c36; }
        .alert ul { margin: 0; padding-left: 20px; }
        .form-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; padding-top: 17px; border-top: 1px solid var(--line); }
        .pagination { margin-top: 18px; }
        .pagination nav { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .pagination svg { width: 16px; height: 16px; }
        .pagination a, .pagination span[aria-current="page"] span { display: inline-flex; min-width: 34px; min-height: 34px; align-items: center; justify-content: center; padding: 5px 10px; border: 1px solid var(--line); border-radius: 6px; background: #fff; color: #526177; font-size: 12px; text-decoration: none; }
        .metrics-grid, .module-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 15px; margin: 20px 0 30px; }
        .metric-card, .module-card { position: relative; display: flex; min-height: 116px; flex-direction: column; justify-content: space-between; gap: 12px; padding: 19px; overflow: hidden; border: 1px solid #edf0f5; border-radius: 12px; background: #fff; color: var(--ink); text-decoration: none; box-shadow: var(--shadow); transition: border-color .16s ease, transform .16s ease, box-shadow .16s ease; }
        .metric-card::after { position: absolute; right: -16px; bottom: -25px; width: 82px; height: 82px; border-radius: 50%; background: rgba(25,168,137,.07); content: ""; }
        .metric-card:hover, .module-card:hover { transform: translateY(-2px); border-color: #b8e2d7; box-shadow: 0 10px 25px rgba(22, 34, 53, .08); }
        .metric-card span, .module-card span { color: var(--muted); font-size: 12px; }
        .metric-card strong { color: #167e69; font-size: 28px; font-weight: 750; letter-spacing: -.8px; }
        .module-card { min-height: 105px; }
        .module-card strong { font-size: 14px; }
        .section-heading { margin-top: 34px; }
        .mobile-nav { display: none; }
        @media (max-width: 980px) {
            .sidebar { width: 220px; }
            .workspace { margin-left: 220px; }
            .topbar { padding-right: 24px; padding-left: 24px; }
            main { padding-right: 24px; padding-left: 24px; }
        }
        @media (max-width: 720px) {
            .sidebar { position: sticky; top: 0; right: 0; bottom: auto; width: 100%; height: auto; padding: 8px 12px; }
            .brand { min-height: 43px; padding: 0 4px 8px; }
            .brand-mark { width: 32px; height: 32px; border-radius: 9px; font-size: 12px; }
            .brand-copy strong { font-size: 12px; }
            .brand-copy small, .sidebar-foot, .nav-label { display: none; }
            .nav-scroll { display: flex; gap: 4px; margin: 0 -12px; padding: 3px 12px 5px; overflow: auto hidden; }
            .nav-link { flex: 0 0 auto; min-height: 34px; margin: 0; padding: 7px 10px; font-size: 11px; }
            .nav-link.active::before { top: auto; right: 9px; bottom: 0; left: 9px; width: auto; height: 2px; }
            .nav-icon { display: none; }
            .workspace { margin-left: 0; }
            .topbar { position: static; min-height: 62px; padding: 10px 16px; }
            .topbar h1 { font-size: 15px; }
            .breadcrumb { font-size: 10px; }
            .admin-copy { display: none; }
            .topbar-actions { gap: 9px; }
            .topbar-actions .button { min-height: 34px; padding: 7px 10px; font-size: 11px; }
            main { padding: 22px 16px 36px; }
            .page-heading { align-items: flex-start; flex-direction: column; }
            .page-heading > .button { align-self: flex-start; }
            .panel { padding: 14px; border-radius: 10px; }
            th, td { padding: 11px 10px; }
            .metrics-grid, .module-grid { grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 10px; }
            .metric-card, .module-card { min-height: 100px; padding: 15px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
        :root {
            --ink: #172b2a;
            --muted: #70817e;
            --line: #e5ece8;
            --surface: #fff;
            --canvas: #f4f7f4;
            --nav: #142421;
            --nav-muted: #a2b4ad;
            --accent: #0f9b78;
            --accent-dark: #08795e;
            --shadow: 0 8px 28px rgba(25, 52, 44, .055);
        }
        body { background: var(--canvas); color: var(--ink); }
        .workspace { background: radial-gradient(ellipse at 75% -20%, rgba(15,155,120,.065), transparent 38%), var(--canvas); }
        .sidebar { width: 264px; padding: 24px 14px 18px; background: linear-gradient(165deg, #172c28 0%, #11201e 100%); }
        .brand { min-height: 60px; margin: 0 3px 15px; padding: 0 10px 19px; border-bottom: 1px solid rgba(255,255,255,.09); }
        .brand-mark { width: 40px; height: 40px; border: 1px solid rgba(255,255,255,.15); border-radius: 13px; background: linear-gradient(145deg, #18b68d, #08785e); box-shadow: 0 5px 14px rgba(4,120,94,.25); }
        .brand-copy strong { font-size: 15px; letter-spacing: -.2px; }
        .nav-label { margin: 21px 12px 8px; color: #829790; font-size: 9px; letter-spacing: 1.35px; }
        .nav-link { min-height: 42px; gap: 12px; margin: 3px 0; border: 1px solid transparent; border-radius: 10px; color: #bdcbc6; }
        .nav-link:hover { background: rgba(255,255,255,.055); color: #fff; }
        .nav-link.active { border-color: rgba(67,207,164,.14); background: rgba(32,177,135,.14); color: #fff; }
        .nav-link.active::before { top: 10px; bottom: 10px; background: #42d4a7; }
        .nav-icon { width: 20px; height: 20px; color: #91a59d; }
        .nav-icon svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .nav-link.active .nav-icon { color: #68dfb8; }
        .sidebar-foot { color: #a9bbb4; font-size: 10px; }
        .workspace { margin-left: 264px; }
        .topbar { min-height: 78px; padding-right: 38px; padding-left: 38px; border-color: rgba(221,232,226,.8); background: rgba(255,255,255,.88); }
        .breadcrumb { color: #84928e; font-size: 10px; font-weight: 550; }
        .topbar h1 { color: #203632; font-size: 16px; font-weight: 700; }
        .avatar { border: 1px solid #d7eee5; background: #e7f5ee; color: #08795e; }
        main { max-width: 1680px; padding: 34px 38px 56px; }
        .page-heading { margin-bottom: 25px; }
        .page-heading h1 { color: #19332d; font-size: clamp(25px, 2.4vw, 32px); font-weight: 750; letter-spacing: -1.1px; }
        .page-heading h2 { color: #233c35; font-size: 19px; letter-spacing: -.4px; }
        .page-heading p { color: #778780; font-size: 13px; }
        .panel { padding: 23px; border-color: #e7ede9; border-radius: 15px; box-shadow: 0 4px 18px rgba(25,52,44,.035); }
        .button, button { min-height: 40px; padding: 10px 15px; border-radius: 9px; box-shadow: 0 2px 4px rgba(6,114,85,.08); }
        .button.secondary, button.secondary { box-shadow: none; }
        .actions .button, .actions button { min-height: 34px; border-radius: 8px; }
        table { border-collapse: separate; border-spacing: 0; }
        th { padding-top: 14px; padding-bottom: 14px; background: #f5f8f6; color: #74847d; font-size: 9px; letter-spacing: .9px; }
        th:first-child { border-radius: 9px 0 0 9px; }
        th:last-child { border-radius: 0 9px 9px 0; }
        td { padding-top: 14px; padding-bottom: 14px; color: #344640; }
        tbody tr:hover { background: #f8fbf9; }
        .badge { padding: 5px 10px; border: 1px solid #e5ebe7; background: #f2f5f3; color: #5b6d65; font-size: 10px; }
        .badge.active, .badge.resolved, .badge.delivered { border-color: #cfecdd; background: #eaf7ef; color: #19724f; }
        .badge.inactive, .badge.failed, .badge.retired { border-color: #f4d9d8; background: #fff0ef; color: #a3413f; }
        .badge.open, .badge.pending, .badge.queued { border-color: #f0e4bf; background: #fff8e7; color: #916719; }
        .badge.acknowledged { border-color: #dce5fa; background: #eff4ff; color: #3d5f9b; }
        label { color: #344940; font-size: 12px; }
        input, select, textarea { max-width: 100%; min-height: 44px; border-color: #dbe5df; border-radius: 9px; background: #fff; }
        input:focus, select:focus, textarea:focus { border-color: #38ad89; box-shadow: 0 0 0 4px rgba(15,155,120,.11); }
        code { background: #edf4f0; color: #2c5b4c; }
        .alert { border-color: #cbe9da; border-radius: 10px; background: #edf8f1; color: #236b4d; }
        .alert-error { border-color: #f2d4d1; background: #fff2f0; color: #9d3934; }
        .pagination a, .pagination span[aria-current="page"] span { border-radius: 8px; }
        .metrics-grid, .module-grid { gap: 16px; }
        .metric-card, .module-card { min-height: 132px; padding: 20px; border-color: #e5ece7; border-radius: 15px; box-shadow: 0 4px 16px rgba(25,52,44,.035); }
        .metric-card::after { right: -20px; bottom: -33px; width: 102px; height: 102px; background: rgba(15,155,120,.08); }
        .metric-card:hover, .module-card:hover { transform: translateY(-3px); border-color: #b7dfce; box-shadow: 0 12px 26px rgba(25,52,44,.09); }
        .metric-card span, .module-card span { color: #72827b; font-size: 12px; }
        .metric-card strong { color: #08795e; font-size: 32px; letter-spacing: -1.1px; }
        .module-card { min-height: 116px; justify-content: flex-start; }
        .module-card strong { color: #263d35; font-size: 14px; }
        .section-heading { margin-top: 39px; }
        .dashboard-hero { position: relative; display: flex; min-height: 248px; align-items: center; justify-content: space-between; gap: 28px; margin-bottom: 25px; padding: 32px 38px; overflow: hidden; border: 1px solid #d8e9df; border-radius: 20px; background: linear-gradient(115deg, #eaf5ed 0%, #f6f8ee 58%, #edf6ef 100%); }
        .dashboard-hero > div:first-child { position: relative; z-index: 2; max-width: 650px; }
        .dashboard-kicker { color: #54816a; font-size: 10px; font-weight: 800; letter-spacing: 1.5px; }
        .dashboard-hero h1 { margin: 11px 0 9px; color: #1c3b30; font-size: clamp(26px, 3vw, 38px); font-weight: 760; letter-spacing: -1.5px; line-height: 1.13; }
        .dashboard-hero p { max-width: 520px; margin: 0; color: #63786d; font-size: 14px; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 22px; }
        .hero-actions .button.secondary { border-color: #d8e6dc; background: rgba(255,255,255,.72); }
        .hero-illustration { position: absolute; top: 0; right: 0; width: 42%; height: 100%; overflow: hidden; }
        .hero-orbit { position: absolute; right: 56px; bottom: -190px; width: 390px; height: 390px; border: 1px solid rgba(53,135,91,.13); border-radius: 50%; }
        .hero-orbit-two { right: 15px; bottom: -230px; width: 470px; height: 470px; }
        .hero-shutter { position: absolute; top: 50%; right: 105px; display: flex; width: 122px; height: 148px; flex-direction: column; justify-content: flex-start; gap: 8px; padding: 19px 15px; transform: translateY(-50%); border: 1px solid rgba(42,112,74,.15); border-radius: 18px; background: rgba(255,255,255,.64); box-shadow: 0 18px 45px rgba(44,105,71,.1); }
        .hero-shutter span { height: 13px; border: 1px solid rgba(40,117,75,.16); border-radius: 4px; background: linear-gradient(90deg, rgba(36,134,83,.12), rgba(36,134,83,.04)); }
        .hero-shutter i { position: absolute; top: 16px; right: 15px; width: 8px; height: 8px; border-radius: 50%; background: #25a879; box-shadow: 0 0 0 5px rgba(37,168,121,.12); }
        .hero-signal { position: absolute; top: calc(50% + 47px); right: 81px; width: 9px; height: 9px; border: 2px solid #fff; border-radius: 50%; background: #35b77e; box-shadow: 0 0 0 5px rgba(53,183,126,.16); }
        .metric-card { min-height: 122px; }
        .metric-card .metric-label { color: #6f8179; }
        .metric-card .metric-link { display: flex; align-items: center; gap: 5px; margin-top: -3px; color: #198363; font-size: 11px; font-weight: 650; opacity: .8; }
        .metric-card .metric-link span { color: #198363; font-size: 16px; line-height: 1; transition: transform .15s ease; }
        .metric-card:hover .metric-link span { transform: translateX(3px); }
        .table-panel { padding: 14px 20px 8px; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
        .table-search { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin: 0 0 15px; }
        .table-search-control { display: flex; width: min(100%, 590px); min-height: 43px; align-items: center; gap: 9px; padding: 3px 4px 3px 12px; border: 1px solid #e0e8e2; border-radius: 10px; background: #fff; transition: border-color .15s ease, box-shadow .15s ease; }
        .table-search-control:focus-within { border-color: #38ad89; box-shadow: 0 0 0 3px rgba(15,155,120,.09); }
        .table-search-control > svg { width: 17px; height: 17px; flex: 0 0 auto; fill: none; stroke: #899790; stroke-linecap: round; stroke-width: 1.8; }
        .table-search-control input { width: auto; min-width: 0; min-height: 34px; flex: 1; padding: 7px 2px; border: 0; border-radius: 0; outline: 0; box-shadow: none; font-size: 12px; }
        .table-search-control input:focus { border: 0; box-shadow: none; }
        .table-search-control button { min-height: 34px; padding: 7px 13px; border-radius: 7px; font-size: 11px; }
        .table-search-clear { color: #72827b; font-size: 11px; text-decoration: none; }
        .table-search-clear:hover { color: #08795e; text-decoration: underline; }
        .table-search-count { color: #839089; font-size: 11px; white-space: nowrap; }
        .provider-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; }
        .provider-card { min-width: 0; }
        .provider-card > .page-heading { align-items: flex-start; }
        .provider-card .field { min-width: 0; }
        .provider-card input, .provider-card select, .provider-card textarea { width: 100%; }
        @media (max-width: 980px) {
            .sidebar { width: 224px; }
            .workspace { margin-left: 224px; }
            .topbar { padding-right: 24px; padding-left: 24px; }
            main { padding-right: 24px; padding-left: 24px; }
        }
        @media (max-width: 720px) {
            .sidebar { width: 100%; padding: 9px 12px 6px; background: #142421; }
            .brand { min-height: 40px; margin: 0; padding: 0 4px 7px; border: 0; }
            .brand-mark { width: 32px; height: 32px; border-radius: 10px; }
            .brand-copy strong { font-size: 13px; }
            .nav-scroll { gap: 5px; margin-right: -12px; margin-left: -12px; padding: 3px 12px 7px; }
            .nav-link { min-height: 35px; border-radius: 8px; }
            .nav-link { gap: 7px; }
            .nav-icon { display: grid; width: 17px; height: 17px; }
            .nav-icon svg { width: 15px; height: 15px; }
            .workspace { margin-left: 0; }
            .topbar { min-height: 62px; padding: 10px 16px; }
            main { padding: 23px 16px 38px; }
            .panel { padding: 16px; border-radius: 12px; }
            .metrics-grid, .module-grid { grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 11px; }
            .metric-card, .module-card { min-height: 108px; padding: 15px; border-radius: 12px; }
            .metric-card strong { font-size: 27px; }
            .provider-grid { grid-template-columns: 1fr; }
            .table-search { align-items: flex-start; flex-direction: column; gap: 7px; }
            .table-search-control { width: 100%; }
            .table-search-count { padding-left: 2px; }
            .dashboard-hero { min-height: 235px; padding: 25px 22px; border-radius: 15px; }
            .hero-illustration { width: 40%; opacity: .62; }
            .hero-shutter { right: 42px; width: 95px; height: 126px; padding: 16px 12px; }
            .hero-signal { right: 21px; }
            .hero-orbit { right: -35px; }
            .hero-orbit-two { right: -75px; }
        }
        @media (max-width: 460px) {
            .dashboard-hero { min-height: 260px; align-items: flex-start; padding: 23px 18px; }
            .dashboard-hero > div:first-child { max-width: 100%; }
            .dashboard-hero h1 { max-width: 260px; font-size: 27px; }
            .dashboard-hero p { max-width: 270px; font-size: 12px; }
            .hero-illustration { right: -60px; bottom: -40px; top: auto; width: 170px; height: 170px; opacity: .32; }
            .hero-shutter { display: none; }
            .hero-signal { display: none; }
        }
        .dashboard-hero { min-height: 294px; align-items: center; margin: 0 0 31px; padding: 34px 38px; border: 0; border-radius: 21px; background: radial-gradient(ellipse at 82% 10%, rgba(53,193,143,.19), transparent 31%), linear-gradient(115deg, #18372f 0%, #15352d 51%, #1d4436 100%); box-shadow: 0 18px 42px rgba(20,57,43,.13); }
        .dashboard-hero-copy { position: relative; z-index: 2; max-width: 600px; }
        .dashboard-kicker { display: flex; align-items: center; gap: 9px; color: #8ee0bc; font-size: 9px; letter-spacing: 1.5px; }
        .status-pulse { position: relative; width: 7px; height: 7px; border-radius: 50%; background: #51d89c; box-shadow: 0 0 0 4px rgba(81,216,156,.14); }
        .dashboard-hero h1 { margin: 18px 0 12px; color: #fff; font-size: clamp(32px, 3.3vw, 45px); font-weight: 750; letter-spacing: -1.8px; line-height: 1.09; }
        .dashboard-hero-copy > p { max-width: 440px; margin: 0; color: #bed0c7; font-size: 13px; line-height: 1.7; }
        .hero-actions { gap: 10px; margin-top: 25px; }
        .hero-primary { border-color: #f0fff7; background: #f4fff8; color: #176b4e; box-shadow: 0 5px 14px rgba(4,26,19,.12); }
        .hero-primary:hover { border-color: #fff; background: #fff; color: #0d593f; }
        .hero-primary svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-width: 2; }
        .hero-secondary { border-color: rgba(255,255,255,.2); background: rgba(255,255,255,.07); color: #f2faf5; box-shadow: none; }
        .hero-secondary:hover { border-color: rgba(255,255,255,.42); background: rgba(255,255,255,.13); color: #fff; }
        .dashboard-hero-aside { position: relative; z-index: 2; width: min(100%, 330px); }
        .hero-date { margin: 0 2px 9px 0; color: #bbd0c6; font-size: 10px; font-weight: 600; letter-spacing: .35px; text-align: right; }
        .fleet-card { padding: 19px 20px 14px; border: 1px solid rgba(255,255,255,.58); border-radius: 16px; background: rgba(248,255,250,.96); box-shadow: 0 16px 38px rgba(8,31,22,.17); }
        .fleet-card-heading { display: flex; align-items: center; gap: 10px; }
        .fleet-icon { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 10px; background: #e4f5eb; color: #17815c; }
        .fleet-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .fleet-card-heading strong, .fleet-card-heading small { display: block; }
        .fleet-card-heading strong { color: #254238; font-size: 12px; }
        .fleet-card-heading small { margin-top: 2px; color: #82918a; font-size: 10px; }
        .fleet-health { display: flex; align-items: center; justify-content: space-between; gap: 17px; padding: 18px 2px 16px; }
        .fleet-ring { display: grid; width: 102px; height: 102px; flex: 0 0 auto; place-items: center; border-radius: 50%; background: conic-gradient(#2ba878 0 var(--health), #e9f0eb var(--health) 100%); }
        .fleet-ring::before { grid-area: 1/1; width: 78px; height: 78px; border-radius: 50%; background: #f8fcf9; content: ""; }
        .fleet-ring > span { z-index: 1; grid-area: 1/1; display: flex; flex-direction: column; align-items: center; line-height: 1.2; }
        .fleet-ring strong { color: #206d4b; font-size: 21px; letter-spacing: -.7px; }
        .fleet-ring small { margin-top: 3px; color: #7d8c84; font-size: 9px; }
        .fleet-numbers { display: grid; flex: 1; gap: 10px; }
        .fleet-numbers > div { display: grid; grid-template-columns: 8px 1fr auto; align-items: center; gap: 7px; color: #74847b; font-size: 10px; }
        .fleet-numbers strong { color: #2b4338; font-size: 11px; font-weight: 700; }
        .health-dot { width: 7px; height: 7px; border-radius: 50%; }
        .health-dot.online { background: #24a574; }
        .health-dot.offline { background: #e36b63; }
        .health-dot.unknown { background: #b3c0b8; }
        .fleet-link { display: flex; justify-content: space-between; padding-top: 12px; border-top: 1px solid #e9f0eb; color: #27805c; font-size: 10px; font-weight: 700; text-decoration: none; }
        .fleet-link:hover { color: #125d40; }
        .hero-decoration { position: absolute; top: 0; right: 27%; width: 290px; height: 100%; overflow: hidden; opacity: .42; pointer-events: none; }
        .hero-decoration span { position: absolute; right: -95px; bottom: -225px; width: 400px; height: 400px; border: 1px solid rgba(140,229,187,.2); border-radius: 50%; }
        .hero-decoration span:nth-child(2) { right: -58px; bottom: -188px; width: 325px; height: 325px; }
        .hero-decoration span:nth-child(3) { right: -18px; bottom: -148px; width: 245px; height: 245px; }
        .dashboard-section { margin-top: 31px; }
        .dashboard-section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 14px; }
        .dashboard-section-heading h2, .insight-heading h2 { margin: 4px 0 0; color: #203a31; font-size: 18px; font-weight: 720; letter-spacing: -.45px; }
        .section-eyebrow { color: #87968d; font-size: 9px; font-weight: 800; letter-spacing: 1.25px; }
        .section-caption { padding-bottom: 3px; color: #89968f; font-size: 10px; }
        .overview-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 13px; }
        .overview-card { position: relative; display: flex; min-width: 0; min-height: 125px; gap: 13px; padding: 18px 16px; border: 1px solid #e6ece8; border-radius: 14px; background: #fff; color: inherit; text-decoration: none; box-shadow: 0 3px 12px rgba(25,52,44,.025); transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease; }
        .overview-card:hover { transform: translateY(-2px); border-color: #c6e4d4; box-shadow: 0 10px 23px rgba(25,52,44,.07); }
        .overview-icon { display: grid; width: 37px; height: 37px; flex: 0 0 auto; place-items: center; border-radius: 11px; }
        .overview-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .icon-green { background: #e8f6ee; color: #18865d; }
        .icon-amber { background: #fff4df; color: #b47a1d; }
        .icon-blue { background: #eaf2ff; color: #4a76bc; }
        .icon-violet { background: #f1edff; color: #7661b2; }
        .icon-red { background: #fff0ee; color: #c44f48; }
        .overview-copy { display: flex; min-width: 0; flex-direction: column; align-items: flex-start; }
        .overview-copy > span { color: #6f8178; font-size: 10px; font-weight: 600; line-height: 1.35; }
        .overview-copy > strong { margin-top: 7px; color: #203c32; font-size: 27px; font-weight: 760; letter-spacing: -1px; line-height: 1; }
        .overview-copy > small { margin-top: 8px; color: #89958f; font-size: 9px; line-height: 1.4; }
        .overview-arrow { position: absolute; top: 16px; right: 14px; color: #9ba9a1; font-size: 14px; opacity: 0; transition: opacity .15s ease, transform .15s ease; }
        .overview-card:hover .overview-arrow { transform: translate(2px,-2px); opacity: 1; }
        .dashboard-insights { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(290px, .9fr); gap: 15px; margin-top: 17px; }
        .insight-panel { min-width: 0; padding: 21px 22px; border: 1px solid #e6ece8; border-radius: 15px; background: #fff; box-shadow: 0 3px 12px rgba(25,52,44,.025); }
        .insight-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; }
        .insight-heading h2 { margin-top: 4px; }
        .insight-heading p { margin: 5px 0 0; color: #84928b; font-size: 10px; }
        .quiet-link { margin-top: 5px; color: #24805c; font-size: 10px; font-weight: 700; text-decoration: none; white-space: nowrap; }
        .quiet-link:hover { color: #125d40; }
        .trend-summary { display: flex; align-items: baseline; gap: 7px; margin-top: 20px; }
        .trend-summary > strong { color: #203c32; font-size: 27px; letter-spacing: -.8px; }
        .trend-summary > span { color: #7f8e86; font-size: 10px; }
        .trend-summary .trend-today { margin-left: auto; padding: 5px 9px; border-radius: 999px; background: #edf7f0; color: #25805c; font-size: 9px; font-weight: 700; }
        .trend-chart { position: relative; margin: 9px 0 0 32px; }
        .trend-chart > svg { display: block; width: 100%; height: 126px; overflow: visible; }
        .trend-grid-line { fill: none; stroke: #eef2ef; stroke-dasharray: 3 5; stroke-width: 1; }
        .trend-area { fill: url(#alert-area-fill); }
        .trend-line { fill: none; stroke: #239c73; stroke-linecap: round; stroke-linejoin: round; stroke-width: 2.6; vector-effect: non-scaling-stroke; }
        .trend-point { fill: #fff; stroke: #239c73; stroke-width: 2; vector-effect: non-scaling-stroke; }
        .trend-y-labels { position: absolute; top: 0; bottom: 20px; left: -30px; display: flex; flex-direction: column; justify-content: space-between; color: #9aa69f; font-size: 8px; }
        .trend-x-labels { display: grid; grid-template-columns: repeat(7, 1fr); margin-top: -4px; text-align: center; }
        .trend-x-labels span { display: flex; flex-direction: column; gap: 3px; }
        .trend-x-labels strong { color: #65766e; font-size: 9px; font-weight: 650; }
        .trend-x-labels small { color: #9aa69f; font-size: 8px; }
        .quick-panel { padding-bottom: 12px; }
        .quick-links { display: grid; margin-top: 15px; }
        .quick-links a { display: flex; align-items: center; gap: 10px; min-width: 0; padding: 12px 0; border-top: 1px solid #edf1ee; color: inherit; text-decoration: none; }
        .quick-links a:hover .quick-link-icon { background: #dff3e8; color: #08795e; }
        .quick-link-icon { display: grid; width: 33px; height: 33px; flex: 0 0 auto; place-items: center; border-radius: 10px; background: #f0f6f2; color: #388263; transition: background .15s ease, color .15s ease; }
        .quick-link-icon svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .quick-links a > span:nth-child(2) { display: flex; min-width: 0; flex: 1; flex-direction: column; gap: 3px; }
        .quick-links strong { color: #31493d; font-size: 10px; font-weight: 700; }
        .quick-links small { overflow: hidden; color: #84928b; font-size: 9px; text-overflow: ellipsis; white-space: nowrap; }
        .quick-chevron { color: #a1ada6; font-size: 13px; transition: transform .15s ease; }
        .quick-links a:hover .quick-chevron { transform: translateX(3px); color: #16805b; }
        .platform-section { margin-top: 31px; }
        .workspace-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); overflow: hidden; border: 1px solid #e6ece8; border-radius: 14px; background: #fff; }
        .workspace-strip > a { display: flex; align-items: center; gap: 11px; min-width: 0; padding: 16px 18px; color: inherit; text-decoration: none; transition: background .15s ease; }
        .workspace-strip > a + a { border-left: 1px solid #edf1ee; }
        .workspace-strip > a:hover { background: #f7fbf8; }
        .workspace-icon { display: grid; width: 34px; height: 34px; flex: 0 0 auto; place-items: center; border-radius: 10px; background: #edf6f0; color: #33805d; }
        .workspace-icon svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.6; }
        .workspace-strip > a > span:nth-child(2) { display: flex; min-width: 0; flex: 1; flex-direction: column; gap: 3px; }
        .workspace-strip small { overflow: hidden; color: #809087; font-size: 9px; text-overflow: ellipsis; white-space: nowrap; }
        .workspace-strip strong { color: #263f34; font-size: 16px; line-height: 1; }
        .workspace-arrow { color: #9baa9f; font-size: 12px; }
        .recent-alerts-section { margin-top: 34px; }
        .section-description { margin: 5px 0 0; color: #84928b; font-size: 11px; }
        .recent-alerts-section .page-heading > .button { white-space: nowrap; }
        .table-panel { padding: 14px 19px 7px; }
        .alert-row-title { display: inline-flex; align-items: center; gap: 8px; font-weight: 650; }
        .alert-indicator { width: 7px; height: 7px; flex: 0 0 auto; border-radius: 50%; background: #86a897; }
        .alert-indicator.critical, .alert-indicator.high { background: #df655c; box-shadow: 0 0 0 3px rgba(223,101,92,.12); }
        .alert-indicator.warning, .alert-indicator.medium { background: #dfa83d; box-shadow: 0 0 0 3px rgba(223,168,61,.12); }
        .severity-label { color: #65766e; font-size: 10px; font-weight: 650; }
        .severity-label.critical, .severity-label.high { color: #b84942; }
        .severity-label.warning, .severity-label.medium { color: #9b6e1b; }
        .admin-dashboard-workspace { display: grid; grid-template-columns: minmax(230px, 260px) minmax(0, 1fr); align-items: start; gap: 20px; }
        .admin-dashboard-workspace[data-collapsed="true"] { grid-template-columns: minmax(0, 1fr); }
        .admin-dashboard-main { min-width: 0; }
        .admin-user-tree { position: sticky; top: 96px; max-height: calc(100vh - 116px); overflow-y: auto; padding: 16px 13px 12px; border: 1px solid #e2ebe5; border-radius: 15px; background: rgba(255,255,255,.96); box-shadow: 0 6px 22px rgba(25,52,44,.045); scrollbar-color: #c6d9ce transparent; scrollbar-width: thin; }
        .admin-user-tree[hidden] { display: none; }
        .tree-toggle-row { display: flex; justify-content: flex-end; margin: 0 0 12px; }
        .admin-tree-heading { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .admin-tree-heading h2 { margin: 3px 0 0; color: #203c32; font-size: 16px; font-weight: 740; }
        .admin-tree-total { min-width: 28px; padding: 4px 8px; border-radius: 999px; background: #edf7f1; color: #237456; font-size: 10px; font-weight: 750; text-align: center; }
        .admin-tree-caption { margin: 9px 0 13px; color: #7b8982; font-size: 10px; line-height: 1.5; }
        .admin-tree-list { display: grid; gap: 4px; }
        .admin-tree-node { min-width: 0; }
        .admin-tree-node.nested { margin-left: 10px; padding-left: 8px; border-left: 1px solid #dce9e1; }
        .admin-tree-node details { min-width: 0; }
        .admin-tree-node summary { display: flex; min-height: 36px; align-items: center; justify-content: space-between; gap: 6px; padding: 6px 7px; border-radius: 8px; color: #35483f; cursor: pointer; list-style: none; }
        .admin-tree-node summary::-webkit-details-marker { display: none; }
        .admin-tree-node summary:hover, .admin-tree-node details[open] > summary { background: #f1f8f3; }
        .admin-tree-account { display: flex; min-width: 0; align-items: center; gap: 6px; }
        .admin-tree-account svg { width: 14px; height: 14px; flex: 0 0 auto; fill: none; stroke: #338267; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .admin-tree-account strong { overflow: hidden; font-size: 9px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .admin-tree-count { flex: 0 0 auto; min-width: 19px; padding: 2px 5px; border-radius: 999px; background: #edf2ee; color: #5e7067; font-size: 9px; font-weight: 700; text-align: center; }
        .admin-tree-role { display: flex; align-items: center; gap: 6px; margin: 0 0 3px 27px; color: #829088; font-size: 9px; }
        .admin-tree-role .badge { padding: 2px 6px; font-size: 8px; }
        .admin-tree-content { padding: 0 3px 6px 7px; }
        .admin-tree-email, .admin-tree-company { display: block; overflow: hidden; margin: 0 0 5px 3px; color: #829088; font-size: 8px; text-overflow: ellipsis; white-space: nowrap; }
        .admin-tree-company { color: #60766a; }
        .admin-tree-devices { display: grid; gap: 2px; }
        .admin-tree-device { display: flex; min-width: 0; align-items: center; justify-content: space-between; gap: 4px; padding: 5px 6px; border-radius: 6px; background: #fafcfb; }
        .admin-tree-device-name { display: flex; min-width: 0; align-items: center; gap: 5px; }
        .admin-tree-device-name strong { overflow: hidden; color: #495d52; font-size: 8px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .admin-tree-device small { flex: 0 0 auto; color: #829088; font-size: 8px; }
        .admin-tree-device-dot { width: 6px; height: 6px; flex: 0 0 auto; border-radius: 50%; background: #9aa9a0; }
        .admin-tree-device-dot.online { background: #25a574; }
        .admin-tree-device-dot.offline { background: #dc5c5c; }
        .admin-tree-empty { margin: 4px; color: #89968f; font-size: 10px; }
        .admin-tree-link { display: flex; justify-content: space-between; margin-top: 10px; padding: 10px 5px 2px; border-top: 1px solid #e9f0eb; color: #27805c; font-size: 10px; font-weight: 700; text-decoration: none; }
        .admin-tree-link:hover { color: #125d40; }
        @media (max-width: 1320px) {
            .admin-dashboard-workspace { grid-template-columns: minmax(0, 1fr); }
            .admin-user-tree { position: static; max-height: 390px; }
        }
        @media (max-width: 1100px) {
            .dashboard-hero { padding: 30px; }
            .dashboard-hero-aside { width: 305px; }
            .overview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .dashboard-insights { grid-template-columns: minmax(0, 1.4fr) minmax(275px, 1fr); }
            .workspace-strip > a { padding-right: 12px; padding-left: 12px; }
        }
        @media (max-width: 820px) {
            .dashboard-hero { align-items: flex-start; flex-direction: column; gap: 24px; }
            .dashboard-hero-aside { width: min(100%, 390px); }
            .hero-date { text-align: left; }
            .hero-decoration { right: 3%; }
            .dashboard-insights { grid-template-columns: 1fr; }
            .workspace-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .workspace-strip > a:nth-child(3) { border-top: 1px solid #edf1ee; border-left: 0; }
            .workspace-strip > a:nth-child(4) { border-top: 1px solid #edf1ee; }
        }
        @media (max-width: 720px) {
            .dashboard-hero { min-height: 0; margin-bottom: 25px; padding: 26px 21px; border-radius: 16px; }
            .dashboard-hero h1 { font-size: 33px; }
            .dashboard-hero-copy > p { max-width: 340px; }
            .hero-decoration { right: -70px; opacity: .26; }
            .dashboard-section { margin-top: 25px; }
            .dashboard-section-heading { align-items: flex-start; flex-direction: column; gap: 6px; }
            .section-caption { padding: 0; }
            .overview-grid { gap: 10px; }
            .overview-card { min-height: 113px; gap: 10px; padding: 14px 12px; border-radius: 12px; }
            .overview-icon { width: 32px; height: 32px; border-radius: 9px; }
            .overview-copy > strong { font-size: 24px; }
            .overview-copy > small { font-size: 8px; }
            .dashboard-insights { gap: 11px; margin-top: 13px; }
            .insight-panel { padding: 17px 15px; border-radius: 12px; }
            .workspace-strip { border-radius: 12px; }
            .workspace-strip > a { padding: 13px 10px; }
            .recent-alerts-section .dashboard-section-heading { align-items: flex-start; }
            .recent-alerts-section .page-heading > .button { align-self: flex-start; }
            .table-panel { padding: 12px; }
        }
        @media (max-width: 420px) {
            .dashboard-hero h1 { font-size: 29px; }
            .overview-card { flex-direction: column; }
            .overview-copy > strong { margin-top: 5px; }
            .overview-copy > small { margin-top: 5px; }
            .overview-arrow { opacity: 1; }
            .workspace-strip > a { gap: 8px; }
            .workspace-icon { width: 29px; height: 29px; }
            .workspace-strip strong { font-size: 14px; }
        }
        .main-sidebar-toggle { display: inline-grid; width: 38px; height: 38px; flex: 0 0 auto; place-items: center; padding: 0; border: 1px solid #e1e8e3; border-radius: 10px; background: #fff; color: #436055; }
        .main-sidebar-toggle svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-width: 1.8; }
        .topbar-title-group { display: flex; min-width: 0; align-items: center; gap: 13px; }
        .app-shell.nav-collapsed .sidebar { width: 76px; padding-right: 10px; padding-left: 10px; }
        .app-shell.nav-collapsed .workspace { margin-left: 76px; }
        .app-shell.nav-collapsed .brand { justify-content: center; padding-right: 0; padding-left: 0; }
        .app-shell.nav-collapsed .brand-copy,
        .app-shell.nav-collapsed .nav-label,
        .app-shell.nav-collapsed .nav-link-label,
        .app-shell.nav-collapsed .sidebar-foot { display: none; }
        .app-shell.nav-collapsed .nav-link { justify-content: center; padding-right: 0; padding-left: 0; }
        .app-shell.nav-collapsed .nav-icon { width: 22px; }
        .app-shell.nav-collapsed .nav-link[title] { position: relative; }
        .sidebar-backdrop { display: none; }
        @media (max-width: 720px) {
            .sidebar { position: fixed; inset: 0 auto 0 0; z-index: 40; width: min(300px, 86vw); height: 100vh; height: 100dvh; padding: 22px 14px 16px; overflow-y: auto; transform: translateX(-105%); transition: transform .2s ease; box-shadow: 14px 0 34px rgba(10,24,19,.2); }
            .app-shell.nav-open .sidebar { transform: translateX(0); }
            .app-shell.nav-collapsed .sidebar { width: min(300px, 86vw); padding-right: 14px; padding-left: 14px; }
            .app-shell.nav-collapsed .brand { justify-content: flex-start; padding-right: 10px; padding-left: 10px; }
            .app-shell.nav-collapsed .brand-copy,
            .app-shell.nav-collapsed .nav-label,
            .app-shell.nav-collapsed .nav-link-label,
            .app-shell.nav-collapsed .sidebar-foot { display: initial; }
            .app-shell.nav-collapsed .brand-copy { display: flex; }
            .app-shell.nav-collapsed .nav-label,
            .app-shell.nav-collapsed .sidebar-foot { display: block; }
            .app-shell.nav-collapsed .nav-link { justify-content: flex-start; padding-right: 11px; padding-left: 11px; }
            .sidebar-backdrop:not([hidden]) { position: fixed; inset: 0; z-index: 30; display: block; width: 100%; height: 100%; border: 0; border-radius: 0; background: rgba(16,29,24,.46); }
            .topbar-title-group { gap: 9px; }
            .main-sidebar-toggle { width: 36px; height: 36px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .sidebar { transition: none; }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="admin-main-sidebar" aria-label="Admin navigation">
            <a class="brand" href="{{ route('master-admin.dashboard') }}">
                <span class="brand-mark" aria-hidden="true">SG</span>
                <span class="brand-copy"><strong>Shop Guard</strong><small>Security platform</small></span>
            </a>
            <nav class="nav-scroll">
                <div class="nav-label">Overview</div>
                <a class="nav-link {{ request()->routeIs('master-admin.dashboard') ? 'active' : '' }}" href="{{ route('master-admin.dashboard') }}" title="Dashboard"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg></span><span class="nav-link-label">Dashboard</span></a>

                <div class="nav-label">Workspace</div>
                <a class="nav-link {{ request()->routeIs('master-admin.companies.*') ? 'active' : '' }}" href="{{ route('master-admin.companies.index') }}" title="Companies"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V7l8-4 8 4v13"/><path d="M2.5 20h19M8 9h.01M12 9h.01M16 9h.01M8 13h.01M12 13h.01M16 13h.01M10 20v-3h4v3"/></svg></span><span class="nav-link-label">Companies</span></a>
                <a class="nav-link {{ request()->routeIs('master-admin.users.*') ? 'active' : '' }}" href="{{ route('master-admin.users.index') }}" title="Users"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.4-3.5 2.7-5.5 6.5-5.5s6.1 2 6.5 5.5M16 5.2a3.5 3.5 0 0 1 0 6.7M18 14.7c2.1.7 3.3 2.4 3.5 5.3"/></svg></span><span class="nav-link-label">Users</span></a>
                <a class="nav-link {{ request()->routeIs('master-admin.roles.*') ? 'active' : '' }}" href="{{ route('master-admin.roles.index') }}" title="Roles &amp; access"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.4 8.3-8 10-4.6-1.7-8-5-8-10V6l8-3Z"/><path d="m9 12 2 2 4-4"/></svg></span><span class="nav-link-label">Roles &amp; access</span></a>
                <a class="nav-link {{ request()->routeIs('master-admin.shops.*') ? 'active' : '' }}" href="{{ route('master-admin.shops.index') }}" title="Shops"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10v10h18V10M2 10l2-6h16l2 6M9 20v-6h6v6"/><path d="M2 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 2 0"/></svg></span><span class="nav-link-label">Shops</span></a>
                <a class="nav-link {{ request()->routeIs('master-admin.devices.*') ? 'active' : '' }}" href="{{ route('master-admin.devices.index') }}" title="Devices"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M9 7h6M9 11h6M9 15h.01M12 15h.01M15 15h.01M9 18h6"/></svg></span><span class="nav-link-label">Devices</span></a>
                <a class="nav-link {{ request()->routeIs('master-admin.configurations.*', 'master-admin.schedules.*') ? 'active' : '' }}" href="{{ route('master-admin.configurations.index') }}" title="Monitoring"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5M4 19h16"/><path d="m6 15 4-4 3 2 6-7"/><circle cx="19" cy="6" r="1.5"/></svg></span><span class="nav-link-label">Monitoring</span></a>

                <div class="nav-label">Operations</div>
                <a class="nav-link {{ request()->routeIs('master-admin.alerts.*') ? 'active' : '' }}" href="{{ route('master-admin.alerts.index') }}" title="Alerts"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg></span><span class="nav-link-label">Alerts</span></a>
                <a class="nav-link {{ request()->routeIs('master-admin.notification-rules.*') ? 'active' : '' }}" href="{{ route('master-admin.notification-rules.index') }}" title="Notification rules"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 9h8M8 12h5"/></svg></span><span class="nav-link-label">Notification rules</span></a>
                <a class="nav-link {{ request()->routeIs('master-admin.notification-providers.*') ? 'active' : '' }}" href="{{ route('master-admin.notification-providers.index') }}" title="Provider settings"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z"/><path d="m4 7 8 6 8-6M7 10l-3 3M17 10l3 3"/></svg></span><span class="nav-link-label">Provider settings</span></a>
                <a class="nav-link {{ request()->routeIs('master-admin.operations.index') ? 'active' : '' }}" href="{{ route('master-admin.operations.index', 'inventory') }}" title="Operations &amp; audit"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/></svg></span><span class="nav-link-label">Operations &amp; audit</span></a>
            </nav>
            <div class="sidebar-foot">Secure platform administration</div>
        </aside>
        <button type="button" class="sidebar-backdrop" id="main-sidebar-backdrop" aria-label="Close main navigation" hidden></button>
        <div class="workspace">
            <header class="topbar">
                <div class="topbar-title-group">
                    <button type="button" class="main-sidebar-toggle" id="main-sidebar-toggle" aria-controls="admin-main-sidebar" aria-expanded="true" aria-label="Collapse main navigation" title="Collapse main navigation">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path><path d="M9 6v12"></path></svg>
                    </button>
                    <div class="topbar-title">
                        <div class="breadcrumb">Shop Guard <span aria-hidden="true">/</span> Super Admin</div>
                        <h1>@yield('title', 'Dashboard')</h1>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a class="admin-profile admin-profile-link" href="{{ route('master-admin.profile') }}">
                        <span class="avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->full_name ?? 'A', 0, 1)) }}</span>
                        <span class="admin-copy"><strong>{{ auth()->user()->full_name ?? 'Administrator' }}</strong><small>Super Administrator</small></span>
                    </a>
                    <form class="logout-form" method="POST" action="{{ route('master-admin.logout') }}">
                        @csrf
                        <button type="submit" class="button secondary">Sign out</button>
                    </form>
                </div>
            </header>
            <main>
                @if (session('status'))
                    <div class="alert" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var panel = document.getElementById(button.getAttribute('aria-controls'));
                var layout = panel ? panel.closest('[data-sidebar-layout]') : null;
                if (!panel || !layout) {
                    return;
                }
                var expanded = button.getAttribute('aria-expanded') === 'true';
                panel.hidden = expanded;
                layout.dataset.collapsed = expanded ? 'true' : 'false';
                button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                button.textContent = expanded ? 'Show user tree' : 'Hide user tree';
            });
        });
    </script>
    <script>
        (function () {
            var shell = document.querySelector('.app-shell');
            var toggle = document.getElementById('main-sidebar-toggle');
            var backdrop = document.getElementById('main-sidebar-backdrop');
            var sidebar = document.getElementById('admin-main-sidebar');
            var mobile = window.matchMedia('(max-width: 720px)');

            if (!shell || !toggle || !backdrop || !sidebar) {
                return;
            }

            var collapsedPreference = 'shopguard-admin-nav-collapsed';

            function updateButton() {
                var expanded = mobile.matches
                    ? shell.classList.contains('nav-open')
                    : !shell.classList.contains('nav-collapsed');
                var label = mobile.matches
                    ? (expanded ? 'Close main navigation' : 'Open main navigation')
                    : (expanded ? 'Collapse main navigation' : 'Expand main navigation');
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                toggle.setAttribute('aria-label', label);
                toggle.setAttribute('title', label);
            }

            function closeMobileNavigation() {
                shell.classList.remove('nav-open');
                backdrop.hidden = true;
                updateButton();
            }

            try {
                if (localStorage.getItem(collapsedPreference) === 'true') {
                    shell.classList.add('nav-collapsed');
                }
            } catch (error) {
                // The sidebar remains usable when browser storage is unavailable.
            }

            updateButton();

            toggle.addEventListener('click', function () {
                if (mobile.matches) {
                    var isOpen = shell.classList.toggle('nav-open');
                    backdrop.hidden = !isOpen;
                } else {
                    var isCollapsed = shell.classList.toggle('nav-collapsed');
                    try {
                        localStorage.setItem(collapsedPreference, isCollapsed ? 'true' : 'false');
                    } catch (error) {
                        // The current page can still be toggled without persisting the preference.
                    }
                }
                updateButton();
            });

            backdrop.addEventListener('click', closeMobileNavigation);
            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (mobile.matches) {
                        closeMobileNavigation();
                    }
                });
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && shell.classList.contains('nav-open')) {
                    closeMobileNavigation();
                    toggle.focus();
                }
            });
            mobile.addEventListener('change', function () {
                shell.classList.remove('nav-open');
                backdrop.hidden = true;
                updateButton();
            });
        })();
    </script>
</body>
</html>
