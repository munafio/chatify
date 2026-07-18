<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maxKb = (int) config('chatify.attachments.max_upload_size', 150) * 1024;
        $mimes = config('chatify.attachments.allowed_images', ['png', 'jpg', 'jpeg', 'gif']);

        return [
            'avatar' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:'.implode(',', $mimes)],
            'dark_mode' => ['sometimes', 'boolean'],
            'active_status' => ['sometimes', 'boolean'],
            'reset_avatar' => ['sometimes', 'boolean'],
            'theme_preferences' => ['sometimes', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('theme_preferences')) {
                return;
            }

            try {
                \Chatify\Support\ThemePreferencesValidator::validate($this->input('theme_preferences'));
            } catch (\InvalidArgumentException $exception) {
                $validator->errors()->add('theme_preferences', $exception->getMessage());
            }
        });
    }

    public function validatedSettingsAttributes(): array
    {
        $validated = [];

        if ($this->has('dark_mode')) {
            $validated['dark_mode'] = (bool) $this->boolean('dark_mode');
        }

        if ($this->has('active_status')) {
            $validated['active_status'] = (bool) $this->boolean('active_status');
        }

        if ($this->has('theme_preferences')) {
            $validated['theme_preferences'] = \Chatify\Support\ThemePreferencesValidator::validate(
                $this->input('theme_preferences')
            );
        }

        if ($this->boolean('reset_avatar')) {
            $validated['reset_avatar'] = true;
        }

        return $validated;
    }
}
