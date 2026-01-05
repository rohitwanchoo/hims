<?php

namespace App\Services\Notification;

use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private int $hospitalId;
    private SmsService $smsService;

    public function __construct(int $hospitalId)
    {
        $this->hospitalId = $hospitalId;
        $this->smsService = new SmsService($hospitalId);
    }

    public function sendToPatient(
        Patient $patient,
        string $templateCode,
        array $data = [],
        ?string $referenceType = null,
        ?int $referenceId = null
    ): array {
        $template = NotificationTemplate::where('hospital_id', $this->hospitalId)
            ->where('template_code', $templateCode)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return ['success' => false, 'error' => 'Template not found'];
        }

        $results = [];

        // Check SMS preference
        $smsEnabled = NotificationPreference::isSmsEnabled(
            $this->hospitalId,
            $patient->patient_id,
            $template->notification_type
        );

        // Check Email preference
        $emailEnabled = NotificationPreference::isEmailEnabled(
            $this->hospitalId,
            $patient->patient_id,
            $template->notification_type
        );

        // Prepare data with patient info
        $data = array_merge([
            'patient_name' => $patient->patient_name,
            'patient_id' => $patient->pcd,
        ], $data);

        // Send SMS
        if ($smsEnabled && $patient->mobile && in_array($template->channel, ['sms', 'both'])) {
            $message = $template->renderSms($data);
            $results['sms'] = $this->smsService->send(
                $patient->mobile,
                $message,
                [
                    'template_id' => $template->sms_dlt_template_id,
                    'notification_type' => $template->notification_type,
                    'recipient_type' => 'patient',
                    'recipient_id' => $patient->patient_id,
                ],
                $patient->patient_name,
                $referenceType,
                $referenceId
            );
        }

        // Send Email
        if ($emailEnabled && $patient->email && in_array($template->channel, ['email', 'both'])) {
            $results['email'] = $this->sendEmail(
                $patient->email,
                $patient->patient_name,
                $template,
                $data,
                $patient->patient_id,
                $referenceType,
                $referenceId
            );
        }

        return ['success' => true, 'results' => $results];
    }

    public function sendSms(
        string $to,
        string $message,
        ?string $recipientName = null,
        array $options = []
    ): NotificationLog {
        return $this->smsService->send($to, $message, $options, $recipientName);
    }

    public function sendEmail(
        string $to,
        string $recipientName,
        NotificationTemplate $template,
        array $data,
        ?int $recipientId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): NotificationLog {
        $rendered = $template->renderEmail($data);

        $log = NotificationLog::create([
            'hospital_id' => $this->hospitalId,
            'template_id' => $template->template_id,
            'notification_type' => $template->notification_type,
            'channel' => 'email',
            'recipient_type' => 'patient',
            'recipient_id' => $recipientId,
            'recipient_name' => $recipientName,
            'recipient_contact' => $to,
            'subject' => $rendered['subject'],
            'message' => $rendered['body'],
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'status' => 'queued',
        ]);

        try {
            Mail::html($rendered['body'], function ($mail) use ($to, $recipientName, $rendered) {
                $mail->to($to, $recipientName)
                    ->subject($rendered['subject']);
            });

            $log->markAsSent();
        } catch (\Exception $e) {
            Log::error('Email Send Error', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            $log->markAsFailed($e->getMessage());
        }

        return $log;
    }

    public function sendBulkSms(array $recipients, string $message, array $options = []): array
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $results[] = $this->smsService->send(
                $recipient['phone'],
                $message,
                $options,
                $recipient['name'] ?? null
            );
        }
        return $results;
    }
}
