<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $sale->sale_code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            width: 80mm;
            padding: 5mm;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .logo {
            font-size: 14pt;
            font-weight: bold;
        }
        .shop-info {
            font-size: 8pt;
            margin-top: 5px;
        }
        .receipt-info {
            margin: 10px 0;
            font-size: 8pt;
        }
        .receipt-info table {
            width: 100%;
        }
        .receipt-info td {
            padding: 1px 0;
        }
        .items {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 10px 0;
            margin: 10px 0;
        }
        .item {
            margin-bottom: 5px;
        }
        .item-name {
            font-weight: bold;
        }
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
        }
        .totals {
            margin: 10px 0;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 2px 0;
        }
        .totals .total-row {
            font-weight: bold;
            font-size: 11pt;
            border-top: 1px solid #000;
        }
        .payment-info {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 8pt;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">EggFlow</div>
        <div class="shop-info">
            <strong>{{ $sale->shop->name }}</strong><br>
            {{ $sale->shop->address ?? '' }}<br>
            {{ $sale->shop->contact ?? '' }}
        </div>
    </div>

    <div class="receipt-info">
        <table>
            <tr>
                <td>Receipt #:</td>
                <td class="text-right">{{ $sale->sale_code }}</td>
            </tr>
            <tr>
                <td>Date:</td>
                <td class="text-right">{{ $sale->created_at->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td>Time:</td>
                <td class="text-right">{{ $sale->created_at->format('h:i A') }}</td>
            </tr>
            <tr>
                <td>Cashier:</td>
                <td class="text-right">{{ $sale->staff->name ?? 'N/A' }}</td>
            </tr>
            @if($sale->customer)
                <tr>
                    <td>Customer:</td>
                    <td class="text-right">{{ $sale->customer->name }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="items">
        @foreach($sale->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->eggCategory->name ?? 'Egg' }}</div>
                <div class="item-details">
                    <span>{{ $item->quantity }} x PHP {{ number_format($item->unit_price, 2) }}</span>
                    <span>PHP {{ number_format($item->line_total, 2) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">PHP {{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            @if($sale->tax > 0)
                <tr>
                    <td>Tax:</td>
                    <td class="text-right">PHP {{ number_format($sale->tax, 2) }}</td>
                </tr>
            @endif
            @if(($sale->discount ?? 0) > 0)
                <tr>
                    <td>Discount:</td>
                    <td class="text-right">-PHP {{ number_format($sale->discount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL:</td>
                <td class="text-right">PHP {{ number_format($sale->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="payment-info">
        <table>
            <tr>
                <td>Payment Method:</td>
                <td class="text-right">{{ ucfirst($sale->payment_method ?? 'Cash') }}</td>
            </tr>
            @if($sale->amount_tendered ?? false)
                <tr>
                    <td>Amount Tendered:</td>
                    <td class="text-right">PHP {{ number_format($sale->amount_tendered, 2) }}</td>
                </tr>
                <tr>
                    <td>Change:</td>
                    <td class="text-right">PHP {{ number_format($sale->change ?? 0, 2) }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="footer">
        <p>Thank you for your purchase!</p>
        <p style="margin-top: 5px;">Fresh eggs, happy customers</p>
        <p style="margin-top: 10px; font-size: 7pt;">
            {{ now()->format('Y-m-d H:i:s') }}
        </p>
    </div>
</body>
</html>
