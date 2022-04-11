<?php

namespace App;

namespace Modules\KnowledgeBase\Entities;

use Illuminate\Database\Eloquent\Model;

class KbArticleKbCategory extends Model
{
    protected $table = 'kb_article_kb_category';

    public $timestamps = false;
}
