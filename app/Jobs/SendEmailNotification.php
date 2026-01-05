<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected int $hospitalId,
        protected string $recipientName,
        protected string $recipientEmail,
        protected string $subject,
        protected string $htmlContent,
        protected ?int $templateId = null,
        protected array $attachments = []
    ) {}

    public function handle(): void
    {
        $log = NotificationLog::create([
            'hospital_id' => $this->hospitalId,
            'template_id' => $this->templateId,
            'channel' => 'email',
            'recipient_name' => $this->recipientName,
            'recipient_contact' => $this->recipientEmail,
            'message' => $this->subject,
            'status' => 'queued',
        ]);

        try {
            Mail::send([], [], function ($message) {
                $message->to($this->recipientEmail, $this->recipientName)
                    ->subject($this->subject)
                    ->html($this->htmlContent);

                foreach ($this->attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    }
                }
            });

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
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
            ->where('recipient_contact', $this->recipientEmail)
            ->where('message', $this->subject)
            ->where('status', 'queued')
            ->update([
                'status' => 'failed',
                'gateway_response' => ['error' => $exception->getMessage()],
            ]);
    }
}
