<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultMaster extends Model
{
    use HasFactory, BelongsToHospital;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'consult_master_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hospital_id',
        'department_id',
        'doctor_id',
        'day_of_week',
        'specific_date',
        'time_period',
        'start_time',
        'end_time',
        'slot_duration',
        'time_slots',
        'max_patients_per_slot',
        'is_active',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'specific_date' => 'date',
        'time_slots' => 'array',
        'is_active' => 'boolean',
        'slot_duration' => 'integer',
        'max_patients_per_slot' => 'integer',
        'day_of_week' => 'integer',
    ];

    /**
     * Get the route key name for Laravel.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'consult_master_id';
    }

    /**
     * Get the department that owns the consult schedule.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /**
     * Get the doctor that owns the consult schedule.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    /**
     * Generate time slots based on start time, end time, and slot duration.
     *
     * @return array
     */
    public function generateTimeSlots(): array
    {
        $slots = [];
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        $current = $start->copy();

        while ($current->lessThan($end)) {
            $slotEnd = $current->copy()->addMinutes($this->slot_duration);

            // Don't add slot if it goes beyond end time
            if ($slotEnd->greaterThan($end)) {
                break;
            }

            $slots[] = [
                'start' => $current->format('H:i'),
                'end' => $slotEnd->format('H:i'),
                'label' => $current->format('h:i A') . ' - ' . $slotEnd->format('h:i A'),
            ];

            $current = $slotEnd;
        }

        return $slots;
    }

    /**
     * Get the time period label.
     *
     * @return string
     */
    public function getTimePeriodLabelAttribute(): string
    {
        return ucfirst($this->time_period);
    }

    /**
     * Get the day name if day_of_week is set.
     *
     * @return string|null
     */
    public function getDayNameAttribute(): ?string
    {
        if ($this->day_of_week === null) {
            return 'All Days';
        }

        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return $days[$this->day_of_week] ?? null;
    }

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by doctor.
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope a query to filter by department.
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope a query to filter by specific date or day of week.
     */
    public function scopeForDate($query, $date)
    {
        $carbonDate = \Carbon\Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeekIso; // 1=Monday, 7=Sunday

        return $query->where(function ($q) use ($date, $dayOfWeek) {
            $q->where('specific_date', $date)
                ->orWhere(function ($sq) use ($dayOfWeek) {
                    $sq->where('day_of_week', $dayOfWeek)
                        ->whereNull('specific_date');
                })
                ->orWhere(function ($sq) {
                    $sq->whereNull('day_of_week')
                        ->whereNull('specific_date');
                });
        });
    }
}
