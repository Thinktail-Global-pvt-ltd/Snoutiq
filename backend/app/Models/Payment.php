<?php

// app/Models/Payment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'amount',
        'currency',
        'status',
        'method',
        'email',
        'contact',
        'notes',
        'raw_response',
    ];

    protected $casts = [
        'notes'        => 'array',
        'raw_response' => 'array',
    ];
}
