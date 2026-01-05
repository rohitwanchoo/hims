<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbdmTransactionLog extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'log_id';

    protected $fillable = [
        'hospital_id', 'transaction_id', 'request_id',
        'api_endpoint', 'request_type', 'request_payload',
        'response_payload', 'status_code', 'status',
        'error_message', 'ip_address',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function scopeByTransactionId($query, string $transactionId)
    {
        return $query->where('transaction_id', $transactionId);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
