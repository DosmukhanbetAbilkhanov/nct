<?php

use App\Exceptions\ApiException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\RateLimitException;
use App\Jobs\FetchProductFromNationalCatalog;
use App\Models\ImportBatch;
use App\Models\ImportBatchItem;
use App\Models\Product;
use App\Services\NationalCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->batch = ImportBatch::create([
        'filename' => 'test.xlsx',
        'total_gtins' => 10,
        'processed_count' => 0,
        'success_count' => 0,
        'failed_count' => 0,
        'status' => 'processing',
    ]);
});

test('job processes new product successfully', function () {
    $batchItem = ImportBatchItem::create([
        'import_batch_id' => $this->batch->id,
        'gtin' => '1234567890123',
        'status' => 'pending',
    ]);

    $productData = [
        'gtin' => '1234567890123',
        'ntin' => '1234567890',
        'nameKk' => 'Тест өнімі',
        'nameRu' => 'Тестовый продукт',
        'nameEn' => 'Test Product',
        'shortNameKk' => 'Тест',
        'shortNameRu' => 'Тест',
        'shortNameEn' => 'Test',
    ];

    $mockService = Mockery::mock(NationalCatalogService::class);
    $mockService->shouldReceive('fetchProductByGtin')
        ->once()
        ->with('1234567890123')
        ->andReturn($productData);

    $this->app->instance(NationalCatalogService::class, $mockService);

    $job = new FetchProductFromNationalCatalog($batchItem);
    $job->handle($mockService);

    // Verify product was created
    $product = Product::where('gtin', '1234567890123')->first();
    expect($product)->not->toBeNull()
        ->and($product->nameKk)->toBe('Тест өнімі')
        ->and($product->nameRu)->toBe('Тестовый продукт')
        ->and($product->nameEn)->toBe('Test Product');

    // Verify batch item was updated
    $batchItem->refresh();
    expect($batchItem->status)->toBe('success')
        ->and($batchItem->product_id)->toBe($product->id)
        ->and($batchItem->error_message)->toBeNull();

    // Verify batch counters were incremented
    $this->batch->refresh();
    expect($this->batch->processed_count)->toBe(1)
        ->and($this->batch->success_count)->toBe(1)
        ->and($this->batch->failed_count)->toBe(0);
});

test('job links to existing product without calling API', function () {
    // Create existing product
    $existingProduct = Product::create([
        'gtin' => '1234567890123',
        'nameKk' => 'Existing Product',
    ]);

    $batchItem = ImportBatchItem::create([
        'import_batch_id' => $this->batch->id,
        'gtin' => '1234567890123',
        'status' => 'pending',
    ]);

    $mockService = Mockery::mock(NationalCatalogService::class);
    $mockService->shouldNotReceive('fetchProductByGtin');

    $this->app->instance(NationalCatalogService::class, $mockService);

    $job = new FetchProductFromNationalCatalog($batchItem);
    $job->handle($mockService);

    // Verify no new product was created
    expect(Product::count())->toBe(1);

    // Verify batch item was linked to existing product
    $batchItem->refresh();
    expect($batchItem->status)->toBe('success')
        ->and($batchItem->product_id)->toBe($existingProduct->id);

    // Verify batch counters
    $this->batch->refresh();
    expect($this->batch->processed_count)->toBe(1)
        ->and($this->batch->success_count)->toBe(1);
});

test('job marks as failed when product not found in API', function () {
    $batchItem = ImportBatchItem::create([
        'import_batch_id' => $this->batch->id,
        'gtin' => '1234567890123',
        'status' => 'pending',
    ]);

    $mockService = Mockery::mock(NationalCatalogService::class);
    $mockService->shouldReceive('fetchProductByGtin')
        ->once()
        ->andThrow(new ProductNotFoundException('1234567890123'));

    $this->app->instance(NationalCatalogService::class, $mockService);

    $job = new FetchProductFromNationalCatalog($batchItem);
    $job->handle($mockService);

    // Verify no product was created
    expect(Product::count())->toBe(0);

    // Verify batch item marked as failed
    $batchItem->refresh();
    expect($batchItem->status)->toBe('failed')
        ->and($batchItem->error_message)->toContain('Product not found in National Catalog');

    // Verify batch counters
    $this->batch->refresh();
    expect($this->batch->processed_count)->toBe(1)
        ->and($this->batch->failed_count)->toBe(1);
});

