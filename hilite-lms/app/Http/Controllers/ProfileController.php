<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\AuthHelper;
use App\Services\PhoneNormalizationService;
use App\Models\User;

class ProfileController extends Controller
{
    public function __construct(protected PhoneNormalizationService $phoneService) {}

    public function update(Request $request)
    {
        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
        ]);

        // Normalize phone via libphonenumber if provided
        $normalizedPhone = null;
        if ($request->filled('phone')) {
            $normalizedPhone = $this->phoneService->normalize($request->phone);
            if ($normalizedPhone === null) {
                return back()
                    ->withErrors(['phone' => 'Invalid phone number. Please enter a valid number with country code (e.g. +919876543210 or 9876543210).'])
                    ->withInput();
            }
        }

        $userModel = User::find($user->id);
        if ($userModel) {
            $userModel->name  = $request->name;
            $userModel->email = $request->email;
            $userModel->phone = $normalizedPhone; // null if field was left blank
            $userModel->save();

            // Refresh session name in case it changed
            session(['user_name' => $userModel->name]);
        }

        return back()->with('success', 'Profile updated successfully!');
    }
}
