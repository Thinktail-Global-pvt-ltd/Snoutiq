<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerClientPet extends Model
{
    //
    protected $fillable = [
        'name',
        'type',
        'breed',
        'dob',
        'gender',
        'medicalHistory',
        'vaccinationLog',
        'user_id',
        'groomer_client_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function groomerClient()
    {
        return $this->belongsTo(GroomerClient::class);
    }
}
