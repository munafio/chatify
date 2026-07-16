<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageGroupParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (bool) config('chatify.groups.enabled', true);
    }

    public function rules(): array
    {
        $maxParticipants = (int) config('chatify.groups.max_participants', 50);

        return [
            'user_ids' => ['required', 'array', 'min:1', 'max:'.$maxParticipants],
            'user_ids.*' => [
                'integer',
                'distinct',
                'exists:users,id',
                Rule::notIn([(int) $this->user()?->getKey()]),
            ],
        ];
    }
}
