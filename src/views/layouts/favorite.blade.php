<div class="favorite-list-item">
    @if($data)
        <div data-channel="{{ $channel_id }}" data-action="0" class="avatar av-m"
            style="background-image: url('{{ $data->avatar }}');">
        </div>
        <p>{{ strlen($data->name) > 5 ? substr($data->name,0,6).'..' : $data->name }}</p>
    @endif
</div>
