<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Security;

use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentValidationTest extends TestCase
{
    public function test_rejects_disallowed_attachment_mime_type(): void
    {
        Storage::fake('public');

        $sender = $this->createUser();
        $recipient = $this->createUser();

        $conversation = app(ConversationService::class)->findOrCreateDirect($sender, $recipient);

        $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", [
                'attachment' => UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attachment']);
    }

    public function test_rejects_oversized_attachment(): void
    {
        Storage::fake('public');

        $sender = $this->createUser();
        $recipient = $this->createUser();

        $conversation = app(ConversationService::class)->findOrCreateDirect($sender, $recipient);

        $maxKb = (int) config('chatify.attachments.max_upload_size', 150) * 1024;

        $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", [
                'attachment' => UploadedFile::fake()->create('large.jpg', $maxKb + 1, 'image/jpeg'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attachment']);
    }

    public function test_rejects_path_traversal_on_attachment_download(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/chatify/v1/attachments/../../.env')
            ->assertNotFound();
    }
}
