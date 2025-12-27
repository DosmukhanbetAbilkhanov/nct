<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\RateLimitException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NationalCatalogService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private int $timeout = 30,
        private int $retryTimes = 3,
    ) {}

    /**
     * Create service instance from config.
     */
    public static function fromConfig(): self
    {
        return new self(
            apiKey: config('services.national_catalog.api_key'),
            baseUrl: config('services.national_catalog.base_url'),
            timeout: config('services.national_catalog.timeout', 30),
            retryTimes: config('services.national_catalog.retry_times', 3),
        );
    }

    /**
     * Fetch product data by GTIN from National Catalog API.
     *
     * @throws ProductNotFoundException If product not found (404 or empty response)
     * @throws RateLimitException If rate limit exceeded (429)
     * @throws ApiException For other API errors
     */
    public function fetchProductByGtin(string $gtin): array
    {
        Log::info('National Catalog API: Fetching product', ['gtin' => $gtin]);

        try {
            $response = $this->client()
                ->retry($this->retryTimes, 100, function ($exception, $request) {
                    // Only retry on network errors or 500-level errors
                    if ($exception instanceof RequestException) {
                        $status = $exception->response->status();

                        return $status >= 500 && $status < 600;
                    }

                    return true;
                }, throw: false)
                ->get($this->buildEndpoint($gtin));

            // Handle different response codes
            if ($response->successful()) {
                $data = $response->json();

                // API returns empty array when product not found
                if (empty($data)) {
                    Log::warning('National Catalog API: Product not found (empty response)', ['gtin' => $gtin]);
                    throw new ProductNotFoundException($gtin);
                }

                Log::info('National Catalog API: Product found', ['gtin' => $gtin]);

                return $data;
            }

            if ($response->status() === 404) {
                Log::warning('National Catalog API: Product not found', ['gtin' => $gtin]);
                throw new ProductNotFoundException($gtin);
            }

            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 60);
                Log::error('National Catalog API: Rate limit exceeded', [
                    'gtin' => $gtin,
                    'retry_after' => $retryAfter,
                ]);
                throw new RateLimitException($retryAfter);
            }

            // Any other error status
            Log::error('National Catalog API: Request failed', [
                'gtin' => $gtin,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new ApiException(
                statusCode: $response->status(),
                message: "API request failed: {$response->body()}"
            );

        } catch (RequestException $e) {
            Log::error('National Catalog API: Request exception', [
                'gtin' => $gtin,
                'message' => $e->getMessage(),
            ]);

            throw new ApiException(
                statusCode: $e->getCode(),
                message: "Network error: {$e->getMessage()}"
            );
        }
    }

    /**
     * Build the API endpoint URL for GTIN lookup.
     */
    protected function buildEndpoint(string $gtin): string
    {
        return "/portal/api/v2/products/{$gtin}";
    }

    /**
     * Create HTTP client with authentication.
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Accept' => 'application/json',
            ]);
    }
}
