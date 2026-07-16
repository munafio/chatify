<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Broadcast;

class BroadcastAuthController extends Controller
{
    public function __invoke(Request $request)
    {
        return Broadcast::auth($request);
    }
}
