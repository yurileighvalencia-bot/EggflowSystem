<?php

namespace App\Livewire;

use App\Models\Batch;
use App\Models\EggCategory;
use App\Models\Farm;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', [
            'stats' => [
                'categories' => EggCategory::count(),
                'farms' => Farm::count(),
                'shops' => Shop::count(),
                'batches' => Batch::where('current_quantity', '>', 0)->count(),
                'users' => User::count(),
                'sales_today' => Sale::whereDate('created_at', today())->count(),
                'low_stock_alerts' => Batch::where('current_quantity', '<=', 10)->where('current_quantity', '>', 0)->count(),
            ],
        ]);
    }
}
