<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Chatify\Services\PresenceService;

class PresenceController extends Controller
{
    public function heartbeat(Request $request, PresenceService $presenceService): JsonResponse
    {
        $online = $presenceService->heartbeat($request->user());

        return response()->json(['data' => ['online' => $online]]);
    }

    public function offline(Request $request, PresenceService $presenceService): JsonResponse
    {
        $presenceService->markOffline($request->user());

        return response()->json(['data' => ['online' => false]]);
    }
}
