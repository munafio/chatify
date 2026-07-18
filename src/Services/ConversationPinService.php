<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Models\Conversation;
use Chatify\Models\ConversationParticipant;
use Chatify\Support\ChatifyModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class ConversationPinService
{
    public function setPinned(Conversation $conversation, Model $user, bool $pinned): ConversationParticipant
    {
        if ($conversation->isSaved()) {
            throw ValidationException::withMessages([
                'conversation' => ['Saved Messages cannot be pinned.'],
            ]);
        }

        $participant = $this->participantFor($conversation, $user);

        if ($pinned) {
            $participant->forceFill([
                'is_pinned' => true,
                'pin_order' => $this->nextPinOrder($user),
            ])->save();
        } else {
            $participant->forceFill([
                'is_pinned' => false,
                'pin_order' => null,
            ])->save();
        }

        return $participant->fresh();
    }

    /**
     * @param  list<string>  $conversationIds
     */
    public function reorderPinned(Model $user, array $conversationIds): void
    {
        $participantTable = config('chatify.tables.participants', 'ch_conversation_participants');
        $userId = (int) $user->getKey();

        $pinnedIds = ChatifyModels::participantClass()::query()
            ->where("{$participantTable}.user_id", $userId)
            ->where("{$participantTable}.is_pinned", true)
            ->pluck('conversation_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (count($conversationIds) !== count($pinnedIds)) {
            throw ValidationException::withMessages([
                'conversation_ids' => ['Pinned conversation order must include all pinned conversations.'],
            ]);
        }

        foreach ($conversationIds as $conversationId) {
            if (! in_array((string) $conversationId, $pinnedIds, true)) {
                throw ValidationException::withMessages([
                    'conversation_ids' => ['Only pinned conversations can be reordered.'],
                ]);
            }
        }

        foreach ($conversationIds as $index => $conversationId) {
            ChatifyModels::participantClass()::query()
                ->where('user_id', $userId)
                ->where('conversation_id', $conversationId)
                ->where('is_pinned', true)
                ->update(['pin_order' => $index]);
        }
    }

    private function participantFor(Conversation $conversation, Model $user): ConversationParticipant
    {
        $participant = ChatifyModels::participantClass()::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->getKey())
            ->first();

        if ($participant === null) {
            throw ValidationException::withMessages([
                'conversation' => ['You are not a participant in this conversation.'],
            ]);
        }

        return $participant;
    }

    private function nextPinOrder(Model $user): int
    {
        $max = ChatifyModels::participantClass()::query()
            ->where('user_id', $user->getKey())
            ->where('is_pinned', true)
            ->max('pin_order');

        return $max === null ? 0 : ((int) $max + 1);
    }
}
