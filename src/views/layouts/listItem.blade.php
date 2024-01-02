{{-- -------------------- Saved Messages -------------------- --}}
@if($get == 'saved')
    <table
        class="messenger-list-item {{Auth::user()->channel_id ? 'contact-item' :'search-item'}}"
        data-channel="{{ Auth::user()->channel_id }}"
        data-user="{{ Auth::user()->id }}"
    >
        <tr data-action="0">
            {{-- Avatar side --}}
            <td>
            <div class="saved-messages avatar av-m">
                <span class="far fa-bookmark"></span>
            </div>
            </td>
            {{-- center side --}}
            <td>
                <p>Saved Messages <span>You</span></p>
                <span>Save messages secretly</span>
            </td>
        </tr>
    </table>
@endif

{{-- -------------------- Contact User -------------------- --}}
@if($get == 'contact-user' && !!$lastMessage)
<?php
$lastMessageBody = mb_convert_encoding($lastMessage->body, 'UTF-8', 'UTF-8');
$lastMessageBody = strlen($lastMessageBody) > 30 ? mb_substr($lastMessageBody, 0, 30, 'UTF-8').'..' : $lastMessageBody;
?>
<table class="messenger-list-item contact-item" data-channel="{{ $channel->id }}">
    <tr data-action="0">
        {{-- Avatar side --}}
        <td style="position: relative">
            @if($user->active_status)
                <span class="activeStatus"></span>
            @endif
        <div class="avatar av-m"
        style="background-image: url('{{ $user->avatar }}');">
        </div>
        </td>
        {{-- center side --}}
        <td>
        <p>
            {{ strlen($user->name) > 12 ? trim(substr($user->name,0,12)).'..' : $user->name }}
            <span class="contact-item-time" data-time="{{$lastMessage->created_at}}">{{ $lastMessage->timeAgo }}</span>
        </p>
        <span>
            {{-- Last Message user indicator --}}
            {!!
                $lastMessage->from_id == Auth::user()->id
                ? '<span class="lastMessageIndicator">You :</span>'
                : ''
            !!}
            {{-- Last message body --}}
            @if($lastMessage->attachment == null)
            {!!
                $lastMessageBody
            !!}
            @else
            <span class="fas fa-file"></span> Attachment
            @endif
        </span>
        {{-- New messages counter --}}
            {!! $unseenCounter > 0 ? "<b>".$unseenCounter."</b>" : '' !!}
        </td>
    </tr>
</table>
@endif

{{-- -------------------- Contact Group -------------------- --}}
@if($get == 'contact-group' && !!$lastMessage)
<?php
$lastMessageBody = mb_convert_encoding($lastMessage->body, 'UTF-8', 'UTF-8');
$lastMessageBody = strlen($lastMessageBody) > 30 ? mb_substr($lastMessageBody, 0, 30, 'UTF-8').'..' : $lastMessageBody;
?>
<table class="messenger-list-item contact-item" data-channel="{{ $channel->id }}">
    <tr data-action="0">
        {{-- Avatar side --}}
        <td style="position: relative">
            <div class="avatar av-m"
                 style="background-image: url('{{ $channel->avatar }}');">
            </div>
        </td>
        {{-- center side --}}
        <td>
            <p>
                {{ strlen($channel->name) > 12 ? trim(substr($channel->name,0,12)).'..' : $channel->name }}
                <span class="contact-item-time" data-time="{{$lastMessage->created_at}}">{{ $lastMessage->timeAgo }}</span>
            </p>
            <span>
                {{-- Last Message user indicator --}}
                {!!
                    $lastMessage->from_id == Auth::user()->id
                    ? '<span class="lastMessageIndicator">You :</span>'
                    : '<span class="lastMessageIndicator">'. $lastMessage->user_name .' :</span>'
                !!}
                {{-- Last message body --}}
                @if($lastMessage->attachment == null)
                    {!!
                        $lastMessageBody
                    !!}
                @else
                    <span class="fas fa-file"></span> Attachment
                @endif
            </span>
            {{-- New messages counter --}}
            {!! $unseenCounter > 0 ? "<b>".$unseenCounter."</b>" : '' !!}
        </td>
    </tr>
</table>
@endif

{{-- -------------------- Search Item -------------------- --}}
@if($get == 'search_item')
<table class="messenger-list-item search-item" data-user="{{ $user->id }}">
    <tr data-action="0">
        {{-- Avatar side --}}
        <td>
            <div class="avatar av-m"
            style="background-image: url('{{ $user->avatar }}');">
            </div>
        </td>
        {{-- center side --}}
        <td>
            <p>{{ strlen($user->name) > 12 ? trim(substr($user->name,0,12)).'..' : $user->name }}</p>
        </td>
    </tr>
</table>
@endif

{{-- -------------------- Modal Search Item -------------------- --}}
@if($get == 'user_search_item')
    <table class="user-list-item" data-user="{{ $user->id }}">
        <tr data-action="0">
            {{-- Avatar side --}}
            <td>
                <div class="avatar av-s"
                     style="background-image: url('{{ $user->avatar }}');">
                </div>
            </td>
            {{-- center side --}}
            <td>
                <p>{{ strlen($user->name) > 12 ? trim(substr($user->name,0,12)).'..' : $user->name }}</p>
            </td>
        </tr>
    </table>
@endif

{{-- -------------------- Shared photos Item -------------------- --}}
@if($get == 'sharedPhoto')
<div class="shared-photo chat-image" style="background-image: url('{{ $image }}')"></div>
@endif


