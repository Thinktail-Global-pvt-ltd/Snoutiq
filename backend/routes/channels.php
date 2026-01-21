<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Keep channel names aligned with the events:
| - CallRequested -> PrivateChannel('doctor.{id}')
| - CallStatusUpdated -> PrivateChannel('doctor.{id}'), PrivateChannel('patient.{id}')
*/

Broadcast::channel('doctor.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('patient.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
