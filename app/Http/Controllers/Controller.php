<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // protected function authenticate($user, $role)
    // {
    //     if (!$user) {
    //         return response()->json([
    //             'code' => 401,
    //             'message' => 'Unauthorized: User not authenticated.'
    //         ], 401);
    //     }

    //     if (!$user->hasRole($role)) {
    //         return response()->json([
    //             'code' => 401,
    //             'message' => "Unauthorized: You are not a $role."
    //         ], 401);
    //     }

    //     return null;
    // }

    protected function authenticate($user, $roles)
    {
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: User not authenticated.'
            ], 401);
        }

        // check if user has at least one of the roles
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return null; // user has a valid role
            }
        }

        return response()->json([
            'code' => 401,
            'message' => 'Unauthorized: You do not have the necessary role.'
        ], 401);
    }
}
