<?php
namespace App\Helpers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class UnauthorizedHelper
{
    public static function throwUnauthorizedException()
    {
        $user = Auth::guard('users')->user();

        if ($user->perms !== 0) {
            throw new AuthorizationException();
        }
    }
}