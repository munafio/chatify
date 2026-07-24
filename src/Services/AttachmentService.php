<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Support\ChatifyModels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AttachmentService
{
    private const AUDIO_EXTENSIONS = ['mp3', 'm4a', 'ogg', 'wav', 'aac', 'flac', 'opus'];

    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'ogv', '3gp', 'mpeg', 'mpg'];
    public function storeMessageAttachment(UploadedFile $file): array
    {
        return $this->storeUploadedFile($file);
    }

    public function storeMessageAttachments(array $files): array
    {
        if ($files === []) {
            return [];
        }

        if (count($files) === 1) {
            return $this->storeUploadedFile($files[0]);
        }

        $items = array_map(fn (UploadedFile $file) => $this->storeUploadedFile($file), $files);

        return [
            'album' => true,
            'items' => $items,
        ];
    }

    public function copyMessageAttachment(?array $attachment): ?array
    {
        if (! is_array($attachment)) {
            return null;
        }

        if (($attachment['album'] ?? false) === true && is_array($attachment['items'] ?? null)) {
            $items = [];

            foreach ($attachment['items'] as $item) {
                if (is_array($item) && ! empty($item['stored_name'])) {
                    $items[] = $this->copyStoredAttachmentItem($item);
                }
            }

            if ($items === []) {
                return null;
            }

            return [
                'album' => true,
                'items' => $items,
            ];
        }

        if (! empty($attachment['stored_name'])) {
            return $this->copyStoredAttachmentItem($attachment);
        }

        return null;
    }

    private function copyStoredAttachmentItem(array $item): array
    {
        $storedName = (string) $item['stored_name'];
        $extension = strtolower(pathinfo($storedName, PATHINFO_EXTENSION));
        $newName = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $folder = config('chatify.attachments.folder', 'attachments');
        $sourcePath = $folder.'/'.basename($storedName);
        $destPath = $folder.'/'.$newName;

        if ($this->storage()->exists($sourcePath)) {
            $this->storage()->copy($sourcePath, $destPath);
        }

        return [
            'stored_name' => $newName,
            'original_name' => $item['original_name'] ?? null,
            'mime' => $item['mime'] ?? null,
            'size' => $item['size'] ?? null,
            'type' => $item['type'] ?? $this->resolveAttachmentType($extension, $item['mime'] ?? null),
        ];
    }

    private function resolveAttachmentType(string $extension, ?string $mime = null, ?string $clientMime = null): string
    {
        foreach ([$clientMime, $mime] as $candidate) {
            if ($candidate === null) {
                continue;
            }

            if (str_starts_with($candidate, 'audio/')) {
                return 'audio';
            }

            if (str_starts_with($candidate, 'video/')) {
                return 'video';
            }
        }

        if (in_array($extension, self::AUDIO_EXTENSIONS, true)) {
            return 'audio';
        }

        if (in_array($extension, self::VIDEO_EXTENSIONS, true)) {
            return 'video';
        }

        if (in_array($extension, config('chatify.attachments.allowed_images', []), true)) {
            return 'image';
        }

        return 'file';
    }

    private function storeUploadedFile(UploadedFile $file): array
    {
        $this->assertAllowed($file);

        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().'.'.$extension;
        $folder = config('chatify.attachments.folder', 'attachments');

        $file->storeAs($folder, $storedName, $this->disk());

        return [
            'stored_name' => $storedName,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'type' => $this->resolveAttachmentType($extension, $file->getMimeType(), $file->getClientMimeType()),
        ];
    }

    public function storeAvatar(UploadedFile $file): array
    {
        return $this->storeImage($file, config('chatify.user_avatar.folder', 'users-avatar'));
    }

    public function storeGroupAvatar(UploadedFile $file): array
    {
        return $this->storeImage($file, config('chatify.groups.avatar_folder', 'groups-avatar'));
    }

    public function storeChatBackground(UploadedFile $file): array
    {
        return $this->storeImage($file, config('chatify.chat_background.folder', 'chat-backgrounds'));
    }

    public function chatBackgroundUrl(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $folder = config('chatify.chat_background.folder', 'chat-backgrounds');

        return $this->storage()->url($folder.'/'.basename($filename));
    }

    private function storeImage(UploadedFile $file, string $folder): array
    {
        $allowed = config('chatify.attachments.allowed_images', ['png', 'jpg', 'jpeg', 'gif']);

        if (! in_array(strtolower($file->getClientOriginalExtension()), $allowed, true)) {
            throw new \InvalidArgumentException(__('chatify::chatify.errors.image_type_not_allowed'));
        }

        if ($file->getSize() > $this->maxBytes()) {
            throw new \InvalidArgumentException(__('chatify::chatify.errors.image_too_large'));
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().'.'.$extension;

        $file->storeAs($folder, $storedName, $this->disk());

        return ['stored_name' => $storedName];
    }

    public function deleteForMessage(Message $message): void
    {
        $attachment = $message->attachment;

        if (! is_array($attachment)) {
            return;
        }

        if (($attachment['album'] ?? false) === true && is_array($attachment['items'] ?? null)) {
            foreach ($attachment['items'] as $item) {
                if (is_array($item) && ! empty($item['stored_name'])) {
                    $this->deleteStoredFile($item['stored_name']);
                }
            }

            return;
        }

        if (! empty($attachment['stored_name'])) {
            $this->deleteStoredFile($attachment['stored_name']);
        }
    }

    private function deleteStoredFile(string $storedName): void
    {
        $path = config('chatify.attachments.folder', 'attachments').'/'.basename($storedName);

        if ($this->storage()->exists($path)) {
            $this->storage()->delete($path);
        }
    }

    public function deleteConversationAttachments(Conversation $conversation): void
    {
        ChatifyModels::messageClass()::query()
            ->forConversation($conversation->id)
            ->whereNotNull('attachment')
            ->each(fn (Message $message) => $this->deleteForMessage($message));
    }

    public function sharedPhotos(Conversation $conversation): array
    {
        $allowedImages = config('chatify.attachments.allowed_images', []);
        $images = [];

        ChatifyModels::messageClass()::query()
            ->forConversation($conversation->id)
            ->whereNotNull('attachment')
            ->orderByDesc('created_at')
            ->each(function (Message $message) use (&$images, $allowedImages) {
                $attachment = $message->attachment;

                if (! is_array($attachment) || empty($attachment['stored_name'])) {
                    return;
                }

                $ext = pathinfo($attachment['stored_name'], PATHINFO_EXTENSION);

                if (in_array(strtolower($ext), $allowedImages, true)) {
                    $images[] = [
                        'filename' => $attachment['stored_name'],
                        'url' => $this->url($attachment['stored_name']),
                        'message_id' => $message->id,
                    ];
                }
            });

        return $images;
    }

    public function paginateForConversation(
        Conversation $conversation,
        string $type,
        int $perPage = 20,
        int $page = 1,
    ): LengthAwarePaginator {
        $items = match ($type) {
            'media' => $this->collectMedia($conversation),
            'docs' => $this->collectDocs($conversation),
            'links' => $this->collectLinks($conversation),
            default => [],
        };

        $total = count($items);
        $slice = array_slice($items, max(0, ($page - 1) * $perPage), $perPage);

        return new Paginator($slice, $total, $perPage, $page);
    }

    public function totalCount(Conversation $conversation): int
    {
        return count($this->collectMedia($conversation))
            + count($this->collectDocs($conversation))
            + count($this->collectLinks($conversation));
    }

    private function collectMedia(Conversation $conversation): array
    {
        $allowedImages = config('chatify.attachments.allowed_images', []);
        $items = [];

        $this->eachAttachment($conversation, function (Message $message, array $attachment) use (&$items, $allowedImages) {
            $ext = pathinfo($attachment['stored_name'], PATHINFO_EXTENSION);

            if (! in_array(strtolower($ext), $allowedImages, true)) {
                return;
            }

            $items[] = [
                'kind' => 'media',
                'filename' => $attachment['stored_name'],
                'url' => $this->url($attachment['stored_name']),
                'original_name' => $attachment['original_name'] ?? null,
                'mime' => $attachment['mime'] ?? null,
                'message_id' => $message->id,
                'created_at' => $message->created_at?->toIso8601String(),
            ];
        });

        return $items;
    }

    private function collectDocs(Conversation $conversation): array
    {
        $allowedImages = config('chatify.attachments.allowed_images', []);
        $items = [];

        $audioExtensions = ['mp3', 'm4a', 'aac', 'wav', 'flac', 'opus', 'oga', 'weba'];

        $this->eachAttachment($conversation, function (Message $message, array $attachment) use (&$items, $allowedImages, $audioExtensions) {
            $ext = strtolower(pathinfo($attachment['stored_name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowedImages, true)) {
                return;
            }

            $mime = (string) ($attachment['mime'] ?? '');
            $type = (string) ($attachment['type'] ?? '');

            if ($type === 'audio' || str_starts_with($mime, 'audio/') || in_array($ext, $audioExtensions, true)) {
                return;
            }

            $items[] = [
                'kind' => 'doc',
                'filename' => $attachment['stored_name'],
                'url' => $this->url($attachment['stored_name']),
                'original_name' => $attachment['original_name'] ?? null,
                'mime' => $attachment['mime'] ?? null,
                'message_id' => $message->id,
                'created_at' => $message->created_at?->toIso8601String(),
            ];
        });

        return $items;
    }

    private function collectLinks(Conversation $conversation): array
    {
        $items = [];

        ChatifyModels::messageClass()::query()
            ->forConversation($conversation->id)
            ->whereNotNull('body')
            ->orderByDesc('created_at')
            ->each(function (Message $message) use (&$items) {
                $body = (string) $message->body;
                preg_match_all('#https?://[^\s<>"\']+#i', $body, $matches);

                foreach ($matches[0] as $url) {
                    $items[] = [
                        'kind' => 'link',
                        'url' => $url,
                        'snippet' => mb_substr($body, 0, 120),
                        'message_id' => $message->id,
                        'created_at' => $message->created_at?->toIso8601String(),
                    ];
                }
            });

        return $items;
    }

    private function eachAttachment(Conversation $conversation, callable $callback): void
    {
        ChatifyModels::messageClass()::query()
            ->forConversation($conversation->id)
            ->whereNotNull('attachment')
            ->orderByDesc('created_at')
            ->each(function (Message $message) use ($callback) {
                $attachment = $message->attachment;

                if (! is_array($attachment)) {
                    return;
                }

                if (($attachment['album'] ?? false) === true && is_array($attachment['items'] ?? null)) {
                    foreach ($attachment['items'] as $item) {
                        if (is_array($item) && ! empty($item['stored_name'])) {
                            $callback($message, $item);
                        }
                    }

                    return;
                }

                if (! empty($attachment['stored_name'])) {
                    $callback($message, $attachment);
                }
            });
    }

    public function url(string $filename): string
    {
        $folder = config('chatify.attachments.folder', 'attachments');

        return $this->storage()->url($folder.'/'.$filename);
    }

    public function downloadPath(string $filename): ?string
    {
        $safe = basename($filename);
        $path = config('chatify.attachments.folder', 'attachments').'/'.$safe;

        return $this->storage()->exists($path) ? $path : null;
    }

    public function storage()
    {
        return Storage::disk($this->disk());
    }

    private function assertAllowed(UploadedFile $file): void
    {
        $allowed = array_merge(
            config('chatify.attachments.allowed_images', []),
            config('chatify.attachments.allowed_files', [])
        );

        if (! in_array(strtolower($file->getClientOriginalExtension()), $allowed, true)) {
            throw new \InvalidArgumentException(__('chatify::chatify.errors.file_extension_not_allowed'));
        }

        if ($file->getSize() > $this->maxBytes()) {
            throw new \InvalidArgumentException(__('chatify::chatify.errors.file_too_large'));
        }
    }

    private function maxBytes(): int
    {
        return (int) config('chatify.attachments.max_upload_size', 150) * 1048576;
    }

    private function disk(): string
    {
        return (string) config('chatify.storage_disk_name', 'public');
    }
}
