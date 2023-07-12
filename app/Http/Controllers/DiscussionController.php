<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Discussion;
use App\Models\Discussion\Comment;
use App\Models\Discussion\Registration;
use App\Models\Discussion\Setting as DiscussionSetting;
use App\Models\Menu;
use App\Models\Setting;
use App\Traits\Form;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Discussion\StoreRequest;
use App\Http\Requests\Discussion\UpdateRequest;
use Illuminate\Support\Str;
use App\Http\Requests\Discussion\Comment\StoreRequest as CommentStoreRequest;
use App\Http\Requests\Discussion\Comment\UpdateRequest as CommentUpdateRequest;


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
        $except = (auth()->user()->canAccessAdmin()) ? ['updated_by', 'created_at', 'owner_name', 'updated_at'] : ['updated_by', 'created_at', 'updated_at', 'owned_by', 'owner_name', 'access_level'];
        $fields = $this->getFields($except);
        $page = 'discussion.form';
	$query = $request->query();

        return view('themes.'.$theme.'.index', compact('page', 'menu', 'fields', 'timezone', 'query'));
    }

    public function cancel(Request $request)
    {
    }

    public function edit(Request $request, int $id)
    {
        $discussion = $this->item = Discussion::select('discussions.*', 'users.name as owner_name', 'users2.name as modifier_name')
                                    ->leftJoin('users', 'discussions.owned_by', '=', 'users.id')
                                    ->leftJoin('users as users2', 'discussions.updated_by', '=', 'users2.id')
                                    ->findOrFail($id);

        if (!$discussion->canAccess()) {
            return redirect()->route('admin.discussions.index')->with('error',  __('messages.generic.access_not_auth'));
        }

        if ($discussion->checked_out && $discussion->checked_out != auth()->user()->id && !$discussion->isUserSessionTimedOut()) {
            return redirect()->route('admin.discussions.index')->with('error',  __('messages.generic.checked_out'));
        }

        $discussion->checkOut();

        $menu = Menu::getMenu('main-menu');
        $menu->allow_registering = Setting::getValue('website', 'allow_registering', 0);
        $theme = Setting::getValue('website', 'theme', 'starter');
        $timezone = Setting::getValue('app', 'timezone');
        // Gather the needed data to build the form.
        //$except = (auth()->user()->getRoleLevel() > $discussion->getOwnerRoleLevel() || $discussion->owned_by == auth()->user()->id) ? ['owner_name'] : ['owned_by'];
        $except = (auth()->user()->canAccessAdmin()) ? [] : ['updated_by', 'created_at', 'updated_at', 'owned_by', 'owner_name', 'access_level'];
        $fields = $this->getFields($except);
        //$this->setFieldValues($fields, $discussion);
        $except = (!$discussion->canEdit()) ? ['destroy', 'save', 'saveClose'] : [];
        $page = 'discussion.form';
        // Add the id parameter to the query.
        $query = array_merge($request->query(), ['discussion' => $id]);

        return view('themes.'.$theme.'.index', compact('page', 'menu', 'fields', 'timezone', 'query'));
    }

    /**
     * Store a new discussion. (AJAX)
     *
     * @param  \App\Http\Requests\Discussion\StoreRequest  $request
     * @return JSON 
     */
    public function store(StoreRequest $request)
    {
        $discussion = Discussion::create([
            'subject' => $request->input('subject'), 
            'status' => $request->input('status'), 
            'description' => $request->input('description'), 
            'media_link' => $request->input('media_link'), 
            'access_level' => $request->input('access_level'), 
            'owned_by' => auth()->user()->id,
            'platform' => $request->input('platform'),
            'discussion_link' => $request->input('discussion_link'),
            'discussion_date' => $request->input('_discussion_date'),
            'registering_alert' => $request->input('registering_alert'),
            'comment_alert' => $request->input('comment_alert'),
            'max_attendees' => $request->input('max_attendees'),
        ]);

        $discussion->slug = Str::slug($discussion->subject, '-').'-'.$discussion->id;

        $discussion->save();

        $category = Category::find($request->input('category_id'));
        $category->discussions()->save($discussion);

        if ($request->input('groups') !== null) {
            $discussion->groups()->attach($request->input('groups'));
        }

        $request->session()->flash('success', __('messages.discussion.create_success'));

        if ($request->input('_close', null)) {
            //return response()->json(['redirect' => route('discussions.index', $request->query())]);
        }

        // Redirect to the edit form.
        return response()->json(['redirect' => route('discussions.edit', array_merge($request->query(), ['discussion' => $discussion->id]))]);
    }

    public function register(Discussion $discussion)
    {
        if (!$discussion->isUserRegistered() && !$discussion->isUserOnWaitingList()) {
            $waitingList = ($discussion->registrations->count() == $discussion->max_attendees) ? true : false;
            $registration = new Registration(['user_id' => auth()->user()->id, 'on_waiting_list' => $waitingList]);
            $discussion->registrations()->save($registration);
        }

        return redirect()->route('discussions', ['id' => $discussion->id, 'slug' => $discussion->slug]);
    }

    public function unregister(Discussion $discussion)
    {
        $discussion->registrations()->where('user_id', auth()->user()->id)->delete();

        return redirect()->route('discussions', ['id' => $discussion->id, 'slug' => $discussion->slug]);
    }

    public function saveComment(CommentStoreRequest $request, $id, $slug)
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

    public function updateComment(CommentUpdateRequest $request, Comment $comment)
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
