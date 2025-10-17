<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorReliability extends Model
{
    use HasFactory;

    protected $table = 'doctor_reliability';
    public $timestamps = false;
    protected $primaryKey = 'doctor_id';
    public $incrementing = false;

    protected $fillable = [
        'doctor_id', 'reliability_score', 'no_show_count', 'on_time_rate', 'median_connect_ms', 'updated_at'
    ];
}

