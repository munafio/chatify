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
    ) {}

    public function paginatedForUser(Model $user, int $perPage = 30): LengthAwarePaginator
    {
        $userId = (int) $user->getKey();
        $conversationTable = config('chatify.tables.conversations', 'ch_conversations');
        $participantTable = config('chatify.tables.participants', 'ch_conversation_participants');
        $messageTable = config('chatify.tables.messages', 'ch_messages');

        $latestMessages = DB::table($messageTable)
            ->select('conversation_id', DB::raw('MAX(created_at) as last_message_at'))
            ->whereNull('deleted_at')
            ->groupBy('conversation_id');

        return ChatifyModels::conversationClass()::query()
            ->select("{$conversationTable}.*", 'latest.last_message_at')
            ->leftJoinSub($latestMessages, 'latest', function ($join) use ($conversationTable) {
                $join->on("{$conversationTable}.id", '=', 'latest.conversation_id');
            })
            ->whereIn("{$conversationTable}.id", function ($query) use ($participantTable, $userId) {
                $query->select('conversation_id')
                    ->from($participantTable)
                    ->where('user_id', $userId);
            })
            ->with(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc(DB::raw("COALESCE(latest.last_message_at, {$conversationTable}.created_at)"))
            ->paginate($perPage);
    }

    public function search(Model $authUser, string $query, int $perPage = 30): LengthAwarePaginator
    {
        $userClass = ChatifyModels::userClass();
        $term = addcslashes(trim($query), '%_\\');

        return $userClass::query()
            ->where('id', '!=', $authUser->getKey())
            ->where(function (Builder $query) use ($term): void {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('email', 'LIKE', '%'.$term.'%');
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function otherParticipant(Conversation $conversation, int $userId): ?Model
    {
        $participant = $conversation->participants()
            ->where('user_id', '!=', $userId)
            ->with('user')
            ->first();

        return $participant?->user;
    }
}
