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
            ->withCount('documents')
            ->whereHas('user'); // Exclude technicians whose user was soft-deleted

        // Filter by verification status
        if ($request->has('status')) {
            $query->where('verification_status', $request->input('status'));
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('service_category_id', $request->input('category_id'));
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
                'documents_count' => $tech->documents_count,
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
                    'file_url' => url('/api/admin/documents/' . $doc->id . '/download'),
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
            'rejection_reason' => $request->input('reason'),
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
     *
     * SCALE-03: Wrapped in DB::transaction so wallet debit + status update are atomic.
     * SCALE-04: Uses settleHeldFunds/releaseFunds since funds were already held
     *           in pending_balance when the technician requested the withdrawal.
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

        $walletService = app(\App\Services\WalletService::class);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $withdrawal, $walletService) {
            $wallet = $withdrawal->user->wallet;

            if ($request->input('action') === 'approve') {
                // Funds were already held in pending_balance — settle them now
                $walletService->settleHeldFunds(
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
                // Release held funds back to available balance
                $walletService->releaseFunds($wallet, (float) $withdrawal->amount);
                $withdrawal->update([
                    'status' => 'rejected',
                    'processed_by' => $request->user()->id,
                    'processed_at' => now(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal ' . $request->input('action') . 'd',
        ]);
    }

    /**
     * Download document securely (Admin only)
     */
    public function downloadDocument($documentId)
    {
        \Log::info("downloadDocument called for document ID: {$documentId}");
        
        $document = TechnicianDocument::findOrFail($documentId);
        
        \Log::info("Document file_path: {$document->file_path}");
        // New secure path
        $path = storage_path('app/' . $document->file_path);

        // Fallback for older documents that were saved in the public disk
        $oldPath = storage_path('app/public/' . $document->file_path);

        $filePath = null;
        if (file_exists($path)) {
            $filePath = $path;
        } elseif (file_exists($oldPath)) {
            $filePath = $oldPath;
        }

        if (!$filePath) {
            \Log::error("Document file not found. Checked: {$path} and {$oldPath}");
            return response()->json([
                'success' => false,
                'message' => 'Document file not found on server.',
            ], 404);
        }

        \Log::info("Serving file from: {$filePath}");

        $mimeType = mime_content_type($filePath);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
