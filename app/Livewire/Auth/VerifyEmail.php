<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Verify Email - EggFlow')]
class VerifyEmail extends Component
{
    public bool $verificationSent = false;

    public function sendVerification()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        Auth::user()->sendEmailVerificationNotification();
        $this->verificationSent = true;

        session()->flash('success', 'Verification link sent!');
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
