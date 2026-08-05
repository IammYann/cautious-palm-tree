<?php

namespace App\Http\Controllers\Admin;

use App\Services\SafeCache;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /*
     * Display a listing of all users
     */
    public function index()
    {
        $users = SafeCache::remember(['users'], 'all_users', 3600, function() {
            return User::all();
        });
        
        return view('admin.users.index', compact('users'));
    }

    
    public function create()
    {
        //
    }

    
    public function store(Request $request)
    {
        //
    }

    
    public function show(User $user)
    {
        //
    }

    
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|string|in:user,admin,delivery',
        ]);

        // Prevent demoting yourself
        if ($user->id === auth()->id() && $request->role !== 'admin') {
            return back()->with('error', 'You cannot change your own role.');
        }

        $user->update($request->only('name', 'email', 'role'));

        SafeCache::flushTags(['users'], ['all_users']);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully!');
    }

    
    public function destroy(User $user)
    {
        //
    }

    /*
     * Promote a user to admin
     */
    public function promote(User $user)
    {
        $user->update(['role' => 'admin']);
        SafeCache::flushTags(['users'], ['all_users']);
        return redirect()->route('admin.users.index')->with('success', $user->name . ' has been promoted to admin!');
    }

    /*
     * Demote an admin to user
     */
    public function demote(User $user)
    {
        // Prevent demoting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot demote yourself!');
        }

        $user->update(['role' => 'user']);
        SafeCache::flushTags(['users'], ['all_users']);
        return redirect()->route('admin.users.index')->with('success', $user->name . ' has been demoted to user!');
    }
}
