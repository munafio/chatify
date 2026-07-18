<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Chatify\Models\ConversationParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupParticipantRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (bool) config('chatify.groups.enabled', true);
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in([
                ConversationParticipant::ROLE_ADMIN,
                ConversationParticipant::ROLE_MODERATOR,
                ConversationParticipant::ROLE_MEMBER,
            ])],
            'permissions' => ['nullable', 'array'],
            'permissions.edit_info' => ['sometimes', 'boolean'],
            'permissions.add_members' => ['sometimes', 'boolean'],
            'permissions.remove_members' => ['sometimes', 'boolean'],
            'permissions.manage_admins' => ['sometimes', 'boolean'],
        ];
    }
}
