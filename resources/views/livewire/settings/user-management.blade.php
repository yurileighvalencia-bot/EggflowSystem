<div class="space-y-6">
    {{-- Header --}}
    <x-page-header 
        title="User Management" 
        description="Manage system users and permissions"
    >
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary">
                <x-slot:iconLeft>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </x-slot:iconLeft>
                Add User
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <x-alert type="success" dismissible>
            {{ session('message') }}
        </x-alert>
    @endif
    @if (session()->has('error'))
        <x-alert type="error" dismissible>
            {{ session('error') }}
        </x-alert>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Users</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-700 p-4">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->stats['active'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Active</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-purple-200 dark:border-purple-700 p-4">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $this->stats['managers'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Managers</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700 p-4">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->stats['staff'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Staff</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by name or email..."
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <select wire:model.live="role"
                class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All Roles</option>
                @foreach($this->roles as $r)
                    <option value="{{ $r->name }}">{{ $r->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="status"
                class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Assignment</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                    <span class="text-amber-600 dark:text-amber-400 font-medium">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @foreach($user->roles as $role)
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    @if($role->name === 'Manager') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300
                                    @elseif($role->name === 'Farm Staff') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                    @elseif($role->name === 'Shop Staff') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                                    @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                    @endif">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            @if($user->farm)
                                <span class="text-green-600 dark:text-green-400">{{ $user->farm->name }}</span>
                            @elseif($user->shop)
                                <span class="text-blue-600 dark:text-blue-400">{{ $user->shop->name }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleActive({{ $user->id }})"
                                @if(auth()->id() === $user->id) disabled @endif
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full transition-colors
                                    {{ $user->is_active 
                                        ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 hover:bg-green-200' 
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200' }}
                                    @if(auth()->id() === $user->id) opacity-50 cursor-not-allowed @endif">
                                <span class="w-2 h-2 rounded-full {{ $user->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="openEditModal({{ $user->id }})"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="openResetPasswordModal({{ $user->id }})"
                                    class="p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded"
                                    title="Reset Password">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                </button>
                                @if(auth()->id() !== $user->id)
                                    <button wire:click="confirmDelete({{ $user->id }})"
                                        class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded"
                                        title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                            No users found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($this->users->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->users->links() }}
            </div>
        @endif
    </div>

    {{-- Form Modal --}}
    @if($showFormModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" wire:click="closeFormModal"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                        {{ $editingId ? 'Edit User' : 'Add User' }}
                    </h3>

                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                            <input type="text" wire:model="name"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                    @error('name') border-red-500 @enderror">
                            @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                            <input type="email" wire:model="email"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                    @error('email') border-red-500 @enderror">
                            @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                                <input type="text" wire:model="phone"
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                                <select wire:model.live="selectedRole"
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                        @error('selectedRole') border-red-500 @enderror">
                                    <option value="">Select Role</option>
                                    @foreach($this->roles as $r)
                                        <option value="{{ $r->name }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedRole') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Farm/Shop assignment based on role --}}
                        @if($selectedRole === 'Farm Staff')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign to Farm</label>
                                <select wire:model="farm_id"
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select Farm</option>
                                    @foreach($this->farms as $farm)
                                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($selectedRole === 'Shop Staff')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign to Shop</label>
                                <select wire:model="shop_id"
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select Shop</option>
                                    @foreach($this->shops as $shop)
                                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                            <textarea wire:model="address" rows="2"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>

                        @if(!$editingId)
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password *</label>
                                    <input type="password" wire:model="password"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                            @error('password') border-red-500 @enderror">
                                    @error('password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password *</label>
                                    <input type="password" wire:model="password_confirmation"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="is_active"
                                    class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" wire:click="closeFormModal"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">
                                {{ $editingId ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Reset Password Modal --}}
    @if($showResetPasswordModal && $resetPasswordUser)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" wire:click="closeResetPasswordModal"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Reset Password</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Set a new password for <strong>{{ $resetPasswordUser->name }}</strong>
                    </p>

                    <form wire:submit="resetPassword" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password</label>
                            <input type="password" wire:model="new_password"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                    @error('new_password') border-red-500 @enderror">
                            @error('new_password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                            <input type="password" wire:model="new_password_confirmation"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" wire:click="closeResetPasswordModal"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal && $deletingUser)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" wire:click="closeDeleteModal"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Delete User</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Are you sure you want to delete <strong>{{ $deletingUser->name }}</strong>?
                        </p>
                    </div>

                    <div class="flex justify-center gap-3">
                        <button wire:click="closeDeleteModal"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="delete"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
