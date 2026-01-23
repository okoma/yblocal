<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Invitation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .business-info {
            background: #f9fafb;
            padding: 20px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
        }
        .permissions {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .permissions ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .permissions li {
            margin: 8px 0;
            color: #374151;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background: #5568d3;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            font-size: 14px;
            color: #6b7280;
        }
        .expires-notice {
            background: #fef3c7;
            color: #92400e;
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">You've Been Invited!</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">Manage a business on {{ config('app.name') }}</p>
    </div>
    
    <div class="content">
        <p>Hello,</p>
        
        <p>You have been invited by <strong>{{ $invitation->inviter->name }}</strong> to manage their business on {{ config('app.name') }}.</p>
        
        <div class="business-info">
            <h3 style="margin-top: 0; color: #667eea;">Business Details</h3>
            <p style="margin: 8px 0;"><strong>Business:</strong> {{ $invitation->business->business_name }}</p>
            <p style="margin: 8px 0;"><strong>Position:</strong> {{ ucfirst(str_replace('_', ' ', $invitation->position)) }}</p>
            @if($invitation->business->city && $invitation->business->state)
            <p style="margin: 8px 0;"><strong>Location:</strong> {{ $invitation->business->city }}, {{ $invitation->business->state }}</p>
            @endif
        </div>
        
        @if($invitation->permissions && count(array_filter($invitation->permissions)) > 0)
        <div class="permissions">
            <h4 style="margin-top: 0; color: #374151;">Your Permissions:</h4>
            <ul>
                @foreach($invitation->permissions as $permission => $granted)
                    @if($granted)
                    <li>{{ ucfirst(str_replace('_', ' ', str_replace('can_', '', $permission))) }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
        @endif
        
        <div class="expires-notice">
            ⏰ <strong>Note:</strong> This invitation expires on {{ $expiresAt }}
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $acceptUrl }}" class="button">Accept Invitation</a>
        </div>
        
        <p style="font-size: 14px; color: #6b7280; margin-top: 30px;">
            If you don't want to accept this invitation, you can simply ignore this email. The invitation will expire automatically.
        </p>
    </div>
    
    <div class="footer">
        <p style="margin: 0;">
            This invitation was sent by {{ config('app.name') }}<br>
            If you have any questions, please contact support.
        </p>
        <p style="margin: 10px 0 0 0; font-size: 12px;">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>
    </div>
</body>
</html>
