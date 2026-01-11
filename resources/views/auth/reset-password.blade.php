@php
    // This view wraps the Livewire ResetPassword component for Fortify compatibility
@endphp

<x-layouts.auth title="Reset Password">
    <livewire:auth.reset-password :token="$request->route('token')" :email="$request->email" />
</x-layouts.auth>
