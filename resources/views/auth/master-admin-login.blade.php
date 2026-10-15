<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111c2e">
    <title>Sign in | Shop Guard</title>
    <style>
        * { box-sizing: border-box; }
        body { display: grid; min-height: 100vh; margin: 0; padding: 28px; place-items: center; background: #f4f7fb; color: #1c293d; font: 14px/1.5 Inter, "Segoe UI", Arial, sans-serif; }
        .login-shell { display: grid; width: min(980px, 100%); min-height: 590px; grid-template-columns: 1fr 1fr; overflow: hidden; border: 1px solid #e9edf3; border-radius: 20px; background: #fff; box-shadow: 0 24px 70px rgba(17, 28, 46, .12); }
        .login-brand { position: relative; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 42px; background: #111c2e; color: #fff; }
        .login-brand::before, .login-brand::after { position: absolute; z-index: 0; border: 1px solid rgba(82, 210, 176, .15); border-radius: 50%; content: ""; }
        .login-brand::before { right: -125px; bottom: -105px; width: 420px; height: 420px; }
        .login-brand::after { right: -68px; bottom: -49px; width: 305px; height: 305px; }
        .brand-lockup, .brand-message, .brand-footer { position: relative; z-index: 1; }
        .brand-lockup { display: flex; align-items: center; gap: 12px; }
        .brand-mark { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 12px; background: #19a889; font-size: 14px; font-weight: 800; }
        .brand-lockup strong { display: block; font-size: 14px; }
        .brand-lockup small { display: block; margin-top: 3px; color: #9dacc0; font-size: 11px; }
        .brand-message { max-width: 380px; padding: 34px 0; }
        .eyebrow { color: #56d6b4; font-size: 10px; font-weight: 750; letter-spacing: 1.5px; text-transform: uppercase; }
        .brand-message h1 { margin: 16px 0 13px; font-size: clamp(29px, 4vw, 42px); letter-spacing: -1.6px; line-height: 1.12; }
        .brand-message p { max-width: 350px; margin: 0; color: #aeb9c9; font-size: 14px; line-height: 1.7; }
        .brand-footer { color: #8796ab; font-size: 11px; }
        .login-content { display: flex; align-items: center; justify-content: center; padding: 42px; }
        .login-card { width: 100%; max-width: 360px; }
        .login-card h2 { margin: 0; color: #1b293d; font-size: 25px; letter-spacing: -.7px; }
        .subtitle { margin: 8px 0 30px; color: #758196; font-size: 13px; }
        .alert-error { margin-bottom: 18px; padding: 11px 13px; border: 1px solid #f2c8c8; border-radius: 7px; background: #fff1f1; color: #a52c36; font-size: 12px; }
        label { display: block; margin: 17px 0 7px; color: #3f4d61; font-size: 12px; font-weight: 650; }
        input { width: 100%; min-height: 44px; padding: 11px 12px; border: 1px solid #dce2ea; border-radius: 7px; outline: 0; background: #fff; color: #263449; font: inherit; font-size: 13px; }
        input:focus { border-color: #59baa4; box-shadow: 0 0 0 3px rgba(25,168,137,.12); }
        button { width: 100%; min-height: 44px; margin-top: 26px; padding: 12px; border: 0; border-radius: 7px; background: #19a889; color: #fff; cursor: pointer; font: inherit; font-size: 13px; font-weight: 700; transition: background .15s ease; }
        button:hover { background: #12866d; }
        .secure-note { margin-top: 20px; color: #8a96a8; font-size: 11px; text-align: center; }
        @media (max-width: 680px) {
            body { padding: 14px; }
            .login-shell { min-height: 0; grid-template-columns: 1fr; border-radius: 15px; }
            .login-brand { min-height: 205px; padding: 23px 24px; }
            .brand-mark { width: 35px; height: 35px; border-radius: 10px; }
            .brand-message { padding: 22px 0 4px; }
            .brand-message h1 { margin: 9px 0; font-size: 25px; }
            .brand-message p, .brand-footer { display: none; }
            .login-content { padding: 30px 24px 33px; }
        }
        body { padding: 32px; background: radial-gradient(ellipse at 18% 12%, rgba(35,166,123,.10), transparent 32%), linear-gradient(135deg, #f0f5f0, #f7f8f6 55%, #edf4ef); color: #172b2a; }
        .login-shell { width: min(1060px, 100%); min-height: 620px; border-color: rgba(224,234,227,.9); border-radius: 24px; box-shadow: 0 30px 90px rgba(22,52,39,.14); }
        .login-brand { padding: 48px; background: radial-gradient(ellipse at 94% 86%, rgba(25,169,124,.3), transparent 38%), linear-gradient(150deg, #172d28 0%, #10211e 100%); }
        .login-brand::before, .login-brand::after { border-color: rgba(94,218,175,.15); }
        .login-brand::before { right: -130px; bottom: -130px; width: 480px; height: 480px; }
        .login-brand::after { right: -78px; bottom: -78px; width: 365px; height: 365px; }
        .brand-mark { border-radius: 14px; background: linear-gradient(145deg, #1abe91, #08785e); box-shadow: 0 8px 22px rgba(4,120,94,.25); }
        .brand-lockup strong { font-size: 15px; letter-spacing: -.2px; }
        .brand-message h1 { max-width: 400px; font-size: clamp(32px, 4.4vw, 47px); letter-spacing: -2px; }
        .brand-message p { color: #bdcbc5; }
        .login-content { padding: 54px; }
        .login-card h2 { color: #19332d; font-size: 29px; letter-spacing: -1px; }
        .subtitle { margin: 9px 0 32px; color: #778780; font-size: 13px; }
        label { color: #344940; font-size: 12px; }
        input { min-height: 48px; padding: 12px 14px; border-color: #dbe5df; border-radius: 10px; }
        input:focus { border-color: #38ad89; box-shadow: 0 0 0 4px rgba(15,155,120,.11); }
        button { min-height: 48px; margin-top: 27px; border-radius: 10px; background: #0f9b78; box-shadow: 0 7px 16px rgba(15,155,120,.19); font-size: 13px; }
        button:hover { background: #08795e; }
        .secure-note { color: #829089; }
        .alert-error { border-color: #f2d4d1; border-radius: 9px; background: #fff2f0; color: #9d3934; }
        @media (max-width: 680px) {
            body { padding: 14px; }
            .login-shell { min-height: 0; border-radius: 18px; }
            .login-brand { min-height: 220px; padding: 24px; }
            .login-content { padding: 32px 24px 36px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <section class="login-brand" aria-label="Shop Guard">
            <div class="brand-lockup">
                <span class="brand-mark" aria-hidden="true">SG</span>
                <span><strong>Shop Guard</strong><small>Security platform</small></span>
            </div>
            <div class="brand-message">
                <div class="eyebrow">Shutter security platform</div>
                <h1>Everything under control.</h1>
                <p>Sign in to manage your organization or monitor the Shop Guard shutters and products in your account.</p>
            </div>
            <div class="brand-footer">Shop Guard &copy; {{ date('Y') }}</div>
        </section>
        <section class="login-content">
            <div class="login-card">
                <h2>Welcome back</h2>
                <p class="subtitle">Sign in with your Shop Guard account to continue.</p>

                @if ($errors->any())
                    <div class="alert-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('master-admin.login.submit') }}">
                    @csrf
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="admin@example.com" required autofocus>

                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>

                    <button type="submit">Sign in</button>
                </form>
                <div class="secure-note">Secure access for authorized Shop Guard users</div>
            </div>
        </section>
    </div>
</body>
</html>
