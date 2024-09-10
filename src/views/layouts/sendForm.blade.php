<div class="messenger-sendCard">
    <form id="message-form" method="POST" action="{{ route('send.message') }}" enctype="multipart/form-data" >
        @csrf
        <label><span class="fas fa-plus-circle"></span><input disabled='disabled' type="file" class="upload-attachment" name="file" accept=".{{implode(', .',config('chatify.attachments.allowed_images'))}}, .{{implode(', .',config('chatify.attachments.allowed_files'))}}" /></label>
        <button class="emoji-button"><span class="fas fa-smile"></span></button>
        <button id="microphone-button" class="microphone-button" data-id="{{ $id }}" accept=".{{implode(', .',config('chatify.attachments.allowed_voice_messages'))}}">
            <i class="fas fa-microphone"></i>
        </button>
        <textarea readonly='readonly' name="message" class="m-send app-scroll" placeholder="Type a message.."></textarea>
        <button disabled='disabled' class="send-button"><span class="fas fa-paper-plane"></span></button>
    </form>

    <!-- Recording Controls -->
 <div class="recording-ui" id="recording-ui" style="display:none;">
    <button class="trash-button" id="trash-button"><span class="fas fa-trash"></span></button>
    <div class="recording-slider-wrapper">
        <div class="recording-slider-content">
            <button class="stop-button" id="stop-button"><span class="fas fa-stop"></span></button>
            <div class="recording-progress-bar" id="recording-progress-bar"></div>
        </div>
        <div class="recording-time" id="recording-time">0:00</div>
    </div>
    <button class="send-record-button" id="send-record-button"><span class="fas fa-paper-plane"></span></button>

</div>
</div>