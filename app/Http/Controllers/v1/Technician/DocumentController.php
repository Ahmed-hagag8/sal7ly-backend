<?php

namespace App\Http\Controllers\v1\Technician;

use App\Http\Controllers\Controller;
use App\Models\TechnicianDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    /**
     * List technician's documents
     */
    public function index(Request $request)
    {
        $technician = $request->user()->technician;

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'Technician profile not found',
            ], 404);
        }

        $documents = $technician->documents()->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $documents->map(fn($doc) => [
                'id' => $doc->id,
                'type' => $doc->type,
                'title' => $doc->title,
                'file_url' => asset('storage/' . $doc->file_path),
                'status' => $doc->status,
                'rejection_reason' => $doc->rejection_reason,
                'uploaded_at' => $doc->created_at,
                'verified_at' => $doc->verified_at,
            ]),
        ]);
    }

    /**
     * Upload new document
     * 
     * Technicians upload ID/certifications for admin review
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => ['required', Rule::in(['national_id', 'certification', 'license', 'other'])],
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        $technician = $request->user()->technician;

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'Technician profile not found',
            ], 404);
        }

        // Store file securely
        $path = $request->file('file')->store(
            "technician_documents/{$technician->id}",
            'public'
        );

        $document = TechnicianDocument::create([
            'technician_id' => $technician->id,
            'type' => $request->type,
            'title' => $request->title,
            'file_path' => $path,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully. Pending admin review.',
            'data' => [
                'id' => $document->id,
                'type' => $document->type,
                'title' => $document->title,
                'status' => $document->status,
            ],
        ], 201);
    }

    /**
     * Delete a document (only if pending)
     */
    public function destroy(Request $request, $id)
    {
        $technician = $request->user()->technician;

        $document = TechnicianDocument::where('technician_id', $technician->id)
            ->where('id', $id)
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        // Only allow deleting pending documents
        if ($document->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a document that has been reviewed',
            ], 403);
        }

        // Delete file
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully',
        ]);
    }
}
