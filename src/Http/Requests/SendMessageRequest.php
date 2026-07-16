<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maxKb = (int) config('chatify.attachments.max_upload_size', 150) * 1024;
        $mimes = array_merge(
            config('chatify.attachments.allowed_images', []),
            config('chatify.attachments.allowed_files', [])
        );

        return [
            'body' => ['nullable', 'string', 'max:5000', 'required_without_all:attachment,attachments'],
            'attachment' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:'.implode(',', $mimes)],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:'.$maxKb, 'mimes:'.implode(',', $mimes)],
            'reply_to_message_id' => ['nullable', 'uuid'],
            'forwarded_from_message_id' => ['nullable', 'uuid'],
        ];
    }
}
