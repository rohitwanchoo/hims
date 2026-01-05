<?php

namespace App\Jobs;

use App\Models\LabOrder;
use App\Models\NotificationTemplate;
use App\Services\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLabResultNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected int $labOrderId
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $labOrder = LabOrder::with(['patient', 'hospital'])
            ->find($this->labOrderId);

        if (!$labOrder || $labOrder->status !== 'completed') {
            return;
        }

        $template = NotificationTemplate::where('hospital_id', $labOrder->hospital_id)
            ->where('template_code', 'lab_result_ready')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $variables = [
            'patient_name' => $labOrder->patient->full_name ?? 'Patient',
            'order_number' => $labOrder->order_number,
            'hospital_name' => $labOrder->hospital->hospital_name ?? '',
            'collection_date' => $labOrder->sample_collected_at?->format('d M Y') ?? '',
        ];

        $notificationService->sendFromTemplate(
            $labOrder->hospital_id,
            $template,
            $labOrder->patient->full_name ?? 'Patient',
            $labOrder->patient->mobile ?? '',
            $labOrder->patient->email ?? null,
            $variables
        );
    }
}
