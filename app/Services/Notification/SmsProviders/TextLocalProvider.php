<?php

namespace App\Services\Notification\SmsProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextLocalProvider implements SmsProviderInterface
{
    private string $apiKey;
    private string $sender;
    private string $baseUrl = 'https://api.textlocal.in';

    public function __construct(string $apiKey, string $senderId, array $settings = [])
    {
        $this->apiKey = $apiKey;
        $this->sender = $senderId;
        if (!empty($settings['base_url'])) {
            $this->baseUrl = $settings['base_url'];
        }
    }

    public function send(string $to, string $message, array $options = []): array
    {
        try {
            // Format phone number
            $to = $this->formatPhoneNumber($to);

            $params = [
                'apikey' => $this->apiKey,
                'numbers' => $to,
                'message' => $message,
                'sender' => $this->sender,
            ];

            // Add DLT template ID if provided
            if (!empty($options['template_id'])) {
                $params['template_id'] = $options['template_id'];
            }

            $response = Http::asForm()->post("{$this->baseUrl}/send/", $params);

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === 'success') {
                $messageId = $data['messages'][0]['id'] ?? null;
                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'response' => $data,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => $data['errors'][0]['message'] ?? 'Unknown error',
                'response' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('TextLocal SMS Error', [
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
            $response = Http::asForm()->post("{$this->baseUrl}/status_message/", [
                'apikey' => $this->apiKey,
                'message_id' => $messageId,
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
            $response = Http::asForm()->post("{$this->baseUrl}/balance/", [
                'apikey' => $this->apiKey,
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

        // Add country code if not present (default to India)
        if (!str_starts_with($phone, '91') && strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        return $phone;
    }
}
