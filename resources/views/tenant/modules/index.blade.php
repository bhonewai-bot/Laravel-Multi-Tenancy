@extends('layouts.dark')

@section('content')
    <section class="card">
        <h1>Available Modules</h1>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($modules as $module)
                @php
                    $isInstalled = in_array($module->slug, $installedModules ?? [], true);
                    $requestStatus = $requestModules[$module->id] ?? null;
                @endphp
                <tr>
                    <td>{{ $module->name }}</td>
                    <td>{{ $module->version }}</td>
                    <td>
                        @if($isInstalled)
                            <span class="badge green">Installed</span>
                        @elseif($requestStatus === 'pending')
                            <span class="badge yellow">Pending</span>
                        @elseif($requestStatus === 'approved')
                            <span class="badge green">Approved</span>
                        @elseif($requestStatus === 'rejected')
                            <span class="badge red">Rejected</span>
                        @else
                            <span class="badge">Not requested</span>
                        @endif
                    </td>
                    <td>
                        @if($isInstalled)
                            <div class="row-actions">
                                <span class="badge green">Installed</span>
                                <form method="POST" action="{{ route('tenant.modules.uninstall') }}">
                                    @csrf
                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                    <button type="submit" class="danger">Uninstall</button>
                                </form>
                            </div>
                        @elseif($requestStatus === 'approved')
                            <form method="POST" action="{{ route('tenant.modules.install') }}">
                                @csrf
                                <input type="hidden" name="module_id" value="{{ $module->id }}">
                                <button type="submit">Install</button>
                            </form>
                        @elseif($requestStatus === 'pending')
                            <span>Waiting for central approval</span>
                        @elseif($requestStatus === 'rejected')
                            <div class="row-actions">
                                <span class="badge red">Rejected</span>
                                <form method="POST" action="{{ route('tenant.modules.request') }}">
                                    @csrf
                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                    <button type="submit">Request Again</button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('tenant.modules.request') }}">
                                @csrf
                                <input type="hidden" name="module_id" value="{{ $module->id }}">
                                <button type="submit">Request Module</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No modules available.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
