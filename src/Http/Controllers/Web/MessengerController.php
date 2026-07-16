<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Web;

use Chatify\Support\ChatifyBootData;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class MessengerController extends Controller
{
    public function index(Request $request, ?string $conversationId = null): View
    {
        if ($conversationId !== null && ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $conversationId)) {
            abort(404);
        }

        $assetUrl = config('chatify.frontend.asset_url');
        $assetBase = $assetUrl ?: asset('vendor/chatify');

        return view('Chatify::pages.app', [
            'chatifyBoot' => ChatifyBootData::fromRequest($request, $conversationId),
            'assetJs' => rtrim($assetBase, '/').'/chatify.js',
            'assetCss' => rtrim($assetBase, '/').'/chatify.css',
        ]);
    }
}
