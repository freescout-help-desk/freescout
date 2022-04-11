<?php

namespace Modules\Tags\Http\Controllers;

use Modules\Tags\Entities\Tag;
use Modules\Tags\Entities\ConversationTag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TagsController extends Controller
{
    /**
     * Ajax.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {
            case 'add':
                if (!empty($request->tag_names)) {
                    foreach ($request->tag_names as $tag_name) {

                        if (is_array($request->conversation_id)) {
                            foreach ($request->conversation_id as $conversation_id) {
                                $tag = Tag::attachByName($tag_name, $conversation_id);
                            }
                        } else {
                            $tag = Tag::attachByName($tag_name, $request->conversation_id);
                        }
                           
                        if ($tag) {
                            $response['tags'][] = [
                                'name' => $tag->name,
                                'url'  => $tag->getUrl(),
                            ];
                        }
                    }
                }
                $response['status'] = 'success';
                break;

            case 'remove':
                Tag::detachByName($request->tag_name, $request->conversation_id);

                $response['status'] = 'success';
                break;

            case 'autocomplete':
                $response = [
                    'results'    => [],
                    'pagination' => ['more' => false],
                ];

                $q = $request->q;

                if (!empty($request->use_id)) {
                    $query = Tag::select('name', 'id');
                } else {
                    $query = Tag::select('name');
                }
                $tags = $query->where('name', 'like', '%'.$q.'%')
                    ->paginate(20);

                foreach ($tags as $tag) {
                    $id = $tag->name;
                    if (!empty($request->use_id)) {
                        $id = $tag->id;
                    }
                    $response['results'][] = [
                        'id'   => $id,
                        'text' => $tag->name,
                    ];
                }

                $response['pagination']['more'] = $tags->hasMorePages();

                return \Response::json($response);
                
                break;

            case 'update':
                $user = \Auth::user();

                if (Tag::canUserEditTags($user)) {
                    $tag = Tag::find($request->tag_id);
                    if ($tag) {
                        $name = Tag::normalizeName($request->name);
                        try {
                            if (Tag::where('id', '<>', $tag->id)->where('name', $name)->count() == 0) {
                                $tag->name = $name;
                                $tag->setColor($request->color);
                                $tag->save();

                                $response['status'] = 'success';
                            } else {
                                $response['msg'] = __('Tag with such name already exists');
                            }
                        } catch (\Exception $e) {

                        }
                    }
                }
                break;

            case 'delete_forever':
                $user = \Auth::user();

                if (Tag::canUserEditTags($user)) {
                    $tag = Tag::find($request->tag_id);
                    if ($tag) {
                        try {
                            ConversationTag::where('tag_id', $tag->id)->delete();
                            $tag->delete();
                            $response['status'] = 'success';
                        } catch (\Exception $e) {
                            $response['msg'] = __('Tag deleted');
                        }
                    }
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }

    public function tags(Request $request)
    {
        $query = Tag::select('tags.*')->orderBy('name');

        $user = \Auth::user();

        if (!$user->isAdmin()) {
            $query->leftJoin('conversation_tag', function ($join) {
                    $join->on('conversation_tag.tag_id', '=', 'tags.id');
                })
                ->leftJoin('conversations', function ($join) {
                    $join->on('conversations.id', '=', 'conversation_tag.conversation_id');
                })
                ->whereIn('conversations.mailbox_id', $user->mailboxesIdsCanView())
                ->groupBy('tags.id');
        }

        /*if (!empty($request->mailbox_id)) {
            
        }*/

        $tags = $query->get();

        return view('tags::tags', [
            'tags' => $tags
        ]);
    }

    /**
     * Ajax HTML controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'update':
                return view('tags::partials/update', [
                    'tag' => Tag::find($request->param),
                ]);
        }

        abort(404);
    }
}
