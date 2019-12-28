<?php

namespace Chatify\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Chatify\Http\Models\Message;
use Chatify\Http\Models\Favorite;
use Chatify;
use App\User;
use Auth;
use Str;


class MessagesController extends Controller
{
    /**
     * Authinticate the connection for pusher
     *
     * @param Request $request
     * @return void
     */
    public function pusherAuth(Request $request)
    {
        // Auth data
        $authData = json_encode([
            'user_id' => Auth::user()->id,
            'user_info' => [
                'name' => Auth::user()->name
            ]
        ]);
        // check if user authorized
        if (Auth::check()) {
            return Chatify::pusherAuth(
                $request['channel_name'],
                $request['socket_id'],
                $authData
            );
        }
        // if not authorized
        return new Response('Unauthorized', 401);
    }

    /**
     * Returning the view of the app with the required data.
     *
     * @param int $id
     * @return void
     */
    public function index($id = null)
    {
        // get current route
        $route = (in_array(\Request::route()->getName(), ['user', config('chatify.path')]))
            ? 'user'
            : \Request::route()->getName();

        // prepare id
        return view('Chatify::pages.app', [
            'id' => ($id == null) ? 0 : $route . '_' . $id,
            'route' => $route,
            'messengerColor' => Auth::user()->messenger_color,
            'dark_mode' => Auth::user()->dark_mode < 1 ? 'light' : 'dark',
        ]);
    }


    /**
     * Fetch data by id for (user/group)
     *
     * @param Request $request
     * @return collection
     */
    public function idFetchData(Request $request)
    {
        // Favorite
        $favorite = Chatify::inFavorite($request['id']);

        // User data
        if ($request['type'] == 'user') {
            $fetch = User::where('id', $request['id'])->first();
        }

        // send the response
        return Response::json([
            'favorite' => $favorite,
            'fetch' => $fetch,
            'user_avatar' => asset('/storage/' . config('chatify.user_avatar.folder') . '/' . $fetch->avatar),
        ]);
    }

