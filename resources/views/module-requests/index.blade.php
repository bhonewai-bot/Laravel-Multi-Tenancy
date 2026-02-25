@extends('layouts.dark')

@section('content')
    <section class="card">
        <h1>Module Requests</h1>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        <div class="actions">
            <a class="btn secondary" href="{{ route('modules.index') }}">Back to Modules</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Module</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Reviewed</th>
                    <th>Review Note</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($moduleRequests as $request)
                <tr>
                    <td>{{ $request->tenant_id }}</td>
                    <td>{{ $request->module->name ?? '-' }}</td>
                    <td>
                        @if($request->status === 'approved')
                            <span class="badge green">Approved</span>
                        @elseif($request->status === 'rejected')
                            <span class="badge red">Rejected</span>
                        @else
                            <span class="badge yellow">Pending</span>
                        @endif
                    </td>
                    <td>{{ optional($request->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ optional($request->reviewed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                    <td>{{ $request->review_note ?: '-' }}</td>
                    <td>
                        @if($request->status === 'pending')
                            <div class="row-actions">
                                <form method="POST" action="{{ route('module-requests.approve', $request) }}">
                                    @csrf
                                    <button type="submit">Approve</button>
                                </form>

                                <form method="POST" action="{{ route('module-requests.reject', $request) }}">
                                    @csrf
                                    <input type="text" name="review_note" placeholder="Reason (optional)">
                                    <button type="submit" class="danger">Reject</button>
                                </form>
                            </div>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No requests found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="pagination">
            <span>
                Showing {{ $moduleRequests->firstItem() ?? 0 }}-{{ $moduleRequests->lastItem() ?? 0 }} of {{ $moduleRequests->total() }}
            </span>
            <div class="pagination-links">
                @if($moduleRequests->onFirstPage())
                    <span class="btn secondary">Previous</span>
                @else
                    <a class="btn secondary" href="{{ $moduleRequests->previousPageUrl() }}">Previous</a>
                @endif

                @if($moduleRequests->hasMorePages())
                    <a class="btn secondary" href="{{ $moduleRequests->nextPageUrl() }}">Next</a>
                @else
                    <span class="btn secondary">Next</span>
                @endif
            </div>
        </div>
    </section>
@endsection
