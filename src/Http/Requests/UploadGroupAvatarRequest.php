<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Chatify\Support\ValidatesUploadedFiles;
use Illuminate\Foundation\Http\FormRequest;

class UploadGroupAvatarRequest extends FormRequest
{
    use ValidatesUploadedFiles;

    public function authorize(): bool
    {
        return $this->user() !== null && (bool) config('chatify.groups.enabled', true);
    }

    public function rules(): array
    {
        $maxKb = (int) config('chatify.attachments.max_upload_size', 150) * 1024;
        $mimes = config('chatify.attachments.allowed_images', ['png', 'jpg', 'jpeg', 'gif']);

        return [
            'avatar' => ['required', 'file', 'max:'.$maxKb, 'mimes:'.implode(',', $mimes)],
        ];
    }

    public function withValidator($validator): void
    {
        $this->addUploadErrorMessages($validator, 'avatar');
    }
}
