<?php

namespace App\Services\Notification\SmsProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Provider implements SmsProviderInterface
{
    private string $apiKey;
    private string $senderId;
    private string $baseUrl = 'https://api.msg91.com/api/v5';

    public function __construct(string $apiKey, string $senderId, array $settings = [])
    {
        $this->apiKey = $apiKey;
        $this->senderId = $senderId;
        if (!empty($settings['base_url'])) {
            $this->baseUrl = $settings['base_url'];
        }
    }

    public function send(string $to, string $message, array $options = []): array
    {
        try {
            // Format phone number
            $to = $this->formatPhoneNumber($to);

            $payload = [
                'sender' => $this->senderId,
                'route' => $options['route'] ?? '4', // Transactional
                'country' => $options['country'] ?? '91',
                'sms' => [
                    [
                        'message' => $message,
                        'to' => [$to],
                    ]
                ],
            ];

            // Add DLT template ID if provided (required for India)
            if (!empty($options['template_id'])) {
                $payload['DLT_TE_ID'] = $options['template_id'];
            }

            $response = Http::withHeaders([
                'authkey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/flow/", $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['type']) && $data['type'] === 'success') {
                return [
                    'success' => true,
                    'message_id' => $data['request_id'] ?? null,
                    'response' => $data,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => $data['message'] ?? 'Unknown error',
                'response' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 SMS Error', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
                'response' => null,
            ];
        }
    }

    public function getStatus(string $messageId): array
    {
        try {
            $response = Http::withHeaders([
                'authkey' => $this->apiKey,
            ])->get("{$this->baseUrl}/report", [
                'request_id' => $messageId,
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'authkey' => $this->apiKey,
            ])->get("{$this->baseUrl}/balance", [
                'type' => 4, // Transactional
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading 0 if present
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Remove 91 prefix if present (will be added by API)
        if (str_starts_with($phone, '91') && strlen($phone) > 10) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }
}
