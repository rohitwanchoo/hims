<?php

namespace App\Services\Notification;

use App\Models\NotificationLog;
use App\Models\SmsGateway;
use App\Services\Notification\SmsProviders\Msg91Provider;
use App\Services\Notification\SmsProviders\SmsProviderInterface;
use App\Services\Notification\SmsProviders\TextLocalProvider;
use App\Services\Notification\SmsProviders\TwilioProvider;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private int $hospitalId;
    private ?SmsGateway $gateway = null;
    private ?SmsProviderInterface $provider = null;

    public function __construct(int $hospitalId, ?int $gatewayId = null)
    {
        $this->hospitalId = $hospitalId;

        if ($gatewayId) {
            $this->gateway = SmsGateway::find($gatewayId);
        } else {
            $this->gateway = SmsGateway::getDefault($hospitalId);
        }

        if ($this->gateway) {
            $this->provider = $this->createProvider($this->gateway);
        }
    }

    public function send(
        string $to,
        string $message,
        array $options = [],
        ?string $recipientName = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): NotificationLog {
        // Create log entry
        $log = NotificationLog::create([
            'hospital_id' => $this->hospitalId,
            'template_id' => $options['template_id'] ?? null,
            'notification_type' => $options['notification_type'] ?? 'general',
            'channel' => 'sms',
            'recipient_type' => $options['recipient_type'] ?? 'patient',
            'recipient_id' => $options['recipient_id'] ?? null,
            'recipient_name' => $recipientName ?? 'Unknown',
            'recipient_contact' => $to,
            'message' => $message,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'status' => 'queued',
            'gateway_id' => $this->gateway?->gateway_id,
        ]);

        if (!$this->provider) {
            $log->markAsFailed('No SMS gateway configured');
            return $log;
        }

        try {
            $result = $this->provider->send($to, $message, $options);

            if ($result['success']) {
                $log->markAsSent($result['message_id'], $result['response']);
            } else {
                $log->markAsFailed($result['error'] ?? 'Unknown error', $result['response'] ?? null);
            }
        } catch (\Exception $e) {
            Log::error('SMS Send Error', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            $log->markAsFailed($e->getMessage());
        }

        return $log;
    }

    public function getStatus(string $messageId): array
    {
        if (!$this->provider) {
            return ['success' => false, 'error' => 'No SMS gateway configured'];
        }

        return $this->provider->getStatus($messageId);
    }

    public function getBalance(): array
    {
        if (!$this->provider) {
            return ['success' => false, 'error' => 'No SMS gateway configured'];
        }

        return $this->provider->getBalance();
    }

    public function testGateway(): array
    {
        if (!$this->gateway) {
            return ['success' => false, 'error' => 'No gateway selected'];
        }

        return $this->getBalance();
    }

    private function createProvider(SmsGateway $gateway): ?SmsProviderInterface
    {
        $settings = $gateway->settings ?? [];
        $settings['api_secret'] = $gateway->api_secret;

        return match ($gateway->provider) {
            'msg91' => new Msg91Provider($gateway->api_key, $gateway->sender_id, $settings),
            'twilio' => new TwilioProvider($gateway->api_key, $gateway->sender_id, $settings),
            'textlocal' => new TextLocalProvider($gateway->api_key, $gateway->sender_id, $settings),
            default => null,
        };
    }

    public function isConfigured(): bool
    {
        return $this->provider !== null;
    }
}
