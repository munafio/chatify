<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Chatify\Models\Conversation;
use Chatify\Services\BlockService;
use Chatify\Services\ContactService;
use Chatify\Services\ConversationService;
use Chatify\Services\MessageService;
use Chatify\Services\ParticipantPermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $unread = 0;
        $otherUser = null;
        $participant = null;
        $conversationService = app(ConversationService::class);
        $permissionService = app(ParticipantPermissionService::class);

        if ($user !== null) {
            $unread = $this->isSaved()
                ? 0
                : app(MessageService::class)->unreadCount($this->resource, (int) $user->getKey());
            $participant = $this->participants->firstWhere('user_id', $user->getKey());

            if ($this->isDirect()) {
                $other = app(ContactService::class)->otherParticipant($this->resource, (int) $user->getKey());
                $otherUser = $other !== null ? (new UserResource($other))->resolve() : null;
            }
        }

        $lastMessage = $this->messages->first();

        $attributes = [
            'conversation_type' => $this->type,
            'name' => $this->name,
            'unread_count' => $unread,
            'is_pinned' => (bool) ($participant?->is_pinned ?? false),
            'pin_order' => $participant?->pin_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        $relationships = [
            'participants' => UserResource::collection(
                $this->participants->map->user->filter()
            ),
            'last_message' => $lastMessage !== null
                ? (new MessageResource($lastMessage))->resolve()
                : null,
            'other_user' => $otherUser,
            'participants_preview' => null,
        ];

        if ($this->isGroup()) {
            $attributes['participant_count'] = $this->participants->count();
            $attributes['description'] = $this->description;
            $attributes['avatar_url'] = $this->avatarUrl();

            if ($this->relationLoaded('creator') && $this->creator !== null) {
                $creatorName = $this->creator->name;

                if ($user !== null) {
                    $blockService = app(BlockService::class);
                    $creatorName = $blockService->shouldRevealIdentity($user, $this->creator)
                        ? $this->creator->name
                        : BlockService::HIDDEN_USER_NAME;
                }

                $attributes['created_by'] = [
                    'id' => $this->creator->getKey(),
                    'name' => $creatorName,
                ];
            }

            if ($user !== null) {
                $userId = (int) $user->getKey();
                $attributes['is_owner'] = $permissionService->isOwner($this->resource, $userId);
                $attributes['my_membership'] = $permissionService->membership($this->resource, $userId);
            }

            $preview = $conversationService->participantsPreview($this->resource, null, $user);
            $relationships['participants_preview'] = ParticipantResource::collection($preview);
        }

        if ($this->isSaved()) {
            $attributes['is_saved'] = true;
            $attributes['saved_title'] = config('chatify.saved_messages.title', 'Saved Messages');
            $attributes['name'] = $attributes['saved_title'];
        }

        return [
            'type' => 'conversation',
            'id' => $this->id,
            'attributes' => $attributes,
            'relationships' => $relationships,
        ];
    }
}
