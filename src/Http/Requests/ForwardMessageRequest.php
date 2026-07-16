<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForwardMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message_id' => ['required', 'uuid'],
        ];
    }
}
