<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileCompletionController extends Controller
{
    /**
     * Show the form for completing the profile.
     */
    public function edit()
    {
        $user = Auth::user();

        // 如果已經完成，直接轉向首頁
        if ($user->is_profile_completed) {
            return redirect()->to(config('filament.home_url', '/admin'));
        }

        return view('auth.profile-completion', ['user' => $user]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->forceFill([
            'name' => $validated['name'],
            'is_profile_completed' => true,
        ])->save();

        return redirect()->to(config('filament.home_url', '/admin'));
    }
}
