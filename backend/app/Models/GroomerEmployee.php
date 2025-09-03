<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerEmployee extends Model
{
    //

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'dob',
        'calendar_color',
        'job_title',
        'notes',
        'services',
        'type',
        'monthly_salary',
        'commissions',
        'address',
    ];

    protected $casts = [
        'services' => 'array',
        'commissions' => 'array',
        'dob' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
