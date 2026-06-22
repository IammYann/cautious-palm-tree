<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of all users
     */
    public function index()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user (not used in this case)
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created user (not used in this case)
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display a specific user (not used)
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing a user (not used)
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update a user (not used)
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Delete a user (not used)
     */
    public function destroy(User $user)
    {
        //
    }

    /**
     * Promote a user to admin
     */
    public function promote(User $user)
    {
        $user->update(['role' => 'admin']);
        return redirect()->route('admin.users.index')->with('success', $user->name . ' has been promoted to admin!');
    }

    /**
     * Demote an admin to user
     */
    public function demote(User $user)
    {
        // Prevent demoting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot demote yourself!');
        }

        $user->update(['role' => 'user']);
        return redirect()->route('admin.users.index')->with('success', $user->name . ' has been demoted to user!');
    }
}
