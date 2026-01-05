<?php

namespace App\Jobs;

use App\Models\IpdAdmission;
use App\Models\NotificationTemplate;
use App\Services\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDischargeNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected int $ipdAdmissionId
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $admission = IpdAdmission::with(['patient', 'hospital'])
            ->find($this->ipdAdmissionId);

        if (!$admission || $admission->status !== 'discharged') {
            return;
        }

        $template = NotificationTemplate::where('hospital_id', $admission->hospital_id)
            ->where('template_code', 'discharge_notification')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $variables = [
            'patient_name' => $admission->patient->full_name ?? 'Patient',
            'admission_number' => $admission->admission_number ?? '',
            'discharge_date' => $admission->discharge_datetime?->format('d M Y') ?? now()->format('d M Y'),
            'hospital_name' => $admission->hospital->hospital_name ?? '',
        ];

        $notificationService->sendFromTemplate(
            $admission->hospital_id,
            $template,
            $admission->patient->full_name ?? 'Patient',
            $admission->patient->mobile ?? '',
            $admission->patient->email ?? null,
            $variables
        );
    }
}
