<?php

namespace App;

namespace Modules\KnowledgeBase\Entities;

use Modules\KnowledgeBase\Entities\KbArticleKbCategory;
use Illuminate\Database\Eloquent\Model;

class KbCategory extends Model
{
    const VISIBILITY_PUBLIC = 1;
    const VISIBILITY_PRIVATE = 2;

    const ARTICLES_ORDER_ALPHABETICALLY = 1;
    const ARTICLES_ORDER_LAST_UPDATED = 2;
    const ARTICLES_ORDER_CUSTOM = 3;
    
    public $locale = '';

    public static $translatable_fields = ['name', 'description'];

    public $timestamps = false;

    public static $categories_cached = null;
    public static $article_to_category_cached = null;
    // only "status" field.
    public static $articles_cached = null;
    // Articles counts for all categories.
    public static $articles_counts = null;
    public static $tree_as_list = null;

    /**
     * Attributes fillable using fill() method.
     *
     * @var [type]
     */
    protected $fillable = ['mailbox_id', 'name', 'description', 'kb_category_id', 'visibility', 'expand', 'articles_order'];

    public function parent()
    {
        $parent = null;
        if ($this->kb_category_id) {
            $parent = self::find($this->kb_category_id);
        }

        return $parent;
    }

    /**
     * This method is used only for translations.
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox')->rememberForever();
    }

    public function articles()
    {
        return $this->belongsToMany('Modules\KnowledgeBase\Entities\KbArticle')
            // pivot.sort_order.
            ->withPivot('sort_order');
    }

    public function articles_published()
    {
        return $this->belongsToMany('Modules\KnowledgeBase\Entities\KbArticle')
            // pivot.sort_order.
            ->withPivot('sort_order')
            ->where('status', \KbArticle::STATUS_PUBLISHED);
    }

    public static function getAllCached($mailbox_id = null/*, $check_visibility = false*/)
    {
        if (self::$categories_cached === null) {
            self::$categories_cached = self::get();
        }

        $categories = [];
        foreach (self::$categories_cached as $category) {
            if ($category->mailbox_id == $mailbox_id || !$mailbox_id) {
                $categories[] = $category;
            }
        }
        $categories = collect($categories);

        if (!auth()->user()) {
            // Remove Private categories.
            foreach ($categories as $i => $category) {
                if ($category->visibility == \KbCategory::VISIBILITY_PRIVATE) {
                    unset($categories[$i]);
                }
            }
        }

        return $categories->sortBy('sort_order');
    }

    public function getSubCategories()
    {
        $all = self::getAllCached($this->mailbox_id);

        $sub_categories = [];
        foreach ($all as $category) {
            if ($this->id == $category->kb_category_id) {
                $sub_categories[] = $category;
            }
        }

        return $sub_categories;
    }

    public static function getList($mailbox_id = null, $check_visibility = false)
    {
        return self::getAllCached($mailbox_id, $check_visibility);
    }

    public static function getTreeAsList($mailbox_id, $check_visibility = false)
    {
        $tree = self::getTree($mailbox_id, self::getList($mailbox_id, $check_visibility));

        return self::treeToList($tree);
    }

    public static function treeToList($tree, $list = [])
    {
        foreach ($tree as $category) {
            $list[] =  $category;
            if ($category->categories) {
                $list = self::treeToList($category->categories, $list);
            }
        }

        return $list;
    }

    public static function getTree($mailbox_id = null, $categories = [], $parent_category_id = 0/*, $check_visibility = true*/)
    {
        $tree = [];

        if (!$parent_category_id) {
            $categories = self::getAllCached($mailbox_id/*, $check_visibility*/);
        }

        if (!$categories) {
            return [];
        }

        foreach ($categories as $category) {
            if ($category->kb_category_id != (int)$parent_category_id) {
                continue;
            }
            
            $category->categories = self::getTree($mailbox_id, $category->getSubCategories(), $category->id);
            $tree[] = $category;
        }

        return $tree;
    }

    public static function getArticlesToCategories()
    {
        if (self::$article_to_category_cached == null) {
            self::$article_to_category_cached = KbArticleKbCategory::get();
        }

        return self::$article_to_category_cached;
    }

    public function getArticlesCount($published = false)
    {
        if (self::$articles_counts != null 
            && isset(self::$articles_counts[$published])
            && !isset(self::$articles_counts[$published][$this->id])
        ) {
            return 0;
        }
        if (isset(self::$articles_counts[$published][$this->id])) {
            return self::$articles_counts[$published][$this->id];
        }

        $count = 0;
        $article_to_category = self::getArticlesToCategories();

        if (self::$articles_cached == null) {
            self::$articles_cached = \KbArticle::select('id', 'status')->get();
        }

        foreach ($article_to_category as $row) {
            if (!isset(self::$articles_counts[$published][$row->kb_category_id])) {
                self::$articles_counts[$published][$row->kb_category_id] = 0;
            }
            // Check mailbox id.
            // $mailbox_id_valid = false;
            // foreach (self::$articles_cached as $article) {
            //     if ($article->id == $row->kb_article_id) {
            //         if ($article->mailbox_id == $this->mailbox_id) {
            //             $mailbox_id_valid = true;
            //         }
            //         break;
            //     }
            // }
            // if (!$mailbox_id_valid) {
            //     continue;
            // }
            if ($published) {
                foreach (self::$articles_cached as $article) {
                    if ($article->id == $row->kb_article_id && $article->status != \KbArticle::STATUS_PUBLISHED) {
                        continue 2;
                    }
                }
            }
            self::$articles_counts[$published][$row->kb_category_id]++;
        }

        return self::$articles_counts[$published][$this->id] ?? 0;
    }

    public static function countUncategorizedArticles($mailbox_id)
    {
        $article_ids = \KbArticle::where('mailbox_id', $mailbox_id)->pluck('id')->toArray();

        $count = 0;
        $article_to_category = self::getArticlesToCategories();

        foreach ($article_ids as $article_id) {
            foreach ($article_to_category as $row) {
                if ($row->kb_article_id == $article_id) {
                    continue 2;
                }
            }
            $count++;
        }
        return $count;
    }

    public function getArticlesSorted($published = false)
    {
        if ($published) {
            $articles = $this->articles_published;
        } else {
            $articles = $this->articles;
        }

        switch ($this->articles_order) {
            case self::ARTICLES_ORDER_CUSTOM:
                $articles = $articles->sortBy('pivot.sort_order');
                break;
            
            case self::ARTICLES_ORDER_LAST_UPDATED:
                $articles = $articles->sortByDesc('updated_at');
                break;

            default:
                // A-Z
                $articles = $articles->sortBy('title');
                break;
        }

        return $articles;
    }

    public function isPublic()
    {
        return $this->visibility == self::VISIBILITY_PUBLIC;
    }

    public function urlFrontend($mailbox)
    {
        return \Kb::route('knowledgebase.frontend.category', ['mailbox_id'=>\Kb::encodeMailboxId($mailbox->id), 'category_id' => $this->id], $mailbox);
    }

    public function checkVisibility()
    {
        if (auth()->user()) {
            return true;
        }

        if (!$this->isPublic()) {
            return false;
        }

        // Check up the tree.
        $categories = \KbCategory::getTreeAsList($this->mailbox_id, true);

        // Unavailable subcategories excluded automatically while building the flat tree.
        foreach ($categories as $i => $category) {
            if ($category->id == $this->id) {
                return true;
            }
        }

        // List does not work.
        // foreach ($categories as $i => $category) {

        //     if ($category->id == $this->id) {

        //         for ($j=$i; $j >= 0; $j--) { 

        //             if (!$categories[$j]->isPublic()) {
        //                 return false;
        //             }
        //             if (!$categories[$j]->kb_category_id) {
        //                 break;
        //             }
        //         }
        //         break;
        //     }
        // }

        return false;
    }

    public static function findCached($category_id)
    {
        $categories = \KbCategory::getList();

        foreach ($categories as $category) {
            if ($category->id == $category_id) {
                return $category;
            }
        }

        return null;
    }

    public function hasChildWithId($child_category_id)
    {
        if (self::$tree_as_list == null) {
            self::$tree_as_list = \KbCategory::getTreeAsList($this->mailbox_id, true);
        }
        // Check up the tree.
        foreach (self::$tree_as_list as $i => $category) {

            if ($category->id == $this->id) {

                for ($j=$i+1; $j < count(self::$tree_as_list); $j++) {
                    $subcategory = self::$tree_as_list[$j];
                    if (!$subcategory->kb_category_id) {
                        return false;
                    }
                    if ($subcategory->id == $child_category_id) {
                        return true;
                    }
                }
                return false;
            }
        }
        return false;
    }

    /**
     * v - default value.
     * l - contains default locale (only in multilingual mode).
     */
    public function setAttribute($field, $value)
    {
        if (in_array($field, self::$translatable_fields)) {
            $key = '';

            $decoded_value = json_decode($this->attributes[$field] ?? '', true);
            $default_locale = \Kb::defaultLocale($this->mailbox);
            if (!is_array($decoded_value)) {
                $decoded_value = [
                    'v' => $this->attributes[$field] ?? '',
                    'l' => $default_locale,
                ];
            }

            if (\Kb::isMultilingual($this->mailbox)) {
                $locale = $this->getLocale();

                // Default locale has changed.
                if (!empty($decoded_value['l'])
                    && $decoded_value['l'] != $default_locale
                    && !empty($decoded_value['v'])
                ) {
                    $decoded_value[$decoded_value['l']] = $decoded_value['v'];
                }

                $decoded_value['l'] = $default_locale;

                if ($locale == $decoded_value['l']) {
                    $key = 'v';
                } else {
                    $key = $locale;
                }
                
            } else {
                $key = 'v';
            }

            if (!empty($decoded_value)) {
                $decoded_value[$key] = $value;
            } else {
                $decoded_value = [$key => $value];
            }

            $value = \Helper::jsonEncodeUtf8($decoded_value);
        }

        parent::setAttribute($field, $value);
    }

    public function getAttribute($field)
    {
        if (isset($this->attributes[$field]) && in_array($field, self::$translatable_fields)) {
            $key = '';
            $old = false;

            $decoded_value = json_decode($this->attributes[$field], true);
            if (!is_array($decoded_value)) {
                $decoded_value = [
                    'v' => $this->attributes[$field],
                    'l' => \Kb::defaultLocale($this->mailbox),
                ];
                $old = true;
            }

            if (\Kb::isMultilingual($this->mailbox)) {
                $locale = $this->getLocale();
                $cur_default_locale = \Kb::defaultLocale($this->mailbox);
                if (!empty($decoded_value['l']) && $decoded_value['l'] != $cur_default_locale) {
                    // Default locale for the mailbox has changed.
                    // Use new default locale.
                    $key = $cur_default_locale;
                } elseif (
                    // When non-multilingual created it contains only v
                    (empty($decoded_value['l']) && $locale == $cur_default_locale)
                    || (!empty($decoded_value['l']) && $decoded_value['l'] == $locale)
                    // In admin areas we do not show primary content instead of empty non-translated.
                    || ($old && \Kb::$use_primary_if_empty)
                    // On frontend we show primary content if not translated yet.
                    || (!isset($decoded_value[$locale]) && \Kb::$use_primary_if_empty)
                ) {
                    // Default locale.
                    $key = 'v';
                } else {
                    $key = $locale;
                }
            } else {
                $key = 'v';
            }

            return $decoded_value[$key] ?? '';
        }

        return parent::getAttribute($field);
    }

    public function getAttributeInLocale($field, $locale)
    {
        $prev_locale = $this->getLocale();
        $this->setLocale($locale);
        $value = $this->getAttribute($field);
        $this->setLocale($prev_locale);

        return $value;
    }

    public function setAttributeInLocale($field, $value, $locale)
    {
        $prev_locale = $this->getLocale();
        $this->setLocale($locale);
        $value = $this->setAttribute($field, $value);
        $this->setLocale($prev_locale);
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getLocale()
    {
        return $this->locale ?: \Kb::getLocale() ?: \Kb::defaultLocale($this->mailbox);
    }

    public function translatedInLocale($locale)
    {
        $prev_locale = $this->getLocale();

        $this->setLocale($locale);

        foreach (['name'] as $field) {
            if (!$this->$field) {
                $this->setLocale($prev_locale);
                return false;
            }
        }
        $this->setLocale($prev_locale);
        return true;
    }
}