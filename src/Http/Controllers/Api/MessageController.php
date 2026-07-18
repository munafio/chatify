<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Actions\Messages\DeleteMessage;
use Chatify\Actions\Messages\ForwardMessage;
use Chatify\Actions\Messages\SendMessage;
use Chatify\Actions\Messages\UpdateMessage;
use Chatify\Http\Requests\ForwardMessageRequest;
use Chatify\Http\Requests\SendMessageRequest;
use Chatify\Http\Requests\UpdateMessageRequest;
use Chatify\Http\Resources\MessageResource;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Services\MessageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MessageController extends Controller
{
    use AuthorizesRequests;

    public function index(Conversation $conversation, Request $request, MessageService $messageService): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = $messageService->paginate(
            $conversation,
            (int) $request->integer('per_page', 30),
            $request->string('after')->toString() ?: null,
            (int) $request->user()->getKey(),
        );

        return MessageResource::collection($messages)->response();
    }

    public function search(Conversation $conversation, Request $request, MessageService $messageService): JsonResponse
    {
        $this->authorize('view', $conversation);

        $query = trim($request->string('q')->toString());

        if (mb_strlen($query) < 2) {
            abort(422, 'Search query must be at least 2 characters.');
        }

        $messages = $messageService->search(
            $conversation,
            $query,
            (int) $request->integer('per_page', 20),
            (int) $request->integer('page', 1),
            (int) $request->user()->getKey(),
        );

        return MessageResource::collection($messages)->response();
    }

    public function store(
        Conversation $conversation,
        SendMessageRequest $request,
        SendMessage $action,
    ): JsonResponse {
        $this->authorize('view', $conversation);

        $attachments = $request->file('attachments', []);
        if (! is_array($attachments)) {
            $attachments = [];
        }

        $message = $action->handle(
            $conversation,
            $request->user(),
            $request->input('body'),
            $request->file('attachment'),
            $attachments,
            $request->input('reply_to_message_id'),
            $request->input('forwarded_from_message_id'),
        );

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    public function update(
        Message $message,
        UpdateMessageRequest $request,
        UpdateMessage $action,
    ): JsonResponse {
        $this->authorize('update', $message);

        $updated = $action->handle($message, $request->user(), $request->string('body')->toString());

        return (new MessageResource($updated))->response();
    }

    public function destroy(Message $message, Request $request, DeleteMessage $action): JsonResponse
    {
        $scope = $request->string('scope', 'all')->toString();
        if (! in_array($scope, ['me', 'all'], true)) {
            abort(422, 'Invalid delete scope.');
        }

        $this->authorize($scope === 'me' ? 'hide' : 'delete', $message);

        $action->handle($message, $request->user(), $scope);

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function forward(
        Conversation $conversation,
        ForwardMessageRequest $request,
        ForwardMessage $action,
    ): JsonResponse {
        $this->authorize('view', $conversation);

        $source = Message::query()->findOrFail($request->string('message_id')->toString());
        $message = $action->handle($conversation, $source, $request->user());

        return (new MessageResource($message))->response()->setStatusCode(201);
    }
}
