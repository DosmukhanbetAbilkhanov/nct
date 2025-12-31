<?php

namespace App\Livewire;

use App\Models\ImportBatch;
use Livewire\Component;
use Livewire\WithPagination;

class MyRequests extends Component
{
    use WithPagination;

    public function render()
    {
        $batches = ImportBatch::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('livewire.my-requests', [
            'batches' => $batches,
        ]);
    }
}
