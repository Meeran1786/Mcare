@php($biz = config('plans.business'))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ $biz['name'] }}</title>
    <link rel="icon" href="/favicon.ico">
    <style>
        :root { --brand:#1565c0; --brand-dark:#0d47a1; --green:#2e7d6b; --ink:#1f2933; --muted:#5f6b7a; --line:#e3e8ef; --bg:#f5f9ff; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:-apple-system, "Segoe UI", Roboto, Arial, sans-serif; color:var(--ink); background:var(--bg); line-height:1.6; }
        a { color:var(--brand); }
        header { background:#fff; border-bottom:1px solid var(--line); }
        .bar { max-width:1040px; margin:0 auto; padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .logo { font-weight:700; font-size:20px; color:var(--brand-dark); text-decoration:none; }
        nav a { margin-left:16px; text-decoration:none; color:var(--ink); font-weight:500; }
        main { max-width:1040px; margin:0 auto; padding:32px 16px 48px; }
        .doc { background:#fff; border-radius:12px; padding:28px; box-shadow:0 1px 8px rgba(0,0,0,.06); max-width:760px; margin:0 auto; }
        h1 { color:var(--brand-dark); margin-top:0; }
        h2 { color:var(--brand-dark); margin-top:28px; font-size:20px; }
        footer { border-top:1px solid var(--line); background:#fff; }
        footer .bar { font-size:14px; color:var(--muted); align-items:flex-start; }
        footer a { margin-right:14px; }
        .muted { color:var(--muted); }
    </style>
    @yield('head')
</head>
<body>
<header>
    <div class="bar">
        <a class="logo" href="/">{{ $biz['name'] }}</a>
        <nav>
            <a href="/">Home</a>
            <a href="/plans">Plans</a>
            <a href="/contact-us">Contact</a>
        </nav>
    </div>
</header>
<main>
    @yield('content')
</main>
<footer>
    <div class="bar">
        <div>
            <a href="/plans">Plans &amp; pricing</a>
            <a href="/pages/terms-and-conditions">Terms</a>
            <a href="/pages/privacy-policy">Privacy</a>
            <a href="/refund-policy">Refunds &amp; cancellation</a>
            <a href="/shipping-policy">Shipping &amp; delivery</a>
            <a href="/contact-us">Contact us</a>
        </div>
        <div>
            © {{ date('Y') }} {{ $biz['legal_name'] }}
            @if($biz['address']) · {{ $biz['address'] }} @endif
            · <a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a>
            @if($biz['phone']) · {{ $biz['phone'] }} @endif
        </div>
    </div>
</footer>
@yield('scripts')
</body>
</html>
