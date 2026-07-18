<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Actions\Conversations\TransferGroupOwnership;
use Chatify\Actions\Conversations\UpdateGroupParticipantRole;
use Chatify\Http\Requests\TransferGroupOwnershipRequest;
use Chatify\Http\Requests\UpdateGroupParticipantRoleRequest;
use Chatify\Http\Resources\ConversationResource;
use Chatify\Http\Resources\ParticipantResource;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Chatify\Support\ChatifyModels;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GroupParticipantController extends Controller
{
    use AuthorizesRequests;

    public function index(
        Conversation $conversation,
        Request $request,
        ConversationService $conversationService,
    ): JsonResponse {
        $this->authorize('view', $conversation);

        if (! $conversation->isGroup()) {
            abort(404);
        }

        $participants = $conversationService->paginateParticipants(
            $conversation,
            (int) $request->integer('per_page', 20),
            $request->string('search')->toString() ?: null,
            $request->user(),
        );

        return ParticipantResource::collection($participants)->response();
    }

    public function update(
        UpdateGroupParticipantRoleRequest $request,
        Conversation $conversation,
        int $user,
        UpdateGroupParticipantRole $action,
    ): JsonResponse {
        $this->authorize('manageParticipantRole', $conversation);

        $role = $request->string('role')->toString();
        $permissions = null;

        if ($role === 'admin') {
            $permissions = $request->has('permissions')
                ? $request->input('permissions')
                : null;
        }

        $conversation = $action->handle(
            $conversation,
            $user,
            $role,
            is_array($permissions) ? $permissions : null,
            (int) $request->user()->getKey(),
        );

        return (new ConversationResource($conversation))->response();
    }

    public function transferOwnership(
        TransferGroupOwnershipRequest $request,
        Conversation $conversation,
        TransferGroupOwnership $action,
    ): JsonResponse {
        $this->authorize('transferOwnership', $conversation);

        $target = ChatifyModels::userClass()::query()->findOrFail($request->integer('user_id'));

        $conversation = $action->handle($conversation, $request->user(), $target);

        return (new ConversationResource($conversation))->response();
    }
}
