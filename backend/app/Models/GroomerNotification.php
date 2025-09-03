<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerNotification extends Model
{
    //
    protected $table = 'groomer_notifications';
    protected $fillable = ['user_id', 'title', 'message', 'image', 'cta_text', 'cta_url'];
}
