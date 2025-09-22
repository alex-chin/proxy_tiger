<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Exceptions\SmsServiceException;
use Illuminate\Support\Facades\Http;

class TigerSmsService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('tiger_test');
    }

    public function getNumber(?string $country = null, ?string $service = null): array
    {
        return $this->makeRequest([
            'action' => 'getNumber',
            'country' => $country ?? $this->config['default_country'],
            'service' => $service ?? $this->config['default_service'],
            'token' => $this->config['token']
        ]);
    }

    public function getSms(string $activation): array
    {
        return $this->makeRequest([
            'action' => 'getSms',
            'token' => $this->config['token'],
            'activation' => $activation
        ]);
    }

    public function cancelNumber(string $activation): array
    {
        return $this->makeRequest([
            'action' => 'cancelNumber',
            'token' => $this->config['token'],
            'activation' => $activation
        ]);
    }

    public function getStatus(string $activation): array
    {
        return $this->makeRequest([
            'action' => 'getStatus',
            'token' => $this->config['token'],
            'activation' => $activation
        ]);
    }

    protected function makeRequest(array $data): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->get($this->config['api_url'], $data);

            if ($response->failed()) {
                throw new SmsServiceException('HTTP error: ' . $response->status());
            }

            $responseData = $response->json();

            $this->logRequest($data, $responseData, $response->status());

            return $responseData;

        } catch (\Exception $e) {
            $this->logError($data, $e->getMessage());
            throw new SmsServiceException('API request failed: ' . $e->getMessage());
        }
    }

    protected function logRequest(array $request, ?array $response, int $statusCode): void
    {
        Log::info('Tiger SMS API Request', [
            'request' => $request,
            'response' => $response,
            'status_code' => $statusCode,
            'timestamp' => now()->toISOString(),
        ]);
    }

    protected function logError(array $request, string $error): void
    {
        Log::error('Tiger SMS API Error', [
            'request' => $request,
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
