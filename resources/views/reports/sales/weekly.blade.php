@extends('reports.layout')

@section('content')
    <div class="meta-info" style="margin-bottom: 20px;">
        <strong>Period:</strong> {{ $period['start'] }} to {{ $period['end'] }} &bull;
        <strong>Week:</strong> {{ $week_number }}, {{ $year }}
    </div>

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ number_format($summary['total_transactions']) }}</div>
                <div class="summary-label">Transactions</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">₱{{ number_format($summary['total_revenue'], 2) }}</div>
                <div class="summary-label">Total Revenue</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ number_format($summary['total_items_sold']) }}</div>
                <div class="summary-label">Items Sold</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">₱{{ number_format($summary['average_daily_revenue'], 2) }}</div>
                <div class="summary-label">Avg Daily Revenue</div>
            </div>
        </div>
    </div>

    @if(isset($comparison))
        <div class="section">
            <h2 class="section-title">Week-over-Week Comparison</h2>
            <table>
                <tr>
                    <td>Previous Week Revenue</td>
                    <td class="text-right">₱{{ number_format($comparison['previous_week_revenue'], 2) }}</td>
                </tr>
                <tr>
                    <td>Change</td>
                    <td class="text-right {{ $comparison['change_amount'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $comparison['change_amount'] >= 0 ? '+' : '' }}₱{{ number_format($comparison['change_amount'], 2) }}
                        ({{ $comparison['change_percentage'] >= 0 ? '+' : '' }}{{ number_format($comparison['change_percentage'], 1) }}%)
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="section">
        <h2 class="section-title">Daily Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Transactions</th>
                    <th class="text-right">Items Sold</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($daily_breakdown as $date => $data)
                    <tr>
                        <td>{{ $date }}</td>
                        <td class="text-right">{{ number_format($data['transactions']) }}</td>
                        <td class="text-right">{{ number_format($data['items_sold']) }}</td>
                        <td class="text-right">₱{{ number_format($data['revenue'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(!empty($category_breakdown))
        <div class="section">
            <h2 class="section-title">Sales by Category</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Quantity Sold</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">% of Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($category_breakdown as $category)
                        <tr>
                            <td>{{ $category['category_name'] }}</td>
                            <td class="text-right">{{ number_format($category['quantity_sold']) }}</td>
                            <td class="text-right">₱{{ number_format($category['revenue'], 2) }}</td>
                            <td class="text-right">
                                {{ $summary['total_revenue'] > 0 ? number_format(($category['revenue'] / $summary['total_revenue']) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($payment_breakdown))
        <div class="section">
            <h2 class="section-title">Payment Methods</h2>
            <table>
                <thead>
                    <tr>
                        <th>Method</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payment_breakdown as $method => $data)
                        <tr>
                            <td>{{ ucfirst($method) }}</td>
                            <td class="text-right">{{ number_format($data['count']) }}</td>
                            <td class="text-right">₱{{ number_format($data['total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
