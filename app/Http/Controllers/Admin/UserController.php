<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Cache;
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
        $users = Cache::remember('all_users', 3600, function() {
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
        //
    }

    
    public function update(Request $request, User $user)
    {
        //
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
        Cache::forget('all_users');
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
        Cache::forget('all_users');
        return redirect()->route('admin.users.index')->with('success', $user->name . ' has been demoted to user!');
    }
}
