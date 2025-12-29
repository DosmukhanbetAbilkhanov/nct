<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Logout extends Component
{
    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect('/');
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <button wire:click="logout" type="button" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 transition-colors cursor-pointer">
                Logout
            </button>
        </div>
        HTML;
    }
}
