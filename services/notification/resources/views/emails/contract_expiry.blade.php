<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Expiry Notice</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #F8FAFC;
            padding: 40px 16px;
            -webkit-font-smoothing: antialiased;
        }

        .em-card {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(37, 37, 120, 0.08);
            box-shadow: 0 4px 20px rgba(37, 37, 120, 0.03);
        }

        /* ── Header ── */
        .em-header {
            background: #252578;
            padding: 32px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .em-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .em-logo-icon {
            width: 36px;
            height: 36px;
            background: #2E85D8;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .em-logo-name {
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        .em-logo-sub { color: rgba(255, 255, 255, 0.7); font-weight: 400; }
        .em-badge-header {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* ── Body ── */
        .em-body { padding: 36px; }

        .em-greeting {
            font-size: 20px;
            font-weight: 600;
            color: #252578;
            margin-bottom: 6px;
            letter-spacing: -0.2px;
        }
        .em-subline {
            font-size: 13px;
            color: rgba(37, 37, 120, 0.6);
            margin-bottom: 28px;
            line-height: 1.5;
        }

        /* ── Alert box ── */
        .em-alert {
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }
        .em-alert-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .em-alert-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .em-alert-msg {
            font-size: 13px;
            line-height: 1.6;
            font-weight: 500;
        }

        /* Severity: Notice (90 days) */
        .alert-blue                 { background: rgba(46, 133, 216, 0.05); border-left: 4px solid #2E85D8; }
        .alert-blue .em-alert-icon  { background: rgba(46, 133, 216, 0.12); }
        .alert-blue .em-alert-label { color: #2E85D8; }
        .alert-blue .em-alert-msg   { color: #252578; }

        /* Severity: Warning (30 days) */
        .alert-navy                 { background: rgba(37, 37, 120, 0.04); border-left: 4px solid #252578; }
        .alert-navy .em-alert-icon  { background: rgba(37, 37, 120, 0.1); }
        .alert-navy .em-alert-label { color: #252578; }
        .alert-navy .em-alert-msg   { color: #2F2F73; }

        /* Severity: Critical (1 day) */
        .alert-urgent               { background: #2F2F73; border-left: 4px solid #2E85D8; }
        .alert-urgent .em-alert-icon{ background: rgba(255, 255, 255, 0.15); }
        .alert-urgent .em-alert-label{ color: #2E85D8; }
        .alert-urgent .em-alert-msg  { color: #ffffff; }

        /* Default fallback */
        .alert-default                { background: rgba(37, 37, 120, 0.03); border-left: 4px solid rgba(37, 37, 120, 0.4); }
        .alert-default .em-alert-icon { background: rgba(37, 37, 120, 0.08); }
        .alert-default .em-alert-label{ color: #2F2F73; }
        .alert-default .em-alert-msg  { color: #252578; }

        /* ── Divider ── */
        .em-divider {
            border: none;
            border-top: 1px solid rgba(37, 37, 120, 0.08);
            margin: 0 0 24px;
        }

        /* ── Detail fields ── */
        .em-fields { margin-bottom: 28px; }
        .em-field {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(37, 37, 120, 0.04);
        }
        .em-field:last-child { border-bottom: none; }
        .em-field-label {
            font-size: 13px;
            color: rgba(37, 37, 120, 0.5);
            font-weight: 500;
        }
        .em-field-value {
            font-size: 13px;
            color: #252578;
            font-weight: 600;
        }
        .em-field-value.pill {
            background: rgba(46, 133, 216, 0.08);
            color: #2E85D8;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        /* ── CTA ── */
        .em-cta { text-align: center; margin-bottom: 6px; }
        .em-btn {
            display: inline-block;
            background: #2E85D8;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: background 0.15s ease;
        }
        .em-btn-note {
            margin-top: 12px;
            font-size: 11px;
            color: rgba(37, 37, 120, 0.4);
            text-align: center;
            word-break: break-all;
        }

        /* ── Footer ── */
        .em-footer {
            background: rgba(37, 37, 120, 0.02);
            border-top: 1px solid rgba(37, 37, 120, 0.06);
            padding: 24px 36px;
            text-align: center;
        }
        .em-footer-text {
            font-size: 11px;
            color: rgba(37, 37, 120, 0.4);
            line-height: 1.7;
            margin-bottom: 8px;
        }
        .em-footer-copy {
            font-size: 10px;
            color: rgba(37, 37, 120, 0.35);
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="em-card">

    {{-- ── Header ── --}}
    <div class="em-header">
        <div class="em-logo">
            <div class="em-logo-icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <rect x="3" y="3" width="6" height="6" rx="1.5" fill="white" opacity="0.9"/>
                    <rect x="11" y="3" width="6" height="6" rx="1.5" fill="white" opacity="0.5"/>
                    <rect x="3" y="11" width="6" height="6" rx="1.5" fill="white" opacity="0.5"/>
                    <rect x="11" y="11" width="6" height="6" rx="1.5" fill="white" opacity="0.9"/>
                </svg>
            </div>
            <span class="em-logo-name">CRMS <span class="em-logo-sub">Capstone</span></span>
        </div>

        @php
            $badgeText = 'Contract Alert';
            if ($notificationType === 'expiry_30') $badgeText = 'Renewal Reminder';
            elseif ($notificationType === 'expiry_90') $badgeText = 'Advance Notice';
        @endphp

        <span class="em-badge-header">{{ $badgeText }}</span>
    </div>

    {{-- ── Body ── --}}
    <div class="em-body">
        <p class="em-greeting">Hello, {{ $recipientName }}!</p>
        <p class="em-subline">You have an important contract notification that requires your attention.</p>

        @php
            $alertClass  = 'alert-default';
            $alertLabel  = 'General Update';
            $iconStroke  = '#252578';
            $iconPath    = 'M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z';
            $actionPill  = 'Action Required';

            if ($notificationType === 'expiry_1') {
                $alertClass = 'alert-urgent';
                $alertLabel = 'Urgent Notice';
                $iconStroke = '#2E85D8';
                $iconPath   = 'M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z';
                $actionPill = 'Renew Now';
            } elseif ($notificationType === 'expiry_30') {
                $alertClass = 'alert-navy';
                $alertLabel = 'Warning';
                $iconStroke = '#252578';
                $iconPath   = 'M12 8v4l3 3M12 2a10 10 0 110 20A10 10 0 0112 2z';
                $actionPill = 'Renewal Required';
            } elseif ($notificationType === 'expiry_90') {
                $alertClass = 'alert-blue';
                $alertLabel = 'Notice';
                $iconStroke = '#2E85D8';
                $iconPath   = 'M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z';
                $actionPill = 'Review & Renew';
            }
        @endphp

        <div class="em-alert {{ $alertClass }}">
            <div class="em-alert-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="{{ $iconPath }}"
                          stroke="{{ $iconStroke }}"
                          stroke-width="2"
                          stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>
            </div>
            <div>
                <div class="em-alert-label">{{ $alertLabel }}</div>
                <div class="em-alert-msg">{{ $messageText }}</div>
            </div>
        </div>

        <hr class="em-divider">

        <div class="em-fields">
            @if($contractId)
            <div class="em-field">
                <span class="em-field-label">Contract ID</span>
                <span class="em-field-value">#{{ $contractId }}</span>
            </div>
            @endif
            <div class="em-field">
                <span class="em-field-label">Required Action</span>
                <span class="em-field-value pill">{{ $actionPill }}</span>
            </div>
            <div class="em-field">
                <span class="em-field-label">System Action</span>
                <span class="em-field-value">Renewal / Action Required</span>
            </div>
        </div>

        @php
            $rolePrefix = 'sales'; // Default fallback
            if (isset($userRole)) {
                if ($userRole === 'Admin') {
                    $rolePrefix = 'admin';
                } elseif (in_array($userRole, ['Manager', 'Finance Manager'])) {
                    $rolePrefix = 'manager';
                } elseif (in_array($userRole, ['Sales', 'Employee', 'Finance Employee', 'Finance'])) {
                    $rolePrefix = 'sales';
                }
            }
        @endphp

        <div class="em-cta">
            <a href="{{ config('app.frontend_url') }}/{{ $rolePrefix }}/contracts" class="em-btn">
                Access Contract Portal →
            </a>
        </div>
        <p class="em-btn-note">{{ config('app.frontend_url') }}/{{ $rolePrefix }}/contracts</p>
    </div>

    {{-- ── Footer ── --}}
    <div class="em-footer">
        <p class="em-footer-text">
            This is an automated notification from the CRMS Contract Management System.<br>
            Please do not reply directly to this email.
        </p>
        <p class="em-footer-copy">&copy; {{ date('Y') }} CRMS Capstone. All rights reserved.</p>
    </div>

</div>
</body>
</html>
