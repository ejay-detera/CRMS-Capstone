<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Expiry Notice</title>
    <style>
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }
        .header {
            background-color: #252578;
            padding: 32px;
            text-align: center;
        }
        .logo-text {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .logo-sub {
            color: #2E85D8;
            font-weight: 400;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .message-box {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 15px;
            line-height: 1.6;
        }
        /* Alert severity classes */
        .alert-expiry-1 {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
        .alert-expiry-30 {
            background-color: #fff7ed;
            border-left: 4px solid #f97316;
            color: #c2410c;
        }
        .alert-expiry-90 {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            color: #1e40af;
        }
        .alert-default {
            background-color: #f8fafc;
            border-left: 4px solid #64748b;
            color: #334155;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }
        .details-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .details-label {
            color: #64748b;
            font-weight: 500;
            width: 30%;
        }
        .details-value {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }
        .cta-container {
            text-align: center;
            margin-bottom: 16px;
        }
        .btn-cta {
            display: inline-block;
            background-color: #2E85D8;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-cta:hover {
            background-color: #252578;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
        }
        .footer a {
            color: #2E85D8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <p class="logo-text">CRMS <span class="logo-sub">Capstone</span></p>
            </div>
            <div class="content">
                <h2 class="greeting">Hello, {{ $recipientName }}!</h2>
                
                @php
                    $alertClass = 'alert-default';
                    $urgencyText = 'General Update';
                    if ($notificationType === 'expiry_1') {
                        $alertClass = 'alert-expiry-1';
                        $urgencyText = 'Critical Urgency';
                    } elseif ($notificationType === 'expiry_30') {
                        $alertClass = 'alert-expiry-30';
                        $urgencyText = 'High Urgency';
                    } elseif ($notificationType === 'expiry_90') {
                        $alertClass = 'alert-expiry-90';
                        $urgencyText = 'Notice';
                    }
                @endphp
                
                <div class="message-box {{ $alertClass }}">
                    <strong>{{ $urgencyText }}:</strong> {{ $messageText }}
                </div>
                
                <table class="details-table">
                    @if($contractId)
                        <tr>
                            <td class="details-label">Contract ID</td>
                            <td class="details-value">#{{ $contractId }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="details-label">System Action</td>
                        <td class="details-value">Renewal / Action Required</td>
                    </tr>
                </table>
                
                <div class="cta-container">
                    <a href="http://localhost:5001/admin/contracts" class="btn-cta" target="_blank">Access Portal</a>
                </div>
            </div>
            <div class="footer">
                <p>This is an automated notification from the CRMS Contract Management System.<br>
                Please do not reply directly to this email.</p>
                <p>&copy; {{ date('Y') }} CRMS Capstone. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
