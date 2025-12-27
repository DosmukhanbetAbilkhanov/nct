<?php

use App\Livewire\GtinImport;
use Illuminate\Support\Facades\Route;

Route::get('/', GtinImport::class)->name('gtin-import');
