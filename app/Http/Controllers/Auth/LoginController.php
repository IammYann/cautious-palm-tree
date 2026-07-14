<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function show()
    {
        return view('auth.login');
    }

    /**
     * Authenticate the user
     */
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Attempt to authenticate
        if (Auth::attempt($validated, $request->boolean('remember'))) { // Auth::attempt() checks whether the email and password match a user in the database. The second argument, $request->boolean('remember'), is used to determine whether the user should be "remembered" after the session expires. If true, Laravel will store an encrypted cookie in the user's browser so that they remain logged in even after closing the browser. If false, the user will be logged out when the session expires.
            $request->session()->regenerate(); // It protects against session fixation attacks, where someone might try to reuse an existing session ID to impersonate the user.
            return redirect()->intended(route('dashboard'))->with('success', 'Logged in successfully!');// If the user was trying to access a protected page (for example, /profile) before being asked to log in, Laravel sends them back to that page after login. Otherwise, it redirects them to the dashboard route.
        }

        // Authentication failed
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}
