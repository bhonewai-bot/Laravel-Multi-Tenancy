@php
    $isTenant = (bool) tenant();
@endphp

<x-app-layout>
    <div class="animate-fade-up">

        {{-- Welcome --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                @if ($isTenant)
                    Welcome back, {{ Auth::user()->name }}
                @else
                    Central Dashboard
                @endif
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if ($isTenant)
                    Here's an overview of your workspace
                @else
                    Here's what's happening across your platform
                @endif
            </p>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @if ($isTenant)
                <x-stat-card label="Team Members" value="{{ $teamMembers ?? 0 }}" description="Active users in workspace">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    </x-slot>
                </x-stat-card>

                <x-stat-card label="Installed Modules" value="{{ $installedModules ?? 0 }}" description="Active in your workspace">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" /></svg>
                    </x-slot>
                </x-stat-card>

                <x-stat-card label="Custom Domains" value="{{ $totalDomains ?? 0 }}" description="Connected to your workspace">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                    </x-slot>
                </x-stat-card>

                <x-stat-card label="Pending Requests" value="{{ $pendingRequests ?? 0 }}" description="Awaiting approval">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </x-slot>
                </x-stat-card>

            @else
                <x-stat-card label="Total Tenants" value="{{ $totalTenants }}" description="Active on your platform">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                    </x-slot>
                </x-stat-card>

                <x-stat-card label="Total Modules" value="{{ $totalModules }}" description="Available on platform">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" /></svg>
                    </x-slot>
                </x-stat-card>

                <x-stat-card label="Pending Requests" value="{{ $pendingRequests }}" description="Awaiting approval">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </x-slot>
                </x-stat-card>

                <x-stat-card label="Total Users" value="{{ $totalUsers }}" description="Across all tenants">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    </x-slot>
                </x-stat-card>
            @endif
        </div>

        {{-- Recent Activity --}}
        <x-card>
            <x-slot name="header">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Recent Activity</h3>
            </x-slot>

            @if (($recentRequests ?? collect())->isEmpty())
                <x-empty-state title="No recent activity" description="Activity will appear here as things happen" />
            @else
                <div class="space-y-4">
                    @foreach (($recentRequests ?? collect()) as $request)
                        <div class="flex items-start gap-3">
                            <div class="mt-1 w-2 h-2 rounded-full shrink-0
                                {{ match($request->status) {
                                    'approved' => 'bg-green-500',
                                    'rejected' => 'bg-red-500',
                                    'pending' => 'bg-amber-500',
                                    default => 'bg-gray-400',
                                } }}"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    <span class="font-medium">{{ $request->module->name ?? 'Module' }}</span>
                                    @if (!$isTenant && $request->tenant)
                                        <span class="text-gray-500 dark:text-gray-400"> for {{ $request->tenant->name }}</span>
                                    @endif
                                    <span class="text-gray-500 dark:text-gray-400">
                                        — {{ match($request->status) {
                                            'pending' => 'request pending',
                                            'approved' => 'approved',
                                            'rejected' => 'rejected',
                                            default => $request->status,
                                        } }}
                                    </span>
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $request->created_at->diffForHumans() }}</p>
                            </div>
                            <x-badge :variant="match($request->status) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'pending' => 'warning',
                                default => 'neutral',
                            }" :label="$request->status" />
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
