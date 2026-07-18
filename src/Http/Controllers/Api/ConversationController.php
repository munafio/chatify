<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Actions\Conversations\AddGroupParticipants;
use Chatify\Actions\Conversations\CreateGroupConversation;
use Chatify\Actions\Conversations\DeleteConversation;
use Chatify\Actions\Conversations\FindOrCreateDirectConversation;
use Chatify\Actions\Conversations\LeaveGroupConversation;
use Chatify\Actions\Conversations\RemoveGroupParticipant;
use Chatify\Actions\Conversations\UpdateGroupConversation;
use Chatify\Actions\Conversations\UpdateGroupAvatar;
use Chatify\Actions\Messages\MarkConversationRead;
use Chatify\Http\Requests\CreateDirectConversationRequest;
use Chatify\Http\Requests\CreateGroupConversationRequest;
use Chatify\Http\Requests\ManageGroupParticipantsRequest;
use Chatify\Http\Requests\UpdateGroupConversationRequest;
use Chatify\Http\Requests\UploadGroupAvatarRequest;
use Chatify\Http\Resources\ConversationResource;
use Chatify\Models\Conversation;
use Chatify\Services\ContactService;
use Chatify\Services\ConversationService;
use Chatify\Services\InboxBroadcastService;
use Chatify\Support\ChatifyModels;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ConversationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, ContactService $contactService): JsonResponse
    {
        $conversations = $contactService->paginatedForUser(
            $request->user(),
            (int) $request->integer('per_page', 30)
        );

        return ConversationResource::collection($conversations)->response();
    }

    public function storeDirect(
        CreateDirectConversationRequest $request,
        FindOrCreateDirectConversation $action,
        InboxBroadcastService $inboxBroadcastService,
    ): JsonResponse {
        $recipient = ChatifyModels::userClass()::query()->findOrFail($request->integer('user_id'));

        $conversation = $action->handle($request->user(), $recipient);
        $conversation->load(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)]);
        $inboxBroadcastService->broadcastForConversation($conversation);

        return (new ConversationResource($conversation))
            ->response()
            ->setStatusCode(201);
    }

    public function storeGroup(
        CreateGroupConversationRequest $request,
        CreateGroupConversation $action,
        InboxBroadcastService $inboxBroadcastService,
    ): JsonResponse {
        $conversation = $action->handle(
            $request->user(),
            $request->string('name')->toString(),
            array_map('intval', $request->input('user_ids', [])),
        );

        $conversation->load(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)]);
        $inboxBroadcastService->broadcastForConversation($conversation);

        return (new ConversationResource($conversation))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load(['participants.user', 'creator', 'messages' => fn ($q) => $q->latest()->limit(1)]);

        return (new ConversationResource($conversation))->response();
    }

    public function update(
        UpdateGroupConversationRequest $request,
        Conversation $conversation,
        UpdateGroupConversation $action,
    ): JsonResponse {
        $this->authorize('updateGroup', $conversation);

        $conversation = $action->handle(
            $conversation,
            $request->has('name') ? $request->string('name')->toString() : null,
            $request->has('description') ? $request->input('description') : null,
        );

        return (new ConversationResource($conversation))->response();
    }

    public function uploadAvatar(
        UploadGroupAvatarRequest $request,
        Conversation $conversation,
        UpdateGroupAvatar $action,
    ): JsonResponse {
        $this->authorize('updateGroup', $conversation);

        $conversation = $action->handle($conversation, $request->file('avatar'));

        return (new ConversationResource($conversation))->response();
    }

    public function addParticipants(
        ManageGroupParticipantsRequest $request,
        Conversation $conversation,
        AddGroupParticipants $action,
    ): JsonResponse {
        $this->authorize('addParticipants', $conversation);

        $conversation = $action->handle(
            $conversation,
            $request->user(),
            array_map('intval', $request->input('user_ids', [])),
        );

        return (new ConversationResource($conversation))->response();
    }

    public function removeParticipant(
        Conversation $conversation,
        int $user,
        RemoveGroupParticipant $action,
        Request $request,
    ): JsonResponse {
        $this->authorize('removeParticipant', [$conversation, $user]);

        $conversationService = app(ConversationService::class);

        if ((int) $request->user()->getKey() === $user && $conversationService->isOwner($conversation, $user)) {
            abort(422, 'Group owner cannot leave via remove. Transfer ownership or delete the group.');
        }

        $conversation = $action->handle($conversation, $request->user(), $user);

        return (new ConversationResource($conversation))->response();
    }

    public function leave(
        Conversation $conversation,
        LeaveGroupConversation $action,
        Request $request,
    ): JsonResponse {
        $this->authorize('leave', $conversation);

        $conversation = $action->handle($conversation, $request->user());

        return response()->json(['data' => ['left' => true]]);
    }

    public function destroy(Conversation $conversation, DeleteConversation $action, Request $request): JsonResponse
    {
        $this->authorize('delete', $conversation);

        $action->handle($conversation, $request->user());

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function markRead(Conversation $conversation, MarkConversationRead $action, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $action->handle($conversation, $request->user());

        return response()->json(['data' => ['read' => true]]);
    }
}
