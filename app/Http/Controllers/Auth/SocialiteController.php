<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google callback and authenticate/create the user.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google authentication failed. Please try again.',
            ]);
        }

        // Check if a user with this google_id already exists
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            // Existing Google user — log them in
            Auth::login($user);
            return redirect()->intended(route('dashboard'))->with('success', 'Logged in with Google!');
        }

        // Check if a user with this email already exists (registered via normal sign-up)
        $existingUser = User::where('email', $googleUser->getEmail())->first();

        if ($existingUser) {
            // Link the Google account to the existing user
            $existingUser->update([
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
            ]);

            Auth::login($existingUser);
            return redirect()->intended(route('dashboard'))->with('success', 'Google account linked! Logged in successfully.');
        }

        // Create a brand-new user from Google data
        $newUser = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'password' => null, // OAuth users don't need a password
            'google_id' => $googleUser->getId(),
            'google_avatar' => $googleUser->getAvatar(),
            'role' => 'user',
        ]);

        Auth::login($newUser);

        return redirect()->route('dashboard')->with('success', 'Account created with Google!');
    }
}
