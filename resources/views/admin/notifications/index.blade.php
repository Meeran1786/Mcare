@extends('adminlte::page')

@section('title', 'Push Notifications')

@section('content_header')
<div class="pn-page-head">
    <div>
        <span class="pn-eyebrow">Campaign Manager</span>
        <h1>Push Notifications</h1>
        <p>Compose, preview, schedule, and track app notifications from one clean workspace.</p>
    </div>
    <a href="{{ route('admin.notifications.index') }}" class="btn pn-soft-btn">
        <i class="fas fa-sync-alt"></i> Refresh
    </a>
</div>
@stop

@section('content')
@php
    $statusClasses = [
        'draft' => 'pn-badge-gray',
        'scheduled' => 'pn-badge-orange',
        'sent' => 'pn-badge-green',
        'failed' => 'pn-badge-red',
    ];
    $recipientOptions = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'role' => 'user',
            'name' => $user->name ?: 'Unnamed user',
            'mobile' => $user->mobile_number,
            'label' => ($user->name ?: $user->mobile_number ?: 'Unnamed user') . ' (User)',
            'initials' => strtoupper(substr($user->name ?: 'U', 0, 1)),
        ];
    })->merge($vendors->map(function ($vendor) {
        return [
            'id' => $vendor->id,
            'role' => 'vendor',
            'name' => $vendor->name ?: 'Unnamed vendor',
            'mobile' => $vendor->mobile_number,
            'label' => ($vendor->name ?: $vendor->mobile_number ?: 'Unnamed vendor') . ' (Vendor)',
            'initials' => strtoupper(substr($vendor->name ?: 'V', 0, 1)),
        ];
    }))->values();
@endphp

@if(session('success'))
    <div class="alert pn-alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert pn-alert-danger">{{ $errors->first() }}</div>
@endif

<!-- <div class="row pn-stats-row">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="pn-stat-card pn-stat-blue">
            <span><i class="fas fa-bell"></i></span>
            <div>
                <p>Total Notifications</p>
                <strong>{{ number_format($stats['total']) }}</strong>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="pn-stat-card pn-stat-green">
            <span><i class="fas fa-check-circle"></i></span>
            <div>
                <p>Sent Successfully</p>
                <strong>{{ number_format($stats['sent']) }}</strong>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="pn-stat-card pn-stat-red">
            <span><i class="fas fa-exclamation-circle"></i></span>
            <div>
                <p>Failed</p>
                <strong>{{ number_format($stats['failed']) }}</strong>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="pn-stat-card pn-stat-gray">
            <span><i class="fas fa-save"></i></span>
            <div>
                <p>Drafts</p>
                <strong>{{ number_format($stats['drafts']) }}</strong>
            </div>
        </div>
    </div>
</div> -->

