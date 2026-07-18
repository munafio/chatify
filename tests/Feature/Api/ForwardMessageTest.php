<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Services\AttachmentService;
use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ForwardMessageTest extends TestCase
{
    public function test_user_can_forward_text_message(): void
    {
        Storage::fake('public');

        $sender = $this->createUser();
        $sourceRecipient = $this->createUser();
        $targetRecipient = $this->createUser();

        $sourceConversation = app(ConversationService::class)->findOrCreateDirect($sender, $sourceRecipient);
        $targetConversation = app(ConversationService::class)->findOrCreateDirect($sender, $targetRecipient);

        $sourceMessage = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$sourceConversation->id}/messages", [
                'body' => 'Forward me',
            ])
            ->assertCreated()
            ->json('data');

        $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$targetConversation->id}/forward", [
                'message_id' => $sourceMessage['id'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.attributes.body', 'Forward me')
            ->assertJsonPath('data.attributes.forwarded_from.id', $sourceMessage['id'])
            ->assertJsonPath('data.attributes.forwarded_from.body', 'Forward me');
    }

    public function test_forward_image_message_copies_attachment_file(): void
    {
        Storage::fake('public');

        $sender = $this->createUser();
        $sourceRecipient = $this->createUser();
        $targetRecipient = $this->createUser();

        $sourceConversation = app(ConversationService::class)->findOrCreateDirect($sender, $sourceRecipient);
        $targetConversation = app(ConversationService::class)->findOrCreateDirect($sender, $targetRecipient);

        $sourceMessage = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$sourceConversation->id}/messages", [
                'attachment' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertCreated()
            ->json('data');

        $originalFilename = $sourceMessage['attributes']['attachment']['filename'];
        $folder = config('chatify.attachments.folder', 'attachments');

        Storage::disk('public')->assertExists($folder.'/'.$originalFilename);

        $forwarded = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$targetConversation->id}/forward", [
                'message_id' => $sourceMessage['id'],
            ])
            ->assertCreated()
            ->json('data');

        $copiedFilename = $forwarded['attributes']['attachment']['filename'];

        $this->assertNotSame($originalFilename, $copiedFilename);
        Storage::disk('public')->assertExists($folder.'/'.$copiedFilename);
    }

    public function test_forward_album_message_copies_all_attachment_files(): void
    {
        Storage::fake('public');

        $sender = $this->createUser();
        $sourceRecipient = $this->createUser();
        $targetRecipient = $this->createUser();

        $sourceConversation = app(ConversationService::class)->findOrCreateDirect($sender, $sourceRecipient);
        $targetConversation = app(ConversationService::class)->findOrCreateDirect($sender, $targetRecipient);

        $sourceMessage = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$sourceConversation->id}/messages", [
                'attachments' => [
                    UploadedFile::fake()->image('one.jpg'),
                    UploadedFile::fake()->image('two.jpg'),
                ],
            ])
            ->assertCreated()
            ->json('data');

        $originalFilenames = array_column($sourceMessage['attributes']['attachments'], 'filename');
        $this->assertCount(2, $originalFilenames);

        $folder = config('chatify.attachments.folder', 'attachments');

        foreach ($originalFilenames as $filename) {
            Storage::disk('public')->assertExists($folder.'/'.$filename);
        }

        $forwarded = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$targetConversation->id}/forward", [
                'message_id' => $sourceMessage['id'],
            ])
            ->assertCreated()
            ->json('data');

        $copiedFilenames = array_column($forwarded['attributes']['attachments'], 'filename');
        $this->assertCount(2, $copiedFilenames);
        $this->assertNotEquals($originalFilenames, $copiedFilenames);

        foreach ($copiedFilenames as $filename) {
            Storage::disk('public')->assertExists($folder.'/'.$filename);
        }

        $attachmentService = app(AttachmentService::class);

        foreach ($originalFilenames as $filename) {
            $this->assertTrue($attachmentService->storage()->exists($folder.'/'.$filename));
        }
    }
}
