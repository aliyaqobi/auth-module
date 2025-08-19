<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendEmailVerificationNotification extends Notification
{
    use Queueable;

    private $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Email Verification')
            ->greeting("Welcome to the website.")
            ->line("Your email verification code is **{$this->code}**.")
            ->line('This code will expire in 15 minutes.');
    }
}
