<?php

namespace App\Livewire;

use App\Models\ImportBatch;
use App\Services\GtinImportService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class GtinImport extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:xlsx,xls,csv|max:10240')]
    public $file;

    public ?ImportBatch $currentBatch = null;

    public bool $isProcessing = false;

    /**
     * Upload and process the GTIN file.
     */
    public function upload(): void
    {
        $this->validate();

        try {
            $service = new GtinImportService;
            $this->currentBatch = $service->processUpload($this->file);
            $this->isProcessing = true;

            // Clear file input
            $this->reset('file');

            session()->flash('success', 'Import started successfully!');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Load current batch progress from database.
     */
    public function loadBatchProgress(): void
    {
        if ($this->currentBatch) {
            $this->currentBatch->refresh();

            // Stop processing when batch is complete or failed
            if (in_array($this->currentBatch->status, ['completed', 'failed'])) {
                $this->isProcessing = false;
            }
        }
    }

    /**
     * Reset import state for new upload.
     */
    public function resetImport(): void
    {
        $this->reset(['file', 'currentBatch', 'isProcessing']);
        session()->forget(['success', 'error']);
    }

    public function render()
    {
        return view('livewire.gtin-import')
            ->layout('layouts.app');
    }
}
