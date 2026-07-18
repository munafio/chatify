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
                'kind' => $this->resource['kind'] ?? 'media',
                'filename' => $this->resource['filename'] ?? null,
                'url' => $this->resource['url'] ?? null,
                'original_name' => $this->resource['original_name'] ?? null,
                'mime' => $this->resource['mime'] ?? null,
                'snippet' => $this->resource['snippet'] ?? null,
                'message_id' => $this->resource['message_id'] ?? null,
                'created_at' => $this->resource['created_at'] ?? null,
            ],
        ];
    }
}
