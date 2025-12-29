<?php

namespace App\Services;

use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobizonSmsService implements SmsServiceInterface
{
    protected string $apiKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.mobizon.api_key');
        $this->baseUrl = config('services.mobizon.base_url', 'https://api.mobizon.kz/service');
    }

    public function send(string $phoneNumber, string $message): bool
    {
        try {
            $response = Http::asForm()->post("{$this->baseUrl}/message/sendsmsmessage", [
                'apiKey' => $this->apiKey,
                'recipient' => $this->formatPhoneNumber($phoneNumber),
                'text' => $message,
                'from' => config('services.mobizon.sender_name', 'NCT'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['code'] ?? null) === 0) {
                    Log::info('SMS sent successfully', [
                        'phone' => $phoneNumber,
                        'message_id' => $data['data']['messageId'] ?? null,
                    ]);

                    return true;
                }

                Log::error('Mobizon API error', [
                    'phone' => $phoneNumber,
                    'error' => $data['message'] ?? 'Unknown error',
                ]);

                return false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getBalance(): ?float
    {
        try {
            $response = Http::get("{$this->baseUrl}/user/getownbalance", [
                'apiKey' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['data']['balance'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get SMS balance', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function formatPhoneNumber(string $phoneNumber): string
    {
        $cleaned = preg_replace('/\D/', '', $phoneNumber);

        if (! str_starts_with($cleaned, '7')) {
            $cleaned = '7'.$cleaned;
        }

        return $cleaned;
    }
}
