<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'user_id',
        'page_name',
        'logs',
    ];

    /**
     * Associated doctor, if any.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Associated user, if any.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
