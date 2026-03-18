<?php

use Illuminate\Support\Facades\Broadcast;

// Private per-user channel (for future authenticated notifications)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel — anyone connected will receive login notifications
Broadcast::channel('notifications', function () {
    return true;
});
