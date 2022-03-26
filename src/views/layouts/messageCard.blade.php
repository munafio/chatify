{{-- -------------------- The default card (white) -------------------- --}}
@if($viewType == 'default')
    @if($from_id != $to_id)
    <div class="message-card" data-id="{{ $id }}">
        <p>{!! ($message == null && $attachment != null && @$attachment[2] != 'file') ? $attachment[1] : nl2br($message) !!}
            <sub title="{{ $fullTime }}">{{ $time }}</sub>
            {{-- If attachment is a file --}}
            @if(@$attachment[2] == 'file')
            <a href="{{ route(config('chatify.attachments.download_route_name'),['fileName'=>$attachment[0]]) }}" style="color: #595959;" class="file-download">
                <span class="fas fa-file"></span> {{$attachment[1]}}</a>
            @endif
        </p>
    </div>
    {{-- If attachment is an image --}}
    @if(@$attachment[2] == 'image')
    <div>
        <div class="message-card">
            <div class="image-file chat-image" style="width: 250px; height: 150px;background-image: url('{{ Storage::disk(config('chatify.disk_name'))->url(config('chatify.attachments.folder').'/'.$attachment[0]) }}')">
            </div>
        </div>
    </div>
    @endif
    @endif
@endif

{{-- -------------------- Sender card (owner) -------------------- --}}
@if($viewType == 'sender')
    <div data-message-id="{{ $id }}">
        <div class="message-card mc-sender" title="{{ $fullTime }}" data-id="{{ $id }}">
            <div class="chatify-d-flex chatify-align-items-center" style="flex-direction: row-reverse; justify-content: flex-end;">
                <i class="fas fa-trash chatify-hover-delete-btn" data-id="{{ $id }}"></i>
                @if($message == null && $attachment != null)
                    <p style="margin-left: 5px;">{{  @$attachment[1] }}</p>
                @elseif($message != null)
                    <p style="margin-left: 5px;">{{ nl2br($message) }}</p>
                @endif
            </div>
            <sub class="chatify-d-flex chatify-align-items-center message-time mb-2 @if($index !== 0) chatify-d-none @endif" data-message-time="{{ $id }}" style="direction: ltr; justify-content: flex-end">
                <span class="fas {{ $seen > 0 ? 'fa-check-double' : 'fa-check' }} seen" style="margin-right: 3px"></span> {{ $time }}
            </sub>
            {{-- If attachment is a file --}}
            @if(@$attachment[2] == 'file')
                <a href="{{ route(config('chatify.attachments.download_route_name'), ['fileName' => $attachment[0]]) }}"
                   class="file-download">
                    <span class="fas fa-file"></span> {{$attachment[1]}}</a>
            @endif
        </div>
        {{-- If attachment is an image --}}
        @if(@$attachment[2] == 'image')
            <div>
                <div class="message-card mc-sender">
                    <div class="image-file chat-image" style="width: 250px; height: 150px;background-image: url('{{ Storage::disk(config('chatify.disk_name'))->url(config('chatify.attachments.folder').'/'.$attachment[0]) }}')">
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
