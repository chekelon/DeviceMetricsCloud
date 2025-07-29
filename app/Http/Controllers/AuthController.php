<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    

    public function register(Request $request){
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'region_id' => 'required|string|exists:regions,name',// Assuming region_id is a string representing the region name
            'password' => 'required|string|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $region = \App\Models\Region::where('name', $request->region_id)->first();


        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'region_id' => $region->id,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 201);

        
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!Auth::guard('web')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::where('email', $request->email)->with('roles')->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Hi, ' . $user->name . ', you are logged in successfully.',
            'access_token' => $token,
            'sensor_id' => $user->sensor ? $user->sensor->id : null,
            'interval_reading'=> $user->sensor ? $user->sensor->interval_reading : null,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
