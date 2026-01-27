<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalServicePrice extends Model
{
    protected $primaryKey = 'price_id';

    protected $fillable = [
        'hospital_service_id',
        'room_id',
        'bed_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected $appends = ['room_name', 'bed_number'];

    public function hospitalService()
    {
        return $this->belongsTo(HospitalService::class, 'hospital_service_id', 'hospital_service_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class, 'bed_id', 'bed_id');
    }

    public function getRoomNameAttribute()
    {
        return $this->room ? $this->room->room_name : null;
    }

    public function getBedNumberAttribute()
    {
        return $this->bed ? $this->bed->bed_number : null;
    }
}
