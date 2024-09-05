<?php namespace Barryvdh\TranslationManager;

use Illuminate\Translation\Translator as LaravelTranslator;
use Illuminate\Events\Dispatcher;

class Translator extends LaravelTranslator {

    /** @var  Dispatcher */
    protected $events;

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function get($key, array $replace = array(), $locale = null, $fallback = true)
    {
        // Get without fallback
        $result = parent::get($key, $replace, $locale, false);
        if($result === $key){
            $this->notifyMissingKey($key);

            // Reget with fallback
            $result = parent::get($key, $replace, $locale, $fallback);
            
        }

        return $result;
    }

    public function setTranslationManager(Manager $manager)
    {
        $this->manager = $manager;
    }

    protected function notifyMissingKey($key)
    {
        list($namespace, $group, $item) = $this->parseKey($key);
        if($this->manager && $namespace === '*' && $group && $item ){
            $this->manager->missingKey($namespace, $group, $item);
        }
    }

}
