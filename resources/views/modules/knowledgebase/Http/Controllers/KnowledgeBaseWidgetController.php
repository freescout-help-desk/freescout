<?php

namespace Modules\KnowledgeBase\Http\Controllers;

use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class KnowledgeBaseWidgetController extends Controller
{
    /**
     * Widget form.
     */
    public function widgetForm(Request $request, $mailbox_id)
    {        
        $mailbox = new Mailbox();
        $mailbox->id = $mailbox_id;
        $article = null;
        $category = null;
        $results = [];

        // Set locale if needed.
        if (!empty($request->locale)) {
            app()->setLocale($request->locale);
        }

        $url_params = array_merge($request->all(), ['mailbox_id' => $mailbox_id]);

        $decoded_mailbox_id = null;
        if (!empty(request()->show_categories)) {
            $decoded_mailbox_id = \Kb::decodeMailboxId($mailbox_id, \Kb::WIDGET_SALT);
        }

        $categories = $this->getCategories($decoded_mailbox_id, $request->category_id);

        if (!empty($request->article_id)) {
            $article = \KbArticle::find($request->article_id);
        }

        if (!empty($request->category_id)) {
            $category = \KbCategory::find($request->category_id);
            if ($category && $category->isPublic()) {
                $articles = $category->getArticlesSorted(true);

                // Remove non-available articles.
                foreach ($articles as $i => $article_item) {
                    if ($article_item->isVisible()) {
                        $article = $article_item;
                        $results[] = [
                            'url' => \Kb::insideWidgetUrl($decoded_mailbox_id, ['article_id' => $article_item->id]),
                            'title' => $article_item->title,
                        ];
                    }
                }
                if (count($results) > 1) {
                    $article = null;
                }
            }
        }

        return view('knowledgebase::widget_form', [
            'mailbox' => $mailbox,
            'decoded_mailbox_id' => $decoded_mailbox_id,
            'form_action' => route('knowledgebase.widget_form', $url_params),
            'results' => $results,
            'article' => $article,
            'category' => $category,
            'contact_form_url' => $this->getContactFormUrl($mailbox_id),
            'categories' => $categories,
            'home_url' => $this->getHomeUrl($request, $mailbox_id),
        ]);
    }

    /**
     * Widget form search.
     */
    public function widgetFormProcess(Request $request, $mailbox_id)
    {        
        $mailbox = new Mailbox();
        $mailbox->id = $mailbox_id;
        $decoded_mailbox_id = null;
        $results = [];

        // Set locale if needed.
        if (!empty($request->locale)) {
            app()->setLocale($request->locale);
        }

        $url_params = array_merge($request->all(), ['mailbox_id' => $mailbox_id]);

        if (!empty($request->q)) {
            $q = trim($request->q);
            $like = '%'.mb_strtolower($q).'%';

            $decoded_mailbox_id = \Kb::decodeMailboxId($mailbox_id, \Kb::WIDGET_SALT);

            $articles = \KbArticle::where('mailbox_id', $decoded_mailbox_id)
                ->where('status', \KbArticle::STATUS_PUBLISHED)
                ->where(function ($query_like) use ($like) {
                    $query_like->whereRaw('lower(title) like ?', $like)
                        ->orWhereRaw('lower(text) like ?', $like);
                })
                ->get();

            // Remove non-available articles.
            foreach ($articles as $i => $article) {
                if (!$article->isVisible()) {
                    continue;
                }
                \Kb::$use_primary_if_empty = false;
                // Not available in current language.
                if (!mb_stristr($article->title, $q) && !mb_stristr($article->text, $q)) {
                    continue;
                }
                \Kb::$use_primary_if_empty = true;
                $results[] = [
                    'url' => \Kb::insideWidgetUrl($decoded_mailbox_id, ['article_id' => $article->id, 'from_search' => $q]),
                    'title' => $article->title,
                ];
            }
        }

        return view('knowledgebase::widget_form', [
            'mailbox' => $mailbox,
            'decoded_mailbox_id' => $decoded_mailbox_id,
            'form_action' => route('knowledgebase.widget_form', $url_params),
            'home_url' => $this->getHomeUrl($request, $mailbox_id),
            'results' => $results,
            'contact_form_url' => $this->getContactFormUrl($mailbox_id, $decoded_mailbox_id),
            'categories' => $this->getCategories($decoded_mailbox_id),
        ]);
    }

    public function getHomeUrl($request, $mailbox_id)
    {
        return route('knowledgebase.widget_form', array_merge($request->all(), [
            'mailbox_id' => $mailbox_id,
            'q' => '',
            'category_id' => '',
            'article_id' => '',
            'from_search' => ''
        ]));
    }

    private function getContactFormUrl($encoded_mailbox_id = null, $decoded_mailbox_id = null)
    {
        if (empty(request()->q) && empty(request()->show_categories)) {
            return '';
        }
        if (\Module::isActive('chat')) {
            if (empty($decoded_mailbox_id)) {
                $decoded_mailbox_id = \Kb::decodeMailboxId($encoded_mailbox_id, \Kb::WIDGET_SALT);
            }
            return route('chat.widget_form', array_merge(request()->all(), ['mailbox_id' => \Chat::encodeMailboxId($decoded_mailbox_id), 'back_url' => \Request::getRequestUri()]));
        }
        if (\Module::isActive('enduserportal')) {
            if (empty($decoded_mailbox_id)) {
                $decoded_mailbox_id = \Kb::decodeMailboxId($encoded_mailbox_id, \Kb::WIDGET_SALT);
            }
            return route('enduserportal.widget_form', array_merge(request()->all(), ['mailbox_id' => \EndUserPortal::encodeMailboxId($decoded_mailbox_id, \EndUserPortal::WIDGET_SALT), 'back_url' => \Request::getRequestUri()]));
        }

        return '';
    }

    private function getCategories($decoded_mailbox_id = null, $parent_category_id = 0)
    {
        if (empty(request()->show_categories) && !$parent_category_id) {
            return [];
        }

        $subcategories = [];
        if ($parent_category_id) {
            $category = \KbCategory::find($parent_category_id);
            if ($category) {
                $subcategories = $category->getSubCategories();
            }
        }

        // Get tree removes private categories.
        return \KbCategory::getTree($decoded_mailbox_id, $subcategories, $parent_category_id);
    }
}
