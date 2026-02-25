@extends('layouts.dark')

@section('content')
    <section class="card">
        <h1>Create Module</h1>

        @if($errors->any())
            <div class="alert error">
                <strong>Validation errors:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="actions">
            <a class="btn secondary" href="{{ route('modules.index') }}">Back to Modules</a>
        </div>

        <form method="POST" action="{{ route('modules.store') }}">
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" required>
                </div>

                <div class="form-group">
                    <label>Version</label>
                    <input type="text" name="version" value="{{ old('version', '1.0.0') }}" required>
                </div>

                <div class="form-group">
                    <label>Icon Path</label>
                    <input type="text" name="icon_path" value="{{ old('icon_path') }}">
                </div>

                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Price</label>
                    <input type="number" step="0.01" min="0" name="price" value="{{ old('price', '0') }}">
                </div>

                <div class="form-group">
                    <label>Active</label>
                    <select name="is_active" required>
                        <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>

            <div class="actions" style="margin-top: 16px;">
                <button type="submit">Save Module</button>
            </div>
        </form>
    </section>
@endsection
