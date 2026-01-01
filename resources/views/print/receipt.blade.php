<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $sale->sale_code }}</title>
    <style>
        /* Screen styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            background: #f3f4f6;
            padding: 20px;
        }

        .receipt-container {
            max-width: 300px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .receipt {
            width: 100%;
        }

        .header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ccc;
            margin-bottom: 10px;
        }

        .shop-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .shop-address {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }

        .receipt-info {
            font-size: 10px;
            text-align: center;
            margin-top: 8px;
        }

        .divider {
            border-top: 1px dashed #ccc;
            margin: 10px 0;
        }

        .items-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ccc;
        }

        .item {
            display: flex;
            flex-direction: column;
            padding: 5px 0;
            border-bottom: 1px dotted #eee;
        }

        .item-name {
            font-weight: bold;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #666;
        }

        .totals {
            margin-top: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .total-row.grand {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 5px;
        }

        .payment-info {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
            color: #666;
        }

        .footer .thanks {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .print-button {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            padding: 12px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .print-button:hover {
            background: #d97706;
        }

        /* Print styles - 80mm thermal receipt */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .receipt-container {
                max-width: 80mm;
                width: 80mm;
                padding: 5mm;
                box-shadow: none;
                border-radius: 0;
            }

            .print-button {
                display: none;
            }

            .receipt {
                font-size: 10px;
            }

            .shop-name {
                font-size: 14px;
            }

            .total-row.grand {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt">
            {{-- Header --}}
            <div class="header">
                <div class="shop-name">{{ $sale->shop->name ?? 'EggFlow Store' }}</div>
                @if($sale->shop->address ?? null)
                    <div class="shop-address">{{ $sale->shop->address }}</div>
                @endif
                @if($sale->shop->phone ?? null)
                    <div class="shop-address">Tel: {{ $sale->shop->phone }}</div>
                @endif
                <div class="receipt-info">
                    <div>Receipt #: {{ $sale->sale_code }}</div>
                    <div>{{ $sale->sold_at->format('M d, Y g:i A') }}</div>
                    <div>Cashier: {{ $sale->staff->name ?? 'N/A' }}</div>
                </div>
            </div>

            {{-- Items --}}
            <div class="items">
                <div class="items-header">
                    <span>ITEM</span>
                    <span>AMOUNT</span>
                </div>

                @foreach($sale->items as $item)
                    <div class="item">
                        <div class="item-name">{{ $item->category->name ?? 'Item' }}</div>
                        <div class="item-details">
                            <span>{{ $item->quantity }} x ₱{{ number_format($item->unit_price, 2) }}</span>
                            <span>₱{{ number_format($item->line_total, 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="divider"></div>

            {{-- Totals --}}
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₱{{ number_format($sale->subtotal, 2) }}</span>
                </div>
                @if($sale->tax > 0)
                    <div class="total-row">
                        <span>Tax:</span>
                        <span>₱{{ number_format($sale->tax, 2) }}</span>
                    </div>
                @endif
                @if($sale->discount > 0)
                    <div class="total-row">
                        <span>Discount:</span>
                        <span>-₱{{ number_format($sale->discount, 2) }}</span>
                    </div>
                @endif
                <div class="total-row grand">
                    <span>TOTAL:</span>
                    <span>₱{{ number_format($sale->total, 2) }}</span>
                </div>
            </div>

            {{-- Payment Info --}}
            <div class="payment-info">
                <div class="total-row">
                    <span>Payment Method:</span>
                    <span>{{ ucfirst($sale->payment_method) }}</span>
                </div>
                @if($sale->payment_method === 'cash' && $sale->amount_tendered)
                    <div class="total-row">
                        <span>Amount Tendered:</span>
                        <span>₱{{ number_format($sale->amount_tendered, 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span>Change:</span>
                        <span>₱{{ number_format($sale->amount_tendered - $sale->total, 2) }}</span>
                    </div>
                @endif
            </div>

            <div class="divider"></div>

            {{-- Footer --}}
            <div class="footer">
                <div class="thanks">Thank You!</div>
                <div>Please come again</div>
                @if($sale->notes)
                    <div style="margin-top: 8px; font-style: italic;">
                        Note: {{ $sale->notes }}
                    </div>
                @endif
                <div style="margin-top: 10px;">
                    --------------------------------
                </div>
                <div>Powered by EggFlow</div>
            </div>
        </div>
    </div>

    <button class="print-button" onclick="window.print()">
        🖨️ Print Receipt (Ctrl+P)
    </button>

    <script>
        // Auto-print option (uncomment if desired)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
