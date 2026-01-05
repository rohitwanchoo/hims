<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\NotificationTemplate;
use App\Services\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAppointmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected int $appointmentId
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $appointment = Appointment::with(['patient', 'doctor', 'department'])
            ->find($this->appointmentId);

        if (!$appointment) {
            return;
        }

        // Don't send reminder if appointment is cancelled or completed
        if (in_array($appointment->status, ['cancelled', 'completed', 'no_show'])) {
            return;
        }

        $template = NotificationTemplate::where('hospital_id', $appointment->hospital_id)
            ->where('template_code', 'appointment_reminder')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $variables = [
            'patient_name' => $appointment->patient->full_name ?? 'Patient',
            'doctor_name' => 'Dr. ' . ($appointment->doctor->user->name ?? 'Doctor'),
            'department' => $appointment->department->department_name ?? '',
            'appointment_date' => $appointment->appointment_date->format('d M Y'),
            'appointment_time' => $appointment->appointment_time?->format('h:i A') ?? '',
            'hospital_name' => $appointment->hospital->hospital_name ?? '',
            'token_number' => $appointment->token_number ?? '',
        ];

        $notificationService->sendFromTemplate(
            $appointment->hospital_id,
            $template,
            $appointment->patient->full_name ?? 'Patient',
            $appointment->patient->mobile ?? '',
            $appointment->patient->email ?? null,
            $variables
        );
    }
}
