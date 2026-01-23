<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $transaction->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            padding: 40px;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #4F46E5;
        }
        
        .header h1 {
            color: #4F46E5;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .status-completed {
            background-color: #10B981;
            color: white;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 10px 0;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 15px;
            color: #111;
            font-weight: 500;
        }
        
        .amount-section {
            background-color: #F9FAFB;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .amount-row:last-child {
            border-bottom: none;
        }
        
        .amount-label {
            font-size: 14px;
            color: #666;
        }
        
        .amount-value {
            font-size: 14px;
            font-weight: 500;
            color: #111;
        }
        
        .total-row {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #4F46E5;
        }
        
        .total-row .amount-label {
            font-size: 18px;
            font-weight: bold;
            color: #111;
        }
        
        .total-row .amount-value {
            font-size: 22px;
            font-weight: bold;
            color: #4F46E5;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .footer p {
            margin-bottom: 8px;
        }
        
        .thank-you {
            margin-top: 40px;
            text-align: center;
            font-size: 18px;
            color: #4F46E5;
            font-weight: 600;
        }
        
        .divider {
            height: 1px;
            background-color: #E5E7EB;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        {{-- Header --}}
        <div class="header">
            <h1>YellowBooks Nigeria</h1>
            <p>Payment Receipt</p>
            <span class="status-badge status-completed">{{ strtoupper($transaction->status) }}</span>
        </div>
        
        {{-- Transaction Information --}}
        <div class="section">
            <div class="section-title">Transaction Details</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Receipt Number</div>
                    <div class="info-value">{{ $transaction->reference }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Transaction Date</div>
                    <div class="info-value">{{ $transaction->created_at->format('F d, Y h:i A') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">{{ $transaction->gateway ? ucfirst($transaction->gateway->name) : 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Type</div>
                    <div class="info-value">
                        @if($transaction->transactionable_type === 'App\Models\Subscription')
                            Subscription Payment
                        @elseif($transaction->transactionable_type === 'App\Models\AdCampaign')
                            Ad Campaign Payment
                        @elseif($transaction->transactionable_type === 'App\Models\Wallet')
                            Wallet Funding
                        @else
                            {{ class_basename($transaction->transactionable_type) }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Customer Information --}}
        <div class="section">
            <div class="section-title">Customer Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Customer Name</div>
                    <div class="info-value">{{ $transaction->user->name ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email Address</div>
                    <div class="info-value">{{ $transaction->user->email ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        
        @if($transaction->transactionable_type === 'App\Models\Subscription' && $transaction->transactionable)
            {{-- Subscription Details --}}
            <div class="section">
                <div class="section-title">Subscription Details</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Plan Name</div>
                        <div class="info-value">{{ $transaction->transactionable->plan->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Billing Cycle</div>
                        <div class="info-value">{{ ucfirst($transaction->transactionable->billing_interval ?? 'monthly') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Start Date</div>
                        <div class="info-value">{{ $transaction->transactionable->starts_at?->format('F d, Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">End Date</div>
                        <div class="info-value">{{ $transaction->transactionable->ends_at?->format('F d, Y') ?? 'N/A' }}</div>
                    </div>
                    @if($transaction->transactionable->business)
                        <div class="info-item">
                            <div class="info-label">Business Name</div>
                            <div class="info-value">{{ $transaction->transactionable->business->business_name }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        @if($transaction->transactionable_type === 'App\Models\AdCampaign' && $transaction->transactionable)
            {{-- Ad Campaign Details --}}
            <div class="section">
                <div class="section-title">Ad Campaign Details</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Campaign Name</div>
                        <div class="info-value">{{ $transaction->transactionable->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Campaign Type</div>
                        <div class="info-value">{{ ucfirst($transaction->transactionable->type ?? 'N/A') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Start Date</div>
                        <div class="info-value">{{ $transaction->transactionable->start_date?->format('F d, Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">End Date</div>
                        <div class="info-value">{{ $transaction->transactionable->end_date?->format('F d, Y') ?? 'N/A' }}</div>
                    </div>
                    @if($transaction->transactionable->business)
                        <div class="info-item">
                            <div class="info-label">Business Name</div>
                            <div class="info-value">{{ $transaction->transactionable->business->business_name }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        {{-- Amount Breakdown --}}
        <div class="amount-section">
            <div class="amount-row">
                <span class="amount-label">Subtotal</span>
                <span class="amount-value">₦{{ number_format($transaction->amount, 2) }}</span>
            </div>
            @if($transaction->discount_amount > 0)
                <div class="amount-row">
                    <span class="amount-label">Discount</span>
                    <span class="amount-value">-₦{{ number_format($transaction->discount_amount, 2) }}</span>
                </div>
            @endif
            <div class="amount-row total-row">
                <span class="amount-label">Total Amount Paid</span>
                <span class="amount-value">₦{{ number_format($transaction->amount - ($transaction->discount_amount ?? 0), 2) }}</span>
            </div>
        </div>
        
        {{-- Thank You Message --}}
        <div class="thank-you">
            Thank you for your business!
        </div>
        
        {{-- Footer --}}
        <div class="footer">
            <p><strong>YellowBooks Nigeria</strong></p>
            <p>Nigeria's #1 Business Directory Platform</p>
            <p>Email: support@yellowbooks.ng | Phone: +234 XXX XXX XXXX</p>
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                This is a computer-generated receipt and does not require a signature.
            </p>
            <p style="font-size: 11px; color: #999;">
                For any inquiries regarding this transaction, please contact us with your receipt number.
            </p>
        </div>
    </div>
</body>
</html>
