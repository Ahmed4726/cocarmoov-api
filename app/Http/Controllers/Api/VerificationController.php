<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    public function verify(Request $request)
    {
        $email = User::where('email', $request->email)->first();
        // Check if the user's email is already verified
        if ($email->hasVerifiedEmail()) {
            return jsonResponse(0, ['error' => 'Email already verified!']);
        }
        // Retrieve the user based on email and token
        $user = User::where('email', $request->email)->where('remember_token', $request->token)->first();

        // Check if token is null
        if ($request->token == null) {
            return jsonResponse(0, ['error' => 'Email verification token not found!']);
        }
        // Check if user with the given email and token exists
        elseif (!$user){
            return jsonResponse(0, ['error' => 'Email verification token mismatch!']);
        }


        // Mark the user's email as verified
        $user->markEmailAsVerified();

        // You can also log in the user here if you want

        return jsonResponse(1, ['message' => 'Email Verified Successfully']);
    }


    public function resend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        
        if ($validator->fails()) {
            return jsonResponse(0, ['error' => $validator->errors()]);
        }

        // Retrieve the user by email
        $user = User::where('email', $request->email)->first();

        // If user not found or email already verified, return error
        if ($user->hasVerifiedEmail()) {
            return jsonResponse(0, ['error' => 'User Email already verified.']);
        }

        // Generate a new remember token
        $newToken = Str::random(60);

        // Update the remember_token field with the new token
        $user->remember_token = $newToken;
        $user->save();
        // Send the verification email with the new token
        // $user->sendEmailVerificationNotification();

        return jsonResponse(1, ['message' => 'Verification email resent successfully.']);
    }


}
