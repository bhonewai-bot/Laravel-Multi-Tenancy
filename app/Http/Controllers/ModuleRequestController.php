<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\ModuleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Handles central review of tenant module requests.
 */
class ModuleRequestController extends Controller
{
    /**
     * Display module requests with related tenant and module data.
     *
     * @return View
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
     *
     * @param  ModuleRequest  $moduleRequest
     * @return RedirectResponse
     */
    public function approve(ModuleRequest $moduleRequest): RedirectResponse
    {
        if ($moduleRequest->status !== 'pending') {
            return back()->with('error', 'Request is not pending');
        }

        $moduleRequest->loadMissing(['tenant', 'module']);

        if (!$moduleRequest->tenant || !$moduleRequest->module) {
            return back()->with('error', 'Request not found');
        }

        $moduleRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'review_note' => null
        ]);

        return back()->with('success', 'Module request approved.');
    }

    /**
     * Reject a pending module request and optionally store a review note.
     *
     * Side effects:
     * - Writes review state to the central module_requests table.
     *
     * @param  Request  $request
     * @param  ModuleRequest  $moduleRequest
     * @return RedirectResponse
     */
    public function reject(Request $request,ModuleRequest $moduleRequest) 
    {
        if ($moduleRequest->status !== 'pending') {
            return back()->with('error', 'Request is not pending');
        }

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000']
        ]);

        $moduleRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);

        return back()->with('success', 'Module request rejected.');
    }
}
