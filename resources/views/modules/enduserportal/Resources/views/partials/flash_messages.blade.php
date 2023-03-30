@if (session(EUP_MODULE.'.flash_success'))
    <div class="alert alert-success">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session(EUP_MODULE.'.flash_success') }}
    </div>
@endif
@if (session(EUP_MODULE.'.flash_success_unescaped'))
    <div class="alert alert-success">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {!! session(EUP_MODULE.'.flash_success_unescaped') !!}
    </div>
@endif
@if (session(EUP_MODULE.'.flash_warning'))
    <div class="alert alert-warning">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session(EUP_MODULE.'.flash_warning') }}
    </div>
@endif
@if (session(EUP_MODULE.'.flash_error'))
    <div class="alert alert-danger">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session(EUP_MODULE.'.flash_error') }}
    </div>
@endif
@if (session(EUP_MODULE.'.flash_error_unescaped'))
    <div class="alert alert-danger">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {!! session(EUP_MODULE.'.flash_error_unescaped') !!}
    </div>
@endif