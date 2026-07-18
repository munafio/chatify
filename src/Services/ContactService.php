<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Models\Conversation;
use Chatify\Support\ChatifyModels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ContactService
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly BlockService $blockService,
        private readonly ConversationService $conversationService,
    ) {}

    public function paginatedForUser(Model $user, int $perPage = 30): LengthAwarePaginator
    {
        if (config('chatify.saved_messages.enabled', true)) {
            $this->conversationService->findOrCreateSavedForUser($user);
        }

        $userId = (int) $user->getKey();
        $conversationTable = config('chatify.tables.conversations', 'ch_conversations');
        $participantTable = config('chatify.tables.participants', 'ch_conversation_participants');
        $messageTable = config('chatify.tables.messages', 'ch_messages');

        $latestMessages = DB::table($messageTable)
            ->select('conversation_id', DB::raw('MAX(created_at) as last_message_at'))
            ->whereNull('deleted_at')
            ->groupBy('conversation_id');

        return ChatifyModels::conversationClass()::query()
            ->select(
                "{$conversationTable}.*",
                'latest.last_message_at',
                "{$participantTable}.is_pinned",
                "{$participantTable}.pin_order",
            )
            ->join($participantTable, function ($join) use ($conversationTable, $participantTable, $userId) {
                $join->on("{$conversationTable}.id", '=', "{$participantTable}.conversation_id")
                    ->where("{$participantTable}.user_id", '=', $userId)
                    ->whereNull("{$participantTable}.hidden_at");
            })
            ->leftJoinSub($latestMessages, 'latest', function ($join) use ($conversationTable) {
                $join->on("{$conversationTable}.id", '=', 'latest.conversation_id');
            })
            ->with(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByRaw("CASE WHEN {$conversationTable}.type = ? THEN 0 ELSE 1 END", [Conversation::TYPE_SAVED])
            ->orderByDesc("{$participantTable}.is_pinned")
            ->orderBy("{$participantTable}.pin_order")
            ->orderByDesc(DB::raw("COALESCE(latest.last_message_at, {$conversationTable}.created_at)"))
            ->paginate($perPage);
    }

    public function search(Model $authUser, string $query, int $perPage = 30): LengthAwarePaginator
    {
        $userClass = ChatifyModels::userClass();
        $term = addcslashes(trim($query), '%_\\');
        $blockedIds = $this->blockService->blockedUserIdsFor($authUser);

        return $userClass::query()
            ->where('id', '!=', $authUser->getKey())
            ->when($blockedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $blockedIds))
            ->where(function (Builder $query) use ($term): void {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('email', 'LIKE', '%'.$term.'%');
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function otherParticipant(Conversation $conversation, int $userId): ?Model
    {
        if ($conversation->isSaved()) {
            return null;
        }

        $participant = $conversation->participants()
            ->where('user_id', '!=', $userId)
            ->with('user')
            ->first();

        return $participant?->user;
    }
}
