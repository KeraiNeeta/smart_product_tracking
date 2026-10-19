<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111c2e">
    <title>@yield('title', 'Portal') | Shop Guard</title>
    <style>
        * { box-sizing: border-box; }
        body { min-width: 320px; margin: 0; background: #f4f7fb; color: #1d293b; font: 14px/1.5 Inter, "Segoe UI", Arial, sans-serif; }
        .topbar { display: flex; min-height: 70px; align-items: center; justify-content: space-between; gap: 16px; padding: 12px max(22px, calc((100vw - 1220px) / 2)); border-bottom: 1px solid #e8edf3; background: #fff; }
        .brand { display: flex; align-items: center; gap: 11px; color: #1b293d; text-decoration: none; }
        .brand-mark { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 11px; background: #19a889; color: #fff; font-size: 13px; font-weight: 800; }
        .brand strong, .brand small { display: block; line-height: 1.3; }
        .brand strong { font-size: 14px; }
        .brand small { margin-top: 3px; color: #7b8799; font-size: 10px; }
        .top-actions { display: flex; align-items: center; gap: 18px; }
        .profile { color: #344258; font-size: 12px; text-align: right; }
        .profile strong, .profile small { display: block; }
        .profile small { margin-top: 3px; color: #78869a; font-size: 10px; }
        nav { display: flex; align-items: center; gap: 7px; }
        nav a { padding: 8px 10px; border-radius: 7px; color: #526177; font-size: 12px; text-decoration: none; }
        nav a.active, nav a:hover { background: #e9f7f3; color: #147b65; }
        main { width: min(1220px, 100%); margin: 0 auto; padding: 30px 22px 48px; }
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 15px; margin-bottom: 22px; }
        h1 { margin: 0; color: #1b293d; font-size: 26px; letter-spacing: -.6px; }
        h2 { margin: 0; font-size: 18px; }
        .muted { color: #778499; }
        .page-heading p { margin: 6px 0 0; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 25px; }
        .card { padding: 18px; border: 1px solid #edf0f5; border-radius: 11px; background: #fff; box-shadow: 0 6px 20px rgba(22,34,53,.04); }
        .metric span { display: block; color: #778499; font-size: 12px; }
        .metric strong { display: block; margin-top: 8px; color: #157d68; font-size: 27px; }
        .section-title { margin: 26px 0 12px; }
        .dealer-workspace { display: grid; grid-template-columns: minmax(240px, 280px) minmax(0, 1fr); align-items: start; gap: 21px; }
        .dealer-workspace[data-collapsed="true"] { grid-template-columns: minmax(0, 1fr); }
        .dealer-sidebar[hidden] { display: none; }
        .dealer-sidebar { position: sticky; top: 96px; max-height: calc(100vh - 116px); overflow-y: auto; padding: 17px 14px; border: 1px solid #e2ebe5; border-radius: 15px; background: rgba(255,255,255,.94); box-shadow: 0 6px 22px rgba(25,52,44,.045); scrollbar-color: #c6d9ce transparent; scrollbar-width: thin; }
        .dealer-sidebar-heading { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .dealer-sidebar-heading h2 { margin: 3px 0 0; color: #203c32; font-size: 16px; font-weight: 740; }
        .dealer-sidebar-caption { margin: 9px 0 14px; color: #7b8982; font-size: 10px; line-height: 1.5; }
        .network-count { padding: 4px 8px; border-radius: 999px; background: #edf7f1; color: #237456; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .dealer-tree { display: grid; gap: 5px; }
        .network-account { min-width: 0; }
        .network-account.nested { margin-left: 12px; padding-left: 9px; border-left: 1px solid #dce9e1; }
        .network-account details { min-width: 0; }
        .network-account summary { display: flex; min-height: 38px; align-items: center; justify-content: space-between; gap: 7px; padding: 7px 8px; border-radius: 8px; color: #35483f; cursor: pointer; list-style: none; }
        .network-account summary::-webkit-details-marker { display: none; }
        .network-account summary:hover, .network-account details[open] > summary { background: #f1f8f3; }
        .network-account-label { display: flex; min-width: 0; align-items: center; gap: 7px; }
        .network-account-label svg { width: 14px; height: 14px; flex: 0 0 auto; fill: none; stroke: #338267; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .network-account-label strong { overflow: hidden; font-size: 10px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .network-account-role { margin: 1px 0 3px 29px; color: #829088; font-size: 9px; }
        .network-account-count { flex: 0 0 auto; min-width: 20px; padding: 2px 6px; border-radius: 999px; background: #edf2ee; color: #5e7067; font-size: 9px; font-weight: 700; text-align: center; }
        .network-account-content { padding: 0 4px 7px 8px; }
        .network-account-email { display: block; overflow: hidden; margin: 0 0 7px 4px; color: #829088; font-size: 9px; text-overflow: ellipsis; white-space: nowrap; }
        .network-device-list { display: grid; gap: 3px; }
        .network-device { display: flex; min-width: 0; align-items: center; justify-content: space-between; gap: 5px; padding: 6px 7px; border-radius: 7px; background: #fafcfb; }
        .network-device:hover { background: #f2f8f4; }
        .network-device-name { display: flex; min-width: 0; align-items: center; gap: 6px; }
        .network-device-dot { width: 6px; height: 6px; flex: 0 0 auto; border-radius: 50%; background: #9aa9a0; }
        .network-device-dot.online { background: #25a574; }
        .network-device-dot.offline { background: #dc5c5c; }
        .network-device-name strong { overflow: hidden; color: #495d52; font-size: 9px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .network-device small { flex: 0 0 auto; color: #829088; font-size: 8px; }
        .network-empty { margin: 8px 4px; color: #89968f; font-size: 10px; }
        .dealer-main { min-width: 0; }
        .table-wrap { overflow-x: auto; padding: 8px 18px; border: 1px solid #edf0f5; border-radius: 11px; background: #fff; box-shadow: 0 6px 20px rgba(22,34,53,.04); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 13px; border-bottom: 1px solid #edf0f4; text-align: left; white-space: nowrap; }
        th { color: #778499; font-size: 10px; letter-spacing: .7px; text-transform: uppercase; }
        td { color: #354258; font-size: 12px; }
        tbody tr:last-child td { border-bottom: 0; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #eef2f7; color: #58677d; font-size: 10px; font-weight: 700; }
        .badge.active, .badge.resolved, .badge.delivered { background: #e4f7ef; color: #13734f; }
        .badge.inactive, .badge.failed, .badge.retired { background: #fff0f0; color: #a6323b; }
        .badge.open, .badge.pending, .badge.queued { background: #fff5df; color: #94620d; }
        .badge.acknowledged { background: #eaf1ff; color: #315ea8; }
        .button, button { display: inline-flex; min-height: 34px; align-items: center; justify-content: center; padding: 8px 12px; border: 1px solid #dce3eb; border-radius: 7px; background: #fff; color: #4e5d72; cursor: pointer; font: inherit; font-size: 11px; font-weight: 600; text-decoration: none; }
        .button:hover, button:hover { border-color: #19a889; color: #147b65; }
        .field { margin-bottom: 18px; }
        .field label { display: block; margin-bottom: 7px; color: #3f4d61; font-size: 12px; font-weight: 650; }
        .field input { width: 100%; max-width: 620px; min-height: 41px; padding: 10px 12px; border: 1px solid #dce2ea; border-radius: 7px; outline: 0; color: #263449; font: inherit; font-size: 13px; }
        .field input:focus { border-color: #59baa4; box-shadow: 0 0 0 3px rgba(25,168,137,.12); }
        .logout-form { margin: 0; }
        .alert { margin-bottom: 18px; padding: 12px 14px; border: 1px solid #f2c8c8; border-radius: 8px; background: #fff1f1; color: #a52c36; }
        .pagination { padding: 14px 0; }
        .pagination nav { justify-content: space-between; }
        .pagination a, .pagination span[aria-current="page"] span { padding: 6px 10px; border: 1px solid #e6ebf2; border-radius: 6px; background: #fff; }
        @media (max-width: 760px) {
            .topbar { align-items: flex-start; flex-wrap: wrap; padding: 12px 16px; }
            .top-actions { width: 100%; justify-content: space-between; }
            main { padding: 22px 15px 35px; }
            .page-heading { align-items: flex-start; flex-direction: column; }
        }
        :root { --ink: #172b2a; --muted: #70817e; --line: #e5ece8; --accent: #0f9b78; --accent-dark: #08795e; }
        body { background: radial-gradient(ellipse at 80% 0, rgba(15,155,120,.06), transparent 34%), #f4f7f4; color: var(--ink); }
        .topbar { position: sticky; top: 0; z-index: 5; min-height: 76px; padding: 12px max(24px, calc((100vw - 1320px) / 2)); border-color: rgba(221,232,226,.85); background: rgba(255,255,255,.9); box-shadow: 0 3px 18px rgba(25,52,44,.035); backdrop-filter: blur(14px); }
        .brand-mark { width: 40px; height: 40px; border: 1px solid rgba(255,255,255,.18); border-radius: 13px; background: linear-gradient(145deg, #18b68d, #08785e); box-shadow: 0 5px 14px rgba(4,120,94,.2); }
        .brand strong { color: #1c3830; font-size: 15px; }
        .brand small { color: #7b8982; }
        .top-actions { gap: 17px; }
        nav { padding: 4px; border: 1px solid #e7eee9; border-radius: 11px; background: #f7faf8; }
        nav a { padding: 8px 12px; border-radius: 8px; color: #667871; font-size: 12px; font-weight: 600; transition: color .16s ease, background .16s ease; }
        nav a.active, nav a:hover { background: #e5f4eb; color: #08795e; }
        .profile { display: flex; align-items: center; gap: 9px; color: #34483e; text-align: left; }
        .profile-avatar { display: grid; width: 36px; height: 36px; place-items: center; border: 1px solid #d7eee5; border-radius: 50%; background: #e7f5ee; color: #08795e; font-size: 12px; font-weight: 800; }
        .notification-bell { position: relative; display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid #e3ebe6; border-radius: 11px; color: #50645b; text-decoration: none; }
        .notification-bell svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
        .notification-count { position: absolute; top: -6px; right: -6px; min-width: 18px; height: 18px; padding: 0 5px; border: 2px solid #fff; border-radius: 999px; background: #e34f56; color: #fff; font-size: 9px; font-weight: 800; line-height: 14px; text-align: center; }
        .profile-link { display: flex; align-items: center; gap: 9px; color: inherit; text-decoration: none; }
        .profile-link:hover .profile-avatar { background: #d9f0e6; }
        .profile strong { max-width: 170px; overflow: hidden; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .profile small { color: #7b8982; font-size: 10px; }
        main { width: min(1320px, 100%); padding: 36px 24px 56px; }
        .page-heading { align-items: center; margin-bottom: 27px; }
        .page-heading h1 { color: #19332d; font-size: clamp(25px, 2.4vw, 32px); font-weight: 750; letter-spacing: -1.1px; }
        .page-heading p { color: #778780; font-size: 13px; }
        .cards { gap: 16px; margin-bottom: 32px; }
        .card { padding: 21px; border-color: #e5ece7; border-radius: 15px; box-shadow: 0 4px 18px rgba(25,52,44,.04); }
        .metric span { color: #71817a; font-size: 12px; }
        .metric strong { color: #08795e; font-size: 31px; letter-spacing: -1px; }
        .section-title { margin: 31px 0 14px; color: #233c35; font-size: 18px; letter-spacing: -.4px; }
        .table-wrap { padding: 12px 17px; border-color: #e5ece7; border-radius: 15px; box-shadow: 0 4px 18px rgba(25,52,44,.04); }
        th { padding-top: 13px; padding-bottom: 13px; background: #f5f8f6; color: #74847d; font-size: 9px; font-weight: 750; letter-spacing: .9px; }
        th:first-child { border-radius: 9px 0 0 9px; }
        th:last-child { border-radius: 0 9px 9px 0; }
        td { padding-top: 14px; padding-bottom: 14px; color: #344640; }
        tbody tr:hover { background: #f8fbf9; }
        .badge { padding: 5px 10px; border: 1px solid #e5ebe7; background: #f2f5f3; color: #5b6d65; font-size: 10px; }
        .badge.active, .badge.resolved, .badge.delivered { border-color: #cfecdd; background: #eaf7ef; color: #19724f; }
        .badge.inactive, .badge.failed, .badge.retired { border-color: #f4d9d8; background: #fff0ef; color: #a3413f; }
        .badge.open, .badge.pending, .badge.queued { border-color: #f0e4bf; background: #fff8e7; color: #916719; }
        .badge.acknowledged { border-color: #dce5fa; background: #eff4ff; color: #3d5f9b; }
        .badge.armed { border-color: #cfecdd; background: #eaf7ef; color: #19724f; }
        .monitoring-controls { display: flex; gap: 6px; }
        .monitoring-controls form { margin: 0; }
        .monitoring-controls button { min-height: 32px; padding: 6px 10px; }
        .monitoring-controls button:disabled { border-color: #e5ebe7; background: #f2f5f3; color: #89968f; cursor: default; }
        .button, button { min-height: 39px; padding: 9px 14px; border-color: #dce5df; border-radius: 9px; color: #40564c; font-size: 12px; font-weight: 650; }
        .button:hover, button:hover { border-color: #38ad89; color: #08795e; }
        .alert { border-color: #f2d4d1; border-radius: 10px; background: #fff2f0; color: #9d3934; }
        .alert-success { border-color: #cfecdd; background: #eaf7ef; color: #19724f; }
        .pagination a, .pagination span[aria-current="page"] span { border-color: #e4ebe6; border-radius: 8px; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
        .table-search { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin: 0 0 14px; }
        .table-search-control { display: flex; width: min(100%, 590px); min-height: 43px; align-items: center; gap: 9px; padding: 3px 4px 3px 12px; border: 1px solid #e0e8e2; border-radius: 10px; background: #fff; }
        .table-search-control:focus-within { border-color: #38ad89; box-shadow: 0 0 0 3px rgba(15,155,120,.09); }
        .table-search-control > svg { width: 17px; height: 17px; flex: 0 0 auto; fill: none; stroke: #899790; stroke-linecap: round; stroke-width: 1.8; }
        .table-search-control input { width: auto; min-width: 0; min-height: 34px; flex: 1; padding: 7px 2px; border: 0; border-radius: 0; outline: 0; box-shadow: none; font-size: 12px; }
        .table-search-control input:focus { border: 0; box-shadow: none; }
        .table-search-control button { min-height: 34px; padding: 7px 13px; border-radius: 7px; background: #0f9b78; color: #fff; font-size: 11px; }
        .table-search-clear { color: #72827b; font-size: 11px; text-decoration: none; }
        .table-search-clear:hover { color: #08795e; text-decoration: underline; }
        .table-search-count { color: #839089; font-size: 11px; white-space: nowrap; }
        .customer-device-card { position: relative; max-width: 900px; overflow: hidden; margin: 8px auto 35px; padding: clamp(22px, 4vw, 38px); border: 1px solid #e1ebe4; border-radius: 22px; background: linear-gradient(145deg, #fff 0%, #f9fcfa 100%); box-shadow: 0 18px 48px rgba(26,68,49,.08); }
        .customer-device-card::before { position: absolute; top: -115px; right: -80px; width: 280px; height: 280px; border: 1px solid rgba(15,155,120,.08); border-radius: 50%; content: ""; pointer-events: none; }
        .customer-device-top, .customer-device-identity, .device-connection, .monitoring-panel, .customer-device-actions { display: flex; align-items: center; }
        .customer-device-top { position: relative; justify-content: space-between; gap: 14px; }
        .customer-eyebrow { color: #71817a; font-size: 10px; font-weight: 800; letter-spacing: 1.3px; }
        .device-connection { gap: 7px; padding: 6px 10px; border: 1px solid #e3ebe6; border-radius: 999px; background: #fff; color: #65766e; font-size: 10px; font-weight: 700; }
        .connection-dot, .request-pending span { width: 7px; height: 7px; border-radius: 50%; background: #9aa9a0; }
        .device-connection.online .connection-dot { background: #20a36f; box-shadow: 0 0 0 3px rgba(32,163,111,.12); }
        .device-connection.offline .connection-dot { background: #d85b57; box-shadow: 0 0 0 3px rgba(216,91,87,.1); }
        .customer-device-identity { position: relative; gap: 15px; margin-top: 26px; }
        .customer-device-icon { display: grid; width: 54px; height: 54px; flex: 0 0 auto; place-items: center; border: 1px solid #d9eee3; border-radius: 16px; background: #edf8f1; color: #14815f; }
        .customer-device-icon svg { width: 27px; height: 27px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.6; }
        .customer-device-identity h2 { color: #1d3a30; font-size: clamp(19px, 3vw, 25px); letter-spacing: -.6px; }
        .customer-device-identity p { margin: 4px 0 0; color: #7b8982; font-size: 12px; }
        .customer-device-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 25px; }
        .customer-device-meta > div { min-width: 0; padding: 13px 15px; border: 1px solid #e8efea; border-radius: 12px; background: rgba(255,255,255,.82); }
        .customer-device-meta span, .customer-device-meta strong { display: block; }
        .customer-device-meta span { margin-bottom: 5px; color: #829088; font-size: 10px; font-weight: 650; }
        .customer-device-meta strong { overflow-wrap: anywhere; color: #354940; font-size: 12px; }
        .monitoring-panel { gap: 13px; margin-top: 17px; padding: 16px; border: 1px solid #d6ecdf; border-radius: 14px; background: linear-gradient(110deg, #edf8f0, #f7fbf7); }
        .monitoring-panel.disarmed { border-color: #e6e9df; background: linear-gradient(110deg, #f5f6f0, #fbfbf8); }
        .monitoring-panel.armed { border-color: #cfe9da; }
        .monitoring-state-icon { display: grid; width: 42px; height: 42px; flex: 0 0 auto; place-items: center; border-radius: 12px; background: #dff2e6; color: #168052; }
        .monitoring-panel.disarmed .monitoring-state-icon { background: #e9ece4; color: #66735f; }
        .monitoring-state-icon svg { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .monitoring-state-copy { min-width: 0; }
        .monitoring-state-copy span, .monitoring-state-copy strong, .monitoring-state-copy small { display: block; }
        .monitoring-state-copy span { color: #71817a; font-size: 9px; font-weight: 800; letter-spacing: .8px; }
        .monitoring-state-copy strong { margin-top: 2px; color: #176d4a; font-size: 18px; }
        .monitoring-panel.disarmed .monitoring-state-copy strong { color: #596754; }
        .monitoring-state-copy small { margin-top: 2px; color: #778780; font-size: 11px; }
        .request-pending { display: inline-flex; align-items: center; gap: 7px; margin-left: auto; padding: 6px 9px; border: 1px solid #f1e3c2; border-radius: 999px; background: #fff9e9; color: #8b691d; font-size: 9px; font-weight: 750; white-space: nowrap; }
        .request-pending span { background: #d49a23; box-shadow: 0 0 0 3px rgba(212,154,35,.12); }
        .customer-device-actions { gap: 11px; margin-top: 20px; }
        .customer-device-actions form { flex: 1; margin: 0; }
        .customer-device-actions button { display: flex; width: 100%; min-height: 52px; gap: 9px; border-radius: 12px; font-size: 13px; font-weight: 750; transition: transform .15s ease, box-shadow .15s ease; }
        .customer-device-actions button:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 7px 18px rgba(26,68,49,.12); }
        .customer-device-actions button svg { width: 19px; height: 19px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .customer-device-actions .arm-button { border-color: #0f9b78; background: #0f9b78; color: #fff; }
        .customer-device-actions .arm-button:not(:disabled):hover { border-color: #08795e; background: #08795e; color: #fff; }
        .customer-device-actions .disarm-button { border-color: #dce5df; background: #fff; color: #46584f; }
        .customer-device-actions .arm-button.is-selected:not(:disabled) { box-shadow: inset 0 0 0 2px rgba(255,255,255,.35), 0 0 0 3px rgba(15,155,120,.15); }
        .customer-device-actions .disarm-button.is-selected:not(:disabled) { border-color: #8a9990; background: #eef2ef; color: #34483e; box-shadow: 0 0 0 3px rgba(87,112,96,.09); }
        .customer-device-actions button:disabled { border-color: #e3e9e5; background: #f1f4f2; color: #8c9992; cursor: default; }
        .customer-device-note { margin: 13px 0 0; color: #829088; font-size: 10px; text-align: center; }
        .customer-empty-card { max-width: 620px; margin: 35px auto 60px; padding: 35px 24px; border: 1px dashed #cfded3; border-radius: 18px; background: #fbfdfb; text-align: center; }
        .customer-empty-icon { display: grid; width: 44px; height: 44px; margin: 0 auto 13px; place-items: center; border-radius: 50%; background: #edf5ef; color: #64806c; font-size: 20px; font-weight: 800; }
        .customer-empty-card h2 { color: #294438; font-size: 17px; }
        .customer-empty-card p { max-width: 390px; margin: 8px auto 0; color: #78877f; font-size: 12px; }
        @media (max-width: 760px) {
            .topbar { position: static; gap: 10px; }
            .top-actions { align-items: center; gap: 8px; }
            .profile { margin-left: auto; }
            .profile-avatar { width: 32px; height: 32px; }
            .profile strong { max-width: 120px; }
            main { padding: 25px 16px 40px; }
            .cards { grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 10px; }
            .dealer-workspace { grid-template-columns: minmax(0, 1fr); gap: 16px; }
            .dealer-sidebar { position: static; max-height: 370px; }
            .card { padding: 16px; border-radius: 12px; }
            .table-wrap { padding: 8px 12px; border-radius: 12px; }
            .table-search { align-items: flex-start; flex-direction: column; gap: 7px; }
            .table-search-control { width: 100%; }
            .table-search-count { padding-left: 2px; }
            .customer-device-card { margin: 4px 0 28px; padding: 22px 17px; border-radius: 17px; }
            .customer-device-identity { margin-top: 22px; }
            .customer-device-meta { gap: 8px; }
            .customer-device-meta > div { padding: 11px; }
            .monitoring-panel { align-items: flex-start; gap: 10px; padding: 13px 11px; }
            .monitoring-state-icon { width: 36px; height: 36px; }
            .request-pending { margin: 0 0 0 auto; padding: 5px 7px; font-size: 8px; }
        }
        @media (max-width: 420px) {
            .customer-device-meta { grid-template-columns: 1fr; }
            .customer-device-actions { align-items: stretch; flex-direction: column; }
            .customer-device-actions button { min-height: 49px; }
            .monitoring-state-copy small { max-width: 190px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('portal.dashboard') }}">
            <span class="brand-mark" aria-hidden="true">SG</span>
            <span><strong>Shop Guard</strong><small>Shutter monitoring</small></span>
        </a>
        <div class="top-actions">
            <nav aria-label="Portal navigation">
                <a class="{{ request()->routeIs('portal.dashboard') ? 'active' : '' }}" href="{{ route('portal.dashboard') }}">My dashboard</a>
                <a class="{{ request()->routeIs('portal.notifications') ? 'active' : '' }}" href="{{ route('portal.notifications') }}">Notifications</a>
            </nav>
            <a class="notification-bell" href="{{ route('portal.notifications') }}" aria-label="Notifications{{ $unreadNotificationCount ? ', '.$unreadNotificationCount.' unread' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path></svg>
                @if ($unreadNotificationCount > 0)<span class="notification-count">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif
            </a>
            <a class="profile profile-link" href="{{ route('portal.profile') }}">
                <span class="profile-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}</span>
                <span><strong>{{ auth()->user()->full_name }}</strong><small>{{ $roleName }}</small></span>
            </a>
            <form class="logout-form" method="POST" action="{{ route('portal.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </div>
    </header>
    <main>
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert" role="alert">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
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
</body>
</html>
