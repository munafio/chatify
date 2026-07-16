<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Events\UserTyping;
use Chatify\Models\Conversation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TypingController extends Controller
{
    use AuthorizesRequests;

    public function store(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'is_typing' => ['required', 'boolean'],
        ]);

        UserTyping::dispatch(
            $conversation->id,
            (int) $request->user()->getKey(),
            (bool) $validated['is_typing'],
        );

        return response()->json(['data' => ['typing' => (bool) $validated['is_typing']]]);
    }
}
