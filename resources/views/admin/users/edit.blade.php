@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div style="max-width: 600px; margin: 30px auto;">
    <div class="panel">
        <h1 style="font-size: 20px; font-weight: 500; margin-bottom: 20px; color: #222; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">Edit User</h1>

        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" style="box-shadow: none; padding: 0; background: none;">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Name *</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ $user->name }}"
                    required
                    class="form-control"
                    placeholder="Enter user name"
                >
                @error('name')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ $user->email }}"
                    required
                    class="form-control"
                    placeholder="Enter user email"
                >
                @error('email')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" class="form-control">
                    <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="delivery" {{ $user->role === 'delivery' ? 'selected' : '' }}>Delivery</option>
                </select>
                @error('role')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary">Update User</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline" style="border: 1px solid var(--border-color); color: var(--grey-color);">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
