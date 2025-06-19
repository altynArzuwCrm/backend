<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'phone' => 'required|digits:8|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,manager,executor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully',
            'user' => [
                'name' => $user->name,
                'surname' => $user->surname,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ], 200);
    }

    public function login(Request $request)
   {
       $validator = Validator::make($request->all(), [
           'phone' => 'required|digits:8',
           'password' => 'required|string|min:6',
       ]);

       if($validator->fails()) {
           return response()->json([
               'status' => false,
               'message' => 'Validation error',
               'errors' => $validator->errors(),
           ], 422);
       }

       $credentials = $request->only('phone', 'password');

       if (!Auth::attempt($credentials)) {
           return response()->json([
               'status' => false,
               'message' => 'Invalid credentials'
           ], 401);
       }

       $user = Auth::user();

       $token = $user->createToken('API Token')->plainTextToken;

       return response()->json([
          'status' => true,
          'message' => 'User logged in successfully',
          'id' => $user->id,
          'access_token' => $token,
          'token_type' => 'Bearer',
       ]);
   }
}
