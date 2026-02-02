<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoInstruction extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_instruction';
    protected $primaryKey = 'instruction_id';

    protected $fillable = [
        'hospital_id',
        'instruction_type',
        'instruction_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
