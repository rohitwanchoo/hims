<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $primaryKey = 'room_id';

    protected $fillable = [
        'ward_id',
        'room_name',
        'bed_capacity',
        'room_type',
        'floor_number',
        'room_description',
    ];

    protected $casts = [
        'bed_capacity' => 'integer',
        'floor_number' => 'integer',
    ];

    /**
     * Get the ward that owns the room.
     */
    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_id', 'ward_id');
    }

    /**
     * Get all beds in the room.
     */
    public function beds()
    {
        return $this->hasMany(Bed::class, 'room_id', 'room_id')
            ->orderByRaw('CAST(bed_number AS UNSIGNED)');
    }

    /**
     * Get available beds in the room.
     */
    public function availableBeds()
    {
        return $this->beds()->where('status', 'available');
    }

    /**
     * Get occupied beds in the room.
     */
    public function occupiedBeds()
    {
        return $this->beds()->where('status', 'occupied');
    }
}
