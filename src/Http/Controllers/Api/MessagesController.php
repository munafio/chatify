<?php

namespace Chatify\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use App\Models\ChMessage as Message;
use App\Models\ChFavorite as Favorite;
use Chatify\Facades\ChatifyMessenger as Chatify;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class MessagesController extends Controller
{
    protected $perPage = 30;

    /**
     * Authinticate the connection for pusher
     *
     * @param Request $request
     * @return void
     */
    public function pusherAuth(Request $request)
    {
        return Chatify::pusherAuth(
            $request->user(),
            Auth::user(),
            $request['channel_name'],
            $request['socket_id']
        );
    }

    /**
     * Fetch data by id for (user/group)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function idFetchData(Request $request)
    {
        return auth()->user();
        // Favorite
        $favorite = Chatify::inFavorite($request['id']);

        // User data
        if ($request['type'] == 'user') {
            $fetch = User::where('id', $request['id'])->first();
            if ($fetch) {
                $userAvatar = Chatify::getUserWithAvatar($fetch)->avatar;
            }
        }

        // send the response
        return Response::json([
            'favorite' => $favorite,
            'fetch' => $fetch ?? null,
            'user_avatar' => $userAvatar ?? null,
        ]);
    }

    /**
     * This method to make a links for the attachments
     * to be downloadable.
     *
     * @param string $fileName
     * @return \Illuminate\Http\JsonResponse
     */
    public function download($fileName)
    {
        $path = config('chatify.attachments.folder') . '/' . $fileName;
        if (Chatify::storage()->exists($path)) {
            return response()->json([
                'file_name' => $fileName,
                'download_path' => Chatify::storage()->url($path)
            ], 200);
        } else {
            return response()->json([
                'message' => "Sorry, File does not exist in our server or may have been deleted!"
            ], 404);
        }
    }

    /**
     * Send a message to database
     *
     * @param Request $request
     * @return JSON response
     */
    public function send(Request $request)
    {
        // default variables
        $error = (object)[
            'status' => 0,
            'message' => null
        ];
        $attachment = null;
        $attachment_title = null;

        // if there is attachment [file]
        if ($request->hasFile('file')) {
            // allowed extensions
            $allowed_images = Chatify::getAllowedImages();
            $allowed_files  = Chatify::getAllowedFiles();
            $allowed        = array_merge($allowed_images, $allowed_files);

            $file = $request->file('file');
            // check file size
            if ($file->getSize() < Chatify::getMaxUploadSize()) {
                if (in_array(strtolower($file->extension()), $allowed)) {
                    // get attachment name
                    $attachment_title = $file->getClientOriginalName();
                    // upload attachment and store the new name
                    $attachment = Str::uuid() . "." . $file->extension();
                    $file->storeAs(config('chatify.attachments.folder'), $attachment, config('chatify.storage_disk_name'));
                } else {
                    $error->status = 1;
                    $error->message = "File extension not allowed!";
                }
            } else {
                $error->status = 1;
                $error->message = "File size you are trying to upload is too large!";
            }
        }

        if (!$error->status) {
            // send to database
            $message = Chatify::newMessage([
                'type' => $request['type'],
                'from_id' => Auth::user()->id,
                'to_id' => $request['id'],
                'body' => htmlentities(trim($request['message']), ENT_QUOTES, 'UTF-8'),
                'attachment' => ($attachment) ? json_encode((object)[
                    'new_name' => $attachment,
                    'old_name' => htmlentities(trim($attachment_title), ENT_QUOTES, 'UTF-8'),
                ]) : null,
            ]);

            // fetch message to send it with the response
            $messageData = Chatify::parseMessage($message);

            // send to user using pusher
            if (Auth::user()->id != $request['id']) {
                Chatify::push("private-chatify." . $request['id'], 'messaging', [
                    'from_id' => Auth::user()->id,
                    'to_id' => $request['id'],
                    // 'message' => $messageData
                    'message' => Chatify::messageCard(@$messageData, true, false),
                ]);
            }
        }

        // send the response
        return Response::json([
            'status' => '200',
            'error' => $error,
            'message' => $messageData ?? [],
            'tempID' => $request['temporaryMsgId'],
        ]);
    }

    /**
     * fetch [user/group] messages from database
     *
     * @param Request $request
     * @return JSON response
     */
    public function fetch(Request $request)
    {
        $query = Chatify::fetchMessagesQuery($request['id'])->latest();
        $messages = $query->paginate($request->per_page ?? $this->perPage);
        $totalMessages = $messages->total();
        $lastPage = $messages->lastPage();
        $response = [
            'total' => $totalMessages,
            'last_page' => $lastPage,
            'last_message_id' => collect($messages->items())->last()->id ?? null,
            'messages' => $messages->items(),
        ];
        return Response::json($response);
    }

    /**
     * Make messages as seen
     *
     * @param Request $request
     * @return void
     */
    public function seen(Request $request)
    {
        // make as seen
        $seen = Chatify::makeSeen($request['id']);
        // send the response
        return Response::json([
            'status' => $seen,
        ], 200);
    }

    /**
     * Get contacts list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse response
     */
    public function getContacts(Request $request)
    {
        // get all users that received/sent message from/to [Auth user]
        $users = Message::join('users',  function ($join) {
            $join->on('ch_messages.from_id', '=', 'users.id')
                ->orOn('ch_messages.to_id', '=', 'users.id');
        })
            ->where(function ($q) {
                $q->where('ch_messages.from_id', Auth::user()->id)
                    ->orWhere('ch_messages.to_id', Auth::user()->id);
            })
            ->where('users.id', '!=', Auth::user()->id)
            ->select('users.*', DB::raw('MAX(ch_messages.created_at) max_created_at'))
            ->orderBy('max_created_at', 'desc')
            ->groupBy('users.id')
            ->paginate($request->per_page ?? $this->perPage);

        return response()->json([
            'contacts' => $users->items(),
            'total' => $users->total() ?? 0,
            'last_page' => $users->lastPage() ?? 1,
        ], 200);
    }

    /**
     * Put a user in the favorites list
     *
     * @param Request $request
     * @return void
     */
    public function favorite(Request $request)
    {
        $userId = $request['user_id'];
        // check action [star/unstar]
        $favoriteStatus = Chatify::inFavorite($userId) ? 0 : 1;
        Chatify::makeInFavorite($userId, $favoriteStatus);

        // send the response
        return Response::json([
            'status' => @$favoriteStatus,
        ], 200);
    }

    /**
     * Get favorites list
     *
     * @param Request $request
     * @return void
     */
    public function getFavorites(Request $request)
    {
        $favorites = Favorite::where('user_id', Auth::user()->id)->get();
        foreach ($favorites as $favorite) {
            $favorite->user = User::where('id', $favorite->favorite_id)->first();
        }
        return Response::json([
            'total' => count($favorites),
            'favorites' => $favorites ?? [],
        ], 200);
    }

    /**
     * Search in messenger
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $input = trim(filter_var($request['input']));
        $records = User::where('id', '!=', Auth::user()->id)
            ->where('name', 'LIKE', "%{$input}%")
            ->paginate($request->per_page ?? $this->perPage);

        foreach ($records->items() as $index => $record) {
            $records[$index] += Chatify::getUserWithAvatar($record);
        }

        return Response::json([
            'records' => $records->items(),
            'total' => $records->total(),
            'last_page' => $records->lastPage()
        ], 200);
    }

    /**
     * Get shared photos
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sharedPhotos(Request $request)
    {
        $images = Chatify::getSharedPhotos($request['user_id']);

        foreach ($images as $image) {
            $image = asset(config('chatify.attachments.folder') . $image);
        }
        // send the response
        return Response::json([
            'shared' => $images ?? [],
        ], 200);
    }

    /**
     * Delete conversation
     *
     * @param Request $request
     * @return void
     */
    public function deleteConversation(Request $request)
    {
        // delete
        $delete = Chatify::deleteConversation($request['id']);

        // send the response
        return Response::json([
            'deleted' => $delete ? 1 : 0,
        ], 200);
    }

    public function updateSettings(Request $request)
    {
        $msg = null;
        $error = $success = 0;

        // dark mode
        if ($request['dark_mode']) {
            $request['dark_mode'] == "dark"
                ? User::where('id', Auth::user()->id)->update(['dark_mode' => 1])  // Make Dark
                : User::where('id', Auth::user()->id)->update(['dark_mode' => 0]); // Make Light
        }

        // If messenger color selected
        if ($request['messengerColor']) {
            $messenger_color = trim(filter_var($request['messengerColor']));
            User::where('id', Auth::user()->id)
                ->update(['messenger_color' => $messenger_color]);
        }
        // if there is a [file]
        if ($request->hasFile('avatar')) {
            // allowed extensions
            $allowed_images = Chatify::getAllowedImages();

            $file = $request->file('avatar');
            // check file size
            if ($file->getSize() < Chatify::getMaxUploadSize()) {
                if (in_array(strtolower($file->extension()), $allowed_images)) {
                    // delete the older one
                    if (Auth::user()->avatar != config('chatify.user_avatar.default')) {
                        $path = Chatify::getUserAvatarUrl(Auth::user()->avatar);
                        if (Chatify::storage()->exists($path)) {
                            Chatify::storage()->delete($path);
                        }
                    }
                    // upload
                    $avatar = Str::uuid() . "." . $file->extension();
                    $update = User::where('id', Auth::user()->id)->update(['avatar' => $avatar]);
                    $file->storeAs(config('chatify.user_avatar.folder'), $avatar, config('chatify.storage_disk_name'));
                    $success = $update ? 1 : 0;
                } else {
                    $msg = "File extension not allowed!";
                    $error = 1;
                }
            } else {
                $msg = "File size you are trying to upload is too large!";
                $error = 1;
            }
        }

        // send the response
        return Response::json([
            'status' => $success ? 1 : 0,
            'error' => $error ? 1 : 0,
            'message' => $error ? $msg : 0,
        ], 200);
    }

    /**
     * Set user's active status
     *
     * @param Request $request
     * @return void
     */
    public function setActiveStatus(Request $request)
    {
        $activeStatus = $request['status'] > 0 ? 1 : 0;
        $status = User::where('id', Auth::user()->id)->update(['active_status' => $activeStatus]);
        return Response::json([
            'status' => $status,
        ], 200);
    }
}
