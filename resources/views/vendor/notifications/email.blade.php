<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'Notification' }}</title>
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }
        
        /* Main styles */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            background-color: #f4f4f4;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
        }
        
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 20px 0;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 40px;
            text-align: center;
        }
        
        .email-logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .email-body {
            padding: 40px;
        }
        
        .email-footer {
            background-color: #f9fafb;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }
        
        .email-footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .email-footer a:hover {
            text-decoration: underline;
        }
        
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #667eea;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        
        .button:hover {
            background-color: #5568d3;
        }
        
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 20px;
        }
        
        .content-line {
            margin-bottom: 15px;
            color: #374151;
        }
        
        .content-line strong {
            color: #111827;
        }
        
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                border-radius: 0 !important;
            }
            .email-header,
            .email-body,
            .email-footer {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td align="center" style="padding: 20px 0;">
                    <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" width="600">
                        <!-- Header with Logo -->
                        <tr>
                            <td class="email-header">
                                @if(config('app.logo_url'))
                                    <img src="{{ config('app.logo_url') }}" alt="{{ config('app.name') }}" class="email-logo">
                                @else
                                    <h1 style="margin: 0; color: #ffffff; font-size: 24px;">{{ config('app.name', 'YellowBooks Nigeria') }}</h1>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Body Content -->
                        <tr>
                            <td class="email-body">
                                @isset($greeting)
                                    <div class="greeting">{{ $greeting }}</div>
                                @endisset
                                
                                @foreach($introLines as $line)
                                    <div class="content-line">{{ $line }}</div>
                                @endforeach
                                
                                @isset($actionText)
                                    <div style="text-align: center; margin: 30px 0;">
                                        <a href="{{ $actionUrl }}" class="button">{{ $actionText }}</a>
                                    </div>
                                @endisset
                                
                                @foreach($outroLines as $line)
                                    <div class="content-line">{{ $line }}</div>
                                @endforeach
                                
                                @isset($salutation)
                                    <div style="margin-top: 30px; color: #6b7280;">{{ $salutation }}</div>
                                @endisset
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td class="email-footer">
                                <p style="margin: 0 0 10px 0;">
                                    <strong>{{ config('app.name') }}</strong>
                                </p>
                                <p style="margin: 0 0 10px 0; font-size: 13px;">
                                    © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                                </p>
                                <p style="margin: 0; font-size: 12px;">
                                    <a href="{{ config('app.url') }}">Visit Website</a> | 
                                    <a href="{{ config('app.url') }}/contact">Contact Support</a>
                                </p>
                                @if(isset($actionUrl))
                                    <p style="margin: 15px 0 0 0; font-size: 12px; color: #9ca3af;">
                                        If you're having trouble clicking the button, copy and paste this URL into your browser:<br>
                                        <a href="{{ $actionUrl }}" style="color: #667eea; word-break: break-all;">{{ $actionUrl }}</a>
                                    </p>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
