<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAiAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pet_id',
        'file_name',
        'mime_type',
        'file_size',
        'document_blob',
        'model',
        'prompt',
        'summary',
        'raw_response',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'raw_response' => 'array',
    ];

    protected $hidden = [
        'document_blob',
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
