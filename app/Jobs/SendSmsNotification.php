<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\Notification\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected int $hospitalId,
        protected string $recipientName,
        protected string $recipientMobile,
        protected string $message,
        protected ?int $templateId = null,
        protected ?string $dltTemplateId = null,
        protected array $metadata = []
    ) {}

    public function handle(SmsService $smsService): void
    {
        $log = NotificationLog::create([
            'hospital_id' => $this->hospitalId,
            'template_id' => $this->templateId,
            'channel' => 'sms',
            'recipient_name' => $this->recipientName,
            'recipient_contact' => $this->recipientMobile,
            'message' => $this->message,
            'status' => 'queued',
        ]);

        try {
            $result = $smsService->send(
                $this->hospitalId,
                $this->recipientMobile,
                $this->message,
                [
                    'dlt_template_id' => $this->dltTemplateId,
                    ...$this->metadata,
                ]
            );

            $log->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'gateway_response' => $result,
                'sent_at' => $result['success'] ? now() : null,
            ]);
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'gateway_response' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        NotificationLog::where('hospital_id', $this->hospitalId)
            ->where('recipient_contact', $this->recipientMobile)
            ->where('message', $this->message)
            ->where('status', 'queued')
            ->update([
                'status' => 'failed',
                'gateway_response' => ['error' => $exception->getMessage()],
            ]);
    }
}
