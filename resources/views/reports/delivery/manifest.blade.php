@extends('reports.layout')

@section('content')
    <div class="section">
        <table style="margin-bottom: 20px;">
            <tr>
                <td style="width: 50%;">
                    <strong>Delivery #:</strong> {{ $delivery->id }}<br>
                    <strong>Date:</strong> {{ $delivery->created_at->format('F j, Y') }}<br>
                    <strong>Status:</strong> 
                    <span class="badge badge-{{ $delivery->status === 'received' ? 'success' : ($delivery->status === 'dispatched' ? 'warning' : 'info') }}">
                        {{ ucfirst($delivery->status) }}
                    </span>
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Dispatched At:</strong><br>
                    {{ $delivery->dispatched_at ? $delivery->dispatched_at->format('M j, Y g:i A') : 'Not specified' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table>
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <h3 style="color: #f97316; margin-bottom: 10px;">From (Farm)</h3>
                    @if($delivery->restockRequest?->shop?->farm)
                        <strong>{{ $delivery->restockRequest->shop->farm->name }}</strong><br>
                        {{ $delivery->restockRequest->shop->farm->address ?? '' }}<br>
                        {{ $delivery->restockRequest->shop->farm->contact ?? '' }}
                    @else
                        <em>Farm details unavailable</em>
                    @endif
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <h3 style="color: #f97316; margin-bottom: 10px;">To (Shop)</h3>
                    <strong>{{ $delivery->shop->name }}</strong><br>
                    {{ $delivery->shop->address ?? '' }}<br>
                    Contact: {{ $delivery->shop->contact ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Category</th>
                    <th class="text-right">Qty Sent</th>
                    <th class="text-right">Qty Received</th>
                    <th class="text-right">Qty Rejected</th>
                    <th class="text-right">Missing</th>
                </tr>
            </thead>
            <tbody>
                @php $totalSent = 0; $totalReceived = 0; $totalRejected = 0; @endphp
                @foreach($delivery->items as $item)
                    @php 
                        $totalSent += $item->qty_sent;
                        $totalReceived += $item->qty_received ?? 0;
                        $totalRejected += $item->qty_rejected ?? 0;
                        $missing = $item->missing_quantity;
                    @endphp
                    <tr>
                        <td>{{ $item->batch->batch_code ?? 'N/A' }}</td>
                        <td>{{ $item->eggCategory->name ?? 'Unknown' }}</td>
                        <td class="text-right">{{ number_format($item->qty_sent) }}</td>
                        <td class="text-right">
                            @if($item->qty_received !== null)
                                {{ number_format($item->qty_received) }}
                            @else
                                <em style="color: #999;">Pending</em>
                            @endif
                        </td>
                        <td class="text-right {{ ($item->qty_rejected ?? 0) > 0 ? 'negative' : '' }}">
                            {{ number_format($item->qty_rejected ?? 0) }}
                        </td>
                        <td class="text-right {{ $missing > 0 ? 'negative' : '' }}">
                            @if($item->qty_received !== null)
                                {{ $missing > 0 ? '-' . number_format($missing) : '0' }}
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
                    <td class="text-right">{{ number_format($totalRejected) }}</td>
                    <td class="text-right {{ ($totalSent - $totalReceived - $totalRejected) > 0 ? 'negative' : '' }}">
                        @php $totalMissing = $totalSent - $totalReceived - $totalRejected; @endphp
                        {{ $totalMissing > 0 ? '-' . number_format($totalMissing) : '0' }}
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
