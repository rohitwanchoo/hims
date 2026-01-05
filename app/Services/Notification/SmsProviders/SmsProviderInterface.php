<?php

namespace App\Services\Notification\SmsProviders;

interface SmsProviderInterface
{
    /**
     * Send an SMS message
     *
     * @param string $to Phone number
     * @param string $message Message content
     * @param array $options Additional options (template_id for DLT, etc.)
     * @return array Response with 'success', 'message_id', 'response'
     */
    public function send(string $to, string $message, array $options = []): array;

    /**
     * Get delivery status of a message
     *
     * @param string $messageId
     * @return array Status information
     */
    public function getStatus(string $messageId): array;

    /**
     * Get remaining balance/credits
     *
     * @return array Balance information
     */
    public function getBalance(): array;
}
