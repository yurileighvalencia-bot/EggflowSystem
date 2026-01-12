<div class="space-y-6">
    {{-- Header --}}
    <x-page-header 
        title="Wastage Report" 
        description="Track and analyze product wastage across sources"
    >
        <x-slot:actions>
            <button 
                wire:click="exportPdf"
                wire:loading.attr="disabled"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-lg flex items-center gap-2 transition-colors"
            >
                <span wire:loading.remove wire:target="exportPdf">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <x-loading-spinner wire:loading wire:target="exportPdf" size="sm" color="white" />
                Export PDF
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Shop Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shop</label>
                <select 
                    wire:model.live="shopId"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">All Shops</option>
                    @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Start Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                <input 
                    type="date" 
                    wire:model.live="startDate"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
            </div>

            {{-- End Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                <input 
                    type="date" 
                    wire:model.live="endDate"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
            </div>

            {{-- View Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Report View</label>
                <select 
                    wire:model.live="view"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="summary">Summary</option>
                    <option value="by-source">By Source</option>
                    <option value="trends">Monthly Trends</option>
                    <option value="discrepancies">Delivery Discrepancies</option>
                </select>
            </div>

            {{-- Quick Presets --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quick Select</label>
                <div class="flex flex-wrap gap-1">
                    <button wire:click="setPreset('today')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300">Today</button>
                    <button wire:click="setPreset('week')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300">Week</button>
                    <button wire:click="setPreset('month')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300">Month</button>
                    <button wire:click="setPreset('quarter')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300">Quarter</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-red-200 dark:border-red-700 p-4">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->summary['summary']['total_quantity']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Wastage</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-orange-200 dark:border-orange-700 p-4">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $this->summary['summary']['total_records'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Wastage Records</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700 p-4">
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">₱{{ number_format($this->summary['summary']['estimated_value'], 2) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Estimated Loss</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                @php
                    $topSource = collect($this->summary['by_source'] ?? [])->sortByDesc('quantity')->keys()->first();
                @endphp
                {{ ucfirst($topSource ?? 'N/A') }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Top Source</div>
        </div>
    </div>

    {{-- Summary View --}}
    @if($view === 'summary')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- By Source --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Wastage by Source</h2>
                @if(empty($this->summary['by_source']))
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No wastage data for this period</p>
                @else
                    <div class="space-y-3">
                        @foreach($this->summary['by_source'] as $source => $data)
                            @php
                                $percentage = $this->summary['summary']['total_quantity'] > 0 
                                    ? ($data['quantity'] / $this->summary['summary']['total_quantity']) * 100 
                                    : 0;
                                $colors = [
                                    'collection' => 'bg-blue-500',
                                    'delivery' => 'bg-purple-500',
                                    'storage' => 'bg-orange-500',
                                    'handling' => 'bg-red-500',
                                    'other' => 'bg-gray-500',
                                ];
                            @endphp
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-700 dark:text-gray-300 capitalize">{{ $source }}</span>
                                    <span class="text-gray-600 dark:text-gray-400">{{ number_format($data['quantity']) }} ({{ number_format($percentage, 1) }}%)</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="{{ $colors[$source] ?? 'bg-gray-500' }} h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- By Category --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Wastage by Category</h2>
                @if(empty($this->summary['by_category']))
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No wastage data for this period</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="px-2 py-2 text-left text-gray-500 dark:text-gray-400">Category</th>
                                    <th class="px-2 py-2 text-right text-gray-500 dark:text-gray-400">Quantity</th>
                                    <th class="px-2 py-2 text-right text-gray-500 dark:text-gray-400">Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($this->summary['by_category'] as $category)
                                    <tr>
                                        <td class="px-2 py-2 text-gray-900 dark:text-white">{{ $category['category_name'] ?? 'Unknown' }}</td>
                                        <td class="px-2 py-2 text-right text-red-600 dark:text-red-400">{{ number_format($category['quantity']) }}</td>
                                        <td class="px-2 py-2 text-right text-gray-600 dark:text-gray-400">₱{{ number_format($category['estimated_value'] ?? 0, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Daily Trend --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daily Wastage Trend</h2>
                @if(empty($this->summary['daily_trend']))
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No trend data available</p>
                @else
                    <div class="overflow-x-auto">
                        <div class="flex items-end space-x-1 h-40 min-w-max">
                            @php
                                $maxQuantity = collect($this->summary['daily_trend'])->max('quantity') ?: 1;
                            @endphp
                            @foreach($this->summary['daily_trend'] as $day)
                                @php
                                    $height = ($day['quantity'] / $maxQuantity) * 100;
                                @endphp
                                <div class="flex flex-col items-center">
                                    <div 
                                        class="w-8 bg-red-500 dark:bg-red-600 rounded-t hover:bg-red-600 dark:hover:bg-red-500 transition-colors cursor-pointer"
                                        style="height: {{ max($height, 2) }}%"
                                        title="{{ $day['date'] }}: {{ $day['quantity'] }} eggs"
                                    ></div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 transform -rotate-45 origin-top-left w-10">
                                        {{ \Carbon\Carbon::parse($day['date'])->format('M d') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- By Source View --}}
    @if($view === 'by-source')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detailed Wastage by Source</h2>
            
            @if(empty($this->bySource['by_source']))
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">No wastage data for this period</p>
            @else
                <div class="space-y-6">
                    @foreach($this->bySource['by_source'] as $source => $data)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white capitalize">{{ $source }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data['description'] }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($data['quantity']) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">({{ $data['percentage'] }}%)</span>
                                </div>
                            </div>
                            
                            @if(!empty($data['by_category']))
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                                    @foreach($data['by_category'] as $cat)
                                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded px-3 py-2">
                                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $cat['category'] }}</div>
                                            <div class="text-sm text-red-600 dark:text-red-400">{{ number_format($cat['quantity']) }} eggs</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Trends View --}}
    @if($view === 'trends')
        <div class="space-y-6">
            {{-- Trend Analysis Summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Trend Analysis</h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Average Monthly</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($this->trends['analysis']['average_monthly_wastage']) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Last Month</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($this->trends['analysis']['last_month_quantity']) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Trend</div>
                        <div class="text-xl font-bold {{ $this->trends['analysis']['trend_direction'] === 'increasing' ? 'text-red-600 dark:text-red-400' : ($this->trends['analysis']['trend_direction'] === 'decreasing' ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400') }}">
                            @if($this->trends['analysis']['trend_direction'] === 'increasing')
                                ↑ Increasing
                            @elseif($this->trends['analysis']['trend_direction'] === 'decreasing')
                                ↓ Decreasing
                            @else
                                → Stable
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Highest Month</div>
                        <div class="text-xl font-bold text-red-600 dark:text-red-400">
                            {{ $this->trends['analysis']['highest_month']['month_name'] ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Monthly Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Monthly Wastage</h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400">Month</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">Quantity</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">Records</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">Est. Value</th>
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400">Trend</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @php
                                $maxQuantity = collect($this->trends['trends'])->max('total_quantity') ?: 1;
                            @endphp
                            @foreach($this->trends['trends'] as $trend)
                                @php
                                    $barWidth = ($trend['total_quantity'] / $maxQuantity) * 100;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $trend['month_name'] }}</td>
                                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400 font-semibold">{{ number_format($trend['total_quantity']) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $trend['record_count'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">₱{{ number_format($trend['estimated_value'], 2) }}</td>
                                    <td class="px-4 py-3 w-32">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-red-500 h-2 rounded-full" style="width: {{ $barWidth }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Discrepancies View --}}
    @if($view === 'discrepancies')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Delivery Discrepancy Analysis</h2>
            
            @if(empty($this->discrepancies['discrepancies']))
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="mt-2 font-medium text-green-600 dark:text-green-400">No delivery discrepancies found!</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->discrepancies['total_discrepancies'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Discrepancies</div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->discrepancies['total_shortage'] ?? 0) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Shortage</div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->discrepancies['total_surplus'] ?? 0) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Surplus</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400">Delivery</th>
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400">Category</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">Expected</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">Received</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">Difference</th>
                                <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->discrepancies['discrepancies'] as $disc)
                                @php
                                    $diff = ($disc->received_quantity ?? 0) - ($disc->expected_quantity ?? 0);
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($disc->created_at)->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">#{{ $disc->delivery_id }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $disc->category_name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ number_format($disc->expected_quantity ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($disc->received_quantity ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $diff < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $disc->resolved ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                            {{ $disc->resolved ? 'Resolved' : 'Pending' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
