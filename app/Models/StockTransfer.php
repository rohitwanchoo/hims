<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'transfer_id';

    protected $fillable = [
        'hospital_id',
        'transfer_number',
        'from_store_id',
        'to_store_id',
        'transfer_date',
        'indent_id',
        'status',
        'transferred_by',
        'received_by',
        'received_at',
        'remarks',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id', 'store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id', 'store_id');
    }

    public function indent(): BelongsTo
    {
        return $this->belongsTo(Indent::class, 'indent_id', 'indent_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(StockTransferDetail::class, 'transfer_id', 'transfer_id');
    }

    public static function generateTransferNumber(int $hospitalId): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');
        $count = static::where('hospital_id', $hospitalId)
            ->whereDate('created_at', today())
            ->count();
        return $prefix . $date . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
