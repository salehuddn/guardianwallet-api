<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DependantController extends Controller
{
    public function dependantProfile(Request $request)
    {
        // $user = $request->user()->load('dependents');

        // return response()->json([
        //     'code' => 200,
        //     'message' => 'success',
        //     'data' => $user
        // ], 200);

        $dependant = $request->user();

        if ($dependant->hasRole('dependant')) {
            return response()->json([
                'code' => 200,
                'user' => $dependant,
            ], 200);
        } else {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a dependant.'
            ], 401);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->only(['name', 'password']);
        $data = array_filter($data);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'code' => 200,
            'message' => 'Profile updated successfully'
        ], 200);
    }

    public function wallet(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'wallet' => $user->wallet
        ], 200);
    }

    public function transactionHistory (Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('dependant')) {
            $transactions = $user->transactions()->with('transactionType')->get();

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'transactions' => $transactions
            ], 200);
        } else {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a dependant.'
            ], 401);
        }
    }
}
