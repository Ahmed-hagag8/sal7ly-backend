<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use App\Models\TechnicianDocument;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    /**
     * List all technicians with filters
     */
    public function index(Request $request)
    {
        $query = Technician::with(['user', 'category', 'city'])
            ->whereHas('user'); // Exclude technicians whose user was soft-deleted

        // Filter by verification status
        if ($request->has('status')) {
            $query->where('verification_status', $request->status);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('service_category_id', $request->category_id);
        }

        $technicians = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => collect($technicians->items())->map(fn($tech) => [
                'id' => $tech->id,
                'user_id' => $tech->user_id,
                'name' => $tech->user?->name,
                'phone' => $tech->user?->phone,
                'email' => $tech->user?->email,
                'category' => $tech->category?->name ?? null,
                'city' => $tech->city?->name ?? null,
                'years_of_experience' => $tech->years_of_experience,
                'verification_status' => $tech->verification_status,
                'documents_count' => $tech->documents()->count(),
                'created_at' => $tech->created_at,
            ]),
            'meta' => [
                'current_page' => $technicians->currentPage(),
                'last_page' => $technicians->lastPage(),
                'total' => $technicians->total(),
            ],
        ]);
    }

    /**
     * Get single technician with documents
     */
    public function show($id)
    {
        $technician = Technician::with(['user', 'category', 'city', 'documents'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $technician->id,
                'user_id' => $technician->user_id,
                'name' => $technician->user?->name,
                'phone' => $technician->user?->phone,
                'email' => $technician->user?->email,
                'category' => $technician->category?->name ?? null,
                'city' => $technician->city?->name ?? null,
                'bio' => $technician->bio,
                'years_of_experience' => $technician->years_of_experience,
                'verification_status' => $technician->verification_status,
                'verified_at' => $technician->verified_at,
                'average_rating' => $technician->average_rating,
                'total_jobs_completed' => $technician->total_jobs_completed,
                'documents' => $technician->documents->map(fn($doc) => [
                    'id' => $doc->id,
                    'type' => $doc->type,
                    'title' => $doc->title,
                    'file_url' => asset('storage/' . $doc->file_path),
                    'status' => $doc->status,
                    'rejection_reason' => $doc->rejection_reason,
                ]),
            ],
        ]);
    }

    /**
     * Approve technician
     */
    public function approve(Request $request, $id)
    {
        $technician = Technician::findOrFail($id);
        $admin = $request->user();

        $technician->update([
            'verification_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'is_available' => true, // Now they can accept jobs
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Technician approved successfully',
        ]);
    }

    /**
     * Reject technician
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $technician = Technician::findOrFail($id);
        $admin = $request->user();

        $technician->update([
            'verification_status' => 'rejected',
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        // TODO: Send notification to technician with rejection reason

        return response()->json([
            'success' => true,
            'message' => 'Technician rejected',
        ]);
    }

    /**
     * Approve a document
     */
    public function approveDocument(Request $request, $documentId)
    {
        $document = TechnicianDocument::findOrFail($documentId);

        $document->update([
            'status' => 'approved',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document approved',
        ]);
    }

    /**
     * Reject a document
     */
    public function rejectDocument(Request $request, $documentId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $document = TechnicianDocument::findOrFail($documentId);

        $document->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document rejected',
        ]);
    }

    /**
     * Process withdrawal request
     */
    public function processWithdrawal(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'required_if:action,reject|string',
        ]);
        $withdrawal = \App\Models\Withdrawal::findOrFail($id);
        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal already processed',
            ], 400);
        }
        if ($request->action === 'approve') {
            $wallet = $withdrawal->user->wallet;

            app(\App\Services\WalletService::class)->debit(
                $wallet,
                (float) $withdrawal->amount,
                "Withdrawal #{$withdrawal->withdrawal_number}"
            );
            $withdrawal->update([
                'status' => 'approved',
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);
        } else {
            $withdrawal->update([
                'status' => 'rejected',
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Withdrawal ' . $request->action . 'd',
        ]);
    }
}
