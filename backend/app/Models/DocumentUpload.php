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
        'file_blob',
        'file_mime',
        'file_name',
        'file_size',
        'uploaded_at',
    ];

    protected $casts = [
        'files_json' => 'array',
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    protected $hidden = [
        'file_blob',
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
