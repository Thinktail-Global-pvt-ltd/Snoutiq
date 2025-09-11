<?php

// app/Models/CallSession.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallSession extends Model
{
    protected $fillable = [
        'patient_id','doctor_id','channel_name','status','payment_status'
    ];
}

