<?php

namespace App\Livewire\Settings;

use App\Models\Farm;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.dashboard')]
#[Title('User Management')]
class UserManagement extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showResetPasswordModal = false;
    public ?int $editingId = null;
    public ?User $deletingUser = null;
    public ?User $resetPasswordUser = null;

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $selectedRole = '';
    public ?int $farm_id = null;
    public ?int $shop_id = null;
    public bool $is_active = true;

    // Reset password
    public string $new_password = '';
    public string $new_password_confirmation = '';

    protected function rules(): array
    {
        $emailRule = $this->editingId 
            ? 'unique:users,email,' . $this->editingId
            : 'unique:users,email';

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', $emailRule],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'selectedRole' => 'required|exists:roles,name',
            'farm_id' => 'nullable|exists:farms,id',
            'shop_id' => 'nullable|exists:shops,id',
            'is_active' => 'boolean',
        ];

        if (!$this->editingId) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.settings.user-management');
    }

    #[Computed]
    public function users()
    {
        $query = User::with(['roles', 'farm', 'shop']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->role) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->role);
            });
        }

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function farms()
    {
        return Farm::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function shops()
    {
        return Shop::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'managers' => User::whereHas('roles', fn ($q) => $q->where('name', 'Manager'))->count(),
            'staff' => User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Farm Staff', 'Shop Staff']))->count(),
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->address = $user->address ?? '';
        $this->selectedRole = $user->roles->first()?->name ?? '';
        $this->farm_id = $user->farm_id;
        $this->shop_id = $user->shop_id;
        $this->is_active = $user->is_active;
        $this->password = '';
        $this->password_confirmation = '';
        
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'is_active' => $this->is_active,
        ];

        // Set farm/shop based on role
        if ($this->selectedRole === 'Farm Staff') {
            $data['farm_id'] = $this->farm_id;
            $data['shop_id'] = null;
        } elseif ($this->selectedRole === 'Shop Staff') {
            $data['shop_id'] = $this->shop_id;
            $data['farm_id'] = null;
        } else {
            $data['farm_id'] = null;
            $data['shop_id'] = null;
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($data);
            $user->syncRoles([$this->selectedRole]);
            session()->flash('message', 'User updated successfully.');
        } else {
            $data['password'] = Hash::make($this->password);
            $user = User::create($data);
            $user->assignRole($this->selectedRole);
            session()->flash('message', 'User created successfully.');
        }

        $this->closeFormModal();
    }

    public function openResetPasswordModal(int $id): void
    {
        $this->resetPasswordUser = User::findOrFail($id);
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->showResetPasswordModal = true;
    }

    public function closeResetPasswordModal(): void
    {
        $this->showResetPasswordModal = false;
        $this->resetPasswordUser = null;
        $this->new_password = '';
        $this->new_password_confirmation = '';
    }

    public function resetPassword(): void
    {
        $this->validate([
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($this->resetPasswordUser) {
            $this->resetPasswordUser->update([
                'password' => Hash::make($this->new_password),
            ]);
            session()->flash('message', 'Password reset successfully.');
        }

        $this->closeResetPasswordModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingUser = User::withCount(['shifts', 'sales'])->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingUser = null;
    }

    public function delete(): void
    {
        if (!$this->deletingUser) {
            return;
        }

        // Prevent deleting yourself
        if (auth()->id() === $this->deletingUser->id) {
            session()->flash('error', 'You cannot delete your own account.');
            $this->closeDeleteModal();
            return;
        }

        $this->deletingUser->delete();
        session()->flash('message', 'User deleted successfully.');

        $this->closeDeleteModal();
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);

        // Prevent deactivating yourself
        if (auth()->id() === $user->id) {
            session()->flash('error', 'You cannot deactivate your own account.');
            return;
        }

        $user->update(['is_active' => !$user->is_active]);
        
        $status = $user->is_active ? 'activated' : 'deactivated';
        session()->flash('message', "User {$status} successfully.");
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRole = '';
        $this->farm_id = null;
        $this->shop_id = null;
        $this->is_active = true;
        $this->resetErrorBag();
    }
}
