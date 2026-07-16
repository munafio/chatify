@extends(config('chatify.web.layout', 'Chatify::layouts.app'))

@push('styles')
    <link rel="stylesheet" href="{{ $assetCss }}">
@endpush

@section('content')
    <div id="chatify-app" data-config='@json($chatifyBoot)'></div>
    <script src="{{ $assetJs }}" defer></script>
@endsection
