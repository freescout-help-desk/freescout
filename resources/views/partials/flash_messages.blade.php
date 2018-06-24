@if (session('flash_success'))
    <div class="alert alert-success alert-floating">
        {{ session('flash_success') }}
    </div>
@endif
@if (session('flash_error'))
    <div class="alert alert-danger">
        {{ session('flash_error') }}
    </div>
@endif