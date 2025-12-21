@extends('reports.layout')

@section('content')
    <div class="meta-info" style="margin-bottom: 20px;">
        <strong>Period:</strong> {{ $period['start'] }} to {{ $period['end'] }}
    </div>

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value negative">{{ number_format($summary['total_quantity']) }}</div>
                <div class="summary-label">Total Wastage</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ number_format($summary['total_records']) }}</div>
                <div class="summary-label">Records</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">₱{{ number_format($summary['estimated_value'], 2) }}</div>
                <div class="summary-label">Estimated Loss</div>
            </div>
        </div>
    </div>

    @if(!empty($by_source))
        <div class="section">
            <h2 class="section-title">Wastage by Source</h2>
            <table>
                <thead>
                    <tr>
                        <th>Source</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Records</th>
                        <th class="text-right">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($by_source as $source => $data)
                        <tr>
                            <td>{{ ucfirst($source) }}</td>
                            <td class="text-right">{{ number_format($data['quantity']) }}</td>
                            <td class="text-right">{{ number_format($data['count']) }}</td>
                            <td class="text-right">
                                {{ $summary['total_quantity'] > 0 ? number_format(($data['quantity'] / $summary['total_quantity']) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($by_category))
        <div class="section">
            <h2 class="section-title">Wastage by Category</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Records</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($by_category as $category)
                        <tr>
                            <td>{{ $category['category_name'] }}</td>
                            <td class="text-right">{{ number_format($category['quantity']) }}</td>
                            <td class="text-right">{{ number_format($category['count']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($by_shop))
        <div class="section">
            <h2 class="section-title">Wastage by Shop</h2>
            <table>
                <thead>
                    <tr>
                        <th>Shop</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Records</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($by_shop as $shop)
                        <tr>
                            <td>{{ $shop['shop_name'] }}</td>
                            <td class="text-right">{{ number_format($shop['quantity']) }}</td>
                            <td class="text-right">{{ number_format($shop['count']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($by_reason))
        <div class="section">
            <h2 class="section-title">Top Reasons for Wastage</h2>
            <table>
                <thead>
                    <tr>
                        <th>Reason</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Occurrences</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($by_reason as $reason)
                        <tr>
                            <td>{{ $reason['reason'] }}</td>
                            <td class="text-right">{{ number_format($reason['quantity']) }}</td>
                            <td class="text-right">{{ number_format($reason['count']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
