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
use App\Services\OtpService;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{



    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try
        {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $validator = Validator::make($request->all(), [
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
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
            'family_name' => $request->first_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'remember_token' => Str::random(60), // Generate verification token
            'marketing_emails' => $request->marketing_emails,
            'otp' => $otp,
        ]);

        // $email_token = $user->remember_token;
        // Send verification email
        // event(new Registered($user));
        // $user->sendEmailVerificationNotification();

        
        // $user->sendOtpNotification($otp); // Custom method to send OTP

        // Create access token
        $token = $user->createToken('API Token')->accessToken;

        $user = User::where('email', $request->email)->select('id','family_name as first_name','last_name','phone_number','email')->first();

        $this->otpService->generateOtp($request->phone_number);

        return $this->jsonResponse(1, [
            'user' => $user,
            'email-otp' => $otp,
            'token' => $token
        ]);
        } catch(Exception) {
            return jsonResponse(0, ['error' => 'An error occurred']);
        }
    }

    /**
     * Login a user and return a token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try
        {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials))
        {
            $email_verify = User::where('email', $request->email)->first();

            $user = User::where('email', $request->email)->select('id','family_name as first_name','last_name','phone_number','photo', 'birthday', 'place_of_birth','language','adddress','postal_code','city','IBAN_number','id_card','bank_details','email')->first();
            $token = auth()->user()->createToken('API Token')->accessToken;


            if (!$email_verify) {
                return $this->jsonResponse(0, ['error' => 'User not found']);
            }

            if (is_null($email_verify->email_verified_at) && $email_verify->phone_verification == 'false') {
                return $this->jsonResponse(0, ['error' => 'Email and Phone number are not verified',
                    // 'user' => $user,
                    'token' => $token
                ]);
            }

            if (is_null($email_verify->email_verified_at)) {
                return $this->jsonResponse(0, ['error' => 'Email is not verified',
                    // 'user' => $user,
                    'token' => $token
                ]);
            }

            if ($email_verify->phone_verification == 'false') {
                return $this->jsonResponse(0, ['error' => 'Phone number is not verified',
                // 'user' => $user,
                'token' => $token
                ]);
            }



            return $this->jsonResponse(1,
            [
                'user' => $user,
                'token' => $token
            ]);
        }

        return $this->jsonResponse(0, ['error' => 'Invalid credentials']);
    }
    catch(Exception) {
        return jsonResponse(0, ['error' => 'An error occurred']);
    }
    }


        /**
     * Logout the user and revoke the token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try
        {
        $request->user()->token()->revoke();

        return $this->jsonResponse(1, ['message' => 'User Logout successfull']);
        }
        catch(Exception) {
            return jsonResponse(0, ['error' => 'An error occurred']);
        }
    }

    /**
     * Get the user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        try{
        $user = User::where('id', auth()->user()->id)->select('id','family_name as first_name','last_name','phone_number','photo', 'birthday', 'place_of_birth','language','adddress','postal_code','city','IBAN_number','id_card','bank_details','email')->first();
        return $this->jsonResponse(1, ['user' => $user]);
        }
        catch(Exception) {
            return jsonResponse(0, ['error' => 'An error occurred']);
        }
    }


    /**
     * Initiate the password reset process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgetPassword(Request $request)
    {
        try
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
    } catch(Exception) {
        return jsonResponse(0, ['error' => 'An error occurred']);
    }
    }


    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try
        {
        // dd($request);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->jsonResponse(0, ['error' => 'Email not found']);
        }

        $token = Str::random(60);
        // dd($token);
        // $user->notify(new ResetPasswordNotification($token));

        return $this->jsonResponse(1,
        [
            'message' => 'Password reset link sent to your email',
            'token' => $token
        ]);
    }
    catch(Exception) {
        return jsonResponse(0, ['error' => 'An error occurred']);
    }
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        try
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
    catch(Exception) {
        return jsonResponse(0, ['error' => 'An error occurred']);
    }
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
