<?php

namespace Modules\FastnIntegrations\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\FastnIntegrations\Services\FastnClient;

define('FASTN_INTEGRATIONS_MODULE', 'fastnintegrations');

class FastnIntegrationsServiceProvider extends ServiceProvider
{
    const SECTION = 'integrations';

    protected $defer = false;

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->hooks();
    }

    public function register()
    {
    }

    public function hooks()
    {
        // Settings > Integrations.
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections[self::SECTION] = ['title' => __('Integrations'), 'icon' => 'link', 'order' => 350];

            return $sections;
        }, 30);

        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {
            if ($section != self::SECTION) {
                return $settings;
            }

            // The only thing an admin sets here. Everything else is server config (.env).
            return [
                'fastn_send_new_tickets' => \Option::get('fastn_send_new_tickets', true),
            ];
        }, 20, 2);

        \Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section != self::SECTION) {
                return $params;
            }

            return [
                'template_vars' => [
                    'fastn_configured' => FastnClient::isConfigured(),
                    'fastn_api_url'    => FastnClient::apiUrl(),
                ],
                'settings' => [
                    'fastn_send_new_tickets' => [
                        'default' => true,
                    ],
                ],
            ];
        }, 20, 2);

        \Eventy::addFilter('settings.view', function ($view, $section) {
            if ($section != self::SECTION) {
                return $view;
            }

            return 'fastnintegrations::settings';
        }, 20, 2);

        // Let the page load fastn's embed script and call its API.
        \Eventy::addFilter('csp.script_src', function ($value) {
            return $value.' '.FastnClient::apiUrl();
        });

        // New ticket from a customer (email in, or the end-user portal).
        \Eventy::addAction('conversation.created_by_customer', function ($conversation, $thread, $customer) {
            FastnClient::sendNewTicket($conversation);
        }, 20, 3);

        // New ticket started by an agent. FreeScout already runs this in the background.
        \Eventy::addAction('conversation.created_by_user', function ($conversation, $thread) {
            FastnClient::sendNewTicket($conversation);
        }, 20, 2);

        // Ticket updates. Each is queued so the agent never waits for fastn.
        \Eventy::addAction('conversation.status_changed', function ($conversation, $user, $changedOnReply, $prevStatus) {
            self::queue($conversation, 'ticket.status_changed', [
                'by'         => self::who($user),
                'prevStatus' => \App\Conversation::statusCodeToName($prevStatus),
            ], 'status:'.$conversation->status.':'.time());
        }, 20, 4);

        \Eventy::addAction('conversation.user_replied', function ($conversation, $thread) {
            self::queue($conversation, 'ticket.replied', [
                'by'         => self::who($thread->created_by_user),
                'byCustomer' => false,
                'text'       => self::text($thread->body),
            ], 'thread:'.$thread->id);
        }, 20, 2);

        \Eventy::addAction('conversation.customer_replied', function ($conversation, $thread, $customer) {
            self::queue($conversation, 'ticket.replied', [
                'by'         => $customer ? (string) $customer->getFullName(true) : '',
                'byCustomer' => true,
                'text'       => self::text($thread->body),
            ], 'thread:'.$thread->id);
        }, 20, 3);

        \Eventy::addAction('conversation.note_added', function ($conversation, $thread) {
            self::queue($conversation, 'ticket.note_added', [
                'by'   => self::who($thread->created_by_user),
                'text' => self::text($thread->body),
            ], 'thread:'.$thread->id);
        }, 20, 2);

        \Eventy::addAction('conversation.user_changed', function ($conversation, $user, $prevUserId) {
            self::queue($conversation, 'ticket.assigned', [
                'by'       => self::who($user),
                'assignee' => $conversation->user ? (string) $conversation->user->getFullName() : '',
            ], 'assign:'.(int) $conversation->user_id.':'.time());
        }, 20, 3);

        // Two-way: every minute, ask fastn whether linked Linear issues changed.
        \Eventy::addFilter('schedule', function ($schedule) {
            $schedule->call(function () {
                FastnClient::runLinearCheck();
            })->everyMinute()->name('fastn-linear-check')->withoutOverlapping();

            return $schedule;
        });

        // The queued job lands here and does the actual sending.
        \Eventy::addAction('fastnintegrations.ticket_event', function ($conversationId, $event, $change, $dedupe) {
            FastnClient::sendTicketEvent($conversationId, $event, (array) $change, $dedupe);
        }, 20, 4);
    }

    protected static function queue($conversation, $event, array $change, $dedupe)
    {
        if (FastnClient::$suppress || !FastnClient::isConfigured()) {
            return;
        }
        \Helper::backgroundAction('fastnintegrations.ticket_event', [(int) $conversation->id, $event, $change, $dedupe]);
    }

    protected static function who($user)
    {
        return ($user && $user->id) ? (string) $user->getFullName() : '';
    }

    protected static function text($html)
    {
        $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", (string) $html))));

        return mb_substr(preg_replace("/\n{3,}/", "\n\n", $text), 0, 4000);
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'fastnintegrations');
    }

    public function registerViews()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'fastnintegrations');
    }

    public function provides()
    {
        return [];
    }
}
