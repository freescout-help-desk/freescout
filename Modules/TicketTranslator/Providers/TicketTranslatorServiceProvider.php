<?php

namespace Modules\TicketTranslator\Providers;

use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('TRANSLATOR_MODULE', 'tickettranslator');

class TicketTranslatorServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($value) {
            array_push($value, '/modules/'.TRANSLATOR_MODULE.'/js/laroute.js');
            array_push($value, '/modules/'.TRANSLATOR_MODULE.'/js/module.js');
            return $value;
        });

        // Show menu item.
        \Eventy::addAction('thread.menu', function($thread) {
            if ($thread->type == Thread::TYPE_LINEITEM) {
                return;
            }
            ?>
            <li>
                <a href="<?php echo route('ticket_translator.modal', ['thread_id' => $thread->id]) ?>" data-trigger="modal" data-modal-title="<?php echo __("Translate") ?>" data-modal-no-footer="true" data-modal-on-show="initTranslateModal"><?php echo __("Translate") ?></a>
            </li>
            <?php
        });

        // Show translations.
        \Eventy::addAction('thread.before_body', function($thread, $loop, $threads, $conversation, $mailbox) {
            $translations = \Helper::jsonToArray($thread->translations);
            if (!$translations || empty($translations['i18n'])) {
                return;
            }
            $i = 0;
            $current_locale = auth()->user()->getLocale();
            ?>
            <div class="margin-bottom">
                <div class="small margin-bottom-10">
                    <strong><?php echo __('Translations') ?>:</strong> 
                    <span class="translation-triggers">
                        <?php foreach ($translations['i18n'] as $locale => $text): ?>
                            <?php $is_current = ($locale == $current_locale); ?>
                            <a href="javascript:toggleTranslation(<?php echo $thread->id ?>, '<?php echo $locale ?>');void(0);" class="translation-trigger-<?php echo $locale ?> <?php if ($is_current): ?> selected<?php endif ?>"><?php echo \Helper::$locales[$locale]['name_en'] ?><span style="margin-left: 3px;" class="caret <?php if (!$is_current): ?>hidden<?php endif ?>"></span></a> <?php if ($i < count($translations['i18n'])-1): ?>&nbsp;|&nbsp;<?php endif ?>
                            <?php $i++; ?>
                        <?php endforeach ?>
                    </span>
                </div>
                <?php foreach ($translations['i18n'] as $locale => $text): ?>
                    <div class="alert alert-note translation-text translation-<?php echo $locale ?> <?php if ($locale != $current_locale): ?>hidden<?php endif ?>">
                        <strong><?php echo __('Automatic Translation') ?> (<?php echo \Helper::$locales[$locale]['name'] ?>)</strong><br/><br/>
                        <?php echo \Helper::nl2brDouble(htmlspecialchars($text)) ?>
                    </div>
                <?php endforeach ?>
            </div>
            <?php
        }, 20, 5);

        // Add module's JS file to the application layout.
        \Eventy::addFilter('search.conversations.or_where', function($query_conversations, $filters, $q) {
            $query_conversations->orWhere('threads.translations', 'like', '%'.mb_strtolower($q).'%');
            return $query_conversations;
        }, 20, 3);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('tickettranslator.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'tickettranslator'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/tickettranslator');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/tickettranslator';
        }, \Config::get('view.paths')), [$sourcePath]), 'tickettranslator');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
