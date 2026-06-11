<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetVaccinationDocument extends Model
{
    protected $table = 'pet_vaccination_documents';

    protected $fillable = [
        'pet_id',
        'document_blob',
        'document_mime',
        'document_name',
        'document_size',
    ];

    protected $hidden = [
        'document_blob',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }
}
