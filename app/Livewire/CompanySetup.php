<?php

namespace App\Livewire;

use App\Models\City;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CompanySetup extends Component
{
    public string $name = '';

    public string $bin_or_iin = '';

    public ?int $city_id = null;

    public string $address = '';

    public function mount(): void
    {
        if (Auth::user()->company) {
            $this->redirect(route('gtin-import'), navigate: true);
        }
    }

    public function saveCompany(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'bin_or_iin' => ['required', 'string', 'size:12', 'regex:/^[0-9]{12}$/'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        Company::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'bin_or_iin' => $validated['bin_or_iin'],
            'city_id' => $validated['city_id'],
            'address' => $validated['address'] ?? null,
        ]);

        session()->flash('success', 'Company information saved successfully!');

        $this->redirect(route('gtin-import'), navigate: true);
    }

    public function skip(): void
    {
        $this->redirect(route('gtin-import'), navigate: true);
    }

    public function render()
    {
        return view('livewire.company-setup', [
            'cities' => City::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
