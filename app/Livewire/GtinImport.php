<?php

namespace App\Livewire;

use App\Models\ImportBatch;
use App\Services\GtinImportService;
use Livewire\Component;
use Livewire\WithFileUploads;

class GtinImport extends Component
{
    use WithFileUploads;

    public $file;

    public ?ImportBatch $currentBatch = null;

    public bool $isProcessing = false;

    public array $recentLogs = [];

    public ?int $previewGtinCount = null;

    public array $previewStats = [];

    /**
     * Load user's most recent batch on mount.
     */
    public function mount(): void
    {
        // Load the most recent batch for this user or session
        if (auth()->check()) {
            // For authenticated users: load only their batches
            $this->currentBatch = ImportBatch::where('user_id', auth()->id())
                ->latest()
                ->first();
        } else {
            // For guests: load batches from current session
            $this->currentBatch = ImportBatch::whereNull('user_id')
                ->where('session_id', session()->getId())
                ->latest()
                ->first();
        }

        if ($this->currentBatch && ! $this->currentBatch->isComplete()) {
            $this->isProcessing = true;
        }
    }

    /**
     * Load recent logs for display.
     */
    public function loadLogs(): void
    {
        $logFile = storage_path('logs/laravel.log');

        if (file_exists($logFile)) {
            $lines = file($logFile);
            $recentLines = array_slice($lines, -100); // Last 100 lines

            $this->recentLogs = array_filter($recentLines, function ($line) {
                // Only show lines with emojis (our processing logs)
                return preg_match('/[📋🔍✅❌🌐💾🎉📦🚀⏸️]/u', $line);
            });

            $this->recentLogs = array_values($this->recentLogs); // Reindex
            $this->recentLogs = array_slice($this->recentLogs, -30); // Keep last 30
        }
    }

    /**
     * Preview the uploaded file and show GTIN count.
     */
    public function previewFile(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new \App\Imports\GtinsImport;
            \Maatwebsite\Excel\Facades\Excel::import($import, $this->file);

            $gtins = $import->getGtins();
            $this->previewGtinCount = $gtins->count();

            // Calculate processing stats
            $threshold = \App\Services\GtinImportService::ASYNC_THRESHOLD;
            $isAsync = $this->previewGtinCount >= $threshold;

            $this->previewStats = [
                'total_gtins' => $this->previewGtinCount,
                'processing_mode' => $isAsync ? 'Asynchronous (Queued)' : 'Synchronous (Immediate)',
                'estimated_time' => $isAsync
                    ? round($this->previewGtinCount * 2 / 60, 1).' minutes'
                    : round($this->previewGtinCount * 3).' seconds',
                'chunks' => $isAsync ? ceil($this->previewGtinCount / 10) : 1,
            ];

            session()->flash('preview_success', 'File analyzed successfully! Review the details below.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error analyzing file: '.$e->getMessage());
            $this->previewGtinCount = null;
            $this->previewStats = [];
        }
    }

    /**
     * Process and import the GTIN file.
     */
    public function startImport(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $service = new GtinImportService;
            $userId = auth()->check() ? auth()->id() : null;
            $sessionId = session()->getId();
            $this->currentBatch = $service->processUpload($this->file, $userId, $sessionId);
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
        $this->reset(['file', 'currentBatch', 'isProcessing', 'previewGtinCount', 'previewStats', 'recentLogs']);
        session()->forget(['success', 'error', 'preview_success']);
    }

    /**
     * Handle file update - auto-preview when file is selected.
     */
    public function updatedFile(): void
    {
        if ($this->file) {
            $this->previewFile();
        }
    }

    public function render()
    {
        return view('livewire.gtin-import')
            ->layout('layouts.app');
    }
}
