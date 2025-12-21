@extends('reports.layout')

@section('content')
    <div class="meta-info" style="margin-bottom: 20px;">
        <strong>Period:</strong> {{ $period['start'] }} to {{ $period['end'] }} 
        ({{ $period['days'] }} days)
    </div>

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ number_format($collection['net_usable']) }}</div>
                <div class="summary-label">Net Collected</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ number_format($sales['total_sold']) }}</div>
                <div class="summary-label">Total Sold</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">₱{{ number_format($sales['total_revenue'], 2) }}</div>
                <div class="summary-label">Revenue</div>
            </div>
            <div class="summary-item">
                <div class="summary-value {{ $efficiency['overall_efficiency'] >= 90 ? 'positive' : ($efficiency['overall_efficiency'] >= 75 ? '' : 'negative') }}">
                    {{ number_format($efficiency['overall_efficiency'], 1) }}%
                </div>
                <div class="summary-label">Overall Efficiency</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">Collection Summary</h2>
        <table>
            <tr>
                <td>Total Collected</td>
                <td class="text-right">{{ number_format($collection['total_collected']) }}</td>
            </tr>
            <tr>
                <td>Damaged at Collection</td>
                <td class="text-right negative">-{{ number_format($collection['damaged']) }}</td>
            </tr>
            <tr>
                <td>Net Usable from Collection</td>
                <td class="text-right"><strong>{{ number_format($collection['net_usable']) }}</strong></td>
            </tr>
            <tr>
                <td>Collection Efficiency</td>
                <td class="text-right">{{ number_format($collection['collection_efficiency'], 1) }}%</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Sales Summary</h2>
        <table>
            <tr>
                <td>Total Eggs Sold</td>
                <td class="text-right">{{ number_format($sales['total_sold']) }}</td>
            </tr>
            <tr>
                <td>Total Revenue</td>
                <td class="text-right">₱{{ number_format($sales['total_revenue'], 2) }}</td>
            </tr>
            <tr>
                <td>Average Price per Egg</td>
                <td class="text-right">₱{{ number_format($sales['average_price'], 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Wastage & Efficiency</h2>
        <table>
            <tr>
                <td>Total Wastage</td>
                <td class="text-right negative">{{ number_format($wastage['total']) }}</td>
            </tr>
            <tr>
                <td>Wastage Rate</td>
                <td class="text-right">{{ number_format($wastage['rate'], 1) }}%</td>
            </tr>
            <tr>
                <td>Collection to Sale Rate</td>
                <td class="text-right">{{ number_format($efficiency['collection_to_sale_rate'], 1) }}%</td>
            </tr>
            <tr>
                <td>Remaining Stock (Period)</td>
                <td class="text-right">{{ number_format($efficiency['stock_remaining']) }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($by_category))
        <div class="section">
            <h2 class="section-title">Breakdown by Category</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Collected</th>
                        <th class="text-right">Sold</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Efficiency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($by_category as $category)
                        <tr>
                            <td>{{ $category['category_name'] }}</td>
                            <td class="text-right">{{ number_format($category['collected']) }}</td>
                            <td class="text-right">{{ number_format($category['sold']) }}</td>
                            <td class="text-right">₱{{ number_format($category['revenue'], 2) }}</td>
                            <td class="text-right">{{ number_format($category['efficiency'], 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($daily_trend))
        <div class="section page-break">
            <h2 class="section-title">Daily Trend</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Collected</th>
                        <th class="text-right">Sold</th>
                        <th class="text-right">Difference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($daily_trend as $day)
                        <tr>
                            <td>{{ $day['date'] }}</td>
                            <td class="text-right">{{ number_format($day['collected']) }}</td>
                            <td class="text-right">{{ number_format($day['sold']) }}</td>
                            <td class="text-right {{ $day['difference'] >= 0 ? 'positive' : 'negative' }}">
                                {{ $day['difference'] >= 0 ? '+' : '' }}{{ number_format($day['difference']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
