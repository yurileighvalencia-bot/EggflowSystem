@extends('reports.layout')

@section('content')
    <div class="meta-info" style="margin-bottom: 20px;">
        <strong>Period:</strong> {{ $month_name }} {{ $year }}
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
                <div class="summary-value">₱{{ number_format($summary['average_transaction_value'], 2) }}</div>
                <div class="summary-label">Avg Transaction</div>
            </div>
        </div>
    </div>

    @if(isset($yoy_comparison))
        <div class="section">
            <h2 class="section-title">Year-over-Year Comparison</h2>
            <table>
                <tr>
                    <td>{{ $month_name }} {{ $year - 1 }} Revenue</td>
                    <td class="text-right">₱{{ number_format($yoy_comparison['previous_year_revenue'], 2) }}</td>
                </tr>
                <tr>
                    <td>{{ $month_name }} {{ $year }} Revenue</td>
                    <td class="text-right">₱{{ number_format($summary['total_revenue'], 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Change</strong></td>
                    <td class="text-right {{ $yoy_comparison['change_amount'] >= 0 ? 'positive' : 'negative' }}">
                        <strong>
                            {{ $yoy_comparison['change_amount'] >= 0 ? '+' : '' }}₱{{ number_format($yoy_comparison['change_amount'], 2) }}
                            ({{ $yoy_comparison['change_percentage'] >= 0 ? '+' : '' }}{{ number_format($yoy_comparison['change_percentage'], 1) }}%)
                        </strong>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    @if(!empty($weekly_breakdown))
        <div class="section">
            <h2 class="section-title">Weekly Breakdown</h2>
            <table>
                <thead>
                    <tr>
                        <th>Week</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($weekly_breakdown as $week)
                        <tr>
                            <td>{{ $week['week_start'] }} - {{ $week['week_end'] }}</td>
                            <td class="text-right">{{ number_format($week['transactions']) }}</td>
                            <td class="text-right">₱{{ number_format($week['revenue'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($category_breakdown))
        <div class="section">
            <h2 class="section-title">Sales by Category</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">% of Total</th>
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

    @if(!empty($shop_breakdown))
        <div class="section">
            <h2 class="section-title">Sales by Shop</h2>
            <table>
                <thead>
                    <tr>
                        <th>Shop</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Items Sold</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shop_breakdown as $shop)
                        <tr>
                            <td>{{ $shop['shop_name'] }}</td>
                            <td class="text-right">{{ number_format($shop['transactions']) }}</td>
                            <td class="text-right">{{ number_format($shop['items_sold']) }}</td>
                            <td class="text-right">₱{{ number_format($shop['revenue'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
