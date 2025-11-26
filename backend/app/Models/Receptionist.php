<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receptionist extends Model
{
    use HasFactory;

    protected $fillable = [
        'vet_registeration_id',
        'name',
        'email',
        'phone',
        'role',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function clinic()
    {
        return $this->belongsTo(VetRegisterationTemp::class, 'vet_registeration_id');
    }
}
