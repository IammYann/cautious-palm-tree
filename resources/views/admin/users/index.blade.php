@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<style>
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        background: var(--white);
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .admin-header h1 {
        font-size: 20px;
        font-weight: 500;
        color: #222;
    }

    .admin-table-container {
        background: var(--white);
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 14px;
    }

    .admin-table th {
        background-color: #fafafa;
        color: var(--grey-color);
        font-weight: 500;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-color);
        text-transform: uppercase;
        font-size: 12px;
    }

    .admin-table td {
        padding: 14px 18px;
        border-bottom: 1px solid #f2f2f2;
        color: #424242;
        vertical-align: middle;
    }

    .admin-table tr:last-child td {
        border-bottom: none;
    }

    .badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-admin {
        background-color: #ffebe0;
        color: var(--primary-color);
    }

    .badge-user {
        background-color: #e0f6fc;
        color: var(--secondary-color);
    }

    .actions-cell {
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
    }
</style>

<div class="admin-header">
    <h1>Manage Users</h1>
</div>

@if ($users->count() > 0)
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 30%;">User Name</th>
                    <th style="width: 40%;">Email</th>
                    <th style="width: 15%; text-align: center;">Role</th>
                    <th style="width: 15%; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td style="font-weight: 500;">{{ $user->name }}</td>
                        <td style="color: var(--grey-color);">{{ $user->email }}</td>
                        <td style="text-align: center;">
                            @if($user->role === 'admin')
                                <span class="badge badge-admin">Admin</span>
                            @else
                                <span class="badge badge-user">User</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions-cell">
                                @if($user->role === 'user')
                                    <!-- Promote button -->
                                    <form action="{{ route('admin.users.promote', $user->id) }}" method="POST" style="margin: 0; padding: 0; background: none; box-shadow: none;">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size: 12px;">
                                            Promote
                                        </button>
                                    </form>
                                @elseif($user->role === 'admin' && $user->id !== auth()->id())
                                    <!-- Demote button (can't demote yourself) -->
                                    <form action="{{ route('admin.users.demote', $user->id) }}" method="POST" style="margin: 0; padding: 0; background: none; box-shadow: none;">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                                            Demote
                                        </button>
                                    </form>
                                @elseif($user->id === auth()->id())
                                    <span style="color: var(--light-grey); font-size: 12px;">(You)</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="panel" style="text-align: center; padding: 40px 20px;">
        <p style="font-size: 16px; color: var(--grey-color);">No users found.</p>
    </div>
@endif
@endsection
