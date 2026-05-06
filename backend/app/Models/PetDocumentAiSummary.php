<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetDocumentAiSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pet_id',
        'file_name',
        'mime_type',
        'file_size',
        'model',
        'output',
        'raw_response',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'raw_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}
