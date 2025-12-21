<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report_title ?? 'EggFlow Report' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f97316;
        }
        .logo {
            font-size: 24pt;
            font-weight: bold;
            color: #f97316;
            margin-bottom: 5px;
        }
        .logo span {
            color: #333;
        }
        .report-title {
            font-size: 16pt;
            color: #333;
            margin-top: 10px;
        }
        .meta-info {
            font-size: 9pt;
            color: #666;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #f97316;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #fde047;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #fef3c7;
            font-weight: bold;
            color: #92400e;
            font-size: 9pt;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #fefce8;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary-box {
            background-color: #fef3c7;
            border: 1px solid #fde047;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
        }
        .summary-value {
            font-size: 18pt;
            font-weight: bold;
            color: #f97316;
        }
        .summary-label {
            font-size: 9pt;
            color: #666;
            margin-top: 5px;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 8pt;
            color: #999;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .positive {
            color: #059669;
        }
        .negative {
            color: #dc2626;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Egg<span>Flow</span></div>
            <div class="report-title">{{ $report_title ?? 'Report' }}</div>
            <div class="meta-info">Generated: {{ $generated_at ?? now()->format('F j, Y g:i A') }}</div>
        </div>

        @yield('content')
    </div>

    <div class="footer">
        EggFlow Poultry Farm Management System &bull; Confidential
    </div>
</body>
</html>
