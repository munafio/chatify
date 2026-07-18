<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferGroupOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (bool) config('chatify.groups.enabled', true);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
        ];
    }
}
