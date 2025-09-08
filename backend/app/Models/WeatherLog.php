<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherLog extends Model
{
    protected $fillable = [
        'lat','lon','temperature','feels_like','humidity','weather','time'
    ];
}

