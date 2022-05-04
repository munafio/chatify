{{-- -------------------- The default card (white) -------------------- --}}
@if($viewType == 'default')
    @if($from_id != $to_id)
    <div class="message-card" data-id="{{ $id }}">
        <p>{!! ($message == null && $attachment != null && @$attachment[2] != 'file') ? $attachment[1] : nl2br($message) !!}
            <sub title="{{ $fullTime }}">{{ $time }}</sub>
            {{-- If attachment is a file --}}
            @if(@$attachment[2] == 'file')
            <a href="{{ route(config('chatify.attachments.download_route_name'), ['fileName'=>$attachment[0]]) }}" style="color: #595959;" class="file-download">
                <span class="fas fa-file"></span> {{$attachment[1]}}</a>
            @endif
        </p>
        {{-- If attachment is an image --}}
        @if(@$attachment[2] == 'image')
        <div class="image-file chat-image" style="width: 250px; height: 150px;background-image: url('{{ Chatify::getAttachmentUrl($attachment[0]) }}')">
        </div>
        @endif
    </div>
    @endif
@endif

{{-- -------------------- Sender card (owner) -------------------- --}}
@if($viewType == 'sender')
    <div class="message-card mc-sender" title="{{ $fullTime }}" data-id="{{ $id }}">
        <div class="chatify-d-flex chatify-align-items-center" style="flex-direction: row-reverse; justify-content: flex-end;">
            <i class="fas fa-trash chatify-hover-delete-btn" data-id="{{ $id }}"></i>
            <p style="margin-left: 5px;">
                {!! ($message == null && $attachment != null && @$attachment[2] != 'file') ? $attachment[1] : nl2br($message) !!}
                <sub title="{{ $fullTime }}" class="message-time">
                    <span class="fas fa-{{ $seen > 0 ? 'check-double' : 'check' }} seen"></span> {{ $time }}</sub>
                </sub>
                {{-- If attachment is a file --}}
                @if(@$attachment[2] == 'file')
                <a href="{{ route(config('chatify.attachments.download_route_name'), ['fileName'=>$attachment[0]]) }}" class="file-download">
                    <span class="fas fa-file"></span> {{$attachment[1]}}</a>
                @endif
            </p>
        </div>
        {{-- If attachment is an image --}}
        @if(@$attachment[2] == 'image')
        <div class="image-file chat-image" style="margin-top:10px;width: 250px; height: 150px;background-image: url('{{ Chatify::getAttachmentUrl($attachment[0]) }}')">
        </div>
        @endif
    </div>
@endif
