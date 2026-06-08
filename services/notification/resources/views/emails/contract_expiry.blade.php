<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Expiry Notice</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #F3F4F9;
            padding: 40px 16px;
            margin: 0;
            color: #2F2F73;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(37, 37, 120, 0.05);
            border: 1px solid rgba(37, 37, 120, 0.06);
        }
        .header {
            background-color: #2F2F73;
            padding: 32px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-box {
            width: 32px;
            height: 32px;
            background: #2E85D8;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-text {
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
        }
        .logo-subtext {
            color: rgba(255, 255, 255, 0.6);
            font-weight: 400;
        }
        .badge {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .content {
            padding: 40px;
        }
        .title {
            font-size: 22px;
            font-weight: 700;
            color: #252578;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        .subtitle {
            font-size: 14px;
            color: rgba(37, 37, 120, 0.6);
            line-height: 1.5;
            margin-bottom: 32px;
        }
        .alert-box {
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            border-left: 4px solid;
            display: flex;
            gap: 16px;
        }
        .alert-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .alert-content {
            flex-grow: 1;
        }
        .alert-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }
        .alert-text {
            font-size: 13px;
            line-height: 1.6;
            font-weight: 500;
        }
        
        /* Alert Types */
        .alert-expiry-1 {
            background-color: #2F2F73;
            border-left-color: #2E85D8;
            color: #ffffff;
        }
        .alert-expiry-1 .alert-title {
            color: #2E85D8;
        }
        .alert-expiry-1 .alert-icon-wrapper {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .alert-expiry-30 {
            background-color: rgba(37, 37, 120, 0.03);
            border-left-color: #252578;
            color: #2F2F73;
        }
        .alert-expiry-30 .alert-title {
            color: #252578;
        }
        .alert-expiry-30 .alert-icon-wrapper {
            background-color: rgba(37, 37, 120, 0.08);
        }
        
        .alert-expiry-90 {
            background-color: rgba(46, 133, 216, 0.04);
            border-left-color: #2E85D8;
            color: #2F2F73;
        }
        .alert-expiry-90 .alert-title {
            color: #2E85D8;
        }
        .alert-expiry-90 .alert-icon-wrapper {
            background-color: rgba(46, 133, 216, 0.08);
        }
        
        .alert-default {
            background-color: rgba(37, 37, 120, 0.02);
            border-left-color: rgba(37, 37, 120, 0.3);
            color: #2F2F73;
        }
        .alert-default .alert-title {
            color: rgba(37, 37, 120, 0.6);
        }
        .alert-default .alert-icon-wrapper {
            background-color: rgba(37, 37, 120, 0.05);
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 36px;
        }
        .meta-row {
            border-bottom: 1px solid rgba(37, 37, 120, 0.06);
        }
        .meta-row:last-child {
            border-bottom: none;
        }
        .meta-label {
            padding: 14px 0;
            font-size: 13px;
            color: rgba(37, 37, 120, 0.55);
            font-weight: 500;
            text-align: left;
            width: 40%;
        }
        .meta-value {
            padding: 14px 0;
            font-size: 13px;
            color: #252578;
            font-weight: 600;
            text-align: right;
        }
        .pill {
            display: inline-block;
            background-color: rgba(46, 133, 216, 0.08);
            color: #2E85D8;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        
        .cta-section {
            text-align: center;
            margin-top: 10px;
        }
        .btn {
            display: inline-block;
            background-color: #2E85D8;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 44px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.2px;
            box-shadow: 0 4px 15px rgba(46, 133, 216, 0.2);
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #252578;
        }
        .url-text {
            margin-top: 14px;
            font-size: 11px;
            color: rgba(37, 37, 120, 0.4);
            word-break: break-all;
        }
        .footer {
            background-color: rgba(37, 37, 120, 0.01);
            border-top: 1px solid rgba(37, 37, 120, 0.05);
            padding: 28px 40px;
            text-align: center;
        }
        .footer-text {
            font-size: 11px;
            color: rgba(37, 37, 120, 0.4);
            line-height: 1.8;
            margin: 0 0 10px 0;
        }
        .footer-copyright {
            font-size: 10px;
            color: rgba(37, 37, 120, 0.3);
            font-weight: 500;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <div class="logo-box">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                        <rect x="3" y="3" width="6" height="6" rx="1.5" fill="white" opacity="0.9"/>
                        <rect x="11" y="3" width="6" height="6" rx="1.5" fill="white" opacity="0.5"/>
                        <rect x="3" y="11" width="6" height="6" rx="1.5" fill="white" opacity="0.5"/>
                        <rect x="11" y="11" width="6" height="6" rx="1.5" fill="white" opacity="0.9"/>
                    </svg>
                </div>
                <span class="logo-text">CRMS <span class="logo-subtext">Capstone</span></span>
            </div>
            
            @php
                $badgeText = 'Contract Alert';
                if ($notificationType === 'expiry_30') $badgeText = 'Renewal Reminder';
                elseif ($notificationType === 'expiry_90') $badgeText = 'Advance Notice';
            @endphp
            <span class="badge">{{ $badgeText }}</span>
        </div>
        
        <!-- Body Content -->
        <div class="content">
            <h1 class="title">Hello, {{ $recipientName }}!</h1>
            <p class="subtitle">You have an important contract notification that requires your attention.</p>
            
            @php
                $alertClass = 'alert-default';
                $alertLabel = 'General Update';
                $iconStroke = '#252578';
                $iconPath   = 'M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z';
                $actionPill = 'Action Required';

                if ($notificationType === 'expiry_1') {
                    $alertClass = 'alert-expiry-1';
                    $alertLabel = 'Urgent Notice';
                    $iconStroke = '#2E85D8';
                    $iconPath   = 'M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z';
                    $actionPill = 'Renew Now';
                } elseif ($notificationType === 'expiry_30') {
                    $alertClass = 'alert-expiry-30';
                    $alertLabel = 'Warning';
                    $iconStroke = '#252578';
                    $iconPath   = 'M12 8v4l3 3M12 2a10 10 0 110 20A10 10 0 0112 2z';
                    $actionPill = 'Renewal Required';
                } elseif ($notificationType === 'expiry_90') {
                    $alertClass = 'alert-expiry-90';
                    $alertLabel = 'Notice';
                    $iconStroke = '#2E85D8';
                    $iconPath   = 'M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z';
                    $actionPill = 'Review & Renew';
                }
            @endphp
            
            <div class="alert-box {{ $alertClass }}">
                <div class="alert-icon-wrapper">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="{{ $iconPath }}"
                              stroke="{{ $iconStroke }}"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="alert-content">
                    <div class="alert-title">{{ $alertLabel }}</div>
                    <div class="alert-text">{{ $messageText }}</div>
                </div>
            </div>
            
            <!-- Metadata details -->
            <table class="meta-table">
                <tbody>
                    @if($contractId)
                    <tr class="meta-row">
                        <th class="meta-label">Contract ID</th>
                        <td class="meta-value">#{{ $contractId }}</td>
                    </tr>
                    @endif
                    <tr class="meta-row">
                        <th class="meta-label">Required Action</th>
                        <td class="meta-value"><span class="pill">{{ $actionPill }}</span></td>
                    </tr>
                    <tr class="meta-row">
                        <th class="meta-label">System Status</th>
                        <td class="meta-value">Renewal Pipeline</td>
                    </tr>
                </tbody>
            </table>
            
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
            
            <!-- Call to Action -->
            <div class="cta-section">
                <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/{{ $rolePrefix }}/contracts" class="btn">
                    Access Contract Portal →
                </a>
                <p class="url-text">{{ config('app.frontend_url', 'http://localhost:5173') }}/{{ $rolePrefix }}/contracts</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                This is an automated notification from the CRMS Contract Management System.<br>
                Please do not reply directly to this email.
            </p>
            <p class="footer-copyright">&copy; {{ date('Y') }} CRMS Capstone. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
