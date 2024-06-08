<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PrivateCarOwnerController extends Controller
{
    public function step1(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|in:'.$user->id,
            'profile_image' => 'required',
            'date_of_birth' => 'required',
            'place_of_birth' => 'required',
            'language' => 'required',
            'address' => 'required',
            'postal_code' => 'required',
            'city' => 'required',
        ]);

        if ($validator->fails()) {
            return jsonResponse(0, ['error' => $validator->errors()]);
        }

        $car_owner = User::find($request->user_id);
        $car_owner->photo = $request->profile_image;
        $car_owner->birthday = $request->date_of_birth;
        $car_owner->place_of_birth = $request->place_of_birth;
        $car_owner->language = $request->language;
        $car_owner->adddress = $request->address;
        $car_owner->postal_code = $request->postal_code;
        $car_owner->city = $request->city;

        $car_owner->save();

        return jsonResponse(1, ['message' => 'Profile data saved Successfully']);
    }

    public function step2(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            // 'user_id' => 'required|in:'.$user->id,
            'account_number' => 'required',
            'iban_number' => 'required',
            'bic_code' => 'required',
        ]);

        if ($validator->fails()) {
            return jsonResponse(0, ['error' => $validator->errors()]);
        }

        $car_owner = User::find($user->id);
        $car_owner->account_number = $request->account_number;
        $car_owner->IBAN_number = $request->iban_number;
        $car_owner->BIC_Code = $request->bic_code;

        $car_owner->save();

        return jsonResponse(1, ['message' => 'Profile data saved Successfully']);
    }

    public function step3(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            // 'user_id' => 'required|in:'.$user->id,
            'id_front' => 'required',
            'id_back' => 'required',
            'bank_id_statement' => 'required',
        ]);

        if ($validator->fails()) {
            return jsonResponse(0, ['error' => $validator->errors()]);
        }

        $car_owner = User::find($user->id);
        $car_owner->id_front = $request->id_front;
        $car_owner->id_back = $request->id_back;
        $car_owner->bank_details = $request->bank_id_statement;

        $car_owner->save();

        return jsonResponse(1, ['message' => 'Profile data saved Successfully']);
    }
}
