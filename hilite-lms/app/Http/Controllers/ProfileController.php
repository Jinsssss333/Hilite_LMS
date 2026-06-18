<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\AuthHelper;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
        ]);

        $userModel = User::find($user->id);
        if ($userModel) {
            $userModel->name = $request->name;
            $userModel->email = $request->email;
            
            if ($request->filled('password')) {
                $userModel->password = Hash::make($request->password);
            }
            
            $userModel->save();
        }

        return back()->with('success', 'Profile updated successfully!');
    }
}
