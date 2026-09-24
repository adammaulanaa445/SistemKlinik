<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id', 'examination_result', 'diagnosis', 'treatment', 'notes',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }
}