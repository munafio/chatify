<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Http\Requests\SearchContactsRequest;
use Chatify\Http\Resources\UserResource;
use Chatify\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ContactController extends Controller
{
    public function search(SearchContactsRequest $request, ContactService $contactService): JsonResponse
    {
        $users = $contactService->search(
            $request->user(),
            $request->string('q')->toString(),
            (int) $request->integer('per_page', 30)
        );

        return UserResource::collection($users)->response();
    }
}
