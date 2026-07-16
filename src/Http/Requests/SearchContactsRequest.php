<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ];
    }
}
