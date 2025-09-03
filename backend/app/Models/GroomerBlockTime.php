<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerBlockTime extends Model
{
    //
    protected $table = 'groomer_block_times';

    protected $fillable = [
        'title',
        'date',
        'start_time',
        'end_time',
        'user_id',
        'groomer_employees_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function groomerEmployee()
    {
        return $this->belongsTo(GroomerEmployee::class, 'groomer_employees_id');
    }
}
