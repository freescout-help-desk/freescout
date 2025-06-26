<?php

namespace Barryvdh\TranslationManager;

use Barryvdh\TranslationManager\Events\TranslationsExportedEvent;
use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Manager
{
    const JSON_GROUP = '_app';

    /** @var \Illuminate\Contracts\Foundation\Application */
    protected $app;
    /** @var \Illuminate\Filesystem\Filesystem */
    protected $files;
    /** @var \Illuminate\Contracts\Events\Dispatcher */
    protected $events;

    protected $config;

    protected $locales;

    protected $ignoreLocales;

    protected $ignoreFilePath;

    public function __construct(Application $app, Filesystem $files, Dispatcher $events)
    {
        $this->app = $app;
        $this->files = $files;
        $this->events = $events;
        $this->config = $app['config']['translation-manager'];
        $this->ignoreFilePath = storage_path('.ignore_locales');
        $this->locales = [];
        $this->ignoreLocales = $this->getIgnoredLocales();
    }

    protected function getIgnoredLocales()
    {
        if (!$this->files->exists($this->ignoreFilePath)) {
            return [];
        }
        $result = json_decode($this->files->get($this->ignoreFilePath));

        return ($result && is_array($result)) ? $result : [];
    }

    public function importTranslations($replace = false, $base = null)
    {
        $counter = 0;
        //allows for vendor lang files to be properly recorded through recursion.
        $vendor = true;
        if ($base == null) {
            $base = $this->app['path.lang'];
            $vendor = false;
        }

        // PHP translations in /resources/lang/.
        foreach ($this->files->directories($base) as $langPath) {
            $locale = basename($langPath);

            //import langfiles for each vendor
            if ($locale == 'vendor') {
                foreach ($this->files->directories($langPath) as $vendor) {
                    $counter += $this->importTranslations($replace, $vendor);
                }
                continue;
            }
            $vendorName = $this->files->name($this->files->dirname($langPath));
            foreach ($this->files->allfiles($langPath) as $file) {
                $info = pathinfo($file);
                $group = $info['filename'];

                if (in_array($group, $this->config['exclude_groups'])) {
                    continue;
                }
                $subLangPath = str_replace($langPath.DIRECTORY_SEPARATOR, '', $info['dirname']);
                $subLangPath = str_replace(DIRECTORY_SEPARATOR, '/', $subLangPath);
                $langPath = str_replace(DIRECTORY_SEPARATOR, '/', $langPath);

                if ($subLangPath != $langPath) {
                    $group = $subLangPath.'/'.$group;
                }

                if (!$vendor) {
                    $translations = \Lang::getLoader()->load($locale, $group);
                } else {
                    $translations = include $file;
                    $group = 'vendor/'.$vendorName;
                }

                if ($translations && is_array($translations)) {
                    foreach (array_dot($translations) as $key => $value) {
                        $importedTranslation = $this->importTranslation($key, $value, $locale, $group, $replace);
                        $counter += $importedTranslation ? 1 : 0;
                    }
                }
            }
        }

        // Import app json translations.
        //$loader = new \Illuminate\Translation\FileLoader($this->files, $this->app[ 'path.lang' ]);
        foreach ($this->files->files($this->app['path.lang']) as $jsonTranslationFile) {
            if (strpos($jsonTranslationFile, '.json') === false) {
                continue;
            }
            $locale = basename($jsonTranslationFile, '.json');
            // Ignore module translations backup files.
            if (!preg_match('/^[a-zA-Z_\-]+$/', $locale)) {
                continue;
            }

            $group = self::JSON_GROUP;
            // Retrieves JSON entries of the given locale only.
            // No need to use - Modules JSON translations are also loaded.
            //$translations = \Lang::getLoader()->load($locale, '*', '*');

            //$translations = $loader->load( $locale, '*', '*' );
            
            $translations = $this->readTranslationsFromJsonFile($jsonTranslationFile);
            if ($translations && is_array($translations)) {
                foreach ($translations as $key => $value) {
                    $importedTranslation = $this->importTranslation($key, $value, $locale, $group, $replace);
                    $counter += $importedTranslation ? 1 : 0;
                }
            }
            unset($translations);
        }

        // Import modules translations.
        // Saving translations to DB takes a lot of time and memory.
        $modules = \Module::all();
        foreach ($modules as $key => $module) {
            $moduleLangPath = $module->getPath().'/Resources/lang/';
            if (!$this->files->exists($moduleLangPath) || !$this->files->isDirectory($moduleLangPath)) {
                continue;
            }

            foreach ($this->files->files($moduleLangPath) as $jsonTranslationFile) {
                if (strpos($jsonTranslationFile, '.json') === false) {
                    continue;
                }

                $locale = basename($jsonTranslationFile, '.json');
                // Miss incorrect locales.
                if (!preg_match('/^[a-zA-Z_]+$/', $locale)) {
                    continue;
                }

                $group = '_'.$module->getAlias();

                // $loader = new \Illuminate\Translation\FileLoader($this->files, $moduleLangPath);
                // $translations = \Lang::getLoader()->load($locale, '*', '*');
                $translations = $this->readTranslationsFromJsonFile($jsonTranslationFile);

                if ($translations && is_array($translations)) {
                    foreach ($translations as $key => $value) {
                        $importedTranslation = $this->importTranslation($key, $value, $locale, $group, $replace);
                        $counter += $importedTranslation ? 1 : 0;
                    }
                }
                unset($translations);
            }
        }

        // Find translations in files.
        // This is done quickly.
        $existingTranslations = $this->findTranslations();

        // Remove translations which do not exist in files.
        $this->removeNonexisting($existingTranslations);

        return $counter;
    }

    public function readTranslationsFromJsonFile($path)
    {
        if (!file_exists($path)) {
            return [];
        }
        $decoded = json_decode(file_get_contents($path), true);

        if (is_null($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $decoded;
    }

    /**
     * Remove translations which do not exist in files.
     *
     * @param [type] $existingTranslations [description]
     *
     * @return [type] [description]
     */
    public function removeNonexisting($existingTranslations)
    {
        // First process translations belonging to Modules one by one.
        // $moduleAliases = [];
        // foreach ($existingTranslations as $translation) {
        //     preg_match("/^_([^\.])+\./", $translation, $matches);
        //     if (empty($matches[1]) || in_array($matches[1], $moduleAliases)) {
        //         continue;
        //     } else {
        //         $moduleAliases[] = $matches[1];
        //     }
        // }

        $existingGroups = [];
        // Get unique groups.
        foreach ($existingTranslations as $key) {
            preg_match("/^([a-zA-Z0-9_]+)\./", $key, $matches);
            // try {
            //     list( $group, $item ) = explode( '.', $key);
            // } catch (\Exception $e) {
            //     continue;
            // }
            if (empty($matches[1]) || in_array($matches[1], $existingGroups)) {
                continue;
            } else {
                $existingGroups[] = $matches[1];
            }
        }

        $existingGroups[] = self::JSON_GROUP;

        // Process translations by groups.
        // Get from DB translations for each module and compare to existing.
        foreach ($existingGroups as $group) {
            $dbTranslations = Translation::where('group', $group)->get();

            foreach ($dbTranslations as $dbTranslation) {
                $found = false;
                foreach ($existingTranslations as $existingKey) {
                    if ($group == self::JSON_GROUP) {
                        if ($dbTranslation['key'] == $existingKey) {
                            $found = true;
                            break;
                        }
                    } else {
                        if ($dbTranslation['group'].'.'.$dbTranslation['key'] == $existingKey) {
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    $dbTranslation->delete();
                }
            }
        }
    }

    public function importTranslation($key, $value, $locale, $group, $replace = false)
    {

        // process only string values
        if (is_array($value)) {
            return false;
        }

        // Miss modules translations: fr.module.json
        if (!preg_match('/^[a-zA-Z_\-]+$/', $locale)) {
            return false;
        }

        $value = (string) $value;
        // $translation = Translation::firstOrNew([
        //     'locale' => $locale,
        //     'group'  => $group,
        //     'key'    => $key,
        // ]);
        $hash = md5($locale.$group.$key);

        $data = [
            'locale' => $locale,
            'group' => $group,
            'key' => $key,
            'value' => $value,
            'hash' => $hash,
        ];
        $inserted = 0;
        try {
            $inserted = \DB::table('ltm_translations')->insert($data);
        } catch(\Exception $e) {
            
        }

        if ($replace && !$inserted) {
            Translation::where('hash', $hash)->update(['value' => $value]);
        }

        return true;

        // Consumes too much memory.
        /*try {
            // $translation = Translation::where('locale', $locale)
            //     ->where('group', $group)
            //     ->where(\DB::raw('BINARY `key`'), $key)
            //     ->first();
            $translation = Translation::where('hash', $hash)->first();
                
            if (!$translation) {
                $translation = new Translation();
                $translation->locale = $locale;
                $translation->group  = $group;
                $translation->key    = $key;
                $translation->hash    = $hash;
            }
        } catch (\Exception $e) {
            // $translation = Translation::firstOrNew([
            //     'locale' => $locale,
            //     'group' => $group,
            //     'key' => $key,
            // ]);
            $translation = Translation::firstOrNew(['hash' => $hash], [
                'locale' => $locale,
                'group' => $group,
                'key' => $key,
                'hash' => $hash,
            ]);
        }
        
        // Check if the database is different then the files
        // $newStatus = $translation->value === $value ? Translation::STATUS_SAVED : Translation::STATUS_CHANGED;
        // if ($newStatus !== (int) $translation->status) {
        //     $translation->status = $newStatus;
        // }

        // Only replace when empty, or explicitly told so
        if ($replace || !$translation->value) {
            $translation->value = $value;
        }

        $translation->save();
        unset($translation);

        return true;*/
    }

    public function findTranslations($path = null)
    {
        $path = $path ?: base_path();
        $groupKeys = [];
        $stringKeys = [];
        $functions = $this->config['trans_functions'];

        $groupPattern =                              // See http://regexr.com/392hu
            "[^\w|>]".                          // Must not have an alphanum or _ or > before real method
            '('.implode('|', $functions).')'.  // Must start with one of the functions
            "\(".                               // Match opening parenthesis
            "[\'\"]".                           // Match " or '
            '('.                                // Start a new group to match:
            '[a-zA-Z0-9_-]+'.               // Must start with group
            "([.|\/](?! )[^\1)]+)+".             // Be followed by one or more items/keys
            ')'.                                // Close group
            "[\'\"]".                           // Closing quote
            "[\),]";                            // Close parentheses or new parameter

        $stringPattern =
            "[^\w|>]".                                     // Must not have an alphanum or _ or > before real method
            '('.implode('|', $functions).')'.             // Must start with one of the functions
            "\(".                                          // Match opening parenthesis
            "(?P<quote>['\"])".                            // Match " or ' and store in {quote}
            "(?P<string>(?:\\\k{quote}|(?!\k{quote}).)*)". // Match any string that can be {quote} escaped
            "\k{quote}".                                   // Match " or ' previously matched
            "[\),]";                                       // Close parentheses or new parameter

        // Find all PHP + Twig files in the app folder, except for storage
        $finder = new Finder();
        $searchInModules = false;
        if (!empty(request()->submit) && request()->submit == 'modules') {
            // Find in modules.
            $searchInModules = true;
            $finder->in($path.DIRECTORY_SEPARATOR.'Modules')->exclude('vendor')->exclude('.git')->name('*.php')->name('*.twig')->name('*.vue')->files();
        } else {
            // Find in core.
            $exclude = ['.git', 'bootstrap', 'config', 'database', 'public', 'routes', 'storage', 'tests', 'tools', 'vendor'];
            $finder->in($path)->exclude($exclude)->name('*.php')->name('*.twig')->name('*.vue')->files();
            // foreach ($exclude as $exclude_folder) {
            //     $finder->exclude($exclude_folder);
            // }
            // $finder->files();
        }

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {
            // Search the current file for the pattern
            if (preg_match_all("/$groupPattern/siU", $file->getContents(), $matches)) {
                // Get all matches
                foreach ($matches[2] as $key) {
                    $group = explode('.', $key)[0];
                    if (!in_array($group, $this->config['incorrect_groups'])) {
                        $groupKeys[] = $key;
                    }
                }
            }

            if (preg_match_all("/$stringPattern/siU", $file->getContents(), $matches)) {
                $moduleAlias = '';
                preg_match("/Modules\/([^\/]+)\//", $file->getPathname(), $m);
                if (!empty($m[1])) {
                    $moduleAlias = strtolower($m[1]);
                }

                foreach ($matches['string'] as $key) {
                    if (preg_match("/(^[a-zA-Z0-9_-]+([.][^\1)\ ]+)+$)/siU", $key, $groupMatches)) {
                        // group{.group}.key format, already in $groupKeys but also matched here
                        // do nothing, it has to be treated as a group
                        continue;
                    }

                    //TODO: This can probably be done in the regex, but I couldn't do it.
                    //skip keys which contain namespacing characters, unless they also contain a
                    //space, which makes it JSON.
                    if (!(str_contains($key, '::') && str_contains($key, '.'))
                         || str_contains($key, ' ')) {

                        // Modules
                        //if ($searchInModules) {
                        if ($moduleAlias) {
                            $groupKeys[] = '_'.$moduleAlias.'.'.$key;
                        } else {
                            $stringKeys[] = $key;
                        }
                    }
                }
            }
        }

        // Remove modules strings existing among the app strings.
        foreach ($groupKeys as $i => $groupKey) {

            if (in_array(preg_replace("/^[^\.]+\./", '', $groupKey), $stringKeys)) {
                unset($groupKeys[$i]);
            }
        }

        // Remove duplicates
        $groupKeys = array_unique($groupKeys);
        $stringKeys = array_unique($stringKeys);

        // Add the translations to the database, if not existing.
        // Modules translations are added here too.
        foreach ($groupKeys as $key) {
            // Split the group and item
            $parts = explode('.', $key, 2);
            if (count($parts) < 2) {
                \Log::warning("Translation key without group: $key");
                continue;
            }
            list($group, $item) = $parts;
            $this->missingKey('', $group, $item);
        }

        foreach ($stringKeys as $key) {
            $group = self::JSON_GROUP;
            $item = $key;
            $this->missingKey('', $group, $item);
        }

        // Return the number of found translations
        //return count( $groupKeys + $stringKeys );
        //return $groupKeys + $stringKeys;
        return array_merge($groupKeys, $stringKeys);
    }

    public function missingKey($namespace, $group, $key)
    {
        if (!in_array($group, $this->config['exclude_groups'])) {
            try {
                $translation = Translation::where('locale', \Helper::getRealAppLocale())
                    ->where('group', $group)
                    ->where(\DB::raw('BINARY `key`'), $key)
                    ->first();
                if (!$translation) {
                    $translation = new Translation();
                    $translation->locale = \Helper::getRealAppLocale();
                    $translation->group  = $group;
                    $translation->key    = $key;
                    $translation->save();
                }
            } catch (\Exception $e) {
                Translation::firstOrCreate([
                    'locale' => \Helper::getRealAppLocale(),
                    'group'  => $group,
                    'key'    => $key,
                ]);
            }
        }
    }

    public function exportTranslations($group = null /*, $json = false*/)
    {
        $basePath = $this->app['path.lang'];

        $json = false;
        // Detect json groups automatically.
        if ($group && $group[0] == '_') {
            $json = true;
        }

        $moduleAlias = '';
        if ($json && $group != self::JSON_GROUP) {
            $moduleAlias = substr($group, 1);
        }

        if (!is_null($group) && !$json) {
            if (!in_array($group, $this->config['exclude_groups'])) {
                $vendor = false;
                if ($group == '*') {
                    return $this->exportAllTranslations();
                } else {
                    if (starts_with($group, 'vendor')) {
                        $vendor = true;
                    }
                }

                $tree = $this->makeTree(Translation::ofTranslatedGroup($group)
                                                    ->orderByGroupKeys(array_get($this->config, 'sort_keys', false))
                                                    ->get());

                foreach ($tree as $locale => $groups) {
                    if (isset($groups[$group])) {
                        $translations = $groups[$group];
                        $path = $this->app['path.lang'];

                        $locale_path = $locale.DIRECTORY_SEPARATOR.$group;
                        if ($vendor) {
                            $path = $basePath.'/'.$group.'/'.$locale;
                            $locale_path = str_after($group, '/');
                        }
                        $subfolders = explode(DIRECTORY_SEPARATOR, $locale_path);
                        array_pop($subfolders);

                        $subfolder_level = '';
                        foreach ($subfolders as $subfolder) {
                            $subfolder_level = $subfolder_level.$subfolder.DIRECTORY_SEPARATOR;

                            $temp_path = rtrim($path.DIRECTORY_SEPARATOR.$subfolder_level, DIRECTORY_SEPARATOR);
                            if (!is_dir($temp_path)) {
                                mkdir($temp_path, 0777, true);
                            }
                        }

                        $path = $path.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$group.'.php';

                        $output = "<?php\n\nreturn ".var_export($translations, true).';'.\PHP_EOL;
                        $this->files->put($path, $output);
                    }
                }
                Translation::ofTranslatedGroup($group)->update(['status' => Translation::STATUS_SAVED]);
            }
        }

        if ($json) {
            $tree = $this->makeTree(Translation::ofTranslatedGroup($group)
                                                ->orderByGroupKeys(array_get($this->config, 'sort_keys', false))
                                                ->get(), true);

            foreach ($tree as $locale => $groups) {
                if (!$moduleAlias) {
                    // _json
                    $path = $this->app['path.lang'].'/'.$locale.'.json';
                } else {
                    // Export module translations into module's folder
                    $modulePath = \Module::getModulePathByAlias($moduleAlias);
                    // Module not found.
                    if (!$modulePath) {
                        continue;
                    }
                    $path = $modulePath.'Resources/lang/'.$locale.'.json';
                    if (!$this->files->exists($modulePath.'Resources/lang')) {
                        $this->files->makeDirectory($modulePath.'Resources/lang', 0755, true);
                    }
                }

                $translations = $groups[$group];
                // Sort translations alphabetically.
                ksort($translations);

                // Strips some tags to avoid XSS when translations are inserted via {!! ... !!}.
                foreach ($translations as $key => $value) {
                    $value = \Helper::stripDangerousTags($value);

                    $translations[$key] = $value;
                }

                $output = json_encode($translations, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
                $this->files->put($path, $output);

                // If it is a module, also export translation into the main langs folder.
                if ($moduleAlias && $modulePath) {
                    $path = $this->app['path.lang'].'/module.'.$moduleAlias.'.'.$locale.'.json';
                    $this->files->put($path, $output);
                }
                // if ( isset( $groups[ self::JSON_GROUP ] ) ) {
                //     $translations = $groups[ self::JSON_GROUP ];
                //     $path         = $this->app[ 'path.lang' ] . '/' . $locale . '.json';
                //     $output       = json_encode( $translations, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE );
                //     $this->files->put( $path, $output );
                // }
            }

            Translation::ofTranslatedGroup(self::JSON_GROUP)->update(['status' => Translation::STATUS_SAVED]);
        }

        $this->events->dispatch(new TranslationsExportedEvent());
    }

    public function exportAllTranslations()
    {
        $groups = Translation::whereNotNull('value')->selectDistinctGroup()->get('group');

        foreach ($groups as $group) {
            $this->exportTranslations($group->group);
            // if ( $group->group == self::JSON_GROUP ) {
            //     $this->exportTranslations( null, true );
            // } else {
            //     $this->exportTranslations( $group->group );
            // }
        }

        $this->events->dispatch(new TranslationsExportedEvent());
    }

    protected function makeTree($translations, $json = false)
    {
        $array = [];
        foreach ($translations as $translation) {
            if ($json) {
                $this->jsonSet($array[$translation->locale][$translation->group], $translation->key,
                    $translation->value);
            } else {
                array_set($array[$translation->locale][$translation->group], $translation->key,
                    $translation->value);
            }
        }

        return $array;
    }

    public function jsonSet(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        $array[$key] = $value;

        return $array;
    }

    public function cleanTranslations()
    {
        Translation::whereNull('value')->delete();
    }

    public function truncateTranslations()
    {
        Translation::truncate();
    }

    public function getLocales()
    {
        if (empty($this->locales)) {
            $locales = array_merge([\Helper::getRealAppLocale()],
                Translation::groupBy('locale')->pluck('locale')->toArray());
            foreach ($this->files->directories($this->app->langPath()) as $localeDir) {
                if (($name = $this->files->name($localeDir)) != 'vendor') {
                    $locales[] = $name;
                }
            }

            $this->locales = array_unique($locales);
            sort($this->locales);
        }

        return array_diff($this->locales, $this->ignoreLocales);
    }

    public function addLocale($locale)
    {
        $localeDir = $this->app->langPath().'/'.basename($locale);

        $this->ignoreLocales = array_diff($this->ignoreLocales, [$locale]);
        $this->saveIgnoredLocales();
        $this->ignoreLocales = $this->getIgnoredLocales();

        if (!$this->files->exists($localeDir) || !$this->files->isDirectory($localeDir)) {
            return $this->files->makeDirectory($localeDir);
        }

        return true;
    }

    protected function saveIgnoredLocales()
    {
        return $this->files->put($this->ignoreFilePath, json_encode($this->ignoreLocales));
    }

    public function removeLocale($locale)
    {
        if (!$locale || !in_array($locale, array_keys(\Helper::$locales))) {
            return false;
        }

        $this->ignoreLocales = array_merge($this->ignoreLocales, [$locale]);
        // Only delete from DB.
        //$this->saveIgnoredLocales();
        $this->ignoreLocales = $this->getIgnoredLocales();

        Translation::where('locale', $locale)->delete();

        // Remove folder.
        $localeDir = $this->app->langPath().'/'.$locale;
        if ($this->files->exists($localeDir)) {
            $this->files->deleteDirectory($localeDir);
        }
    }

    public function getConfig($key = null)
    {
        if ($key == null) {
            return $this->config;
        } else {
            return $this->config[$key];
        }
    }
}
