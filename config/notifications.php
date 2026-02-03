<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deduplication Window
    |--------------------------------------------------------------------------
    |
    | The time window (in minutes) during which duplicate notifications are
    | suppressed. If an identical notification (same hotel, user, event type,
    | entity type, and entity ID) was created within this window, the new
    | notification will be skipped.
    |
    */

    'dedup_window_minutes' => env('NOTIFICATION_DEDUP_WINDOW', 5),

];
