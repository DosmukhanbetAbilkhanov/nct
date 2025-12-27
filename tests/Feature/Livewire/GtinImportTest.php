<?php

use App\Jobs\FetchProductFromNationalCatalog;
use App\Livewire\GtinImport;
use App\Models\ImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('component renders successfully', function () {
    Livewire::test(GtinImport::class)
        ->assertStatus(200)
        ->assertSee('GTIN Import')
        ->assertSee('Upload an Excel or CSV file');
});

test('component displays file upload form when no batch exists', function () {
    Livewire::test(GtinImport::class)
        ->assertSee('Upload a file')
        ->assertSee('drag and drop')
        ->assertSee('Start Import');
});

test('upload validates required file', function () {
    Livewire::test(GtinImport::class)
        ->call('upload')
        ->assertHasErrors(['file' => 'required']);
});

test('upload validates file type', function () {
    $file = UploadedFile::fake()->create('test.txt', 100);

    Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('upload')
        ->assertHasErrors(['file' => 'mimes']);
});

test('upload validates file size', function () {
    $file = UploadedFile::fake()->create('test.xlsx', 11000); // 11MB

    Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('upload')
        ->assertHasErrors(['file' => 'max']);
});

test('upload processes valid CSV file', function () {
    Queue::fake();
    Storage::fake('local');

    // Copy test fixture to fake storage
    $content = file_get_contents(base_path('tests/Fixtures/sample-gtins.csv'));
    Storage::put('sample-gtins.csv', $content);

    $file = UploadedFile::fake()->createWithContent('sample-gtins.csv', $content);

    $component = Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('upload')
        ->assertHasNoErrors()
        ->assertSet('isProcessing', true);

    expect($component->get('currentBatch'))->not->toBeNull();
});

test('upload creates import batch and dispatches jobs', function () {
    Queue::fake();
    Storage::fake('local');

    // Copy test fixture to fake storage
    $content = file_get_contents(base_path('tests/Fixtures/sample-gtins.csv'));
    $file = UploadedFile::fake()->createWithContent('sample-gtins.csv', $content);

    Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('upload');

    expect(ImportBatch::count())->toBe(1);
    Queue::assertPushed(FetchProductFromNationalCatalog::class, 5);
});

test('upload sets success state for valid file', function () {
    Queue::fake();
    Storage::fake('local');

    // Copy test fixture to fake storage
    $content = file_get_contents(base_path('tests/Fixtures/sample-gtins.csv'));
    $file = UploadedFile::fake()->createWithContent('sample-gtins.csv', $content);

    $component = Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('upload')
        ->assertSet('isProcessing', true);

    expect($component->get('currentBatch'))->not->toBeNull();
    expect($component->get('currentBatch')->status)->toBe('processing');
});

test('upload handles errors gracefully for invalid file', function () {
    Queue::fake();
    Storage::fake('local');

    // Copy test fixture to fake storage
    $content = file_get_contents(base_path('tests/Fixtures/invalid-gtins.csv'));
    $file = UploadedFile::fake()->createWithContent('invalid-gtins.csv', $content);

    $component = Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('upload');

    // Batch should be created but in failed status
    expect(ImportBatch::count())->toBe(1);
    expect(ImportBatch::first()->status)->toBe('failed');
});

test('component displays progress section when batch exists', function () {
    Queue::fake();

    $batch = ImportBatch::create([
        'filename' => 'test.csv',
        'total_gtins' => 10,
        'processed_count' => 5,
        'success_count' => 4,
        'failed_count' => 1,
        'status' => 'processing',
        'started_at' => now(),
    ]);

    Livewire::test(GtinImport::class)
        ->set('currentBatch', $batch)
        ->set('isProcessing', true)
        ->assertSee('Import Progress')
        ->assertSee('test.csv')
        ->assertSee('10') // total
        ->assertSee('5') // processed
        ->assertSee('4') // success
        ->assertSee('1'); // failed
});

test('loadBatchProgress refreshes batch data', function () {
    Queue::fake();

    $batch = ImportBatch::create([
        'filename' => 'test.csv',
        'total_gtins' => 10,
        'processed_count' => 5,
        'success_count' => 5,
        'failed_count' => 0,
        'status' => 'processing',
        'started_at' => now(),
    ]);

    $component = Livewire::test(GtinImport::class)
        ->set('currentBatch', $batch)
        ->set('isProcessing', true);

    // Update batch in database
    $batch->update([
        'processed_count' => 10,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    $component->call('loadBatchProgress')
        ->assertSet('isProcessing', false);

    expect($component->get('currentBatch')->processed_count)->toBe(10);
});

test('loadBatchProgress stops processing when batch is completed', function () {
    Queue::fake();

    $batch = ImportBatch::create([
        'filename' => 'test.csv',
        'total_gtins' => 10,
        'processed_count' => 10,
        'success_count' => 10,
        'failed_count' => 0,
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    Livewire::test(GtinImport::class)
        ->set('currentBatch', $batch)
        ->set('isProcessing', true)
        ->call('loadBatchProgress')
        ->assertSet('isProcessing', false);
});

test('loadBatchProgress stops processing when batch is failed', function () {
    Queue::fake();

    $batch = ImportBatch::create([
        'filename' => 'test.csv',
        'total_gtins' => 10,
        'processed_count' => 5,
        'success_count' => 0,
        'failed_count' => 5,
        'status' => 'failed',
        'started_at' => now(),
    ]);

    Livewire::test(GtinImport::class)
        ->set('currentBatch', $batch)
        ->set('isProcessing', true)
        ->call('loadBatchProgress')
        ->assertSet('isProcessing', false);
});

test('resetImport clears state', function () {
    Queue::fake();

    $batch = ImportBatch::create([
        'filename' => 'test.csv',
        'total_gtins' => 10,
        'processed_count' => 10,
        'success_count' => 10,
        'failed_count' => 0,
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    Livewire::test(GtinImport::class)
        ->set('currentBatch', $batch)
        ->set('isProcessing', false)
        ->call('resetImport')
        ->assertSet('currentBatch', null)
        ->assertSet('isProcessing', false)
        ->assertSet('file', null);
});

test('component shows upload new file button when processing is complete', function () {
    Queue::fake();

    $batch = ImportBatch::create([
        'filename' => 'test.csv',
        'total_gtins' => 10,
        'processed_count' => 10,
        'success_count' => 10,
        'failed_count' => 0,
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    Livewire::test(GtinImport::class)
        ->set('currentBatch', $batch)
        ->set('isProcessing', false)
        ->assertSee('Upload New File');
});

test('component calculates progress percentage correctly', function () {
    Queue::fake();

    $batch = ImportBatch::create([
        'filename' => 'test.csv',
        'total_gtins' => 100,
        'processed_count' => 50,
        'success_count' => 45,
        'failed_count' => 5,
        'status' => 'processing',
        'started_at' => now(),
    ]);

    Livewire::test(GtinImport::class)
        ->set('currentBatch', $batch)
        ->set('isProcessing', true)
        ->assertSee('50%');
});

test('component is accessible via root route', function () {
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSeeLivewire(GtinImport::class);
});
