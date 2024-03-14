<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'last_name' => 'required|string|max:255',
            'family_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => 'required|confirmed|min:8',
            'phone_number' => 'required',
            'user_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(0, ['error' => $validator->errors()]);
        }

        $user = User::create([
            'last_name' => $request->last_name,
            'family_name' => $request->family_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'remember_token' => Str::random(60), // Generate verification token
        ]);

        // Send verification email
        event(new Registered($user));

        // Create access token
        $token = $user->createToken('API Token')->accessToken;

        return $this->jsonResponse(1, [
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Login a user and return a token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = $request->user();
            $token = auth()->user()->createToken('API Token')->accessToken;

            return $this->jsonResponse(1,
            [
                'user' => $user,
                'token' => $token
            ]);
        }

        return $this->jsonResponse(0, ['error' => 'Invalid credentials']);
    }


        /**
     * Logout the user and revoke the token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->jsonResponse(1, ['message' => 'User Logout successfull']);
    }

    /**
     * Get the user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        return $this->jsonResponse(1, ['user' => $user]);
    }


    /**
     * Initiate the password reset process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(0, $validator->errors());
        }

        $response = Password::sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? $this->jsonResponse(1, ['message' => 'Password reset link sent to your email'])
            : $this->jsonResponse(0, ['error' => 'Unable to send reset link. Please check your email.']);
    }


    public function sendResetLinkEmail(Request $request)
    {
        // dd($request);
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->jsonResponse(0, ['error' => 'Email not found']);
        }

        $token = Str::random(60);
        // dd($token);
        $user->notify(new ResetPasswordNotification($token));

        return $this->jsonResponse(1, ['message' => 'Password reset link sent to your email']);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        // dd($request);
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(0, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->jsonResponse(0, ['error' => 'Email not found']);
        }

        if (!$user->tokens->isEmpty()) {
            $user->tokens->each(function ($token) {
                $token->delete();
            });
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->jsonResponse(1, ['message' => 'Password reset successful']);
    }

    /**
     * Generate a JSON response.
     *
     * @param  int  $code
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonResponse($code, $data)
    {
        return response()->json([
            'code' => $code,
            'data' => $data,
        ]);
    }
}
