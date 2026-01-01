<?php

use App\Livewire\GtinImport;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('component renders successfully', function () {
    Livewire::test(GtinImport::class)
        ->assertStatus(200)
        ->assertSee('NTIN Import')
        ->assertSee('Upload an Excel or CSV file');
});

test('component displays file upload form when no batch exists', function () {
    Livewire::test(GtinImport::class)
        ->assertSee('Upload a file')
        ->assertSee('Excel or CSV files');
});

test('startImport validates required file', function () {
    $this->actingAs($this->user);

    Livewire::test(GtinImport::class)
        ->call('startImport')
        ->assertHasErrors(['file' => 'required']);
});

test('startImport validates file type', function () {
    $this->actingAs($this->user);

    $file = UploadedFile::fake()->create('test.txt', 100);

    Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('startImport')
        ->assertHasErrors(['file' => 'mimes']);
});

test('startImport validates file size', function () {
    $this->actingAs($this->user);

    $file = UploadedFile::fake()->create('test.xlsx', 11000); // 11MB

    Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('startImport')
        ->assertHasErrors(['file' => 'max']);
});

test('guest users see auth modal when trying to start import', function () {
    Storage::fake('local');

    $content = file_get_contents(base_path('tests/Fixtures/sample-gtins.csv'));
    $file = UploadedFile::fake()->createWithContent('sample-gtins.csv', $content);

    Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('previewFile') // Preview should work for guests
        ->assertSet('previewGtinCount', 5)
        ->call('startImport') // But start import should show modal
        ->assertSet('showAuthModal', true);
});

test('startImport processes valid CSV file', function () {
    $this->actingAs($this->user);

    Queue::fake();
    Storage::fake('local');

    // Copy test fixture to fake storage
    $content = file_get_contents(base_path('tests/Fixtures/sample-gtins.csv'));
    Storage::put('sample-gtins.csv', $content);

    $file = UploadedFile::fake()->createWithContent('sample-gtins.csv', $content);

    $component = Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('startImport')
        ->assertHasNoErrors();

    expect($component->get('currentBatch'))->not->toBeNull();
    expect($component->get('currentBatch')->user_id)->toBe($this->user->id);
});

test('startImport creates import batch for authenticated user', function () {
    $this->actingAs($this->user);

    Queue::fake();
    Storage::fake('local');

    // Copy test fixture to fake storage
    $content = file_get_contents(base_path('tests/Fixtures/sample-gtins.csv'));
    $file = UploadedFile::fake()->createWithContent('sample-gtins.csv', $content);

    Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('startImport');

    expect(ImportBatch::count())->toBe(1);
    expect(ImportBatch::first()->user_id)->toBe($this->user->id);
    expect(ImportBatch::first()->session_id)->toBeNull();

    // Small files (< 50 GTINs) process synchronously, so status will be 'completed'
    // Large files (>= 50 GTINs) dispatch queued jobs
});

test('startImport processes small files synchronously', function () {
    $this->actingAs($this->user);

    Queue::fake();
    Storage::fake('local');

    // Copy test fixture to fake storage (5 GTINs - below async threshold of 50)
    $content = file_get_contents(base_path('tests/Fixtures/sample-gtins.csv'));
    $file = UploadedFile::fake()->createWithContent('sample-gtins.csv', $content);

    $component = Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('startImport')
        ->assertSet('isProcessing', false); // Small files complete synchronously

    expect($component->get('currentBatch'))->not->toBeNull();
    expect($component->get('currentBatch')->status)->toBe('completed'); // Completed immediately
});

test('startImport handles errors gracefully for invalid file', function () {
    $this->actingAs($this->user);

    Queue::fake();
    Storage::fake('local');

    // Copy test fixture to fake storage
    $content = file_get_contents(base_path('tests/Fixtures/invalid-gtins.csv'));
    $file = UploadedFile::fake()->createWithContent('invalid-gtins.csv', $content);

    $component = Livewire::test(GtinImport::class)
        ->set('file', $file)
        ->call('startImport');

    // Batch should be created but in failed status
    expect(ImportBatch::count())->toBe(1);
    expect(ImportBatch::first()->status)->toBe('failed');
});

test('component displays progress section when batch exists', function () {
    $this->actingAs($this->user);

    Queue::fake();

    $batch = ImportBatch::create([
        'user_id' => $this->user->id,
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
    $this->actingAs($this->user);

    Queue::fake();

    $batch = ImportBatch::create([
        'user_id' => $this->user->id,
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
    $this->actingAs($this->user);

    Queue::fake();

    $batch = ImportBatch::create([
        'user_id' => $this->user->id,
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
    $this->actingAs($this->user);

    Queue::fake();

    $batch = ImportBatch::create([
        'user_id' => $this->user->id,
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
    $this->actingAs($this->user);

    Queue::fake();

    $batch = ImportBatch::create([
        'user_id' => $this->user->id,
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
    $this->actingAs($this->user);

    Queue::fake();

    $batch = ImportBatch::create([
        'user_id' => $this->user->id,
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
    $this->actingAs($this->user);

    Queue::fake();

    $batch = ImportBatch::create([
        'user_id' => $this->user->id,
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

test('component displays download template link', function () {
    Livewire::test(GtinImport::class)
        ->assertSee('Download Template')
        ->assertSee('Upload File');
});
