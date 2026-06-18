<?php

namespace App\Http\Helpers;

use App\Models\User;

class AuthHelper
{
    public static function user(): ?User
    {
        $id = session('user_id');
        if (!$id) return null;
        return User::find($id);
    }

    public static function role(): ?string
    {
        return self::user()?->role;
    }

    public static function can(array $roles): bool
    {
        return in_array(self::role(), $roles);
    }
}
