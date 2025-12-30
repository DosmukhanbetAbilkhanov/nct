<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesSmsVerification;
use App\Models\ImportBatch;
use App\Services\GtinImportService;
use Livewire\Component;
use Livewire\WithFileUploads;

class GtinImport extends Component
{
    use HandlesSmsVerification, WithFileUploads;

    public $file;

    public ?ImportBatch $currentBatch = null;

    public bool $isProcessing = false;

    public array $recentLogs = [];

    public ?int $previewGtinCount = null;

    public array $previewStats = [];

    public bool $showAuthModal = false;

    public string $activeAuthTab = 'login';

    // Login form fields
    public string $loginEmail = '';

    public string $loginPassword = '';

    public bool $remember = false;

    // Register form fields
    public string $registerName = '';

    public string $registerEmail = '';

    public string $registerPhone = '';

    public string $registerPassword = '';

    public string $registerPasswordConfirmation = '';

    public string $verificationCode = '';

    /**
     * Load user's most recent batch on mount.
     */
    public function mount(): void
    {
        // Restore file from session if guest uploaded before login
        $this->restoreFileFromSession();

        // Load the most recent batch for authenticated user only
        if (auth()->check()) {
            $this->currentBatch = ImportBatch::where('user_id', auth()->id())
                ->latest()
                ->first();

            if ($this->currentBatch && ! $this->currentBatch->isComplete()) {
                $this->isProcessing = true;
            }
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

        // Require authentication to start import
        if (! auth()->check()) {
            $this->saveFileForLater();
            $this->showAuthModal = true;

            return;
        }

        try {
            $service = new GtinImportService;
            $this->currentBatch = $service->processUpload($this->file, auth()->id(), null);

            // Refresh batch to check if it completed synchronously (small files)
            $this->currentBatch->refresh();

            // Set processing state based on batch status
            $this->isProcessing = ! $this->currentBatch->isComplete();

            // Clear file input and session
            $this->reset('file');
            $this->clearSavedFile();

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

    /**
     * Switch authentication tab.
     */
    public function switchAuthTab(string $tab): void
    {
        $this->activeAuthTab = $tab;
        $this->resetErrorBag();
    }

    /**
     * Handle login submission.
     */
    public function login(): void
    {
        $this->validate([
            'loginEmail' => ['required', 'string'],
            'loginPassword' => ['required', 'string'],
        ]);

        $credentials = str_contains($this->loginEmail, '@')
            ? ['email' => $this->loginEmail, 'password' => $this->loginPassword]
            : ['phone_number' => $this->cleanPhoneNumber($this->loginEmail), 'password' => $this->loginPassword];

        if (! auth()->attempt($credentials, $this->remember)) {
            $this->addError('loginEmail', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();

        // Close modal and start import with preserved file
        $this->showAuthModal = false;
        $this->reset(['loginEmail', 'loginPassword', 'remember']);

        // Now that user is authenticated, start the import
        if ($this->file) {
            $this->startImport();
        }
    }

    /**
     * Auto-verify code when user types 6 digits.
     */
    public function updatedVerificationCode(): void
    {
        $this->verifyEnteredCode();
    }

    /**
     * Trait method implementations.
     */
    protected function getPhoneFieldName(): string
    {
        return 'registerPhone';
    }

    protected function getPhoneNumber(): string
    {
        return $this->registerPhone;
    }

    protected function getVerificationCodeFieldName(): string
    {
        return 'verificationCode';
    }

    protected function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    /**
     * Handle registration submission.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'registerName' => ['required', 'string', 'max:255'],
            'registerEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'registerPhone' => ['required', 'string', 'regex:/^(\+7|7|8)?[0-9]{10}$/', 'unique:users,phone_number'],
            'registerPassword' => ['required', 'confirmed:registerPasswordConfirmation', \Illuminate\Validation\Rules\Password::defaults()],
            'verificationCode' => ['required', 'string', 'size:6'],
        ]);

        // Check if code has been verified
        if (! $this->codeVerified) {
            $this->addError('verificationCode', 'Please verify your phone number first.');

            return;
        }

        $cleanedPhone = $this->cleanPhoneNumber($this->registerPhone);

        $user = \App\Models\User::create([
            'name' => $validated['registerName'],
            'email' => $validated['registerEmail'],
            'phone_number' => $cleanedPhone,
            'password' => \Illuminate\Support\Facades\Hash::make($validated['registerPassword']),
            'phone_verified_at' => now(),
        ]);

        auth()->login($user);
        session()->regenerate();

        // Close modal and reset form
        $this->showAuthModal = false;
        $this->reset(['registerName', 'registerEmail', 'registerPhone', 'registerPassword', 'registerPasswordConfirmation', 'verificationCode', 'codeSent', 'codeSentAt', 'codeVerified']);

        // Now that user is authenticated, start the import
        if ($this->file) {
            $this->startImport();
        }
    }

    /**
     * Close authentication modal.
     */
    public function closeAuthModal(): void
    {
        $this->showAuthModal = false;
        $this->reset(['loginEmail', 'loginPassword', 'remember', 'registerName', 'registerEmail', 'registerPhone', 'registerPassword', 'registerPasswordConfirmation', 'verificationCode', 'codeSent', 'codeSentAt', 'codeVerified']);
    }

    /**
     * Save uploaded file to temporary storage for later use after login.
     */
    protected function saveFileForLater(): void
    {
        if (! $this->file) {
            return;
        }

        // Save file to temporary location
        $tempPath = $this->file->store('temp/uploads', 'local');

        // Store file information in session
        session([
            'pending_import' => [
                'temp_path' => $tempPath,
                'original_name' => $this->file->getClientOriginalName(),
                'gtin_count' => $this->previewGtinCount,
                'stats' => $this->previewStats,
            ],
        ]);
    }

    /**
     * Restore file from session after login.
     */
    protected function restoreFileFromSession(): void
    {
        if (! session()->has('pending_import')) {
            return;
        }

        $pendingImport = session('pending_import');

        // Restore preview data
        $this->previewGtinCount = $pendingImport['gtin_count'];
        $this->previewStats = $pendingImport['stats'];

        // Create a temporary uploaded file instance from stored path
        $tempPath = storage_path('app/'.$pendingImport['temp_path']);

        if (file_exists($tempPath)) {
            // Create Livewire temporary uploaded file from the stored file
            $this->file = \Livewire\Features\SupportFileUploads\TemporaryUploadedFile::createFromLivewire($tempPath);

            session()->flash('info', 'Your previously uploaded file has been restored. Click "Start Import" to continue.');
        }
    }

    /**
     * Clear saved file from session and temporary storage.
     */
    protected function clearSavedFile(): void
    {
        if (! session()->has('pending_import')) {
            return;
        }

        $pendingImport = session('pending_import');
        $tempPath = storage_path('app/'.$pendingImport['temp_path']);

        // Delete temporary file
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        // Clear session
        session()->forget('pending_import');
    }

    public function render()
    {
        return view('livewire.gtin-import')
            ->layout('layouts.app');
    }
}
