<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateGroupConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (bool) config('chatify.groups.enabled', true);
    }

    public function rules(): array
    {
        $maxName = (int) config('chatify.groups.max_name_length', 100);
        $maxParticipants = (int) config('chatify.groups.max_participants', 50);
        $maxOthers = max(0, $maxParticipants - 1);

        return [
            'name' => ['required', 'string', 'max:'.$maxName],
            'user_ids' => ['required', 'array', 'min:1', 'max:'.$maxOthers],
            'user_ids.*' => [
                'integer',
                'distinct',
                'exists:users,id',
                Rule::notIn([(int) $this->user()?->getKey()]),
            ],
        ];
    }
}
