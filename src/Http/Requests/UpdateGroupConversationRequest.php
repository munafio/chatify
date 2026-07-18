<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (bool) config('chatify.groups.enabled', true);
    }

    public function rules(): array
    {
        $maxName = (int) config('chatify.groups.max_name_length', 100);
        $maxDescription = (int) config('chatify.groups.max_description_length', 500);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:'.$maxName],
            'description' => ['sometimes', 'nullable', 'string', 'max:'.$maxDescription],
        ];
    }
}
