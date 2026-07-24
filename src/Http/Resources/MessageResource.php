<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Chatify\Models\Message;
use Chatify\Services\AttachmentService;
use Chatify\Services\BlockService;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $read = false;

        if ($viewer !== null) {
            $participant = app(ConversationService::class)->getParticipant(
                $this->conversation,
                (int) $viewer->getKey()
            );
            $read = $this->isReadByParticipant($participant);
        }

        $attachmentService = app(AttachmentService::class);
        $attachment = null;
        $attachments = [];

        if (is_array($this->attachment)) {
            if (($this->attachment['album'] ?? false) === true && is_array($this->attachment['items'] ?? null)) {
                foreach ($this->attachment['items'] as $item) {
                    if (! is_array($item) || empty($item['stored_name'])) {
                        continue;
                    }

                    $attachments[] = [
                        'filename' => $item['stored_name'],
                        'original_name' => $item['original_name'] ?? null,
                        'type' => $item['type'] ?? 'image',
                        'url' => $attachmentService->url($item['stored_name']),
                    ];
                }

                $attachment = $attachments[0] ?? null;
            } elseif (! empty($this->attachment['stored_name'])) {
                $attachment = [
                    'filename' => $this->attachment['stored_name'],
                    'original_name' => $this->attachment['original_name'] ?? null,
                    'type' => $this->attachment['type'] ?? 'file',
                    'url' => $attachmentService->url($this->attachment['stored_name']),
                ];
                $attachments = [$attachment];
            }
        }

        $replyTo = null;

        if ($this->relationLoaded('replyTo') && $this->replyTo !== null) {
            $replyTo = [
                'id' => $this->replyTo->id,
                'body' => $this->replyTo->body,
                'sender_name' => $this->maskedSenderName($this->replyTo->sender, $viewer),
            ];
        }

        $forwardedFrom = null;

        if ($this->forwarded_from_message_id !== null) {
            if ($this->relationLoaded('forwardedFrom') && $this->forwardedFrom !== null) {
                $forwardedFrom = [
                    'id' => $this->forwardedFrom->id,
                    'body' => $this->forwardedFrom->body,
                    'sender_name' => $this->maskedSenderName($this->forwardedFrom->sender, $viewer),
                ];
            } else {
                $forwardedFrom = [
                    'id' => $this->forwarded_from_message_id,
                    'body' => null,
                    'sender_name' => 'User',
                ];
            }
        }

        return [
            'type' => 'message',
            'id' => $this->id,
            'attributes' => [
                'conversation_id' => $this->conversation_id,
                'kind' => $this->kind ?? 'user',
                'system_event' => $this->system_event,
                'body' => $this->body,
                'attachment' => $attachment,
                'attachments' => $attachments,
                'read' => $read,
                'edited_at' => $this->edited_at?->toIso8601String(),
                'reply_to' => $replyTo,
                'forwarded_from' => $forwardedFrom,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'sender' => [
                    'data' => [
                        'type' => 'user',
                        'id' => $this->user_id,
                    ],
                ],
            ],
        ];
    }

    private function maskedSenderName(?Model $sender, ?Model $viewer): string
    {
        if ($sender === null) {
            return 'User';
        }

        if ($viewer === null) {
            return $sender->name;
        }

        $blockService = app(BlockService::class);

        return $blockService->shouldRevealIdentity($viewer, $sender)
            ? $sender->name
            : BlockService::hiddenUserName();
    }
}
