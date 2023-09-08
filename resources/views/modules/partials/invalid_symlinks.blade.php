<div class="alert alert-danger">
    <strong>{{ __('Invalid or missing modules symlinks') }}</strong> (<a href="https://github.com/freescout-helpdesk/freescout/wiki/FreeScout-Modules#-invalid-or-missing-modules-symlinks-error" target="_blank">{{ __('read more') }}</a>)
    <ul>
        @foreach ($invalid_symlinks as $invalid_symlink_from => $invalid_symlinks_to)
            <li>{{ $invalid_symlink_from }} -&gt; {{ $invalid_symlinks_to }}</li>
        @endforeach
    </ul>
</div>