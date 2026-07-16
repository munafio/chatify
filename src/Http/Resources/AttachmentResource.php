<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'attachment',
            'attributes' => [
                'filename' => $this->resource['filename'],
                'url' => $this->resource['url'],
                'message_id' => $this->resource['message_id'] ?? null,
            ],
        ];
    }
}
