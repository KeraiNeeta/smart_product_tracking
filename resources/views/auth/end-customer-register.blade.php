<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111c2e">
    <title>Customer registration | Shop Guard</title>
    <style>
        * { box-sizing: border-box; }
        body { display: grid; min-height: 100vh; margin: 0; padding: 24px; place-items: center; background: #f4f7fb; color: #1c293d; font: 14px/1.5 Inter, "Segoe UI", Arial, sans-serif; }
        .register-card { width: min(460px, 100%); padding: 36px; border: 1px solid #e9edf3; border-radius: 16px; background: #fff; box-shadow: 0 20px 55px rgba(17, 28, 46, .1); }
        .brand { margin-bottom: 26px; color: #08795e; font-size: 12px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase; }
        h1 { margin: 0; color: #1b293d; font-size: 25px; letter-spacing: -.7px; }
        .intro { margin: 8px 0 22px; color: #758196; font-size: 13px; }
        label { display: block; margin: 16px 0 6px; color: #3f4d61; font-size: 12px; font-weight: 650; }
        input { width: 100%; min-height: 43px; padding: 10px 12px; border: 1px solid #dce2ea; border-radius: 7px; outline: 0; color: #263449; font: inherit; font-size: 13px; }
        input:focus { border-color: #59baa4; box-shadow: 0 0 0 3px rgba(25,168,137,.12); }
        button { width: 100%; min-height: 44px; margin-top: 23px; border: 0; border-radius: 7px; background: #19a889; color: #fff; cursor: pointer; font: inherit; font-size: 13px; font-weight: 700; }
        button:hover { background: #12866d; }
        .error { margin-top: 5px; color: #a52c36; font-size: 11px; }
        .alert-error { margin: 0 0 14px; padding: 10px 12px; border: 1px solid #f2c8c8; border-radius: 7px; background: #fff1f1; color: #a52c36; font-size: 12px; }
        .footer { margin: 18px 0 0; color: #758196; font-size: 12px; text-align: center; }
        .footer a { color: #08795e; font-weight: 650; }
        @media (max-width: 480px) { body { padding: 14px; } .register-card { padding: 27px 22px; } }
    </style>
</head>
<body>
    <main class="register-card">
        <div class="brand">Shop Guard</div>
        <h1>Create your customer account</h1>
        <p class="intro">Register with the serial number on your Shop Guard device. One device can be linked to each customer account.</p>

        @if ($errors->any())
            <div class="alert-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.submit') }}">
            @csrf
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" value="{{ old('full_name') }}" autocomplete="name" required autofocus>
            @error('full_name') <div class="error">{{ $message }}</div> @enderror

            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="mobile">Mobile number (optional)</label>
            <input id="mobile" name="mobile" type="tel" value="{{ old('mobile') }}" autocomplete="tel">
            @error('mobile') <div class="error">{{ $message }}</div> @enderror

            <label for="serial_number">Device serial number</label>
            <input id="serial_number" name="serial_number" value="{{ old('serial_number') }}" maxlength="120" autocomplete="off" required>
            @error('serial_number') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>

            <button type="submit">Create customer account</button>
        </form>
        <p class="footer">Already registered? <a href="{{ route('master-admin.login') }}">Sign in</a></p>
    </main>
</body>
</html>
