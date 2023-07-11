<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Discussion;
use App\Models\Discussion\Comment;
use App\Models\Discussion\Setting as DiscussionSetting;
use App\Models\Menu;
use App\Models\Setting;
use App\Traits\Form;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Discussion\Comment\StoreRequest;
use App\Http\Requests\Discussion\Comment\UpdateRequest;


class DiscussionController extends Controller
{
    use Form;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * The item to edit in the form.
     */
    protected $item = null;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['show']);
        $this->middleware('discussions')->except(['show']);
        $this->model = new Discussion;
    }

    public function show(Request $request, $id, $slug)
    {
        $discussion = Discussion::select('discussions.*', 'users.nickname as nickname', 'users.name as owner_name', 'users2.name as modifier_name')
			->leftJoin('users', 'discussions.owned_by', '=', 'users.id')
			->leftJoin('users as users2', 'discussions.updated_by', '=', 'users2.id')
			->where('discussions.id', $id)->first();

        $menu = Menu::getMenu('main-menu');
        $menu->allow_registering = Setting::getValue('website', 'allow_registering', 0);
        $theme = Setting::getValue('website', 'theme', 'starter');

        if (!$discussion) {
            $page = '404';
            return view('themes.'.$theme.'.index', compact('page', 'menu'));
	}

	if (!$discussion->canAccess()) {
            $page = '403';
            return view('themes.'.$theme.'.index', compact('page', 'menu'));
	}

        $page = 'discussion';

        //$discussion->global_settings = DiscussionSetting::getDataByGroup('discussions');
	//$settings = $discussion->getSettings();
        //$discussion->time_before_discussion = $discussion->getTimeBeforeDiscussion();
        $timezone = Setting::getValue('app', 'timezone');
        //$metaData = $discussion->meta_data;
        $segments = Setting::getSegments('Discussion');
	$query = array_merge($request->query(), ['id' => $id, 'slug' => $slug]);

        return view('themes.'.$theme.'.index', compact('page', 'menu', 'id', 'slug', 'discussion', 'segments', 'timezone', 'query'));
    }

    public function create(Request $request)
    {
        $menu = Menu::getMenu('main-menu');
        $menu->allow_registering = Setting::getValue('website', 'allow_registering', 0);
        $theme = Setting::getValue('website', 'theme', 'starter');
        $timezone = Setting::getValue('app', 'timezone');
        $fields = $this->getFields(['updated_by', 'created_at', 'updated_at', 'owner_name', 'access_level']);
        $page = 'discussion.form';
	$query = $request->query();

        return view('themes.'.$theme.'.index', compact('page', 'menu', 'fields', 'timezone', 'query'));
    }

    public function cancel(Request $request)
    {
    }

    public function edit(Request $request)
    {
        echo 'EDIT';
    }

    public function unregister()
    {
    }

    public function saveComment(StoreRequest $request, $id, $slug)
    {
        $comment = Comment::create([
            'text' => $request->input('comment-0'), 
            'owned_by' => Auth::id()
        ]);

        $discussion = Discussion::find($id);
        $discussion->comments()->save($comment);

        $comment->author = auth()->user()->name;
        $theme = Setting::getValue('website', 'theme', 'starter');
        $timezone = Setting::getValue('app', 'timezone');

        return response()->json([
            'id' => $comment->id, 
            'action' => 'create', 
            'render' => view('themes.'.$theme.'.partials.discussion.comment', compact('comment', 'timezone'))->render(),
            'text' => $comment->text,
            'message' => __('messages.discussion.create_comment_success'),
        ]);
    }

    public function updateComment(UpdateRequest $request, Comment $comment)
    {
        // Make sure the user match the comment owner.
        if (auth()->user()->id != $comment->owned_by) {
            return response()->json([
                'errors' => [],
                'commentId' => $comment->id,
                'status' => true,
                'message' => __('messages.discussion.edit_comment_not_auth')
            ], 422);
        }

        $comment->text = $request->input('comment-'.$comment->id); 
        $comment->save();

        return response()->json([
            'id' => $comment->id, 
            'action' => 'update', 
            'message' => __('messages.discussion.update_comment_success')
        ]);
    }

    public function deleteComment(Request $request, Comment $comment)
    {
        // Make sure the user match the comment owner.
        if (auth()->user()->id != $comment->owned_by) {
            return response()->json([
                'errors' => [],
                'commentId' => $comment->id,
                'status' => true,
                'message' => __('messages.discussion.delete_comment_not_auth')
            ], 422);
        }

        $id = $comment->id;
        $comment->delete();

        return response()->json([
            'id' => $id, 
            'action' => 'delete', 
            'message' => __('messages.discussion.delete_comment_success')
        ]);
    }
}
