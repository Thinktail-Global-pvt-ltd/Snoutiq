<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name','email','phone','role','referral_code','last_vet_slug','last_vet_id','password',
        'pet_name','pet_gender','pet_age','pet_doc1','pet_doc2','pet_doc2_blob','pet_doc2_mime',
        'api_token_hash','summary','google_token','breed','latitude','longitude',
        'qr_scanner_id','feedback',
    ];

    protected $hidden = ['password','remember_token','api_token_hash','pet_doc2_blob'];

    protected $casts  = ['pet_age' => 'integer'];

    // // auto-hash password
    // public function setPasswordAttribute($value)
    // {
    //     if ($value) $this->attributes['password'] = Hash::make($value);
    // }

    public function qrScanner()
    {
        return $this->belongsTo(LegacyQrRedirect::class, 'qr_scanner_id');
    }

    public function callSessions()
    {
        return $this->hasMany(CallSession::class, 'patient_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(\App\Models\VetResponseReminderLog::class);
    }
}
