<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'vet_registeration_id',
        'day_of_week',
        'open_time',
        'close_time',
        'closed',
    ];

    public function clinic()
    {
        return $this->belongsTo(VetRegisterationTemp::class, 'vet_registeration_id');
    }
}

