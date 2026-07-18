<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Data\SendMessageData;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Models\MessageUserState;
use Chatify\Support\ChatifyModels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class MessageService
{
    public function __construct(
        private readonly BlockService $blockService,
    ) {}

    public function send(Conversation $conversation, Model $sender, SendMessageData $data): Message
    {
        $message = ChatifyModels::messageClass()::query()->create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'user_id' => $sender->getKey(),
            'body' => $data->body !== null ? strip_tags($data->body) : null,
            'attachment' => $data->attachmentMeta,
            'reply_to_message_id' => $data->replyToMessageId,
            'forwarded_from_message_id' => $data->forwardedFromMessageId,
        ]);

        return $message->load(['sender', 'replyTo.sender', 'forwardedFrom.sender']);
    }

    public function sendSystemMessage(
        Conversation $conversation,
        Model $actor,
        string $event,
        array $targetUserIds,
        string $body,
    ): Message {
        $message = ChatifyModels::messageClass()::query()->create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'user_id' => $actor->getKey(),
            'kind' => 'system',
            'body' => $body,
            'system_event' => [
                'event' => $event,
                'actor_user_id' => (int) $actor->getKey(),
                'target_user_ids' => array_values(array_map('intval', $targetUserIds)),
            ],
        ]);

        return $message->load(['sender', 'replyTo.sender']);
    }

    public function forConversation(Conversation $conversation, ?int $viewerId = null): Builder
    {
        $query = ChatifyModels::messageClass()::query()
            ->forConversation($conversation->id)
            ->with(['sender', 'replyTo.sender', 'forwardedFrom.sender'])
            ->latest();

        if ($viewerId !== null) {
            $query->whereNotIn('id', function ($sub) use ($viewerId) {
                $sub->select('message_id')
                    ->from('ch_message_user_states')
                    ->where('user_id', $viewerId)
                    ->whereNotNull('hidden_at');
            });

            $viewer = ChatifyModels::userClass()::query()->find($viewerId);

            if ($viewer !== null) {
                $blockedIds = $this->blockService->blockedUserIdsFor($viewer);

                if ($blockedIds !== []) {
                    $query->whereNotIn('user_id', $blockedIds);
                }
            }
        }

        return $query;
    }

    public function paginate(
        Conversation $conversation,
        int $perPage = 30,
        ?string $after = null,
        ?int $viewerId = null,
    ): LengthAwarePaginator {
        $query = $this->forConversation($conversation, $viewerId);

        if ($after !== null) {
            $query->after($after);
        }

        return $query->paginate($perPage);
    }

    public function search(
        Conversation $conversation,
        string $query,
        int $perPage = 20,
        int $page = 1,
        ?int $viewerId = null,
    ): LengthAwarePaginator {
        $builder = $this->forConversation($conversation, $viewerId)
            ->where('body', 'like', '%'.$query.'%');

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    public function edit(Message $message, string $body): Message
    {
        $message->update([
            'body' => strip_tags($body),
            'edited_at' => now(),
        ]);

        return $message->fresh(['sender', 'replyTo.sender']);
    }

    public function hideForUser(Message $message, int $userId): void
    {
        $state = MessageUserState::query()->firstOrNew([
            'message_id' => $message->id,
            'user_id' => $userId,
        ]);

        if (! $state->exists) {
            $state->id = (string) Str::uuid();
        }

        $state->hidden_at = now();
        $state->save();
    }

    public function delete(Message $message): void
    {
        $message->delete();
    }

    public function deleteAllForConversation(Conversation $conversation): void
    {
        ChatifyModels::messageClass()::query()
            ->forConversation($conversation->id)
            ->delete();
    }

    public function unreadCount(Conversation $conversation, int $userId): int
    {
        if ($conversation->isSaved()) {
            return 0;
        }

        $participant = $conversation->participants()->where('user_id', $userId)->first();

        $query = ChatifyModels::messageClass()::query()
            ->forConversation($conversation->id)
            ->where('user_id', '!=', $userId);

        if ($participant?->last_read_at !== null) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->count();
    }
}
