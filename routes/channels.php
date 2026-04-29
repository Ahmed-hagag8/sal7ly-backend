<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('job.{jobId}', function ($user, $jobId) {
    $job = \App\Models\Job::find($jobId);
    return $job && (
        $job->customer_id === $user->customer?->id ||
        $job->technician_id === $user->technician?->id
    );
});
