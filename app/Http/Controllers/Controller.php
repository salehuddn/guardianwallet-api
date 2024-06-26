<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function authenticate($user, $role)
    {
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: User not authenticated.'
            ], 401);
        }

        if (!$user->hasRole($role)) {
            return response()->json([
                'code' => 401,
                'message' => "Unauthorized: You are not a $role."
            ], 401);
        }

        return null;
    }
}
