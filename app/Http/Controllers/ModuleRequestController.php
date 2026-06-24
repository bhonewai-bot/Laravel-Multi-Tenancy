<?php

namespace App\Http\Controllers;

use App\Models\ModuleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Handles central review of tenant module requests.
 */
class ModuleRequestController extends Controller
{
    /**
     * Display module requests with related tenant and module data.
     */
    public function index(): View
    {
        $moduleRequests = ModuleRequest::with(['tenant', 'module'])
            ->latest()
            ->paginate(20);

        return view('module-requests.index', compact('moduleRequests'));
    }

    /**
     * Approve a pending module request.
     *
     * Side effects:
     * - Writes review state to the central module_requests table.
     */
    public function approve(ModuleRequest $moduleRequest): RedirectResponse
    {
        if ($moduleRequest->status !== 'pending') {
            return back()->with('error', 'Request is not pending');
        }

        $moduleRequest->loadMissing(['tenant', 'module']);

        if (! $moduleRequest->tenant || ! $moduleRequest->module) {
            return back()->with('error', 'Request not found');
        }

        $moduleRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        return back()->with('success', 'Module request approved.');
    }

    /**
     * Reject a pending module request.
     *
     * Side effects:
     * - Writes review state to the central module_requests table.
     */
    public function reject(ModuleRequest $moduleRequest): RedirectResponse
    {
        if ($moduleRequest->status !== 'pending') {
            return back()->with('error', 'Request is not pending');
        }

        $moduleRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        return back()->with('success', 'Module request rejected.');
    }
}
