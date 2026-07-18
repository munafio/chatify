<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Chatify\Models\ConversationParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $permissions = $this->permissions;

        return [
            'type' => 'participant',
            'id' => $this->user_id,
            'attributes' => [
                'role' => $this->role,
                'permissions' => is_array($permissions) ? $permissions : null,
                'is_full_admin' => $this->role === ConversationParticipant::ROLE_ADMIN
                    && $this->permissions === null,
                'is_you' => $user !== null && (int) $user->getKey() === (int) $this->user_id,
            ],
            'relationships' => [
                'user' => $this->user !== null
                    ? (new UserResource($this->user))->resolve()
                    : null,
            ],
        ];
    }
}