<div class="row">
    <div class="col-xl-7 mb-4">
        <form method="POST" action="{{ route('admin.notifications.store') }}" enctype="multipart/form-data" id="notificationForm" class="pn-card pn-compose-card">
            @csrf
            <div class="pn-card-header">
                <div>
                    <span class="pn-eyebrow">New Campaign</span>
                    <h2>Compose Notification</h2>
                </div>
                <span class="pn-live-dot"><i class="fas fa-circle"></i> Live preview</span>
            </div>

            <div class="pn-section">
                <label class="pn-label">Notification Title</label>
                <input class="form-control pn-input" name="title" id="notificationTitle" value="{{ old('title') }}" required maxlength="150" placeholder="Eg: Your prescription is ready">
                <div class="pn-field-meta"><span>Shown as the notification headline.</span><span id="titleCounter">0 / 150</span></div>
            </div>

            <div class="pn-section">
                <label class="pn-label">Notification Message</label>
                <textarea class="form-control pn-input" name="body" id="notificationBody" rows="4" required maxlength="1000" placeholder="Write a clear, action-oriented message.">{{ old('body') }}</textarea>
                <div class="pn-field-meta"><span>Keep it short for better open rates.</span><span id="bodyCounter">0 / 1000</span></div>
            </div>

            <div class="pn-section">
                <label class="pn-label">Audience Type</label>
                <div class="pn-choice-grid pn-choice-grid-3" data-radio-group="target_type">
                    <label class="pn-choice active">
                        <input type="radio" name="target_type" value="users" {{ old('target_type', 'users') === 'users' ? 'checked' : '' }}>
                        <i class="fas fa-users"></i>
                        <strong>Users</strong>
                        <small>Customers only</small>
                    </label>
                    <label class="pn-choice">
                        <input type="radio" name="target_type" value="vendors" {{ old('target_type') === 'vendors' ? 'checked' : '' }}>
                        <i class="fas fa-store"></i>
                        <strong>Vendors</strong>
                        <small>Pharmacies only</small>
                    </label>
                    <label class="pn-choice">
                        <input type="radio" name="target_type" value="both" {{ old('target_type') === 'both' ? 'checked' : '' }}>
                        <i class="fas fa-layer-group"></i>
                        <strong>Both</strong>
                        <small>Everyone</small>
                    </label>
                </div>
            </div>

            <div class="pn-section">
                <label class="pn-label">Send Mode</label>
                <div class="pn-segmented" data-radio-group="send_mode">
                    <label class="active">
                        <input type="radio" name="send_mode" value="bulk" {{ old('send_mode', 'bulk') === 'bulk' ? 'checked' : '' }}>
                        <i class="fas fa-broadcast-tower"></i> Send to All
                    </label>
                    <label>
                        <input type="radio" name="send_mode" value="single" {{ old('send_mode') === 'single' ? 'checked' : '' }}>
                        <i class="fas fa-user"></i> Single User
                    </label>
                    <label>
                        <input type="radio" name="send_mode" value="multiple" {{ old('send_mode') === 'multiple' ? 'checked' : '' }}>
                        <i class="fas fa-user-friends"></i> Multiple Users
                    </label>
                </div>
                <p class="pn-mode-help" id="modeHelp">Notification goes to everyone in the selected audience.</p>
            </div>

            <div class="pn-section" id="recipientSection">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <label class="pn-label mb-2">Recipients</label>
                    <span class="pn-count-pill"><span id="selectedCount">0</span> selected</span>
                </div>
                <div class="pn-picker">
                    <i class="fas fa-search"></i>
                    <input type="search" id="recipientSearch" placeholder="Search by name or mobile number">
                </div>
                <div class="pn-results" id="recipientResults"></div>
                <div class="pn-selected" id="selectedRecipients"></div>
                <div id="recipientHiddenInputs"></div>

                @if($recentRecipients->isNotEmpty())
                    <div class="pn-recent">
                        <span>Recently sent</span>
                        @foreach($recentRecipients as $recipient)
                            <button type="button" class="pn-recipient-shortcut" data-id="{{ $recipient->id }}">
                                {{ $recipient->name ?: $recipient->mobile_number }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="pn-section">
                <label class="pn-label">Notification Image</label>
                <label class="pn-upload">
                    <input type="file" name="image" id="notificationImage" accept="image/*">
                    <i class="fas fa-image"></i>
                    <span>Upload image</span>
                    <small>Optional, max 2MB</small>
                </label>
            </div>

            <div class="pn-section">
                <label class="pn-toggle">
                    <input type="checkbox" id="scheduleToggle">
                    <span></span>
                    Schedule for later
                </label>
                <div class="pn-datetime" id="scheduleField">
                    <i class="far fa-clock"></i>
                    <input type="datetime-local" class="form-control pn-input" name="scheduled_at" id="scheduledAt" disabled>
                </div>
            </div>

            <div class="pn-actions">
                <button type="reset" class="btn pn-soft-btn" id="cancelCompose">Cancel</button>
                <div>
                    <button class="btn pn-draft-btn" name="action" value="draft">
                        <i class="far fa-bookmark"></i> Save for Later
                    </button>
                    <button class="btn pn-primary-btn" name="action" value="send">
                        <i class="fas fa-paper-plane"></i> Send Now
                    </button>
                    <small>Notification will be saved without sending when you choose Save for Later.</small>
                </div>
            </div>
        </form>
    </div>

    <div class="col-xl-5 mb-4">
        <div class="pn-card pn-preview-card">
            <div class="pn-card-header">
                <div>
                    <span class="pn-eyebrow">Android Style</span>
                    <h2>Live Preview</h2>
                </div>
            </div>
            <div class="pn-phone">
                <div class="pn-phone-top"></div>
                <div class="pn-notification-preview">
                    <div class="pn-app-row">
                        <span class="pn-app-logo">M</span>
                        <strong>Mcare</strong>
                        <small id="previewTime">Now</small>
                    </div>
                    <div class="pn-preview-content">
                        <div>
                            <h3 id="previewTitle">Notification title</h3>
                            <p id="previewBody">Your message preview will appear here while you type.</p>
                        </div>
                        <img id="previewImage" alt="Notification image preview">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- <div class="pn-card pn-history-card">
    <div class="pn-history-top">
        <div>
            <span class="pn-eyebrow">Delivery Log</span>
            <h2>Notification History</h2>
        </div>
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="pn-filters">
            <div class="pn-filter-control pn-filter-search">
                <i class="fas fa-search"></i>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search notifications">
            </div>
            <select name="status" class="pn-filter-control">
                <option value="">All statuses</option>
                @foreach(['draft', 'scheduled', 'sent', 'failed'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ request('date') }}" class="pn-filter-control">
            <button class="btn pn-primary-btn" type="submit"><i class="fas fa-filter"></i></button>
        </form>
    </div>

    <div class="pn-history-list">
        @forelse($notifications as $notification)
            @php
                $totalDelivered = max(($notification->sent_count + $notification->failed_count), 1);
                $sentPercent = min(100, round(($notification->sent_count / $totalDelivered) * 100));
                $failedPercent = min(100, round(($notification->failed_count / $totalDelivered) * 100));
                $openedBase = max($notification->sent_count, 1);
                $openedPercent = min(100, round(($notification->opened_count / $openedBase) * 100));
                $statusClass = $statusClasses[$notification->status] ?? 'pn-badge-gray';
            @endphp
            <div class="pn-history-item">
                <div class="pn-history-main">
                    <div class="pn-history-icon"><i class="fas fa-bell"></i></div>
                    <div>
                        <div class="pn-history-title-row">
                            <h3>{{ $notification->title }}</h3>
                            <span class="pn-status {{ $statusClass }}">{{ ucfirst($notification->status) }}</span>
                        </div>
                        <p>{{ Str::limit($notification->body, 115) }}</p>
                        <div class="pn-meta-row">
                            <span><i class="fas fa-users"></i> {{ ucfirst($notification->target_type ?? 'Audience') }}</span>
                            <span><i class="fas fa-paper-plane"></i> {{ ucfirst($notification->send_mode ?? 'Bulk') }}</span>
                            <span><i class="far fa-calendar"></i> {{ optional($notification->created_at)->format('d M Y, h:i A') }}</span>
                        </div>
                    </div>
                </div>

                <div class="pn-analytics">
                    <div>
                        <span>Sent</span><strong>{{ $sentPercent }}%</strong>
                        <div class="pn-progress"><b style="width: {{ $sentPercent }}%"></b></div>
                    </div>
                    <div>
                        <span>Opened</span><strong>{{ $openedPercent }}%</strong>
                        <div class="pn-progress pn-progress-opened"><b style="width: {{ $openedPercent }}%"></b></div>
                    </div>
                    <div>
                        <span>Failed</span><strong>{{ $failedPercent }}%</strong>
                        <div class="pn-progress pn-progress-failed"><b style="width: {{ $failedPercent }}%"></b></div>
                    </div>
                </div>

                <div class="pn-history-actions">
                    <button type="button" class="btn pn-icon-btn" data-toggle="modal" data-target="#notificationModal{{ $notification->id }}" title="View">
                        <i class="far fa-eye"></i>
                    </button>
                    <button type="button" class="btn pn-icon-btn pn-duplicate"
                        title="Duplicate"
                        data-title="{{ e($notification->title) }}"
                        data-body="{{ e($notification->body) }}"
                        data-target="{{ $notification->target_type ?: 'users' }}"
                        data-mode="{{ $notification->send_mode ?: 'bulk' }}">
                        <i class="far fa-copy"></i>
                    </button>
                    <form method="POST" action="{{ route('admin.notifications.resend', $notification) }}">
                        @csrf
                        <button class="btn pn-icon-btn" title="Resend"><i class="fas fa-redo"></i></button>
                    </form>
                    <form method="POST" action="{{ route('admin.notifications.delete', $notification) }}" onsubmit="return confirm('Delete this notification?')">
                        @csrf
                        <button class="btn pn-icon-btn pn-danger-icon" title="Delete"><i class="far fa-trash-alt"></i></button>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="notificationModal{{ $notification->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content pn-modal">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $notification->title }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <p>{{ $notification->body }}</p>
                            @if($notification->image_path)
                                <img src="{{ asset('storage/' . $notification->image_path) }}" class="img-fluid rounded" alt="Notification image">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="pn-empty">
                <i class="far fa-bell-slash"></i>
                <strong>No notifications found</strong>
                <span>Create a campaign or adjust your filters.</span>
            </div>
        @endforelse
    </div>

    <div class="pn-pagination">
        {{ $notifications->links() }}
    </div>
