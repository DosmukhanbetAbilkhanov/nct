<?php

use App\Services\NationalCatalogApiException;
use App\Services\NationalCatalogRateLimitException;
use App\Services\NationalCatalogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->service = new NationalCatalogService(
        apiKey: 'test-api-key',
        baseUrl: 'https://api.test.com',
        timeout: 30,
        retryTimes: 3,
    );
});

test('fetchProductByGtin returns product data on successful response', function () {
    $gtin = '1234567890123';
    $productData = [
        'gtin' => $gtin,
        'nameKk' => 'Тест өнімі',
        'nameRu' => 'Тестовый продукт',
        'nameEn' => 'Test Product',
    ];

    Http::fake([
        '*/portal/api/v2/products/*' => Http::response($productData, 200),
    ]);

    $result = $this->service->fetchProductByGtin($gtin);

    expect($result)->toBe($productData);

    Http::assertSent(function ($request) use ($gtin) {
        return $request->url() === "https://api.test.com/portal/api/v2/products/{$gtin}"
            && $request->hasHeader('X-API-KEY', 'test-api-key')
            && $request->hasHeader('Accept', 'application/json');
    });
});

test('fetchProductByGtin returns null when API returns empty array', function () {
    $gtin = '1234567890123';

    Http::fake([
        '*/portal/api/v2/products/*' => Http::response([], 200),
    ]);

    $result = $this->service->fetchProductByGtin($gtin);

    expect($result)->toBeNull();
});

test('fetchProductByGtin returns null on 404 response', function () {
    $gtin = '1234567890123';

    Http::fake([
        '*/portal/api/v2/products/*' => Http::response(null, 404),
    ]);

    $result = $this->service->fetchProductByGtin($gtin);

    expect($result)->toBeNull();
});

test('fetchProductByGtin throws RateLimitException on 429 response', function () {
    $gtin = '1234567890123';

    Http::fake([
        '*/portal/api/v2/products/*' => Http::response('Rate limit exceeded', 429),
    ]);

    expect(fn () => $this->service->fetchProductByGtin($gtin))
        ->toThrow(NationalCatalogRateLimitException::class, 'Rate limit exceeded for National Catalog API');
});

test('fetchProductByGtin throws ApiException on 500 response', function () {
    $gtin = '1234567890123';

    Http::fake([
        '*/portal/api/v2/products/*' => Http::response('Internal Server Error', 500),
    ]);

    expect(fn () => $this->service->fetchProductByGtin($gtin))
        ->toThrow(NationalCatalogApiException::class);
});

test('fetchProductByGtin throws ApiException on other error responses', function () {
    $gtin = '1234567890123';

    Http::fake([
        '*/portal/api/v2/products/*' => Http::response('Bad Request', 400),
    ]);

    expect(fn () => $this->service->fetchProductByGtin($gtin))
        ->toThrow(NationalCatalogApiException::class);
});

test('fetchProductByGtin logs successful requests', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('National Catalog API: Fetching product', ['gtin' => '1234567890123']);

    Log::shouldReceive('info')
        ->once()
        ->with('National Catalog API: Product found', ['gtin' => '1234567890123']);

    $gtin = '1234567890123';

    Http::fake([
        '*/portal/api/v2/products/*' => Http::response(['gtin' => $gtin], 200),
    ]);

    $this->service->fetchProductByGtin($gtin);
});

test('fetchProductByGtin logs not found responses', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('National Catalog API: Fetching product', ['gtin' => '1234567890123']);

    Log::shouldReceive('warning')
        ->once()
        ->with('National Catalog API: Product not found (empty response)', ['gtin' => '1234567890123']);

    $gtin = '1234567890123';

    Http::fake([
        '*/portal/api/v2/products/*' => Http::response([], 200),
    ]);

    $this->service->fetchProductByGtin($gtin);
});

test('fromConfig creates service instance with config values', function () {
    config([
        'services.national_catalog.api_key' => 'config-api-key',
        'services.national_catalog.base_url' => 'https://config.test.com',
        'services.national_catalog.timeout' => 60,
        'services.national_catalog.retry_times' => 5,
    ]);

    $service = NationalCatalogService::fromConfig();

    expect($service)->toBeInstanceOf(NationalCatalogService::class);
});
