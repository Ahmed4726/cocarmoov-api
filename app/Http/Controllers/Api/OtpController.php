<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\OtpService;
use Exception;

class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function sendOtp(Request $request)
    {
        // $request->validate(['phone_number' => 'required']);

        try {
            $this->otpService->generateOtp($request->phone_number);
            return jsonResponse(1,['message' => 'OTP sent successfully']);
        } catch (Exception) {
            return jsonResponse(0,['message' => 'Something went wrong']);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            // 'phone_number' => 'required',
            'otp' => 'required|digits:6'
        ]);

        try {
            $verified = $this->otpService->verifyOtp(auth()->user()->phone_number, $request->otp);

            if ($verified) {
                $user = User::where('id', auth()->user()->id)->first();
                $user->phone_verification = 'Verified';
                $user->save();

                return jsonResponse(1,['message' => 'OTP verified successfully']);
            }

            return jsonResponse(0,['message' => 'Invalid OTP']);
        } catch (Exception) {
            return jsonResponse(0,['message' => 'Something went wrong']);
        }
    }

    public function resendOtp()
    {
        // $request->validate(['phone_number' => 'required']);

        try {
            $this->otpService->resendOtp(auth()->user()->phone_number);
            return jsonResponse(1,['message' => 'OTP resent successfully']);
        } catch (Exception) {
            return jsonResponse(0,['message' => 'Something Went Wrong']);
        }
    }

    public function changeNumber(Request $request)
    {
        $request->validate(['phone_number' => 'required']);
        try
        {
            $user = User::where('email', auth()->user()->email)->first();

            // Generate a new remember token
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $user->otp = $otp;
            $user->save();

            $user->sendPhoneChangeOtpNotification($otp);

            $this->otpService->generateOtp($request->phone_number);

            return jsonResponse(1,['message' => 'OTP(s) resent successfully']);
        } catch (Exception) {
            return jsonResponse(0,['message' => 'Something Went Wrong']);
        }
    }

    public function verifyNewNumber(Request $request)
    {
        $request->validate([
            'phone_number' => 'required',
            'email_otp' => 'required|digits:6', // Changed from 'email-otp' to 'email_otp'
            'otp' => 'required|digits:6'
        ]);
        try
        {
            // Verify mobile OTP first
            $mobileVerified = $this->otpService->verifyOtp($request->phone_number, $request->otp);

            // If mobile OTP is verified, proceed to verify email OTP
            if ($mobileVerified) {
                $emailVerified = $request->email_otp === auth()->user()->otp; // Assuming '123456' is the correct email OTP, replace it with the actual logic to verify email OTP

                if ($emailVerified) {
                    // Update user's phone number with the new number
                    $user = User::where('email', auth()->user()->email)->first();
                    $user->phone_number = $request->phone_number;
                    $user->phone_verification = 'Verified';
                    $user->save();

                    return jsonResponse(1,['message' => 'New phone number verified and updated successfully']);
                } else {
                    return jsonResponse(0,['message' => 'Invalid email OTP']);
                }
            } else {
                return jsonResponse(0,['message' => 'Invalid mobile OTP']);
            }
        }  catch (Exception) {
            return jsonResponse(0,['message' => 'Something Went Wrong']);
        }
    }

}
