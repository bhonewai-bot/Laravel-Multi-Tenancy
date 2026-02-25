@extends('layouts.dark')

@section('content')
    <section class="card">
        <h1>Module Management</h1>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        <div class="actions">
            <a class="btn" href="{{ route('modules.create') }}">+ Upload/Create Module</a>
            <a class="btn secondary" href="{{ route('module-requests.index') }}">View Module Requests</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Version</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($modules as $module)
                <tr>
                    <td>{{ $module->name }}</td>
                    <td>{{ $module->slug }}</td>
                    <td>{{ $module->version }}</td>
                    <td>${{ number_format((float) $module->price, 2) }}</td>
                    <td>
                        @if($module->is_active)
                            <span class="badge green">Active</span>
                        @else
                            <span class="badge red">Disabled</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('modules.toggle', $module) }}">
                            @csrf
                            <button type="submit" class="{{ $module->is_active ? 'danger' : '' }}">
                                {{ $module->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No modules found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="pagination">
            <span>
                Showing {{ $modules->firstItem() ?? 0 }}-{{ $modules->lastItem() ?? 0 }} of {{ $modules->total() }}
            </span>
            <div class="pagination-links">
                @if($modules->onFirstPage())
                    <span class="btn secondary">Previous</span>
                @else
                    <a class="btn secondary" href="{{ $modules->previousPageUrl() }}">Previous</a>
                @endif

                @if($modules->hasMorePages())
                    <a class="btn secondary" href="{{ $modules->nextPageUrl() }}">Next</a>
                @else
                    <span class="btn secondary">Next</span>
                @endif
            </div>
        </div>
    </section>
@endsection
