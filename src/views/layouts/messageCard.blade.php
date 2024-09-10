<?php
$seenIcon = (!!$seen ? 'check-double' : 'check');
$timeAndSeen = "<span data-time='$created_at' class='message-time'>
        ".($isSender ? "<span class='fas fa-$seenIcon' seen'></span>" : '' )." <span class='time'>$timeAgo</span>
    </span>";
?>

<div class="message-card @if($isSender) mc-sender @endif" data-id="{{ $id }}">
    {{-- Delete Message Button --}}
    @if ($isSender)
        <div class="actions">
            <i class="fas fa-trash delete-btn" data-id="{{ $id }}"></i>
        </div>
    @endif
    {{-- Card --}}
    <div class="message-card-content">
        @if (@$attachment->type == 'file' || $message)
            <div class="message">
                {!! ($message == null && $attachment != null && @$attachment->type != 'file') ? $attachment->title : nl2br($message) !!}
                {!! $timeAndSeen !!}
                {{-- If attachment is a file --}}
                @if(@$attachment->type == 'file')
                <a href="{{ route(config('chatify.attachments.download_route_name'), ['fileName'=>$attachment->file]) }}" class="file-download">
                    <span class="fas fa-file"></span> {{$attachment->title}}</a>
                @endif
            </div>
        @endif
        @if(@$attachment->type == 'image')
        <div class="image-wrapper" style="text-align: {{$isSender ? 'end' : 'start'}}">
            <div class="image-file chat-image" style="background-image: url(
            '{{ Chatify::getAttachmentUrl($attachment->file) }}')">
                <div>{{ $attachment->title }}</div>
            </div>
            <div style="margin-bottom:5px">
                {!! $timeAndSeen !!}
            </div>
        </div>
    @endif
    @if ($attachment->type == 'audio')
    <div class="audio-wrap" id="player-{{ $id }}" >
        <? $color = Auth::user()->messenger_color ?? 'blue'; ?>
        <div class="controls" style="  background-color: {{$isSender ?     $color : 'white'  }}">
            <button type="button" class="btn-toggle-play" data-player-id="{{ $id }}" >
                <i class="fa fa-play" style="  color: {{$isSender ?  'white' : 'grey' }}"></i>
            </button>

        <div id="waveform-{{ $id }}" class="waveform" data-audio-id="{{ $id }}" data-audio-url="{{ Chatify::getAttachmentUrl($attachment->file) }}"></div>
            <span class="duration" style="  color: {{$isSender ? 'white' : 'grey' }}">0:00</span>
        </div>
    </div>
    <div style="margin-bottom:5px">
        {!! $timeAndSeen !!}
    </div>

@endif
</div>
</div>

