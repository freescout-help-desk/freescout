<?php namespace Barryvdh\TranslationManager;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Support\Collection;

class Controller extends BaseController
{
    /** @var \Barryvdh\TranslationManager\Manager  */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getIndex($group = null)
    {
        $locales = $this->manager->getLocales();
        $groups = Translation::groupBy('group');
        $excludedGroups = $this->manager->getConfig('exclude_groups');
        if($excludedGroups){
            $groups->whereNotIn('group', $excludedGroups);
        }

        $groups = $groups->select('group')->orderBy('group')->get()->pluck('group', 'group');
        if ($groups instanceof Collection) {
            $groups = $groups->all();
        }
        $groups = [''=>'Choose a group'] + $groups;

        $selected_locale = request()->input('locale');
        if ($selected_locale == 'en') {
            $selected_locale = '';
        }
        if (!$selected_locale) {
            foreach ($locales as $locale) {
                if ($locale != 'en') {
                    $selected_locale = $locale;
                    break;
                }
            }
        }

        $numChanged = Translation::where('group', $group)
            ->where('status', Translation::STATUS_CHANGED)
            ->where('locale', $selected_locale)
            ->count();

        $allTranslations = Translation::where('group', $group)->orderBy('key', 'asc')->get();
        
        $translations = [];
        $numTranslations = 0;
        $numDone = 0;
        foreach($allTranslations as $translation){
            if ($translation->locale != $selected_locale && $translation->locale != 'en') {
                continue;
            }
            $translations[$translation->key][$translation->locale] = $translation;
            if ($translation->locale == $selected_locale && $translation->value) {
                $numDone++;
            }
            if ($translation->locale == 'en') {
                $numTranslations++;
            }
        }

        $numTodo = $numTranslations - $numDone;

        return view('translation-manager::index')
            ->with('translations', $translations)
            ->with('locales', $locales)
            ->with('groups', $groups)
            ->with('group', $group)
            ->with('selected_locale', $selected_locale)
            ->with('numTranslations', $numTranslations)
            ->with('numTodo', $numTodo)
            ->with('numChanged', $numChanged)
            ->with('editUrl', action('\Barryvdh\TranslationManager\Controller@postEdit', [$group]))
            ->with('deleteEnabled', $this->manager->getConfig('delete_enabled'));
    }

    public function getView($group = null)
    {
        return $this->getIndex($group);
    }

    protected function loadLocales()
    {
        //Set the default locale as the first one.
        $locales = Translation::groupBy('locale')
            ->select('locale')
            ->get()
            ->pluck('locale');

        if ($locales instanceof Collection) {
            $locales = $locales->all();
        }
        $locales = array_merge([\Helper::getRealAppLocale()], $locales);
        return array_unique($locales);
    }

    public function postAdd($group = null)
    {
        $keys = explode("\n", request()->get('keys'));

        foreach($keys as $key){
            $key = trim($key);
            if($group && $key){
                $this->manager->missingKey('*', $group, $key);
            }
        }
        return redirect()->back();
    }

    public function postEdit($group = null)
    {
        if(!in_array($group, $this->manager->getConfig('exclude_groups'))) {
            $name = request()->get('name');
            $value = request()->get('value');

            list($locale, $key) = explode('|', $name, 2);
            // $translation = Translation::firstOrNew([
            //     'locale' => $locale,
            //     'group' => $group,
            //     'key' => $key,
            // ]);
            try {
                $translation = Translation::where('locale', $locale)
                    ->where('group', $group)
                    ->where(\DB::raw('BINARY `key`'), $key)
                    ->first();
                if (!$translation) {
                    $translation = new Translation();
                    $translation->locale = $locale;
                    $translation->group  = $group;
                    $translation->key    = $key;
                    $translation->hash   = md5($locale.$group.$key);
                }
            } catch (\Exception $e) {
                $translation = Translation::firstOrNew([
                    'locale' => $locale,
                    'group' => $group,
                    'key' => $key,
                ]);
            }
            $translation->value = (string) $value ?: null;
            $translation->status = Translation::STATUS_CHANGED;
            $translation->save();
            return array('status' => 'ok');
        }
    }

    public function postDelete($group = null, $key)
    {
        if(!in_array($group, $this->manager->getConfig('exclude_groups')) && $this->manager->getConfig('delete_enabled')) {
            Translation::where('group', $group)->where('key', $key)->delete();
            return ['status' => 'ok'];
        }
    }

    public function postImport(Request $request)
    {
        $replace = $request->get('replace', false);
        
        set_time_limit(0);
        ini_set('max_execution_time', '600');
        ini_set('memory_limit', '500M');

        // Clean translations table.
        Translation::truncate();

        $counter = $this->manager->importTranslations($replace);

        return ['status' => 'ok', 'counter' => $counter];
    }

    public function postFind()
    {
        $numFound = $this->manager->findTranslations();

        return ['status' => 'ok', 'counter' => (int) $numFound];
    }

    public function postPublish($group = null)
    {
         $json = false;

        if($group === '_json'){
            $json = true;
        }

        $this->manager->exportTranslations($group, $json);

        return ['status' => 'ok'];
    }

    public function postAddGroup(Request $request)
    {
        $group = str_replace(".", '', $request->input('new-group'));
        if ($group)
        {
            return redirect()->action('\Barryvdh\TranslationManager\Controller@getView',$group);
        }
        else
        {
            return redirect()->back();
        }
    }

    public function postAddLocale(Request $request)
    {
        $locales = $this->manager->getLocales();
        $newLocale = str_replace([], '-', trim($request->input('new-locale')));
        if (!$newLocale || in_array($newLocale, $locales)) {
            return redirect()->back();
        }
        $this->manager->addLocale($newLocale);
        return redirect()->back();
    }

    public function postRemoveLocale(Request $request)
    {
        foreach ($request->input('remove-locale', []) as $locale => $val) {
            $this->manager->removeLocale($locale);
        }
        return redirect()->back();
    }
}
