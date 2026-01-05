<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingNote extends Model
{
    protected $primaryKey = 'note_id';

    protected $fillable = [
        'admission_id',
        'note_date',
        'note_time',
        'note_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id', 'admission_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
