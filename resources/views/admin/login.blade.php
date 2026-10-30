<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mcare Admin Login</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #18213a;
            background:
                radial-gradient(circle at 20% 12%, rgba(74, 91, 212, 0.18), transparent 28%),
                radial-gradient(circle at 85% 18%, rgba(26, 180, 142, 0.18), transparent 30%),
                linear-gradient(135deg, #f6fbff 0%, #eef4ff 48%, #f8fbff 100%);
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: min(100%, 430px);
            padding: 34px;
            border: 1px solid rgba(255, 255, 255, 0.78);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.86);
            box-shadow: 0 24px 70px rgba(28, 45, 88, 0.16);
            backdrop-filter: blur(14px);
        }

        .brand {
            text-align: center;
            margin-bottom: 26px;
        }

        .brand img {
            width: 92px;
            height: 92px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .brand h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0;
            color: #13182f;
        }

        .brand p {
            margin: 8px 0 0;
            font-size: 14px;
            color: #6b7280;
            line-height: 1.5;
        }

        .alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 14px;
            color: #9f1239;
            background: #fff1f2;
            font-size: 14px;
            line-height: 1.4;
        }

        .field {
            position: relative;
            margin-bottom: 14px;
        }

        .field input {
            width: 100%;
            height: 52px;
            padding: 0 46px 0 16px;
            border: 1px solid #dbe3f0;
            border-radius: 16px;
            background: #ffffff;
            color: #18213a;
            font-size: 15px;
            outline: none;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .field input:focus {
            border-color: #4a5bd4;
            box-shadow: 0 0 0 4px rgba(74, 91, 212, 0.12);
        }

        .field i {
            position: absolute;
            top: 50%;
            right: 17px;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .submit {
            width: 100%;
            height: 52px;
            margin-top: 8px;
            border: 0;
            border-radius: 18px;
            color: #ffffff;
            background: linear-gradient(135deg, #3b82f6, #6d28d9);
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 14px 26px rgba(74, 91, 212, 0.24);
        }

        .footer-note {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }

        @media (max-width: 480px) {
            .page {
                padding: 16px;
            }

            .login-card {
                padding: 26px 20px;
                border-radius: 24px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="login-card">
            <div class="brand">
                <img src="{{ asset('images/logo.png') }}" alt="Mcare logo">
                <h1>Mcare Admin</h1>
                <p>Secure access for managing healthcare operations.</p>
            </div>

            @if($errors->any())
                <div class="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ url('/admin/login') }}" method="POST" autocomplete="on">
                @csrf

                <div class="field">
                    <input
                        type="email"
                        name="email"
                        placeholder="Email address"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="field">
                    <input
                        type="password"
                        name="password"
                        placeholder="Password"
                        required
                    >
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="submit">Sign In</button>
            </form>

            <div class="footer-note">Caring hearts, trusted health.</div>
        </section>
    </main>
</body>
</html>
