<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pet_id',
        'record_type',
        'record_label',
        'source',
        'file_count',
        'files_json',
        'uploaded_at',
    ];

    protected $casts = [
        'files_json' => 'array',
        'uploaded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}
