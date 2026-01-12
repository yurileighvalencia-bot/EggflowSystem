<div class="space-y-6">
    {{-- Header --}}
    <x-page-header 
        title="Collection vs Sales" 
        description="Compare egg collection performance with sales outcomes"
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
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            {{-- Farm Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Farm</label>
                <select 
                    wire:model.live="farmId"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">All Farms</option>
                    @foreach($this->farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>

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
                    <option value="overview">Overview</option>
                    <option value="by-category">By Category</option>
                    <option value="daily-trend">Daily Trend</option>
                </select>
            </div>

            {{-- Quick Presets --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quick Select</label>
                <div class="flex flex-wrap gap-1">
                    <button wire:click="setPreset('week')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300">Week</button>
                    <button wire:click="setPreset('month')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300">Month</button>
                    <button wire:click="setPreset('quarter')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300">Quarter</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Overview Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Collection Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Collection</span>
            </div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->comparison['collection']['net_usable']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ number_format($this->comparison['collection']['total_collected']) }} collected, {{ number_format($this->comparison['collection']['damaged']) }} damaged
            </div>
        </div>

        {{-- Sales Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Sales</span>
            </div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->comparison['sales']['total_sold']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                ₱{{ number_format($this->comparison['sales']['total_revenue'], 2) }} revenue
            </div>
        </div>

        {{-- Wastage Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-red-200 dark:border-red-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Wastage</span>
            </div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->comparison['wastage']['total']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ $this->comparison['wastage']['rate'] }}% wastage rate
            </div>
        </div>

        {{-- Efficiency Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Efficiency</span>
            </div>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->comparison['efficiency']['overall_efficiency'] }}%</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ number_format($this->comparison['efficiency']['stock_remaining']) }} remaining stock
            </div>
        </div>
    </div>

    {{-- Overview View --}}
    @if($view === 'overview')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Flow Visualization --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Egg Flow</h2>
                
                <div class="space-y-4">
                    {{-- Collected --}}
                    <div class="flex items-center gap-4">
                        <div class="w-24 text-sm text-gray-600 dark:text-gray-400">Collected</div>
                        <div class="flex-1">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 relative">
                                <div class="bg-blue-500 h-6 rounded-full flex items-center justify-center text-white text-xs font-medium" style="width: 100%">
                                    {{ number_format($this->comparison['collection']['total_collected']) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Arrow --}}
                    <div class="flex justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                    </div>

                    {{-- Net Usable (minus damaged) --}}
                    <div class="flex items-center gap-4">
                        <div class="w-24 text-sm text-gray-600 dark:text-gray-400">Net Usable</div>
                        <div class="flex-1">
                            @php
                                $netWidth = $this->comparison['collection']['total_collected'] > 0 
                                    ? ($this->comparison['collection']['net_usable'] / $this->comparison['collection']['total_collected']) * 100 
                                    : 0;
                            @endphp
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 relative">
                                <div class="bg-cyan-500 h-6 rounded-full flex items-center justify-center text-white text-xs font-medium" style="width: {{ $netWidth }}%">
                                    {{ number_format($this->comparison['collection']['net_usable']) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Arrow --}}
                    <div class="flex justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                    </div>

                    {{-- Sold --}}
                    <div class="flex items-center gap-4">
                        <div class="w-24 text-sm text-gray-600 dark:text-gray-400">Sold</div>
                        <div class="flex-1">
                            @php
                                $soldWidth = $this->comparison['collection']['total_collected'] > 0 
                                    ? ($this->comparison['sales']['total_sold'] / $this->comparison['collection']['total_collected']) * 100 
                                    : 0;
                            @endphp
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 relative">
                                <div class="bg-green-500 h-6 rounded-full flex items-center justify-center text-white text-xs font-medium" style="width: {{ $soldWidth }}%">
                                    {{ number_format($this->comparison['sales']['total_sold']) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Wastage + Remaining --}}
                    <div class="flex items-center gap-4 pt-2 border-t dark:border-gray-700">
                        <div class="w-24 text-sm text-gray-600 dark:text-gray-400">Wastage</div>
                        <div class="flex-1">
                            @php
                                $wastageWidth = $this->comparison['collection']['total_collected'] > 0 
                                    ? ($this->comparison['wastage']['total'] / $this->comparison['collection']['total_collected']) * 100 
                                    : 0;
                            @endphp
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 relative">
                                <div class="bg-red-500 h-4 rounded-full" style="width: {{ min($wastageWidth, 100) }}%"></div>
                            </div>
                        </div>
                        <div class="text-sm font-medium text-red-600 dark:text-red-400 w-16 text-right">
                            {{ number_format($this->comparison['wastage']['total']) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Key Metrics --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Key Metrics</h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-gray-600 dark:text-gray-400">Collection to Sale Rate</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->comparison['efficiency']['collection_to_sale_rate'] }}%</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-gray-600 dark:text-gray-400">Overall Efficiency</span>
                        <span class="text-lg font-bold {{ $this->comparison['efficiency']['overall_efficiency'] >= 80 ? 'text-green-600 dark:text-green-400' : ($this->comparison['efficiency']['overall_efficiency'] >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ $this->comparison['efficiency']['overall_efficiency'] }}%
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-gray-600 dark:text-gray-400">Damage Rate</span>
                        <span class="text-lg font-bold {{ $this->comparison['collection']['damage_rate'] <= 2 ? 'text-green-600 dark:text-green-400' : ($this->comparison['collection']['damage_rate'] <= 5 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ $this->comparison['collection']['damage_rate'] }}%
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-gray-600 dark:text-gray-400">Average Sale Price</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">₱{{ number_format($this->comparison['sales']['average_price'], 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-gray-600 dark:text-gray-400">Period Length</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->comparison['period']['days'] }} days</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- By Category View --}}
    @if($view === 'by-category')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Comparison by Category</h2>
            
            @if(empty($this->comparison['by_category']))
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No data available for this period</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Category</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Collected</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Damaged</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Sold</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Revenue</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Efficiency</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->comparison['by_category'] as $category)
                                @php
                                    $efficiency = ($category['collected'] ?? 0) > 0 
                                        ? (($category['sold'] ?? 0) / $category['collected']) * 100 
                                        : 0;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $category['category_name'] ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">{{ number_format($category['collected'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($category['damaged'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ number_format($category['sold'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-medium">₱{{ number_format($category['revenue'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $efficiency >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($efficiency >= 60 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400') }}">
                                            {{ number_format($efficiency, 1) }}%
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

    {{-- Daily Trend View --}}
    @if($view === 'daily-trend')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daily Trend</h2>
            
            @if(empty($this->comparison['daily_trend']))
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No trend data available for this period</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Date</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Collected</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Sold</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Wastage</th>
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Comparison</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @php
                                $maxCollected = collect($this->comparison['daily_trend'])->max('collected') ?: 1;
                            @endphp
                            @foreach($this->comparison['daily_trend'] as $day)
                                @php
                                    $collectedWidth = ($day['collected'] / $maxCollected) * 100;
                                    $soldWidth = $day['collected'] > 0 ? ($day['sold'] / $day['collected']) * 100 : 0;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($day['date'])->format('M d, D') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">{{ number_format($day['collected']) }}</td>
                                    <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ number_format($day['sold']) }}</td>
                                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($day['wastage'] ?? 0) }}</td>
                                    <td class="px-4 py-3 w-48">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 relative overflow-hidden">
                                            <div class="bg-blue-400 h-4 absolute left-0" style="width: {{ $collectedWidth }}%"></div>
                                            <div class="bg-green-500 h-4 absolute left-0" style="width: {{ min($soldWidth, 100) * ($collectedWidth / 100) }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Legend --}}
                <div class="flex items-center gap-4 mt-4 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-blue-400 rounded"></div>
                        <span class="text-gray-600 dark:text-gray-400">Collected</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-green-500 rounded"></div>
                        <span class="text-gray-600 dark:text-gray-400">Sold</span>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
