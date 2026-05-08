<?php

namespace App\Http\Controllers\v1\Shared;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * List my conversations
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        /** @var \Illuminate\Pagination\LengthAwarePaginator $conversations */
        $conversations = Conversation::where('participant_1_id', $userId)
            ->orWhere('participant_2_id', $userId)
            ->with(['participant1', 'participant2', 'job'])
            ->latest('last_message_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $conversations->getCollection()->map(fn($c) => [
                'id' => $c->id,
                'job_number' => $c->job?->job_number,
                'other_user' => $c->participant_1_id == $userId
                    ? $c->participant2->name
                    : $c->participant1->name,
                'last_message_at' => $c->last_message_at,
            ]),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    /**
     * Get messages in conversation
     */
    public function messages(Request $request, $conversationId)
    {
        $userId = $request->user()->id;

        $conversation = Conversation::where(function ($q) use ($userId) {
            $q->where('participant_1_id', $userId)
                ->orWhere('participant_2_id', $userId);
        })->findOrFail($conversationId);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest()
            ->paginate(20);

        // Mark as read
        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Send message
     */
    public function send(Request $request, $conversationId)
    {
        $request->validate(['body' => 'required|string|max:1000']);

        $userId = $request->user()->id;

        $conversation = Conversation::where(function ($q) use ($userId) {
            $q->where('participant_1_id', $userId)
                ->orWhere('participant_2_id', $userId);
        })->findOrFail($conversationId);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'body' => $request->body,
            'is_read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => ['id' => $message->id, 'body' => $message->body],
        ], 201);
    }

    /**
     * Start conversation (on job creation)
     */
    public static function createForJob($job)
    {
        return Conversation::create([
            'job_id' => $job->id,
            'participant_1_id' => $job->customer->user_id,
            'participant_2_id' => $job->technician->user_id,
            'last_message_at' => now(),
        ]);
    }
}
