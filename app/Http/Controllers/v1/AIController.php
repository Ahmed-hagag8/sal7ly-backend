<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ai.url');
    }

    /*
     * Predict price for a service
     *
     * The AI model expects an Arabic category name (e.g. سباكة، كهرباء) rather than
     * a numeric service_id. This method resolves the service_id to its parent
     * category's Arabic name before forwarding to the AI microservice.
     */
    public function predictPrice(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'description' => 'required|string',
        ]);

        try {
            // Resolve service_id → category Arabic name for the AI model
            $service = Service::with('category')->findOrFail($request->service_id);
            $categoryNameAr = $service->category->name_ar ?? $service->category->name;

            $response = Http::timeout(30)->post("{$this->baseUrl}/predict-price", [
                'service_id'       => $request->service_id,
                'category_name_ar' => $categoryNameAr,
                'category_name'    => $service->category->name,
                'service_name'     => $service->name,
                'description'      => $request->description,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'AI service error',
            ], 502);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable',
            ], 503);
        }
    }

    /*
     * Detect/validate service image
     *
     * The AI model's full pipeline requires both an image AND an Arabic description.
     * When no description is provided (the default), the AI service should run only
     * Stage 1A (image relevance check). When a description IS provided, the full
     * matching pipeline can run.
     */
    public function detectImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
            'description' => 'nullable|string', // Optional: enables full AI pipeline
        ]);

        try {
            $httpRequest = Http::timeout(30)
                ->attach('image', file_get_contents($request->file('image')), 'image.jpg');

            // If a description is provided, send it as a form field alongside the image
            // This enables the AI service to run the full matching pipeline
            if ($request->filled('description')) {
                $httpRequest = Http::timeout(30)
                    ->attach('image', file_get_contents($request->file('image')), 'image.jpg')
                    ->attach('description', $request->description);
            }

            $response = $httpRequest->post("{$this->baseUrl}/detect-image");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'AI service error',
                'debug' => [
                    'ai_status' => $response->status(),
                    'ai_body' => $response->body(),
                    'ai_url' => $this->baseUrl . '/detect-image',
                ],
            ], 502);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable',
                'debug' => [
                    'exception' => $e->getMessage(),
                    'ai_url' => $this->baseUrl . '/detect-image',
                ],
            ], 503);
        }
    }

    /*
     * AI Chatbot
     *
     * The AI chatbot uses session_id for conversation tracking, while the Laravel
     * backend uses user_id. We send both fields so the AI service can use either one.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $userId = $request->user()->id;

            $response = Http::timeout(30)->post("{$this->baseUrl}/chatbot", [
                'message'    => $request->message,
                'user_id'    => $userId,
                'session_id' => (string) $userId, // Map user_id → session_id for AI compatibility
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                ]);
            }

            // DEBUG: Show actual AI service error (remove after debugging)
            return response()->json([
                'success' => false,
                'message' => 'AI service error',
                'debug' => [
                    'ai_status' => $response->status(),
                    'ai_body' => $response->body(),
                    'ai_url' => $this->baseUrl . '/chatbot',
                ],
            ], 502);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable',
                'debug' => [
                    'exception' => $e->getMessage(),
                    'ai_url' => $this->baseUrl . '/chatbot',
                ],
            ], 503);
        }
    }
}
