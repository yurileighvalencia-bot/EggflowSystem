@extends('reports.layout')

@section('content')
    <div class="section">
        <table style="margin-bottom: 20px;">
            <tr>
                <td style="width: 50%;">
                    <strong>Delivery #:</strong> {{ $delivery->delivery_number }}<br>
                    <strong>Date:</strong> {{ $delivery->created_at->format('F j, Y') }}<br>
                    <strong>Status:</strong> 
                    <span class="badge badge-{{ $delivery->status === 'received' ? 'success' : ($delivery->status === 'dispatched' ? 'warning' : 'info') }}">
                        {{ ucfirst($delivery->status) }}
                    </span>
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Expected Arrival:</strong><br>
                    {{ $delivery->expected_arrival ? $delivery->expected_arrival->format('M j, Y g:i A') : 'Not specified' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table>
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <h3 style="color: #f97316; margin-bottom: 10px;">From (Farm)</h3>
                    <strong>{{ $delivery->farm->name }}</strong><br>
                    {{ $delivery->farm->location ?? '' }}<br>
                    {{ $delivery->farm->phone ?? '' }}<br>
                    Contact: {{ $delivery->farm->contact_person ?? 'N/A' }}
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <h3 style="color: #f97316; margin-bottom: 10px;">To (Shop)</h3>
                    <strong>{{ $delivery->shop->name }}</strong><br>
                    {{ $delivery->shop->location ?? '' }}<br>
                    {{ $delivery->shop->phone ?? '' }}<br>
                    Contact: {{ $delivery->shop->contact_person ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </div>

    @if($delivery->driver_name || $delivery->vehicle_info)
        <div class="section">
            <h2 class="section-title">Transport Details</h2>
            <table>
                @if($delivery->driver_name)
                    <tr>
                        <td style="width: 150px;">Driver Name:</td>
                        <td>{{ $delivery->driver_name }}</td>
                    </tr>
                @endif
                @if($delivery->driver_phone)
                    <tr>
                        <td>Driver Phone:</td>
                        <td>{{ $delivery->driver_phone }}</td>
                    </tr>
                @endif
                @if($delivery->vehicle_info)
                    <tr>
                        <td>Vehicle:</td>
                        <td>{{ $delivery->vehicle_info }}</td>
                    </tr>
                @endif
            </table>
        </div>
    @endif

    <div class="section">
        <h2 class="section-title">Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Category</th>
                    <th class="text-right">Qty Sent</th>
                    <th class="text-right">Qty Received</th>
                    <th class="text-right">Difference</th>
                </tr>
            </thead>
            <tbody>
                @php $totalSent = 0; $totalReceived = 0; @endphp
                @foreach($delivery->items as $item)
                    @php 
                        $totalSent += $item->quantity_sent;
                        $totalReceived += $item->quantity_received ?? 0;
                        $diff = ($item->quantity_received ?? $item->quantity_sent) - $item->quantity_sent;
                    @endphp
                    <tr>
                        <td>{{ $item->batch->batch_number ?? 'N/A' }}</td>
                        <td>{{ $item->batch->eggCategory->name ?? 'Unknown' }}</td>
                        <td class="text-right">{{ number_format($item->quantity_sent) }}</td>
                        <td class="text-right">
                            @if($item->quantity_received !== null)
                                {{ number_format($item->quantity_received) }}
                            @else
                                <em style="color: #999;">Pending</em>
                            @endif
                        </td>
                        <td class="text-right {{ $diff < 0 ? 'negative' : ($diff > 0 ? 'positive' : '') }}">
                            @if($item->quantity_received !== null)
                                {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff) }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; border-top: 2px solid #333;">
                    <td colspan="2">TOTAL</td>
                    <td class="text-right">{{ number_format($totalSent) }}</td>
                    <td class="text-right">{{ number_format($totalReceived) }}</td>
                    <td class="text-right {{ ($totalReceived - $totalSent) < 0 ? 'negative' : '' }}">
                        {{ ($totalReceived - $totalSent) >= 0 ? '+' : '' }}{{ number_format($totalReceived - $totalSent) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($delivery->notes)
        <div class="section">
            <h2 class="section-title">Notes</h2>
            <p>{{ $delivery->notes }}</p>
        </div>
    @endif

    <div class="section" style="margin-top: 40px;">
        <table>
            <tr>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px;">
                        Dispatched By<br>
                        <small>{{ $delivery->dispatchedBy->name ?? '________________' }}</small>
                    </div>
                </td>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px;">
                        Received By<br>
                        <small>{{ $delivery->receivedBy->name ?? '________________' }}</small>
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endsection
