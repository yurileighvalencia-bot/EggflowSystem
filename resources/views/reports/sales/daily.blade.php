@extends('reports.layout')

@section('content')
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ number_format($total_transactions) }}</div>
                <div class="summary-label">Transactions</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">₱{{ number_format($total_revenue, 2) }}</div>
                <div class="summary-label">Total Revenue</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ number_format($total_items_sold) }}</div>
                <div class="summary-label">Items Sold</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">₱{{ number_format($average_transaction_value, 2) }}</div>
                <div class="summary-label">Avg Transaction</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">Payment Method Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="text-right">Transactions</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">% of Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payment_breakdown as $method => $data)
                    <tr>
                        <td>{{ ucfirst($method) }}</td>
                        <td class="text-right">{{ number_format($data['count']) }}</td>
                        <td class="text-right">₱{{ number_format($data['total'], 2) }}</td>
                        <td class="text-right">
                            {{ $total_revenue > 0 ? number_format(($data['total'] / $total_revenue) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No sales recorded</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Hourly Sales Distribution</h2>
        <table>
            <thead>
                <tr>
                    <th>Hour</th>
                    <th class="text-right">Transactions</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hourly_sales as $hour)
                    @if($hour['transactions'] > 0)
                        <tr>
                            <td>{{ $hour['hour'] }}</td>
                            <td class="text-right">{{ number_format($hour['transactions']) }}</td>
                            <td class="text-right">₱{{ number_format($hour['revenue'], 2) }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
