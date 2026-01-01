<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sales Summary Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #f59e0b;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 11px;
            color: #6b7280;
        }

        .meta {
            margin-bottom: 20px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 4px;
        }

        .meta-row {
            display: inline-block;
            margin-right: 20px;
        }

        .meta-label {
            color: #6b7280;
        }

        .meta-value {
            font-weight: bold;
        }

        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-card {
            display: table-cell;
            width: 20%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .summary-card .value {
            font-size: 14px;
            font-weight: bold;
            color: #059669;
        }

        .summary-card .label {
            font-size: 9px;
            color: #6b7280;
            margin-top: 3px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1f2937;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
        }

        td {
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-green {
            color: #059669;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sales Summary Report</h1>
        <div class="subtitle">EggFlow Management System</div>
    </div>

    <div class="meta">
        <span class="meta-row">
            <span class="meta-label">Period:</span>
            <span class="meta-value">{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</span>
        </span>
        @if($shop)
            <span class="meta-row">
                <span class="meta-label">Shop:</span>
                <span class="meta-value">{{ $shop->name }}</span>
            </span>
        @else
            <span class="meta-row">
                <span class="meta-label">Shop:</span>
                <span class="meta-value">All Shops</span>
            </span>
        @endif
        <span class="meta-row">
            <span class="meta-label">Generated:</span>
            <span class="meta-value">{{ $generatedAt->format('M d, Y g:i A') }}</span>
        </span>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <div class="value">₱{{ number_format($summary['total_sales'], 2) }}</div>
            <div class="label">Total Sales</div>
        </div>
        <div class="summary-card">
            <div class="value" style="color: #2563eb;">{{ number_format($summary['total_transactions']) }}</div>
            <div class="label">Transactions</div>
        </div>
        <div class="summary-card">
            <div class="value" style="color: #d97706;">₱{{ number_format($summary['average_sale'], 2) }}</div>
            <div class="label">Average Sale</div>
        </div>
        <div class="summary-card">
            <div class="value" style="color: #7c3aed;">₱{{ number_format($summary['total_discount'], 2) }}</div>
            <div class="label">Discounts</div>
        </div>
        <div class="summary-card">
            <div class="value" style="color: #6b7280;">₱{{ number_format($summary['total_tax'], 2) }}</div>
            <div class="label">Tax</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Sales by Date</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Transactions</th>
                    <th class="text-right">Total Sales</th>
                    <th class="text-right">Discounts</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesByDate as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->date)->format('M d, Y') }}</td>
                        <td class="text-right">{{ number_format($row->count) }}</td>
                        <td class="text-right text-green">₱{{ number_format($row->total, 2) }}</td>
                        <td class="text-right">₱{{ number_format($row->discount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No sales data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Sales by Category</div>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Code</th>
                    <th class="text-right">Quantity Sold</th>
                    <th class="text-right">Total Sales</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesByCategory as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->code }}</td>
                        <td class="text-right">{{ number_format($row->quantity) }}</td>
                        <td class="text-right text-green">₱{{ number_format($row->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No sales data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Sales by Payment Method</div>
        <table>
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="text-right">Transactions</th>
                    <th class="text-right">Total Sales</th>
                    <th class="text-right">% of Total</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotal = $summary['total_sales'] ?: 1; @endphp
                @forelse($salesByPayment as $row)
                    <tr>
                        <td>{{ ucfirst($row->payment_method) }}</td>
                        <td class="text-right">{{ number_format($row->count) }}</td>
                        <td class="text-right text-green">₱{{ number_format($row->total, 2) }}</td>
                        <td class="text-right">{{ number_format(($row->total / $grandTotal) * 100, 1) }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No sales data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Report generated by EggFlow Management System on {{ $generatedAt->format('M d, Y g:i:s A') }}
    </div>
</body>
</html>
