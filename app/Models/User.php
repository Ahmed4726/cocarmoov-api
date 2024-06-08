<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\SendOtpNotification;
use App\Notifications\SendPhoneChangeOtpNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'last_name',
        'family_name',
        'phone_number',
        'email',
        'otp',
        'password',
        'user_type',
        'quantity',
        'marketing_emails',
        'remember_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function sendOtpNotification($otp)
    {
        $this->notify(new SendOtpNotification($otp));
    }

    public function sendPhoneChangeOtpNotification($otp)
    {
        $this->notify(new SendPhoneChangeOtpNotification($otp));
    }
}
