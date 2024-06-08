<?php

// app/Services/OtpService.php

namespace App\Services;

use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class OtpService
{
    protected $twilio;
    protected $verifyServiceSid;

    public function __construct()
    {
        $this->twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $this->verifyServiceSid = env('TWILIO_VERIFY_SERVICE_SID');
    }

    public function generateOtp($phoneNumber)
    {
        try {
            $verification = $this->twilio->verify->v2->services($this->verifyServiceSid)
                                      ->verifications
                                      ->create($phoneNumber, "sms");

            return $verification->sid;
        } catch (TwilioException $e) {
            throw new \Exception("Failed to send OTP: " . $e->getMessage());
        }
    }

    public function verifyOtp($phoneNumber, $otp)
    {
        try {
            $verificationCheck = $this->twilio->verify->v2->services($this->verifyServiceSid)
                                          ->verificationChecks
                                          ->create([
                                              'to' => $phoneNumber,
                                              'code' => $otp
                                          ]);

            return $verificationCheck->status == 'approved';
        } catch (TwilioException $e) {
            throw new \Exception("Failed to verify OTP: " . $e->getMessage());
        }
    }

    public function resendOtp($phoneNumber)
    {
        try {
            $verification = $this->twilio->verify->v2->services($this->verifyServiceSid)
                                      ->verifications
                                      ->create($phoneNumber, "sms");

            return $verification->sid;
        } catch (TwilioException $e) {
            throw new \Exception("Failed to resend OTP: " . $e->getMessage());
        }
    }
}
