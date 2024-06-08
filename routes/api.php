<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PrivateCarOwnerController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\OtpController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::middleware('auth:api')->group(function () {
    Route::post('/step-1',[PrivateCarOwnerController::class,'step1']);
    Route::post('/step-2',[PrivateCarOwnerController::class,'step2']);
    Route::post('/step-3',[PrivateCarOwnerController::class,'step3']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/verify-email', [VerificationController::class, 'verify'])->name('verification.verify');
    Route::post('/resend-verification-email', [VerificationController::class, 'resend'])->name('verification.resend');

    Route::post('/verify-otp', [OtpController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/resend-otp', [OtpController::class, 'resendOtp'])->name('resend-otp');
    Route::post('/change-number', [OtpController::class, 'changeNumber'])->name('change-number');
    Route::post('/verify-update-number', [OtpController::class, 'verifyNewNumber'])->name('verifyNewNumber');
});
Route::post('/send-otp', [OtpController::class, 'sendOtp'])->name('send-otp');


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
