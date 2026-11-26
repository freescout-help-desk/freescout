<form class="form-horizontal margin-top" method="POST" action="">
    {{ csrf_field() }}

    <h3 class="subheader">{{ __('Connected apps') }}</h3>
    <p class="text-help">
        {{ __('Connect your Linear, ClickUp, Asana, HubSpot and Google Sheets accounts. They are used for this help desk only.') }}
    </p>

    @if ($fastn_configured)
        <div id="fastn-hub" style="min-height: 560px; border: 1px solid #e3e8eb; border-radius: 4px; margin-bottom: 10px;"></div>
        <p id="fastn-hub-error" class="text-danger hidden"></p>
    @else
        <div class="alert alert-warning">
            {{ __('Integrations are not set up on this server yet. Ask whoever runs FreeScout to add FASTN_API_KEY to the .env file.') }}
        </div>
    @endif

    <h3 class="subheader">{{ __('When a new ticket comes in') }}</h3>

    <div class="form-group">
        <label for="fastn_send_new_tickets" class="col-sm-2 control-label">{{ __('Send new tickets') }}</label>
        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[fastn_send_new_tickets]" value="1" id="fastn_send_new_tickets" class="onoffswitch-checkbox" @if (old('settings.fastn_send_new_tickets', $settings['fastn_send_new_tickets']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="fastn_send_new_tickets"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {{ __('Every new ticket creates a Linear issue, creates or updates the customer in HubSpot, and adds a row to Google Sheets, for each app that is connected above.') }}
            </p>
            @if ($fastn_configured)
                <button type="button" class="btn btn-default btn-sm" id="fastn-test-btn">{{ __('Send test ticket') }}</button>
                <pre id="fastn-test-result" class="hidden" style="margin-top: 10px; white-space: pre-wrap;"></pre>
            @endif
        </div>
    </div>

    <div class="form-group margin-top">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </div>
    </div>
</form>

@if ($fastn_configured)
    <script src="{{ $fastn_api_url }}/api/v1/embed/assets/fastn-embed.min.js" {!! \Helper::cspNonceAttr() !!}></script>
    <script {!! \Helper::cspNonceAttr() !!}>
        (function () {
            var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function post(url) {
                return fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}
                }).then(function (r) {
                    return r.json().then(function (d) {
                        if (!r.ok) { throw new Error(d.error || ('HTTP ' + r.status)); }
                        return d;
                    });
                });
            }

            function showError(msg) {
                var el = document.getElementById('fastn-hub-error');
                el.textContent = msg;
                el.classList.remove('hidden');
            }

            try {
                var hub = FastnEmbed.createFastnEmbed({
                    baseUrl: @json($fastn_api_url),
                    // The token is minted by this FreeScout server; the API key never reaches the browser.
                    getToken: function () {
                        return post(@json(route('fastnintegrations.token'))).then(function (d) { return d.token; });
                    },
                    view: 'hub'
                });
                hub.on('error', function (e) { showError((e && e.message) ? e.message : 'fastn error'); });
                hub.mount('#fastn-hub');
            } catch (e) {
                showError(e.message);
            }

            var btn = document.getElementById('fastn-test-btn');
            if (btn) {
                btn.addEventListener('click', function () {
                    var out = document.getElementById('fastn-test-result');
                    btn.disabled = true;
                    out.classList.remove('hidden');
                    out.textContent = @json(__('Sending...'));
                    post(@json(route('fastnintegrations.test'))).then(function (d) {
                        var lines = [];
                        Object.keys(d.results).forEach(function (k) {
                            var r = d.results[k];
                            if (r.skipped) {
                                lines.push(k + ': not switched on');
                            } else {
                                lines.push(k + ': ' + (r.ok ? 'sent' : 'failed - ' + r.error));
                            }
                        });
                        out.textContent = lines.join('\n');
                    }).catch(function (e) {
                        out.textContent = e.message;
                    }).then(function () { btn.disabled = false; });
                });
            }
        })();
    </script>
@endif
