<?php
    $isGroup = isset($channel->owner_id);
?>
<nav>
    <p>{{isset($channel->owner_id) ? 'Group Details' : 'User Details'}}</p>
    <a href="#"><i class="fas fa-times"></i></a>
</nav>

<div class="avatar avatar-channel av-l chatify-d-flex"></div>
<p class="info-name">{{ config('chatify.name') }}</p>
@if($isGroup)
    <div style="max-width: 250px; margin: auto">
        <h4 style="text-align: center; margin-bottom: 10px; margin-top: 30px; font-weight: normal; font-size: 14px">Users in this group</h4>
        <div class="app-scroll users-list">
            @foreach($channel->users as $user)
                {!! view('Chatify::layouts.listItem', ['get' => 'user_search_item', 'user' => Chatify::getUserWithAvatar($user)])->render() !!}
            @endforeach
        </div>
    </div>
@endif

<div class="messenger-infoView-btns">
    @if($isGroup && $channel && $channel->owner_id === Auth::user()->id)
        <a href="#" class="danger delete-group">Delete Group</a>
    @elseif($isGroup)
        <a href="#" class="danger leave-group">Leave Group</a>
    @else
        <a href="#" class="danger delete-conversation">Delete Conversation</a>
    @endif
</div>

{{-- shared photos --}}
<div class="messenger-infoView-shared">
    <p class="messenger-title"><span>Shared Photos</span></p>
    <div class="shared-photos-list"></div>
</div>
