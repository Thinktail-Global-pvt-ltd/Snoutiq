<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VetLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vet_name',
        'vet_phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
