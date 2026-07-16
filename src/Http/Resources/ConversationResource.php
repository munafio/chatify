<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Chatify\Models\Conversation;
use Chatify\Services\ContactService;
use Chatify\Services\ConversationService;
use Chatify\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conversation */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $unread = 0;
        $otherUser = null;

        if ($user !== null) {
            $unread = app(MessageService::class)->unreadCount($this->resource, (int) $user->getKey());

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
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->isGroup()) {
            $attributes['participant_count'] = $this->participants->count();

            if ($user !== null) {
                $attributes['is_owner'] = app(ConversationService::class)->isOwner(
                    $this->resource,
                    (int) $user->getKey()
                );
            }
        }

        return [
            'type' => 'conversation',
            'id' => $this->id,
            'attributes' => $attributes,
            'relationships' => [
                'participants' => UserResource::collection(
                    $this->participants->map->user->filter()
                ),
                'last_message' => $lastMessage !== null
                    ? (new MessageResource($lastMessage))->resolve()
                    : null,
                'other_user' => $otherUser,
            ],
        ];
    }
}
