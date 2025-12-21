@extends('reports.layout')

@section('content')
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value {{ $low_stock_count > 0 ? '' : 'positive' }}">{{ number_format($low_stock_count) }}</div>
                <div class="summary-label">Low Stock Items</div>
            </div>
        </div>
    </div>

    @if($low_stock_count > 0)
        <div class="section">
            <h2 class="section-title">Items Below Reorder Level</h2>
            <table>
                <thead>
                    <tr>
                        <th>Shop</th>
                        <th>Category</th>
                        <th>Batch</th>
                        <th class="text-right">Current Stock</th>
                        <th class="text-right">Reorder Level</th>
                        <th class="text-right">Shortage</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item['shop_name'] }}</td>
                            <td>{{ $item['category_name'] }}</td>
                            <td>{{ $item['batch_number'] }}</td>
                            <td class="text-right">{{ number_format($item['available_stock']) }}</td>
                            <td class="text-right">{{ number_format($item['reorder_level']) }}</td>
                            <td class="text-right negative">-{{ number_format($item['shortage']) }}</td>
                            <td>
                                @if($item['available_stock'] <= $item['reorder_level'] * 0.5)
                                    <span class="badge badge-danger">Critical</span>
                                @else
                                    <span class="badge badge-warning">Low</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="section">
            <p style="text-align: center; color: #059669; font-size: 14pt; padding: 40px;">
                ✓ All inventory levels are healthy
            </p>
        </div>
    @endif
@endsection
