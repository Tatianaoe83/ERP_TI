@foreach (session('flash_notification', collect())->toArray() as $message)
    @if ($message['overlay'])
        @include('flash::modal', [
            'modalClass' => 'flash-modal',
            'title'      => $message['title'],
            'body'       => $message['message']
        ])
    @else
        <div class="app-flash" hidden data-level="{{ $message['level'] }}">{!! $message['message'] !!}</div>
    @endif
@endforeach

{{ session()->forget('flash_notification') }}
