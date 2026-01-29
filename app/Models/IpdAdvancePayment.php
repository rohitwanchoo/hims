<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class IpdAdvancePayment extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'advance_id';

    protected $fillable = [
        'hospital_id',
        'ipd_id',
        'receipt_number',
        'payment_date',
        'amount',
        'payment_mode',
        'reference_number',
        'remarks',
        'is_refunded',
        'refund_amount',
        'refund_date',
        'refund_reason',
        'refund_mode',
        'authorized_by',
        'refunded_by',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'refund_date' => 'date',
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'is_refunded' => 'boolean',
    ];

    protected $appends = ['effective_amount'];

    public function getEffectiveAmountAttribute()
    {
        return $this->amount - ($this->refund_amount ?? 0);
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }

    public function refundedByUser()
    {
        return $this->belongsTo(User::class, 'refunded_by', 'user_id');
    }

    // Scopes
    public function scopeNotRefunded($query)
    {
        return $query->where('is_refunded', false);
    }

    public function scopeRefunded($query)
    {
        return $query->where('is_refunded', true);
    }

    // Generate receipt number
    public static function generateReceiptNumber($hospitalId)
    {
        $prefix = 'ADV';
        $year = date('Y');
        $month = date('m');

        $lastReceipt = self::where('hospital_id', $hospitalId)
            ->whereYear('created_at', $year)
            ->orderBy('advance_id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastReceipt && preg_match('/(\d+)$/', $lastReceipt->receipt_number, $matches)) {
            $sequence = (int)$matches[1] + 1;
        }

        return $prefix . $year . $month . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
