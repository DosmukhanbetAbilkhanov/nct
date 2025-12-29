<?php

use App\Livewire\GtinImport;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', GtinImport::class)->name('gtin-import');

Route::get('/import/download/{batch}/{type}', function (ImportBatch $batch, string $type) {
    $filePath = $type === 'success' ? $batch->success_file_path : $batch->failed_file_path;

    if (! $filePath || ! Storage::exists($filePath)) {
        abort(404, 'File not found');
    }

    $filename = basename($filePath);

    return Storage::download($filePath, $filename);
})->name('import.download');
