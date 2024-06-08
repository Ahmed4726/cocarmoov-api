<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpNotification extends Notification
{
    use Queueable;

    protected $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function via($notifiable)
    { 
        return ['mail']; // or ['sms'] if you have an SMS service integrated
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Your OTP Code:' . $this->otp)
                    ->line('Your OTP code is ' . $this->otp)
                    ->line('Thank you for using our application!');
    }

    // Add SMS or other notification channels as needed
}


