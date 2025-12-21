@extends('reports.layout')

@section('content')
    <div class="meta-info" style="margin-bottom: 20px;">
        <strong>Looking ahead:</strong> {{ $within_days }} days
    </div>

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value {{ $expiring_batches_count > 0 ? 'negative' : 'positive' }}">
                    {{ number_format($expiring_batches_count) }}
                </div>
                <div class="summary-label">Expiring Batches</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ number_format($total_expiring_stock) }}</div>
                <div class="summary-label">Total Expiring Stock</div>
            </div>
        </div>
    </div>

    @if(!empty($by_days_remaining))
        <div class="section">
            <h2 class="section-title">Expiry Timeline</h2>
            <table>
                <thead>
                    <tr>
                        <th>Days Remaining</th>
                        <th class="text-right">Batches</th>
                        <th class="text-right">Stock</th>
                        <th>Urgency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($by_days_remaining as $dayData)
                        <tr>
                            <td>{{ $dayData['days'] }} day{{ $dayData['days'] !== 1 ? 's' : '' }}</td>
                            <td class="text-right">{{ $dayData['batches'] }}</td>
                            <td class="text-right">{{ number_format($dayData['stock']) }}</td>
                            <td>
                                @if($dayData['days'] <= 1)
                                    <span class="badge badge-danger">Critical</span>
                                @elseif($dayData['days'] <= 3)
                                    <span class="badge badge-warning">Urgent</span>
                                @else
                                    <span class="badge badge-success">Plan</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($items))
        <div class="section">
            <h2 class="section-title">Detailed Inventory</h2>
            <table>
                <thead>
                    <tr>
                        <th>Shop</th>
                        <th>Category</th>
                        <th>Batch</th>
                        <th class="text-right">Available</th>
                        <th class="text-right">Reserved</th>
                        <th>Expiry Date</th>
                        <th class="text-right">Days Left</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item['shop_name'] }}</td>
                            <td>{{ $item['category_name'] }}</td>
                            <td>{{ $item['batch_number'] }}</td>
                            <td class="text-right">{{ number_format($item['available_stock']) }}</td>
                            <td class="text-right">{{ number_format($item['reserved_stock']) }}</td>
                            <td>{{ $item['expiry_date'] }}</td>
                            <td class="text-right {{ $item['days_until_expiry'] <= 1 ? 'negative' : '' }}">
                                {{ $item['days_until_expiry'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="section">
            <p style="text-align: center; color: #059669; font-size: 14pt; padding: 40px;">
                ✓ No batches expiring within {{ $within_days }} days
            </p>
        </div>
    @endif
@endsection
