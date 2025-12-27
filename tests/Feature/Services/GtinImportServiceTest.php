<?php

use App\Jobs\FetchProductFromNationalCatalog;
use App\Models\ImportBatch;
use App\Models\ImportBatchItem;
use App\Services\GtinImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new GtinImportService;
});

test('processUpload creates import batch from CSV file', function () {
    Queue::fake();

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    $batch = $this->service->processUpload($file);

    expect($batch)->toBeInstanceOf(ImportBatch::class)
        ->and($batch->filename)->toBe('sample-gtins.csv')
        ->and($batch->total_gtins)->toBe(5)
        ->and($batch->status)->toBe('processing')
        ->and($batch->started_at)->not->toBeNull();
});

test('processUpload creates batch items for each GTIN', function () {
    Queue::fake();

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    $batch = $this->service->processUpload($file);

    expect(ImportBatchItem::where('import_batch_id', $batch->id)->count())->toBe(5);

    // Verify specific GTINs were created
    expect(ImportBatchItem::where('import_batch_id', $batch->id)->where('gtin', '1234567890123')->exists())->toBeTrue()
        ->and(ImportBatchItem::where('import_batch_id', $batch->id)->where('gtin', '9876543210987')->exists())->toBeTrue();
});

test('processUpload dispatches jobs for each GTIN', function () {
    Queue::fake();

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    $batch = $this->service->processUpload($file);

    // Verify 5 jobs were dispatched (one per valid GTIN)
    Queue::assertPushed(FetchProductFromNationalCatalog::class, 5);
});

test('processUpload sets all batch items to pending status', function () {
    Queue::fake();

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    $batch = $this->service->processUpload($file);

    $items = ImportBatchItem::where('import_batch_id', $batch->id)->get();

    foreach ($items as $item) {
        expect($item->status)->toBe('pending');
    }
});

test('processUpload throws exception when no valid GTINs found', function () {
    Queue::fake();

    $file = new UploadedFile(
        base_path('tests/Fixtures/invalid-gtins.csv'),
        'invalid-gtins.csv',
        'text/csv',
        null,
        true
    );

    expect(fn () => $this->service->processUpload($file))
        ->toThrow(\InvalidArgumentException::class, 'No valid GTINs found in file');
});

test('processUpload marks batch as failed when exception occurs', function () {
    Queue::fake();

    $file = new UploadedFile(
        base_path('tests/Fixtures/invalid-gtins.csv'),
        'invalid-gtins.csv',
        'text/csv',
        null,
        true
    );

    try {
        $this->service->processUpload($file);
    } catch (\InvalidArgumentException $e) {
        // Expected exception
    }

    // Verify batch was created and marked as failed
    $batch = ImportBatch::where('filename', 'invalid-gtins.csv')->first();
    expect($batch)->not->toBeNull()
        ->and($batch->status)->toBe('failed');
});

test('processUpload initializes batch counters to zero', function () {
    Queue::fake();

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    $batch = $this->service->processUpload($file);

    expect($batch->processed_count)->toBe(0)
        ->and($batch->success_count)->toBe(0)
        ->and($batch->failed_count)->toBe(0);
});

test('processUpload handles duplicate GTINs correctly', function () {
    Queue::fake();

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    $batch = $this->service->processUpload($file);

    // Should only create 5 items (duplicates removed)
    expect($batch->total_gtins)->toBe(5);

    // Verify no duplicate batch items
    $gtins = ImportBatchItem::where('import_batch_id', $batch->id)
        ->pluck('gtin')
        ->toArray();

    expect(count($gtins))->toBe(count(array_unique($gtins)));
});

test('processUpload uses transaction for atomic batch creation', function () {
    Queue::fake();

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    $batch = $this->service->processUpload($file);

    // All items should be created and batch updated atomically
    expect(ImportBatchItem::where('import_batch_id', $batch->id)->count())->toBe($batch->total_gtins);
});
