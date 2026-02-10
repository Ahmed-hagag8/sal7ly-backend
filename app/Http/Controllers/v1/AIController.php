<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
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
     */
    public function predictPrice(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'description' => 'required|string',
        ]);

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/predict-price", [
                'service_id' => $request->service_id,
                'description' => $request->description,
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
     */
    public function detectImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        try {
            $response = Http::timeout(30)
                ->attach('image', file_get_contents($request->file('image')), 'image.jpg')
                ->post("{$this->baseUrl}/detect-image");

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
     * AI Chatbot
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/chatbot", [
                'message' => $request->message,
                'user_id' => $request->user()->id,
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
}
