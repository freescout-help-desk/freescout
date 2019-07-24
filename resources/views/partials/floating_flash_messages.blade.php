@if (session('flash_success_floating'))
    @section('body_bottom')
        <div class="alert alert-success alert-floating">
            <div><i class="glyphicon glyphicon-ok"></i>{!! session('flash_success_floating') !!}</div>
        </div>
    @endsection
@endif
@if (session('flash_warning_floating'))
    @section('body_bottom')
        <div class="alert alert-warning alert-floating">
            <div>{!! session('flash_warning_floating') !!}</div>
        </div>
    @endsection
@endif
@if (session('flash_error_floating'))
    @section('body_bottom')
        <div class="alert alert-danger alert-floating">
            <div><i class="glyphicon glyphicon-exclamation-sign"></i>{!! session('flash_error_floating') !!}</div>
        </div>
    @endsection
@endif

@if (!empty(session('flashes_floating')) && is_array(session('flashes_floating')))
    @foreach (session('flashes_floating') as $flash)
        @if (!empty($flash['text']) && (empty($flash['role']) || \App\User::checkRole($flash['role'])))
            <div class="alert alert-{{ $flash['type'] }} alert-floating alert-noautohide">
                <div>
                    @if (!empty($flash['unescaped']))
                        {!! $flash['text'] !!}
                    @else
                        {{ $flash['text'] }}
                    @endif
                </div>
            </div>
        @endif
    @endforeach
    {{-- Flashes set in service provider may not be removed --}}
    @php
        Session::forget('flashes_floating');
    @endphp
@endif