<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hl7Message extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'message_id';

    protected $fillable = [
        'hospital_id', 'message_control_id', 'message_type',
        'trigger_event', 'direction', 'source_system',
        'destination_system', 'raw_message', 'parsed_message',
        'status', 'processing_error', 'processed_at',
        'reference_type', 'reference_id',
    ];

    protected $casts = [
        'parsed_message' => 'array',
        'processed_at' => 'datetime',
    ];

    public function getReference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        return match ($this->reference_type) {
            'patient' => Patient::find($this->reference_id),
            'appointment' => Appointment::find($this->reference_id),
            'lab_order' => LabOrder::find($this->reference_id),
            'radiology_order' => RadiologyOrder::find($this->reference_id),
            default => null,
        };
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, string $type, ?string $event = null)
    {
        $query->where('message_type', $type);
        if ($event) {
            $query->where('trigger_event', $event);
        }
        return $query;
    }
}