    /**
     * This method to make a links for the attachments
     * to be downloadable.
     *
     * @param string $fileName
     * @return void
     */
    public function download($fileName)
    {
        $path = storage_path() . '/app/public/' . config('chatify.attachments.folder') . '/' . $fileName;
        if (file_exists($path)) {
            return \Response::download($path, $fileName);
        } else {
            return abort(404, "Sorry, File does not exist in our server or may have been deleted!");
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
        $error_msg = $attachment = $attachment_title = null;

        // if there is attachment [file]
        if ($request->hasFile('file')) {
            // allowed extensions
            $allowed_images = Chatify::getAllowedImages();
            $allowed_files  = Chatify::getAllowedFiles();
            $allowed        = array_merge($allowed_images, $allowed_files);

            $file = $request->file('file');
            // if size less than 150MB
            if ($file->getSize() < 150000000) {
                if (in_array($file->getClientOriginalExtension(), $allowed)) {
                    // get attachment name
                    $attachment_title = $file->getClientOriginalName();
                    // upload attachment and store the new name
                    $attachment = Str::uuid() . "." . $file->getClientOriginalExtension();
                    $file->storeAs("public/" . config('chatify.attachments.folder'), $attachment);
                } else {
                    $error_msg = "File extension not allowed!";
                }
            } else {
                $error_msg = "File size is too long!";
            }
        }

        if (!$error_msg) {
            // send to database
            $messageID = mt_rand(9, 999999999) + time();
            Chatify::newMessage([
                'id' => $messageID,
                'type' => $request['type'],
                'from_id' => Auth::user()->id,
                'to_id' => $request['id'],
                'body' => trim(htmlentities($request['message'])),
                'attachment' => ($attachment) ? $attachment . ',' . $attachment_title : null,
            ]);

            // fetch message to send it with the response
            $messageData = Chatify::fetchMessage($messageID);

            // send to user using pusher
            Chatify::push('private-chatify', 'messaging', [
                'from_id' => Auth::user()->id,
                'to_id' => $request['id'],
                'message' => Chatify::messageCard($messageData, 'default')
            ]);
        }

        // send the response
        return Response::json([
            'status' => '200',
            'error' => $error_msg ? 1 : 0,
            'error_msg' => $error_msg,
            'message' => Chatify::messageCard(@$messageData),
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
        // messages variable
        $allMessages = null;

        // fetch messages
        $query = Chatify::fetchMessagesQuery($request['id'])->orderBy('created_at', 'asc');
        $messages = $query->get();

        // if there is a messages
        if ($query->count() > 0) {
            foreach ($messages as $message) {
                $allMessages .= Chatify::messageCard(
                    Chatify::fetchMessage($message->id)
                );
            }
            // send the response
            return Response::json([
                'count' => $query->count(),
                'messages' => $allMessages,
            ]);
        }
        // send the response
        return Response::json([
            'count' => $query->count(),
            'messages' => '<p class="message-hint"><span>Say \'hi\' and start messaging</span></p>',
        ]);
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
     * @return JSON response
     */
    public function getContacts(Request $request)
    {
        // get all users that received/sent message from/to [Auth user]
        $users = Message::join('users',  function ($join) {
            $join->on('messages.from_id', '=', 'users.id')
                ->orOn('messages.to_id', '=', 'users.id');
        })
            ->where('messages.from_id', Auth::user()->id)
            ->orWhere('messages.to_id', Auth::user()->id)
            ->orderBy('messages.created_at', 'desc')
            ->get()
            ->unique('id');

        if ($users->count() > 0) {
            // fetch contacts
            $contacts = null;
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    // Get user data
                    $userCollection = User::where('id', $user->id)->first();
                    $contacts .= Chatify::getContactItem($request['messenger_id'], $userCollection);
                }
            }
        }

        // send the response
        return Response::json([
            'contacts' => $users->count() > 0 ? $contacts : '<br><p class="message-hint"><span>Your contatct list is empty</span></p>',
        ], 200);
    }

    /**
     * Update user's list item data
     *
     * @param Request $request
     * @return JSON response
     */
    public function updateContactItem(Request $request)
    {
        // Get user data
        $userCollection = User::where('id', $request['user_id'])->first();
        $contactItem = Chatify::getContactItem($request['messenger_id'], $userCollection);

        // send the response
        return Response::json([
            'contactItem' => $contactItem,
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
        // check action [star/unstar]
        if (Chatify::inFavorite($request['user_id'])) {
            // UnStar
            Chatify::makeInFavorite($request['user_id'], 0);
            $status = 0;
        } else {
            // Star
            Chatify::makeInFavorite($request['user_id'], 1);
            $status = 1;
        }

        // send the response
        return Response::json([
            'status' => @$status,
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
        $favoritesList = null;
        $favorites = Favorite::where('user_id', Auth::user()->id);
        foreach ($favorites->get() as $favorite) {
            // get user data
            $user = User::where('id', $favorite->favorite_id)->first();
            $favoritesList .= view('Chatify::layouts.favorite', [
                'user' => $user,
            ]);
        }
        // send the response
        return Response::json([
            'favorites' => $favorites->count() > 0
                ? $favoritesList
                : '<p class="message-hint"><span>Your favorite list is empty</span></p>',
        ], 200);
    }

    /**
     * Search in messenger
     *
     * @param Request $request
     * @return void
     */
    public function search(Request $request)
    {
        $getRecords = null;
        $input = trim(filter_var($request['input'], FILTER_SANITIZE_STRING));
        $records = User::where('name', 'LIKE', "%{$input}%");
        foreach ($records->get() as $record) {
            $getRecords .= view('Chatify::layouts.listItem', [
                'get' => 'search_item',
                'type' => 'user',
                'user' => $record,
            ])->render();
        }
        // send the response
        return Response::json([
            'records' => $records->count() > 0
                ? $getRecords
                : '<p class="message-hint"><span>Nothing to show.</span></p>',
            'addData' => 'html'
        ], 200);
    }

    /**
     * Get shared photos 
     *
     * @param Request $request
     * @return void
     */
    public function sharedPhotos(Request $request)
    {
        $shared = Chatify::getSharedPhotos($request['user_id']);
        $sharedPhotos = null;

        // shared with its template
        for ($i = 0; $i < count($shared); $i++) {
            $sharedPhotos .= view('Chatify::layouts.listItem', [
                'get' => 'sharedPhoto',
                'image' => asset('storage/attachments/' . $shared[$i]),
            ])->render();
        }
        // send the response
        return Response::json([
            'shared' => count($shared) > 0 ? $sharedPhotos : '<p class="message-hint"><span>Nothing shared yet</span></p>',
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

            $messenger_color = explode('-', trim(filter_var($request['messengerColor'], FILTER_SANITIZE_STRING)));
            $messenger_color = Chatify::getMessengerColors()[$messenger_color[1]];
            User::where('id', Auth::user()->id)
                ->update(['messenger_color' => $messenger_color]);
        }
        // if there is a [file]
        if ($request->hasFile('avatar')) {
            // allowed extensions
            $allowed_images = Chatify::getAllowedImages();

            $file = $request->file('avatar');
            // if size less than 150MB
            if ($file->getSize() < 150000000) {
                if (in_array($file->getClientOriginalExtension(), $allowed_images)) {
                    // delete the older one
                    if (Auth::user()->avatar != config('chatify.user_avatar.default')) {
                        $path = storage_path('app/public/' . config('chatify.user_avatar.folder') . '/' . Auth::user()->avatar);
                        if (file_exists($path)) {
                            @unlink($path);
                        }
                    }
                    // upload
                    $avatar = Str::uuid() . "." . $file->getClientOriginalExtension();
                    $update = User::where('id', Auth::user()->id)->update(['avatar' => $avatar]);
                    $file->storeAs("public/" . config('chatify.user_avatar.folder'), $avatar);
                    $success = $update ? 1 : 0;
                } else {
                    $msg = "File extension not allowed!";
                    $error = 1;
                }
            } else {
                $msg = "File extension not allowed!";
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
        $update = $request['status'] > 0
            ? User::where('id', $request['user_id'])->update(['active_status' => 1])
            : User::where('id', $request['user_id'])->update(['active_status' => 0]);
        // send the response
        return Response::json([
            'status' => $update,
        ], 200);
    }
}
