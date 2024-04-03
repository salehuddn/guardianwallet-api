<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterDependantRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GuardianController extends Controller
{
    public function guardianProfile(Request $request)
    {
        // $user = $request->user()->load('dependents');

        // return response()->json([
        //     'code' => 200,
        //     'message' => 'success',
        //     'data' => $user
        // ], 200);

        $guardian = $request->user();

        if ($guardian->hasRole('guardian')) {
            if ($guardian->dependants->isNotEmpty()) {
                return response()->json([
                    'code' => 200,
                    'user' => $guardian,
                ], 200);
            } else {
                return response()->json([
                    'code' => 200,
                    'user' => $guardian,
                ], 200);
            }
        } else {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a guardian.'
            ], 401);
        }
    }

    public function guardianDependents(Request $request)
    {
        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $request->user()->dependents
        ], 200);
    }

    public function registerDependant(RegisterDependantRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'dob' => $request->date_of_birth
        ]);

        $user->assignRole('dependant');
        $user->save();

        $guardian = $request->user();
        $guardian->dependants()->attach($user->id);

        return response()->json([
            'code' => 201,
            'message' => 'User registered successfully'
        ], 201);

    }
}
