<?php

namespace Modules\KnowledgeBase\Http\Middleware;

use App\Mailbox;
use Closure;

class CustomDomain
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $host = $request->getHttpHost();

        // If mailbox has Custom Domain, but KB accessed on a regular domain.
        if ($host == parse_url(config('app.url'), PHP_URL_HOST) /*&& \Kb::isKb()*/) {
            $mailbox_id = \Kb::getMailboxIdFromUrl(\Request::url());
            if ($mailbox_id) {
                $mailbox = Mailbox::find($mailbox_id);
                if ($mailbox && !empty($mailbox->meta['kb']['domain'])) {
                    // Redirect to this mailbox's KB.
                    return redirect(\Kb::getKbUrl($mailbox));
                }
            }
        }

        // To avoid CORS errors for fonts.
        $prev_host = parse_url(config('minify.config.css_url_path'), PHP_URL_HOST);
        \Config::set('minify.config.css_url_path', str_replace('://'.$prev_host, '://'.$host, config('minify.config.css_url_path')));
        \Config::set('minify.config.js_url_path', str_replace('://'.$prev_host, '://'.$host, config('minify.config.js_url_path')));

        return $next($request);
    }
}
