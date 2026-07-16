<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDirectConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
