<?php

namespace App\Services;

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
     * Returns product data array on success, null if not found.
     *
     * @throws NationalCatalogApiException
     * @throws NationalCatalogRateLimitException
     */
    public function fetchProductByGtin(string $gtin): ?array
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

                    return null;
                }

                Log::info('National Catalog API: Product found', ['gtin' => $gtin]);

                return $data;
            }

            if ($response->status() === 404) {
                Log::warning('National Catalog API: Product not found', ['gtin' => $gtin]);

                return null;
            }

            if ($response->status() === 429) {
                Log::error('National Catalog API: Rate limit exceeded', ['gtin' => $gtin]);
                throw new NationalCatalogRateLimitException(
                    'Rate limit exceeded for National Catalog API'
                );
            }

            // Any other error status
            Log::error('National Catalog API: Request failed', [
                'gtin' => $gtin,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new NationalCatalogApiException(
                "API request failed with status {$response->status()}: {$response->body()}",
                $response->status()
            );

        } catch (RequestException $e) {
            Log::error('National Catalog API: Request exception', [
                'gtin' => $gtin,
                'message' => $e->getMessage(),
            ]);

            throw new NationalCatalogApiException(
                "Network error: {$e->getMessage()}",
                0,
                $e
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

/**
 * Exception thrown when National Catalog API returns an error.
 */
class NationalCatalogApiException extends \Exception {}

/**
 * Exception thrown when National Catalog API rate limit is exceeded.
 */
class NationalCatalogRateLimitException extends NationalCatalogApiException {}
