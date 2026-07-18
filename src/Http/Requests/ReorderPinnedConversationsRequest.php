<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPinnedConversationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'conversation_ids' => ['required', 'array', 'min:1'],
            'conversation_ids.*' => ['required', 'uuid'],
        ];
    }
}
