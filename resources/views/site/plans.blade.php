@extends('site.layout')
@section('title', 'Plans and pricing')
@section('head')
<style>
    .hero { text-align:center; max-width:680px; margin:0 auto 28px; }
    .hero p { color:var(--muted); font-size:17px; }
    .toggle { display:inline-flex; background:#fff; border:1px solid var(--line); border-radius:999px; padding:4px; margin-top:8px; }
    .toggle button { border:0; background:none; padding:8px 18px; border-radius:999px; font-size:15px; cursor:pointer; color:var(--ink); }
    .toggle button.on { background:var(--brand); color:#fff; }
    .grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px; }
    .card { background:#fff; border-radius:14px; padding:24px; box-shadow:0 1px 8px rgba(0,0,0,.06); display:flex; flex-direction:column; }
    .card.pharmacy { border-top:4px solid var(--green); }
    .card.family { border-top:4px solid var(--brand); }
    .card h2 { margin:0; }
    .card .tag { color:var(--muted); margin:4px 0 14px; }
    .price { font-size:34px; font-weight:700; }
    .price small { font-size:15px; font-weight:400; color:var(--muted); }
    .card ul { padding-left:20px; flex:1; }
    .free { margin-top:24px; background:#fff; border-radius:14px; padding:20px 24px; box-shadow:0 1px 8px rgba(0,0,0,.06); }
    .btn { display:inline-block; border:0; border-radius:10px; padding:12px 18px; font-size:16px; font-weight:600; cursor:pointer; background:var(--brand); color:#fff; text-align:center; }
    .btn.green { background:var(--green); }
    .btn[disabled] { opacity:.5; cursor:default; }
    .panel { background:#fff; border-radius:14px; padding:24px; box-shadow:0 1px 8px rgba(0,0,0,.06); max-width:520px; margin:28px auto 0; }
    .panel input { width:100%; font-size:18px; padding:12px; border:1px solid var(--line); border-radius:10px; margin:6px 0 12px; }
    .msg { min-height:22px; color:#b42318; }
    .ok { color:var(--green); }
    .hidden { display:none; }
    .link { background:none; border:0; color:var(--brand); cursor:pointer; padding:0; font-size:14px; }
</style>
@endsection
@section('content')
<div class="hero">
    <h1>Simple plans. The basics stay free.</h1>
    <p>Reminders, ordering medicines and receiving orders are free for everyone. Paid plans add family care for patients and more customers for pharmacies.@if($trialDays > 0) New accounts get the paid plan free for {{ $trialDays }} days.@endif</p>
    <div class="toggle" role="group" aria-label="Billing period">
        <button type="button" data-period="monthly" class="on">Monthly</button>
        <button type="button" data-period="yearly">Yearly (save more)</button>
    </div>
</div>

<div class="grid">
    @foreach($plans as $key => $plan)
        <div class="card {{ $key }}">
            <h2>{{ $plan['name'] }}</h2>
            <div class="tag">{{ $plan['tagline'] }} · for {{ $plan['role'] === 'vendor' ? 'pharmacies' : 'patients and families' }}</div>
            @foreach($plan['prices'] as $period => $price)
                <div class="price" data-show="{{ $period }}" @if($period !== 'monthly') hidden @endif>
                    ₹{{ number_format($price['amount'] / 100) }} <small>{{ $price['label'] }}</small>
                </div>
            @endforeach
            <ul>
                @foreach($plan['features'] as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
            <button class="btn {{ $key === 'pharmacy' ? 'green' : '' }}" type="button" data-buy="{{ $key }}" data-role="{{ $plan['role'] }}">Get {{ $plan['name'] }}</button>
        </div>
    @endforeach
</div>

<div class="free">
    <strong>Free, always:</strong> medicine reminders with alarms, voice commands, ordering from nearby pharmacies, one family member for missed-dose alerts, and receiving and managing orders for pharmacies.
</div>

<div class="panel" id="panel">
    <div id="signed-out" @if($user) class="hidden" @endif>
        <h2 style="margin-top:0">Sign in to buy</h2>
        <p class="muted">Use the phone number of your Mcare+ or Mcare+ Partner account. We will text you a 4-digit code.</p>
        <label for="mobile">Phone number</label>
        <input id="mobile" inputmode="numeric" autocomplete="tel" maxlength="15" placeholder="10-digit mobile number">
        <div id="otp-row" class="hidden">
            <label for="otp">Code from SMS</label>
            <input id="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="4" placeholder="4-digit code">
        </div>
        <div class="msg" id="msg-in"></div>
        <button class="btn" type="button" id="send">Send code</button>
        <button class="btn hidden" type="button" id="verify">Sign in</button>
    </div>
    <div id="signed-in" @unless($user) class="hidden" @endunless>
        @if($user)
            <h2 style="margin-top:0">Hello{{ $user->name ? ', '.$user->name : '' }}</h2>
            <p>Signed in as {{ $user->mobile_number }} ({{ $user->role === 'vendor' ? 'pharmacy' : 'patient' }} account).<br>
                Current plan: <strong>{{ $status['plan_name'] }}</strong>@if($status['is_trial']) (free trial)@endif
                @if($status['ends_at']) until {{ \Carbon\Carbon::parse($status['ends_at'])->timezone('Asia/Kolkata')->format('j M Y') }}@endif.
            </p>
            @if(!$payable)
                <p class="muted">Online payment opens soon.</p>
            @else
                <p class="muted">Choose a plan above to pay. Paying again adds time after your current paid period.</p>
            @endif
        @endif
        <div class="msg" id="msg-buy"></div>
        <form method="post" action="/plans/sign-out">@csrf<button class="link" type="submit">Sign out</button></form>
    </div>
</div>
@endsection
@section('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
<script>
(function () {
    var token = document.querySelector('meta[name=csrf-token]').content;
    var signedIn = {{ $user ? 'true' : 'false' }};
    var role = @json($user?->role);
    var key = @json($razorpayKey);
    var period = 'monthly';

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(body || {})
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (j) {
                if (!r.ok || j.success === false) {
                    var m = j.message || (r.status === 429 ? 'Please wait a minute and try again.' : 'Something went wrong. Please try again.');
                    throw new Error(m);
                }
                return j;
            });
        });
    }

    document.querySelectorAll('.toggle button').forEach(function (b) {
        b.addEventListener('click', function () {
            period = b.dataset.period;
            document.querySelectorAll('.toggle button').forEach(function (x) { x.classList.toggle('on', x === b); });
            document.querySelectorAll('[data-show]').forEach(function (p) { p.hidden = p.dataset.show !== period; });
        });
    });

    var $ = function (id) { return document.getElementById(id); };
    var mobile = $('mobile'), otp = $('otp');

    $('send').addEventListener('click', function () {
        $('msg-in').textContent = '';
        $('send').disabled = true;
        post('/plans/send-otp', { mobile_number: mobile.value.trim() }).then(function () {
            $('otp-row').classList.remove('hidden');
            $('verify').classList.remove('hidden');
            $('send').textContent = 'Send again';
            otp.focus();
        }).catch(function (e) { $('msg-in').textContent = e.message; })
          .finally(function () { $('send').disabled = false; });
    });

    $('verify').addEventListener('click', function () {
        $('msg-in').textContent = '';
        post('/plans/verify-otp', { mobile_number: mobile.value.trim(), otp: otp.value.trim() })
            .then(function () { window.location.reload(); })
            .catch(function (e) { $('msg-in').textContent = e.message; });
    });

    document.querySelectorAll('[data-buy]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!signedIn) {
                $('panel').scrollIntoView({ behavior: 'smooth' });
                mobile.focus();
                return;
            }
            var msg = $('msg-buy');
            msg.className = 'msg';
            msg.textContent = '';
            post('/plans/order', { plan: b.dataset.buy, period: period }).then(function (o) {
                var rzp = new Razorpay({
                    key: key,
                    order_id: o.order_id,
                    amount: o.amount,
                    currency: 'INR',
                    name: o.name,
                    description: o.description,
                    prefill: o.prefill,
                    theme: { color: '#1565c0' },
                    handler: function (resp) {
                        msg.textContent = 'Confirming your payment…';
                        post('/plans/confirm', resp).then(function () {
                            msg.className = 'msg ok';
                            msg.textContent = 'Payment received. Your plan is active. Open the app to see it.';
                            setTimeout(function () { window.location.reload(); }, 2500);
                        }).catch(function (e) { msg.textContent = e.message; });
                    }
                });
                rzp.on('payment.failed', function (r) {
                    msg.textContent = (r.error && r.error.description) || 'Payment failed. No money was taken.';
                });
                rzp.open();
            }).catch(function (e) { msg.textContent = e.message; $('panel').scrollIntoView({ behavior: 'smooth' }); });
        });
    });
})();
</script>
@endsection
