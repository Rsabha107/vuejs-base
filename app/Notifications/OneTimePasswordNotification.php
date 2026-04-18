<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\OneTimePasswords\Models\OneTimePassword;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification as SpatieOneTimePasswordNotification;

class OneTimePasswordNotification extends SpatieOneTimePasswordNotification implements ShouldQueue
{
    use Queueable;
}