<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->save();

        return response()->json([
            'code' => 201,
            'message' => 'User registered successfully'
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('GuardianWallet')->plainTextToken;

            return response()->json(['token' => $token], 200);
        }

        return response()->json([
            'code' => 401,
            'message' => 'Unauthorized'
        ], 401);
    }

    public function deleteUser(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'code' => 200,
            'message' => 'User deleted successfully'
        ], 200);
    }

    public function generateApiKey()
    {
        $apiKey = Str::random(60);

        return response()->json([
            'code' => 200,
            'api_key' => $apiKey
        ], 200);
    }
}
