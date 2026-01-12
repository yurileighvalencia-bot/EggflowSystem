@extends('reports.layout')

@section('title', $title ?? 'Inventory Report')

@section('content')
<div class="report-header">
    <h1>{{ $title ?? 'Inventory Report' }}</h1>
    <p>Generated: {{ $generated_at->format('F j, Y g:i A') }}</p>
    <p>Shop: {{ $shop }}</p>
</div>

{{-- Summary Stats --}}
<div class="summary-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
    <div class="stat-box" style="background: #f0fdf4; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ number_format($snapshot['total_available']) }}</div>
        <div style="font-size: 12px; color: #666;">Available Stock</div>
    </div>
    <div class="stat-box" style="background: #fffbeb; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 24px; font-weight: bold; color: #d97706;">{{ number_format($snapshot['total_reserved']) }}</div>
        <div style="font-size: 12px; color: #666;">Reserved Stock</div>
    </div>
    <div class="stat-box" style="background: #fef2f2; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 24px; font-weight: bold; color: #dc2626;">{{ $low_stock->count() }}</div>
        <div style="font-size: 12px; color: #666;">Low Stock Alerts</div>
    </div>
    <div class="stat-box" style="background: #fff7ed; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 24px; font-weight: bold; color: #ea580c;">{{ $expiring->count() }}</div>
        <div style="font-size: 12px; color: #666;">Expiring Soon</div>
    </div>
</div>

{{-- Stock by Category --}}
<h2 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 5px;">Stock by Category</h2>
<table class="data-table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
    <thead>
        <tr style="background: #f3f4f6;">
            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Category</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Available</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Reserved</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Total</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Batches</th>
        </tr>
    </thead>
    <tbody>
        @forelse($snapshot['categories'] as $category)
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $category['category_name'] }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee; color: #16a34a;">{{ number_format($category['available_stock']) }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee; color: #d97706;">{{ number_format($category['reserved_stock']) }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee; font-weight: bold;">{{ number_format($category['total_stock']) }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee;">{{ $category['batch_count'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="padding: 20px; text-align: center; color: #666;">No inventory data available</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background: #f3f4f6; font-weight: bold;">
            <td style="padding: 10px;">Total</td>
            <td style="padding: 10px; text-align: right; color: #16a34a;">{{ number_format($snapshot['total_available']) }}</td>
            <td style="padding: 10px; text-align: right; color: #d97706;">{{ number_format($snapshot['total_reserved']) }}</td>
            <td style="padding: 10px; text-align: right;">{{ number_format($snapshot['total_stock']) }}</td>
            <td style="padding: 10px;"></td>
        </tr>
    </tfoot>
</table>

{{-- Low Stock Alerts --}}
@if($low_stock->isNotEmpty())
<h2 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #dc2626; padding-bottom: 5px; color: #dc2626;">Low Stock Alerts</h2>
<table class="data-table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
    <thead>
        <tr style="background: #fef2f2;">
            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Category</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Current Stock</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Threshold</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Shortage</th>
        </tr>
    </thead>
    <tbody>
        @foreach($low_stock as $item)
        @php
            $shortage = max(0, $item->low_stock_threshold - ($item->available_stock ?? 0));
        @endphp
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $item->name }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee; color: #dc2626; font-weight: bold;">{{ number_format($item->available_stock ?? 0) }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee;">{{ number_format($item->low_stock_threshold) }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee; color: #dc2626;">-{{ number_format($shortage) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Expiring Items --}}
@if($expiring->isNotEmpty())
<h2 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #ea580c; padding-bottom: 5px; color: #ea580c;">Expiring Soon</h2>
<table class="data-table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
    <thead>
        <tr style="background: #fff7ed;">
            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Batch</th>
            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Category</th>
            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Shop</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Available</th>
            <th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Days Left</th>
        </tr>
    </thead>
    <tbody>
        @foreach($expiring as $item)
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee; font-family: monospace;">{{ $item['batch_code'] }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $item['category_name'] }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $item['shop_name'] }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee;">{{ number_format($item['available_stock']) }}</td>
            <td style="padding: 8px; text-align: center; border-bottom: 1px solid #eee; color: {{ $item['days_left'] <= 3 ? '#dc2626' : '#ea580c' }}; font-weight: bold;">{{ $item['days_left'] }} days</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Stock Valuation --}}
<h2 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #16a34a; padding-bottom: 5px;">Stock Valuation</h2>
<table class="data-table" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f0fdf4;">
            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Category</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Quantity</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Avg. Price</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Value</th>
        </tr>
    </thead>
    <tbody>
        @forelse($valuation['categories'] as $category)
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $category['category_name'] }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee;">{{ number_format($category['quantity']) }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee;">₱{{ number_format($category['avg_price'], 2) }}</td>
            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee; color: #16a34a; font-weight: bold;">₱{{ number_format($category['value'], 2) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" style="padding: 20px; text-align: center; color: #666;">No valuation data available</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background: #f0fdf4; font-weight: bold;">
            <td style="padding: 10px;">Total</td>
            <td style="padding: 10px; text-align: right;">{{ number_format($valuation['total_quantity']) }}</td>
            <td style="padding: 10px;"></td>
            <td style="padding: 10px; text-align: right; color: #16a34a;">₱{{ number_format($valuation['total_value'], 2) }}</td>
        </tr>
    </tfoot>
</table>
@endsection
