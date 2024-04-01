<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function userProfile (Request $request) 
    {
        $user = $request->user();

        // $user->assignRole('guardian');

        if ($user) {
            return response()->json([
                'code' => 200,
                'user' => $user
            ], 200);
        } else {
            return response()->json([
                'code' => 404,
                'message' => 'User not found'
            ], 404);
        }
    }
}
