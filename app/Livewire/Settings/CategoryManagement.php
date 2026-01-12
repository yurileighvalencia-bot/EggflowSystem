<?php

namespace App\Livewire\Settings;

use App\Models\EggCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Category Management')]
class CategoryManagement extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?EggCategory $deletingCategory = null;

    // Form fields
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public int $low_stock_threshold = 30;
    public int $restock_quantity = 100;
    public float $default_price = 0;
    public int $sort_order = 0;
    public bool $is_active = true;
    public bool $is_tax_exempt = false;

    protected function rules(): array
    {
        $uniqueRule = $this->editingId 
            ? 'unique:egg_categories,code,' . $this->editingId
            : 'unique:egg_categories,code';

        return [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', $uniqueRule],
            'description' => 'nullable|string|max:500',
            'low_stock_threshold' => 'required|integer|min:0',
            'restock_quantity' => 'required|integer|min:1',
            'default_price' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_tax_exempt' => 'boolean',
        ];
    }

    public function render()
    {
        return view('livewire.settings.category-management');
    }

    #[Computed]
    public function categories()
    {
        $query = EggCategory::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->orderBy('sort_order')->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => EggCategory::count(),
            'active' => EggCategory::where('is_active', true)->count(),
            'inactive' => EggCategory::where('is_active', false)->count(),
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->sort_order = EggCategory::max('sort_order') + 1;
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $category = EggCategory::findOrFail($id);
        
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description ?? '';
        $this->low_stock_threshold = $category->low_stock_threshold;
        $this->restock_quantity = $category->restock_quantity;
        $this->default_price = (float) $category->default_price;
        $this->sort_order = $category->sort_order;
        $this->is_active = $category->is_active;
        $this->is_tax_exempt = $category->is_tax_exempt;
        
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->authorize('manage-settings');

        $this->validate();

        $data = [
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'description' => $this->description ?: null,
            'low_stock_threshold' => $this->low_stock_threshold,
            'restock_quantity' => $this->restock_quantity,
            'default_price' => $this->default_price,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'is_tax_exempt' => $this->is_tax_exempt,
        ];

        if ($this->editingId) {
            $category = EggCategory::findOrFail($this->editingId);
            $category->update($data);
            session()->flash('message', 'Category updated successfully.');
        } else {
            EggCategory::create($data);
            session()->flash('message', 'Category created successfully.');
        }

        $this->closeFormModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingCategory = EggCategory::withCount(['batches', 'saleItems', 'inventories'])->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingCategory = null;
    }

    public function delete(): void
    {
        $this->authorize('manage-settings');

        if (!$this->deletingCategory) {
            return;
        }

        // Check if category has related records
        $hasRelated = $this->deletingCategory->batches_count > 0 
            || $this->deletingCategory->sale_items_count > 0
            || $this->deletingCategory->inventories_count > 0;

        if ($hasRelated) {
            // Soft delete only if has related records
            $this->deletingCategory->delete();
            session()->flash('message', 'Category archived successfully.');
        } else {
            // Force delete if no related records
            $this->deletingCategory->forceDelete();
            session()->flash('message', 'Category deleted successfully.');
        }

        $this->closeDeleteModal();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('manage-settings');

        $category = EggCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);
        
        $status = $category->is_active ? 'activated' : 'deactivated';
        session()->flash('message', "Category {$status} successfully.");
    }

    public function moveUp(int $id): void
    {
        $category = EggCategory::findOrFail($id);
        $previous = EggCategory::where('sort_order', '<', $category->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($previous) {
            $tempOrder = $category->sort_order;
            $category->update(['sort_order' => $previous->sort_order]);
            $previous->update(['sort_order' => $tempOrder]);
        }
    }

    public function moveDown(int $id): void
    {
        $category = EggCategory::findOrFail($id);
        $next = EggCategory::where('sort_order', '>', $category->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($next) {
            $tempOrder = $category->sort_order;
            $category->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $tempOrder]);
        }
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->low_stock_threshold = 30;
        $this->restock_quantity = 100;
        $this->default_price = 0;
        $this->sort_order = 0;
        $this->is_active = true;
        $this->is_tax_exempt = false;
        $this->resetErrorBag();
    }
}
