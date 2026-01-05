<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'log_id';

    protected $fillable = [
        'hospital_id',
        'template_id',
        'notification_type',
        'channel',
        'recipient_type',
        'recipient_id',
        'recipient_name',
        'recipient_contact',
        'subject',
        'message',
        'reference_type',
        'reference_id',
        'status',
        'gateway_id',
        'gateway_response',
        'message_id',
        'sent_at',
        'delivered_at',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id', 'template_id');
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(SmsGateway::class, 'gateway_id', 'gateway_id');
    }

    public function markAsSent(string $messageId = null, array $response = null): void
    {
        $this->update([
            'status' => 'sent',
            'message_id' => $messageId,
            'gateway_response' => $response,
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsFailed(string $error, array $response = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'gateway_response' => $response,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRetryable($query, int $maxRetries = 3)
    {
        return $query->where('status', 'failed')
            ->where('retry_count', '<', $maxRetries);
    }
}
