<?php

namespace App\Services\Notification\SmsProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioProvider implements SmsProviderInterface
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private string $baseUrl;

    public function __construct(string $apiKey, string $senderId, array $settings = [])
    {
        $this->accountSid = $apiKey;
        $this->authToken = $settings['api_secret'] ?? '';
        $this->fromNumber = $senderId;
        $this->baseUrl = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}";
    }

    public function send(string $to, string $message, array $options = []): array
    {
        try {
            // Format phone number
            $to = $this->formatPhoneNumber($to);

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("{$this->baseUrl}/Messages.json", [
                    'From' => $this->fromNumber,
                    'To' => $to,
                    'Body' => $message,
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['sid'])) {
                return [
                    'success' => true,
                    'message_id' => $data['sid'],
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
            Log::error('Twilio SMS Error', [
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
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get("{$this->baseUrl}/Messages/{$messageId}.json");

            $data = $response->json();

            return [
                'success' => $response->successful(),
                'status' => $data['status'] ?? null,
                'data' => $data,
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
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get("{$this->baseUrl}/Balance.json");

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

        return '+' . $phone;
    }
}
