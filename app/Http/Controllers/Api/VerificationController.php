<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    public function verify(Request $request)
    {
        try
        {
        $email = User::where('email', auth()->user()->email)->first();
        // Check if the user's email is already verified
        if ($email->hasVerifiedEmail()) {
            return jsonResponse(0, ['error' => 'Email already verified!']);
        }
        // Retrieve the user based on email and token
        $user = User::where('email', auth()->user()->email)->where('otp', $request->otp)->first();

        // Check if token is null
        if ($request->otp == null) {
            return jsonResponse(0, ['error' => 'Email verification otp not found!']);
        }
        // Check if user with the given email and token exists
        elseif (!$user){
            return jsonResponse(0, ['error' => 'Email verification token mismatch!']);
        }


        // Mark the user's email as verified
        $user->markEmailAsVerified();

        // You can also log in the user here if you want

        return jsonResponse(1, ['message' => 'Email Verified Successfully']);

        } catch(Exception) {
            return jsonResponse(0, ['error' => 'An error occurred']);
        }

    }


    public function resend(Request $request)
    {
        try
        {
        // $validator = Validator::make($request->all(), [
        //     'email' => 'required|email|exists:users,email',
        // ]);

        // if ($validator->fails()) {
        //     return jsonResponse(0, ['error' => $validator->errors()]);
        // }

        // Retrieve the user by email
        $user = User::where('email', auth()->user()->email)->first();

        // If user not found or email already verified, return error
        if ($user->hasVerifiedEmail()) {
            return jsonResponse(0, ['error' => 'User Email already verified.']);
        }

        // Generate a new remember token
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Update the remember_token field with the new token
        $user->otp = $otp;
        $user->save();
        // Send the verification email with the new token
        $user->sendOtpNotification($otp);

        return jsonResponse(1,
        [
            'message' => 'Verification email-otp resent successfully.',
            'otp' => $otp
        ]);
        } catch(Exception) {
            return jsonResponse(0, ['error' => 'An error occurred']);
        }
    }
}
