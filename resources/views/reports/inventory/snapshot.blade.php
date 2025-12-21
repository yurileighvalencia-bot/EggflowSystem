@extends('reports.layout')

@section('content')
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ number_format($summary['total_available_stock']) }}</div>
                <div class="summary-label">Available Stock</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ number_format($summary['total_reserved_stock']) }}</div>
                <div class="summary-label">Reserved Stock</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ number_format($summary['total_stock']) }}</div>
                <div class="summary-label">Total Stock</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ number_format($summary['unique_batches']) }}</div>
                <div class="summary-label">Active Batches</div>
            </div>
        </div>
    </div>

    @foreach($by_shop as $shop)
        <div class="section">
            <h2 class="section-title">{{ $shop['shop_name'] }}</h2>
            <p style="margin-bottom: 10px; color: #666;">
                Total: {{ number_format($shop['total_stock']) }} eggs 
                ({{ number_format($shop['total_available']) }} available, 
                {{ number_format($shop['total_reserved']) }} reserved)
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Available</th>
                        <th class="text-right">Reserved</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Batches</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shop['categories'] as $category)
                        <tr>
                            <td>{{ $category['category_name'] }}</td>
                            <td class="text-right">{{ number_format($category['available_stock']) }}</td>
                            <td class="text-right">{{ number_format($category['reserved_stock']) }}</td>
                            <td class="text-right">{{ number_format($category['total_stock']) }}</td>
                            <td class="text-right">{{ $category['batch_count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endsection
