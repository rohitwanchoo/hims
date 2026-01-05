<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'template_id';

    protected $fillable = [
        'hospital_id',
        'template_code',
        'template_name',
        'notification_type',
        'channel',
        'sms_template',
        'sms_dlt_template_id',
        'email_subject',
        'email_template',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function renderSms(array $data): string
    {
        $template = $this->sms_template;
        foreach ($data as $key => $value) {
            $template = str_replace("{{{$key}}}", $value, $template);
        }
        return $template;
    }

    public function renderEmail(array $data): array
    {
        $subject = $this->email_subject;
        $body = $this->email_template;

        foreach ($data as $key => $value) {
            $subject = str_replace("{{{$key}}}", $value, $subject);
            $body = str_replace("{{{$key}}}", $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel)
            ->orWhere('channel', 'both');
    }
}