test('job marks as failed with invalid GTIN', function () {
    $batchItem = ImportBatchItem::create([
        'import_batch_id' => $this->batch->id,
        'gtin' => '123', // Invalid GTIN (too short)
        'status' => 'pending',
    ]);

    $mockService = Mockery::mock(NationalCatalogService::class);
    $mockService->shouldNotReceive('fetchProductByGtin');

    $this->app->instance(NationalCatalogService::class, $mockService);

    $job = new FetchProductFromNationalCatalog($batchItem);
    $job->handle($mockService);

    // Verify batch item marked as failed with validation error
    $batchItem->refresh();
    expect($batchItem->status)->toBe('failed')
        ->and($batchItem->error_message)->toContain('Invalid GTIN format');

    // Verify batch counters
    $this->batch->refresh();
    expect($this->batch->failed_count)->toBe(1);
});

test('job releases back to queue on rate limit exception', function () {
    $batchItem = ImportBatchItem::create([
        'import_batch_id' => $this->batch->id,
        'gtin' => '1234567890123',
        'status' => 'pending',
    ]);

    $mockService = Mockery::mock(NationalCatalogService::class);
    $mockService->shouldReceive('fetchProductByGtin')
        ->once()
        ->andThrow(new RateLimitException(60));

    $this->app->instance(NationalCatalogService::class, $mockService);

    Queue::fake();

    $job = new FetchProductFromNationalCatalog($batchItem);
    $job->handle($mockService);

    // Verify batch item status is set back to pending (will retry)
    $batchItem->refresh();
    expect($batchItem->status)->toBe('pending');

    // Verify batch counters were NOT incremented (job will retry)
    $this->batch->refresh();
    expect($this->batch->processed_count)->toBe(0);
});

test('job marks as failed on API exception', function () {
    $batchItem = ImportBatchItem::create([
        'import_batch_id' => $this->batch->id,
        'gtin' => '1234567890123',
        'status' => 'pending',
    ]);

    $mockService = Mockery::mock(NationalCatalogService::class);
    $mockService->shouldReceive('fetchProductByGtin')
        ->once()
        ->andThrow(new ApiException(500, 'API Error'));

    $this->app->instance(NationalCatalogService::class, $mockService);

    $job = new FetchProductFromNationalCatalog($batchItem);
    $job->handle($mockService);

    // Verify batch item marked as failed
    $batchItem->refresh();
    expect($batchItem->status)->toBe('failed')
        ->and($batchItem->error_message)->toContain('API error');

    // Verify batch counters
    $this->batch->refresh();
    expect($this->batch->failed_count)->toBe(1);
});

test('failed method marks batch item as failed', function () {
    $batchItem = ImportBatchItem::create([
        'import_batch_id' => $this->batch->id,
        'gtin' => '1234567890123',
        'status' => 'processing',
    ]);

    $job = new FetchProductFromNationalCatalog($batchItem);
    $exception = new \Exception('Test exception');
    $job->failed($exception);

    // Verify batch item marked as failed
    $batchItem->refresh();
    expect($batchItem->status)->toBe('failed')
        ->and($batchItem->error_message)->toContain('Job failed after all retries');

    // Verify batch counters
    $this->batch->refresh();
    expect($this->batch->failed_count)->toBe(1);
});

test('job has correct queue configuration', function () {
    $batchItem = ImportBatchItem::create([
        'import_batch_id' => $this->batch->id,
        'gtin' => '1234567890123',
        'status' => 'pending',
    ]);

    $job = new FetchProductFromNationalCatalog($batchItem);

    expect($job->tries)->toBe(5)
        ->and($job->backoff)->toBe([30, 60, 120, 300, 600]);
});

test('job has rate limited middleware', function () {
    $batchItem = ImportBatchItem::create([
        'import_batch_id' => $this->batch->id,
        'gtin' => '1234567890123',
        'status' => 'pending',
    ]);

    $job = new FetchProductFromNationalCatalog($batchItem);
    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(\Illuminate\Queue\Middleware\RateLimited::class);
});
