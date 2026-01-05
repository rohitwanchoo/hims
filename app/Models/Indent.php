<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indent extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'indent_id';

    protected $fillable = [
        'hospital_id',
        'indent_number',
        'from_store_id',
        'to_store_id',
        'indent_date',
        'required_by_date',
        'priority',
        'remarks',
        'status',
        'submitted_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'indent_date' => 'date',
        'required_by_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id', 'store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id', 'store_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(IndentDetail::class, 'indent_id', 'indent_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by', 'user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public static function generateIndentNumber(int $hospitalId): string
    {
        $prefix = 'IND';
        $date = now()->format('Ymd');
        $count = static::where('hospital_id', $hospitalId)
            ->whereDate('created_at', today())
            ->count();
        return $prefix . $date . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
