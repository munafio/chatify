<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Support\ChatifyModels;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AttachmentService
{
    public function storeMessageAttachment(UploadedFile $file): array
    {
        return $this->storeUploadedFile($file);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
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
            'type' => in_array($extension, config('chatify.attachments.allowed_images', []), true) ? 'image' : 'file',
        ];
    }

    public function storeAvatar(UploadedFile $file): array
    {
        return $this->storeImage($file, config('chatify.user_avatar.folder', 'users-avatar'));
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
            throw new \InvalidArgumentException('Image file type not allowed.');
        }

        if ($file->getSize() > $this->maxBytes()) {
            throw new \InvalidArgumentException('Image file is too large.');
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
            throw new \InvalidArgumentException('File extension not allowed.');
        }

        if ($file->getSize() > $this->maxBytes()) {
            throw new \InvalidArgumentException('File is too large.');
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
