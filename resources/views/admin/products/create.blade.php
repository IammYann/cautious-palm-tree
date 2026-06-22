@extends('layouts.app')

@section('title', 'Add New Product')

@section('content')
<div style="max-width: 600px; margin: 30px auto;">
    <div class="panel">
        <h1 style="font-size: 20px; font-weight: 500; margin-bottom: 20px; color: #222; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">Add New Product</h1>

        <form action="{{ route('admin.products.store') }}" method="POST" style="box-shadow: none; padding: 0; background: none;">
            @csrf

            <div class="form-group">
                <label for="name">Product Name *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}"
                    required
                    class="form-control"
                    placeholder="Enter product name"
                >
                @error('name')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea 
                    id="description" 
                    name="description" 
                    required
                    class="form-control"
                    placeholder="Describe your product"
                >{{ old('description') }}</textarea>
                @error('description')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="price">Price (Rs.) *</label>
                <input 
                    type="number" 
                    id="price" 
                    name="price" 
                    value="{{ old('price') }}"
                    step="0.01"
                    min="0"
                    required
                    class="form-control"
                    placeholder="0.00"
                >
                @error('price')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary">Create Product</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline" style="border: 1px solid var(--border-color); color: var(--grey-color);">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
