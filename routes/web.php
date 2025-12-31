<?php

use App\Http\Controllers\LanguageController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\CompanySetup;
use App\Livewire\GtinImport;
use App\Livewire\MyRequests;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

// Homepage - accessible by everyone
Route::get('/', GtinImport::class)->name('gtin-import');

// Language switcher
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

Route::middleware('auth')->group(function () {
    Route::get('/company/setup', CompanySetup::class)->name('company.setup');
    Route::get('/my-requests', MyRequests::class)->name('my-requests');

    // Download requires authentication
    Route::get('/import/download/{batch}/{type}', function (ImportBatch $batch, string $type) {
        // Only allow download if batch belongs to user OR batch has no user (guest batch)
        if ($batch->user_id && $batch->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $filePath = $type === 'success' ? $batch->success_file_path : $batch->failed_file_path;

        if (! $filePath || ! Storage::exists($filePath)) {
            abort(404, 'File not found');
        }

        $filename = basename($filePath);

        return Storage::download($filePath, $filename);
    })->name('import.download');
});
