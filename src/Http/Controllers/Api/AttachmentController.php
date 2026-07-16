<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Http\Resources\AttachmentResource;
use Chatify\Models\Conversation;
use Chatify\Services\AttachmentService;
use Chatify\Services\ConversationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    public function index(Conversation $conversation, AttachmentService $attachmentService): JsonResponse
    {
        $this->authorize('view', $conversation);

        $photos = $attachmentService->sharedPhotos($conversation);

        return AttachmentResource::collection($photos)->response();
    }

    public function download(string $filename, Request $request, AttachmentService $attachmentService, ConversationService $conversationService): StreamedResponse|JsonResponse
    {
        $safeName = basename($filename);
        $path = $attachmentService->downloadPath($safeName);

        if ($path === null) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $message = \Chatify\Support\ChatifyModels::messageClass()::query()
            ->where('attachment->stored_name', $safeName)
            ->first();

        if ($message === null) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $this->authorize('view', $message);

        return $attachmentService->storage()->download($path, $safeName);
    }
}
