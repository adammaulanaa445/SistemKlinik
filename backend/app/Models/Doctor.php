<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'polyclinic_id', 'doctor_code', 'specialization', 'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function polyclinic()
    {
        return $this->belongsTo(Polyclinic::class);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}