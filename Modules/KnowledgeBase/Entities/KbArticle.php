<?php

namespace App;

namespace Modules\KnowledgeBase\Entities;

use Illuminate\Database\Eloquent\Model;

class KbArticle extends Model
{
    const STATUS_DRAFT = 1;
    const STATUS_PUBLISHED = 2;

    public $locale = '';

    public static $translatable_fields = ['title', 'slug', 'text'];
    
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    /**
     * Attributes fillable using fill() method.
     *
     * @var [type]
     */
    protected $fillable = ['mailbox_id', 'title', 'slug', 'text'];

    /**
     * This method is used only for translations.
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox')->rememberForever();
    }

    public function categories()
    {
        return $this->belongsToMany('Modules\KnowledgeBase\Entities\KbCategory');
    }

    public function getStatusName()
    {
        switch ($this->status) {
            case self::STATUS_DRAFT:
                return __('Draft');
            case self::STATUS_PUBLISHED:
                return __('Published');
        }
    }

    public function isPublished()
    {
        return $this->status == self::STATUS_PUBLISHED;
    }

    public function urlFrontend($mailbox, $category_id = null)
    {
        $params = ['mailbox_id'=>\Kb::encodeMailboxId($mailbox->id), 'article_id' => $this->id];
        if (!$category_id && request()->category_id) {
            $category_id = request()->category_id;
        }
        if ($category_id) {
            $params['category_id'] = $category_id;
        }
        if ($this->slug) {
            $params['slug'] = $this->slug;
            return \Kb::route('knowledgebase.frontend.article', $params, $mailbox);
        } else {
            return \Kb::route('knowledgebase.frontend.article_without_slug', $params, $mailbox);
        }
    }

    public function isVisible()
    {
        if (auth()->user()) {
            return true;
        }
        
        $article_categories = $this->categories;
        $visible = false;
        if (!count($article_categories)) {
            $visible = true;
        }
        if (!$visible) {
            foreach ($article_categories as $article_category) {
                if ($article_category->isPublic()) {
                    $visible = true;
                    break;
                }
            }
        }
        return $visible;
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
                    'l' => \Kb::defaultLocale($this->mailbox),
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
                
                $decoded_value['l'] = \Kb::defaultLocale($this->mailbox);

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

        foreach (['title', 'text'] as $field) {
            if (!$this->$field) {
                $this->setLocale($prev_locale);
                return false;
            }
        }
        $this->setLocale($prev_locale);
        return true;
    }
}
