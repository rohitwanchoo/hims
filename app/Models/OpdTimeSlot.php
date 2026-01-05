<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class OpdTimeSlot extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'slot_id';

    protected $fillable = [
        'hospital_id',
        'doctor_id',
        'department_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'max_patients_per_slot',
        'max_patients_per_session',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /**
     * Get available slots for a doctor on a given date
     */
    public static function getAvailableSlots($doctorId, $date)
    {
        $dayOfWeek = strtolower(date('l', strtotime($date)));

        $timeSlots = self::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        $slots = [];
        foreach ($timeSlots as $timeSlot) {
            $start = strtotime($timeSlot->start_time);
            $end = strtotime($timeSlot->end_time);
            $duration = $timeSlot->slot_duration_minutes * 60;

            $slotNumber = 1;
            while ($start < $end) {
                $slotTime = date('H:i:s', $start);

                // Count existing appointments for this slot
                $bookedCount = Appointment::where('doctor_id', $doctorId)
                    ->whereDate('appointment_date', $date)
                    ->where('slot_start_time', $slotTime)
                    ->whereNotIn('status', ['cancelled', 'no_show'])
                    ->count();

                if ($bookedCount < $timeSlot->max_patients_per_slot) {
                    $slots[] = [
                        'slot_number' => $slotNumber,
                        'start_time' => $slotTime,
                        'end_time' => date('H:i:s', $start + $duration),
                        'available' => $timeSlot->max_patients_per_slot - $bookedCount,
                    ];
                }

                $start += $duration;
                $slotNumber++;
            }
        }

        return $slots;
    }

    /**
     * Generate time slots for display
     */
    public function generateSlots()
    {
        $slots = [];
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        $duration = $this->slot_duration_minutes * 60;

        $slotNumber = 1;
        while ($start < $end) {
            $slots[] = [
                'slot_number' => $slotNumber,
                'start_time' => date('H:i:s', $start),
                'end_time' => date('H:i:s', $start + $duration),
            ];
            $start += $duration;
            $slotNumber++;
        }

        return $slots;
    }
}