</div> -->
@stop

@section('css')
<style>
body { background: #f5f7fb; }
.pn-page-head { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:18px; }
.pn-page-head h1, .pn-card h2 { color:#101828; font-weight:800; letter-spacing:0; margin:0; }
.pn-page-head h1 { font-size:28px; }
.pn-page-head p { color:#667085; margin:5px 0 0; }
.pn-eyebrow { color:#246bfd; font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
.pn-card { background:rgba(255,255,255,.92); border:1px solid rgba(222,228,238,.9); border-radius:18px; box-shadow:0 16px 45px rgba(16,24,40,.08); }
.pn-card-header { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:22px 24px; border-bottom:1px solid #edf1f7; }
.pn-card-header h2 { font-size:20px; }
.pn-stat-card { min-height:118px; border-radius:18px; color:#fff; padding:20px; display:flex; align-items:center; gap:16px; box-shadow:0 16px 34px rgba(16,24,40,.12); overflow:hidden; position:relative; }
.pn-stat-card:after { content:""; position:absolute; width:110px; height:110px; border-radius:999px; background:rgba(255,255,255,.16); right:-30px; top:-35px; }
.pn-stat-card span { width:48px; height:48px; border-radius:14px; display:grid; place-items:center; background:rgba(255,255,255,.2); font-size:20px; flex:0 0 auto; }
.pn-stat-card p { margin:0 0 3px; opacity:.86; font-weight:700; }
.pn-stat-card strong { font-size:30px; line-height:1; }
.pn-stat-blue { background:linear-gradient(135deg,#246bfd,#00a5ff); }
.pn-stat-green { background:linear-gradient(135deg,#079455,#12b76a); }
.pn-stat-red { background:linear-gradient(135deg,#d92d20,#f04438); }
.pn-stat-gray { background:linear-gradient(135deg,#475467,#98a2b3); }
.pn-section { padding:20px 24px; border-bottom:1px solid #edf1f7; }
.pn-label { color:#344054; font-size:13px; font-weight:800; margin-bottom:9px; }
.pn-input, .pn-filter-control { border:1px solid #d9e2ef; border-radius:12px; min-height:44px; color:#101828; box-shadow:none; }
.pn-input:focus, .pn-filter-control:focus { border-color:#246bfd; box-shadow:0 0 0 4px rgba(36,107,253,.12); }
.pn-field-meta { margin-top:7px; display:flex; justify-content:space-between; gap:10px; color:#667085; font-size:12px; }
.pn-choice-grid { display:grid; gap:12px; }
.pn-choice-grid-3 { grid-template-columns:repeat(3,minmax(0,1fr)); }
.pn-choice, .pn-segmented label { cursor:pointer; user-select:none; transition:.18s ease; }
.pn-choice { border:1px solid #d9e2ef; border-radius:16px; padding:16px; background:#fff; display:flex; flex-direction:column; gap:6px; min-height:112px; }
.pn-choice input, .pn-segmented input { display:none; }
.pn-choice i { color:#246bfd; font-size:22px; }
.pn-choice strong { color:#101828; }
.pn-choice small { color:#667085; }
.pn-choice.active, .pn-choice:hover { border-color:#246bfd; box-shadow:0 10px 26px rgba(36,107,253,.14); transform:translateY(-1px); }
.pn-segmented { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); background:#eef3fb; border-radius:14px; padding:5px; gap:5px; }
.pn-segmented label { border-radius:11px; padding:12px 10px; text-align:center; color:#475467; font-weight:800; margin:0; }
.pn-segmented label.active { background:#fff; color:#246bfd; box-shadow:0 7px 18px rgba(16,24,40,.08); }
.pn-mode-help { color:#667085; margin:10px 0 0; font-size:13px; }
.pn-picker { height:46px; border:1px solid #d9e2ef; border-radius:14px; display:flex; align-items:center; gap:10px; padding:0 14px; background:#fff; }
.pn-picker input { border:0; outline:0; width:100%; }
.pn-picker i { color:#98a2b3; }
.pn-results { margin-top:10px; max-height:210px; overflow:auto; border:1px solid #edf1f7; border-radius:14px; background:#fff; display:none; }
.pn-result { width:100%; border:0; background:#fff; padding:11px 13px; display:flex; align-items:center; gap:10px; text-align:left; }
.pn-result:hover { background:#f2f6ff; }
.pn-avatar { width:34px; height:34px; border-radius:50%; background:#e7f0ff; color:#246bfd; display:grid; place-items:center; font-weight:800; flex:0 0 auto; }
.pn-selected { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
.pn-chip, .pn-recipient-shortcut, .pn-count-pill { border:1px solid #d9e2ef; background:#fff; border-radius:999px; color:#344054; font-weight:700; font-size:12px; }
.pn-chip { padding:7px 10px; display:flex; align-items:center; gap:8px; }
.pn-chip button { border:0; background:transparent; color:#667085; padding:0; }
.pn-count-pill { padding:6px 10px; }
.pn-recent { margin-top:14px; display:flex; align-items:center; flex-wrap:wrap; gap:8px; color:#667085; font-size:12px; }
.pn-recipient-shortcut { padding:6px 10px; }
.pn-upload { border:1px dashed #b8c7dc; border-radius:16px; padding:18px; display:flex; align-items:center; gap:12px; background:#f8fbff; cursor:pointer; margin:0; }
.pn-upload input { display:none; }
.pn-upload i { color:#246bfd; font-size:22px; }
.pn-upload span { font-weight:800; color:#344054; }
.pn-upload small { color:#667085; margin-left:auto; }
.pn-toggle { display:inline-flex; align-items:center; gap:10px; color:#344054; font-weight:800; cursor:pointer; }
.pn-toggle input { display:none; }
.pn-toggle span { width:44px; height:24px; border-radius:999px; background:#d0d5dd; position:relative; transition:.18s ease; }
.pn-toggle span:before { content:""; width:18px; height:18px; border-radius:50%; background:#fff; position:absolute; top:3px; left:3px; transition:.18s ease; }
.pn-toggle input:checked + span { background:#246bfd; }
.pn-toggle input:checked + span:before { transform:translateX(20px); }
.pn-datetime { margin-top:12px; display:none; position:relative; }
.pn-datetime i { position:absolute; z-index:2; left:14px; top:14px; color:#98a2b3; }
.pn-datetime input { padding-left:40px; }
.pn-actions { padding:18px 24px; display:flex; justify-content:space-between; gap:14px; align-items:center; flex-wrap:wrap; }
.pn-actions > div { display:flex; align-items:center; justify-content:flex-end; gap:10px; flex-wrap:wrap; }
.pn-actions small { display:block; width:100%; color:#667085; text-align:right; }
.pn-primary-btn, .pn-draft-btn, .pn-soft-btn { border-radius:12px; font-weight:800; min-height:42px; }
.pn-primary-btn { background:linear-gradient(135deg,#246bfd,#00a5ff); border:0; color:#fff; box-shadow:0 10px 24px rgba(36,107,253,.22); }
.pn-draft-btn { background:#101828; border:0; color:#fff; }
.pn-soft-btn { background:#fff; border:1px solid #d9e2ef; color:#344054; }
.pn-live-dot { color:#079455; font-size:12px; font-weight:800; }
.pn-live-dot i { font-size:8px; }
.pn-preview-card { position:sticky; top:16px; }
.pn-phone { margin:26px auto 30px; width:min(360px, calc(100% - 28px)); min-height:560px; border-radius:34px; background:linear-gradient(160deg,#111827,#26364f); padding:52px 18px 18px; box-shadow:inset 0 0 0 8px #0b1220, 0 22px 55px rgba(16,24,40,.22); position:relative; }
.pn-phone-top { position:absolute; top:18px; left:50%; transform:translateX(-50%); width:92px; height:20px; border-radius:999px; background:#050914; }
.pn-notification-preview { background:#fff; border-radius:20px; padding:14px; box-shadow:0 18px 34px rgba(0,0,0,.24); }
.pn-app-row { display:flex; align-items:center; gap:8px; color:#344054; font-size:13px; }
.pn-app-row small { margin-left:auto; color:#98a2b3; }
.pn-app-logo { width:28px; height:28px; border-radius:8px; background:#246bfd; color:#fff; display:grid; place-items:center; font-weight:900; }
.pn-preview-content { display:flex; gap:12px; margin-top:12px; }
.pn-preview-content h3 { font-size:16px; font-weight:900; margin:0 0 5px; color:#101828; overflow-wrap:anywhere; }
.pn-preview-content p { margin:0; color:#475467; font-size:13px; line-height:1.4; overflow-wrap:anywhere; }
.pn-preview-content img { display:none; width:74px; height:74px; object-fit:cover; border-radius:14px; flex:0 0 auto; }
.pn-history-card { padding:0; margin-bottom:24px; }
.pn-history-top { padding:22px 24px; display:flex; justify-content:space-between; align-items:center; gap:16px; border-bottom:1px solid #edf1f7; flex-wrap:wrap; }
.pn-history-top h2 { margin:0; font-size:20px; font-weight:900; color:#101828; }
.pn-filters { display:flex; gap:10px; flex-wrap:wrap; }
.pn-filter-control { min-height:42px; padding:0 12px; background:#fff; border-radius:12px; }
.pn-filter-search { display:flex; align-items:center; gap:8px; padding:0 12px; min-width:240px; }
.pn-filter-search input { border:0; outline:0; width:100%; }
.pn-filter-search i { color:#98a2b3; }
.pn-history-list { display:grid; gap:14px; padding:18px; }
.pn-history-item { display:grid; grid-template-columns:1.4fr 1fr auto; gap:18px; align-items:center; padding:18px; border:1px solid #edf1f7; border-radius:18px; background:#fff; }
.pn-history-main { display:flex; gap:14px; min-width:0; }
.pn-history-icon { width:42px; height:42px; border-radius:14px; background:#e7f0ff; color:#246bfd; display:grid; place-items:center; flex:0 0 auto; }
.pn-history-title-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.pn-history-title-row h3 { margin:0; color:#101828; font-size:16px; font-weight:900; overflow-wrap:anywhere; }
.pn-history-main p { color:#667085; margin:5px 0 8px; overflow-wrap:anywhere; }
.pn-meta-row { display:flex; flex-wrap:wrap; gap:12px; color:#667085; font-size:12px; }
.pn-status { border-radius:999px; padding:5px 9px; font-size:11px; font-weight:900; }
.pn-badge-gray { background:#f2f4f7; color:#475467; }
.pn-badge-orange { background:#fff4e5; color:#b54708; }
.pn-badge-green { background:#ecfdf3; color:#067647; }
.pn-badge-red { background:#fef3f2; color:#b42318; }
.pn-analytics { display:grid; gap:8px; }
.pn-analytics div { min-width:150px; }
.pn-analytics span { color:#667085; font-size:12px; font-weight:800; }
.pn-analytics strong { float:right; color:#344054; font-size:12px; }
.pn-progress { height:8px; border-radius:999px; background:#eef3fb; overflow:hidden; clear:both; }
.pn-progress b { display:block; height:100%; background:#12b76a; border-radius:999px; }
.pn-progress-opened b { background:#246bfd; }
.pn-progress-failed b { background:#f04438; }
.pn-history-actions { display:flex; gap:7px; align-items:center; }
.pn-icon-btn { width:38px; height:38px; border-radius:12px; border:1px solid #d9e2ef; background:#fff; color:#475467; display:grid; place-items:center; }
.pn-icon-btn:hover { color:#246bfd; border-color:#b8ccff; }
.pn-danger-icon:hover { color:#d92d20; border-color:#fecaca; }
.pn-empty { padding:42px; display:grid; place-items:center; color:#667085; text-align:center; }
.pn-empty i { font-size:34px; color:#98a2b3; margin-bottom:10px; }
.pn-pagination { padding:0 24px 22px; }
.pn-alert-success, .pn-alert-danger { border:0; border-radius:14px; font-weight:700; }
.pn-alert-success { background:#ecfdf3; color:#067647; }
.pn-alert-danger { background:#fef3f2; color:#b42318; }
.pn-modal { border:0; border-radius:18px; overflow:hidden; }
@media (max-width: 1199.98px) { .pn-preview-card { position:static; } .pn-history-item { grid-template-columns:1fr; } .pn-history-actions { justify-content:flex-start; } }
@media (max-width: 767.98px) { .pn-page-head, .pn-history-top { align-items:flex-start; flex-direction:column; } .pn-choice-grid-3, .pn-segmented { grid-template-columns:1fr; } .pn-section, .pn-card-header, .pn-actions { padding:16px; } .pn-filters, .pn-filter-search { width:100%; } .pn-filter-control { width:100%; } .pn-actions > div, .pn-actions small { justify-content:flex-start; text-align:left; } .pn-phone { min-height:430px; } }
</style>
@stop

@section('js')
<script>
var recipients = @json($recipientOptions);
var selectedRecipients = new Map();
var modeHelp = {
    bulk: 'Notification goes to everyone in the selected audience.',
    single: 'Send to one person. Choose a recipient from the searchable picker.',
    multiple: 'Choose multiple recipients. Selected people appear as removable chips.'
};

function activeValue(name) {
    var checked = document.querySelector('input[name="' + name + '"]:checked');
    return checked ? checked.value : null;
}

function syncChoiceGroups() {
    document.querySelectorAll('[data-radio-group]').forEach(function(group) {
        group.querySelectorAll('label').forEach(function(label) {
            var input = label.querySelector('input');
            label.classList.toggle('active', input && input.checked);
        });
    });
}

function updateRecipientSection() {
    var mode = activeValue('send_mode');
    document.getElementById('recipientSection').style.display = mode === 'bulk' ? 'none' : 'block';
    document.getElementById('modeHelp').textContent = modeHelp[mode] || modeHelp.bulk;
    if (mode === 'single' && selectedRecipients.size > 1) {
        var first = Array.from(selectedRecipients.keys())[0];
        selectedRecipients.forEach(function(_, key) {
            if (String(key) !== String(first)) selectedRecipients.delete(key);
        });
        renderSelectedRecipients();
    }
    renderRecipientResults();
}

function recipientAllowed(recipient) {
    var target = activeValue('target_type');
    return target === 'both' || (target === 'users' && recipient.role === 'user') || (target === 'vendors' && recipient.role === 'vendor');
}

function addRecipient(recipient) {
    if (activeValue('send_mode') === 'single') selectedRecipients.clear();
    selectedRecipients.set(String(recipient.id), recipient);
    renderSelectedRecipients();
    renderRecipientResults();
}

function removeRecipient(id) {
    selectedRecipients.delete(String(id));
    renderSelectedRecipients();
    renderRecipientResults();
}

function renderSelectedRecipients() {
    var wrap = document.getElementById('selectedRecipients');
    var hidden = document.getElementById('recipientHiddenInputs');
    wrap.innerHTML = '';
    hidden.innerHTML = '';
    selectedRecipients.forEach(function(recipient) {
        var chip = document.createElement('span');
        chip.className = 'pn-chip';
        chip.innerHTML = recipient.label + ' <button type="button" aria-label="Remove">&times;</button>';
        chip.querySelector('button').addEventListener('click', function() { removeRecipient(recipient.id); });
        wrap.appendChild(chip);

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'receiver_ids[]';
        input.value = recipient.id;
        hidden.appendChild(input);
    });
    document.getElementById('selectedCount').textContent = selectedRecipients.size;
}

function renderRecipientResults() {
    var results = document.getElementById('recipientResults');
    var query = document.getElementById('recipientSearch').value.toLowerCase().trim();
    var matches = recipients.filter(function(recipient) {
        var haystack = (recipient.name + ' ' + (recipient.mobile || '') + ' ' + recipient.label).toLowerCase();
        return recipientAllowed(recipient) && !selectedRecipients.has(String(recipient.id)) && (!query || haystack.indexOf(query) !== -1);
    }).slice(0, 8);

    results.innerHTML = '';
    results.style.display = activeValue('send_mode') === 'bulk' ? 'none' : 'block';
    matches.forEach(function(recipient) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'pn-result';
        button.innerHTML = '<span class="pn-avatar">' + recipient.initials + '</span><span><strong>' + recipient.label + '</strong><br><small>' + (recipient.mobile || 'No mobile number') + '</small></span>';
        button.addEventListener('click', function() { addRecipient(recipient); });
        results.appendChild(button);
    });

    if (!matches.length) {
        results.innerHTML = '<div class="p-3 text-muted small">No matching recipients.</div>';
    }
}

function updatePreview() {
    var title = document.getElementById('notificationTitle').value.trim();
    var body = document.getElementById('notificationBody').value.trim();
    document.getElementById('previewTitle').textContent = title || 'Notification title';
    document.getElementById('previewBody').textContent = body || 'Your message preview will appear here while you type.';
    document.getElementById('titleCounter').textContent = document.getElementById('notificationTitle').value.length + ' / 150';
    document.getElementById('bodyCounter').textContent = document.getElementById('notificationBody').value.length + ' / 1000';
}

function setRadioValue(name, value) {
    var input = document.querySelector('input[name="' + name + '"][value="' + value + '"]');
    if (input) input.checked = true;
}

document.querySelectorAll('input[name="target_type"], input[name="send_mode"]').forEach(function(input) {
    input.addEventListener('change', function() {
        syncChoiceGroups();
        updateRecipientSection();
    });
});

document.getElementById('recipientSearch').addEventListener('input', renderRecipientResults);
document.getElementById('notificationTitle').addEventListener('input', updatePreview);
document.getElementById('notificationBody').addEventListener('input', updatePreview);

document.getElementById('notificationImage').addEventListener('change', function(event) {
    var image = document.getElementById('previewImage');
    var file = event.target.files[0];
    if (!file) {
        image.style.display = 'none';
        image.removeAttribute('src');
        return;
    }
    image.src = URL.createObjectURL(file);
    image.style.display = 'block';
});

document.getElementById('scheduleToggle').addEventListener('change', function() {
    document.getElementById('scheduleField').style.display = this.checked ? 'block' : 'none';
    document.getElementById('scheduledAt').disabled = !this.checked;
});

document.querySelectorAll('.pn-recipient-shortcut').forEach(function(button) {
    button.addEventListener('click', function() {
        var recipient = recipients.find(function(item) { return String(item.id) === String(button.dataset.id); });
        if (recipient && recipientAllowed(recipient) && activeValue('send_mode') !== 'bulk') addRecipient(recipient);
    });
});

document.querySelectorAll('.pn-duplicate').forEach(function(button) {
    button.addEventListener('click', function() {
        document.getElementById('notificationTitle').value = button.dataset.title || '';
        document.getElementById('notificationBody').value = button.dataset.body || '';
        setRadioValue('target_type', button.dataset.target || 'users');
        setRadioValue('send_mode', button.dataset.mode || 'bulk');
        selectedRecipients.clear();
        syncChoiceGroups();
        updateRecipientSection();
        updatePreview();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

document.getElementById('cancelCompose').addEventListener('click', function() {
    selectedRecipients.clear();
    setTimeout(function() {
        syncChoiceGroups();
        updateRecipientSection();
        renderSelectedRecipients();
        updatePreview();
        document.getElementById('previewImage').style.display = 'none';
    }, 0);
});

syncChoiceGroups();
updateRecipientSection();
renderSelectedRecipients();
updatePreview();
</script>
@stop
