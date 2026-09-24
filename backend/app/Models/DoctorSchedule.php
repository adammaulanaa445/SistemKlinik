<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id', 'polyclinic_id', 'day', 'start_time', 'end_time', 'quota',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function polyclinic()
    {
        return $this->belongsTo(Polyclinic::class);
    }
}