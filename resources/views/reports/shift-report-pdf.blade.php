<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shift Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #1f2937;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 15px;
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
            margin-bottom: 15px;
            font-size: 9px;
            color: #6b7280;
        }
        .filters {
            background: #f9fafb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 9px;
        }
        .filters span {
            margin-right: 15px;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-row {
            display: table-row;
        }
        .summary-card {
            display: table-cell;
            width: 16.66%;
            padding: 8px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .summary-card .value {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
        }
        .summary-card .label {
            font-size: 8px;
            color: #6b7280;
            margin-top: 3px;
        }
        .summary-card.shortage .value { color: #dc2626; }
        .summary-card.overage .value { color: #16a34a; }
        .summary-card.warning .value { color: #d97706; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        thead {
            background: #f3f4f6;
        }
        th {
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        th.right, td.right {
            text-align: right;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 9px;
        }
        .date-cell {
            white-space: nowrap;
        }
        .date-cell .time {
            font-size: 8px;
            color: #9ca3af;
        }
        .balanced {
            color: #16a34a;
            font-weight: 500;
        }
        .overage {
            color: #16a34a;
            font-weight: 500;
        }
        .shortage {
            color: #dc2626;
            font-weight: 500;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 500;
        }
        .badge-balanced {
            background: #dcfce7;
            color: #166534;
        }
        .footer {
            margin-top: 20px;
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
    <div class="container">
        <div class="header">
            <h1>EggFlow Shift Report</h1>
            <div class="subtitle">Cash Drawer Accountability Report</div>
        </div>

        <div class="meta">
            Generated: {{ now()->format('F d, Y g:i A') }}
        </div>

        @if($filters['shop'] || $filters['user'] || $filters['dateFrom'] || $filters['dateTo'])
            <div class="filters">
                <strong>Filters:</strong>
                @if($filters['shop'])
                    <span>Shop: {{ $filters['shop'] }}</span>
                @endif
                @if($filters['user'])
                    <span>Staff: {{ $filters['user'] }}</span>
                @endif
                @if($filters['dateFrom'])
                    <span>From: {{ $filters['dateFrom'] }}</span>
                @endif
                @if($filters['dateTo'])
                    <span>To: {{ $filters['dateTo'] }}</span>
                @endif
            </div>
        @endif

        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-card">
                    <div class="value">{{ $summary['total_shifts'] }}</div>
                    <div class="label">Total Shifts</div>
                </div>
                <div class="summary-card warning">
                    <div class="value">{{ $summary['with_discrepancy'] }}</div>
                    <div class="label">With Discrepancy</div>
                </div>
                <div class="summary-card">
                    <div class="value">{{ number_format($summary['discrepancy_rate'], 1) }}%</div>
                    <div class="label">Discrepancy Rate</div>
                </div>
                <div class="summary-card shortage">
                    <div class="value">₱{{ number_format(abs($summary['total_shortage']), 2) }}</div>
                    <div class="label">Total Shortage</div>
                </div>
                <div class="summary-card overage">
                    <div class="value">₱{{ number_format($summary['total_overage'], 2) }}</div>
                    <div class="label">Total Overage</div>
                </div>
                <div class="summary-card {{ $summary['net_discrepancy'] >= 0 ? 'overage' : 'shortage' }}">
                    <div class="value">{{ $summary['net_discrepancy'] >= 0 ? '+' : '' }}₱{{ number_format($summary['net_discrepancy'], 2) }}</div>
                    <div class="label">Net Discrepancy</div>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Staff</th>
                    <th>Shop</th>
                    <th class="right">Opening</th>
                    <th class="right">Expected</th>
                    <th class="right">Actual</th>
                    <th class="right">Discrepancy</th>
                    <th class="right">Duration</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shifts as $shift)
                    <tr>
                        <td class="date-cell">
                            {{ $shift->opened_at->format('M d, Y') }}<br>
                            <span class="time">{{ $shift->opened_at->format('g:i A') }} - {{ $shift->closed_at?->format('g:i A') ?? 'Open' }}</span>
                        </td>
                        <td>{{ $shift->user->name ?? 'N/A' }}</td>
                        <td>{{ $shift->shop->name ?? 'N/A' }}</td>
                        <td class="right">₱{{ number_format($shift->opening_cash, 2) }}</td>
                        <td class="right">₱{{ number_format($shift->expected_cash, 2) }}</td>
                        <td class="right">₱{{ number_format($shift->closing_cash, 2) }}</td>
                        <td class="right">
                            @if($shift->discrepancy == 0)
                                <span class="badge badge-balanced">Balanced</span>
                            @elseif($shift->discrepancy > 0)
                                <span class="overage">+₱{{ number_format($shift->discrepancy, 2) }}</span>
                            @else
                                <span class="shortage">-₱{{ number_format(abs($shift->discrepancy), 2) }}</span>
                            @endif
                        </td>
                        <td class="right">
                            @if($shift->closed_at)
                                {{ $shift->opened_at->diff($shift->closed_at)->format('%Hh %Im') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            This is a computer-generated report. EggFlow &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
