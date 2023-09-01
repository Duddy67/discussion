<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Discussion;
use App\Models\Cms\Category;
use App\Models\Cms\Comment;
use App\Models\Discussion\Registration;
use App\Models\Discussion\Setting as DiscussionSetting;
use App\Models\Cms\Setting;
use App\Models\Cms\Email;
use App\Models\User;
use App\Models\User\Group;
use App\Traits\Form;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Discussion\StoreRequest;
use App\Http\Requests\Discussion\UpdateRequest;
use Illuminate\Support\Str;
use App\Http\Requests\Cms\Comment\StoreRequest as CommentStoreRequest;
use App\Http\Requests\Cms\Comment\UpdateRequest as CommentUpdateRequest;
use Carbon\Carbon;


class DiscussionController extends Controller
{
    use Form;

    /*
     * Instance of the Discussion model, (used in the Form trait).
     */
    protected $item = null;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['show', 'index']);
        // Unregistered users can access discussions.
        $this->middleware('discussions')->except(['show', 'index']);
        $this->item = new Discussion;
    }

    public function index(Request $request)
    {
        // Get the current day (yyyy-mm-dd).
        $daypicker = Carbon::now()->toDateString();

        if ($request->has('_day_picker') && $request->filled('_day_picker')) {
            $daypicker = $request->input('_day_picker');
        }

        $discussions = Discussion::select('discussions.*', 'users.nickname as organizer')
			->join('users', 'discussions.owned_by', '=', 'users.id')
			->where('discussion_date', 'LIKE', $daypicker.'%')
                        ->orderBy('discussion_date', 'asc')->get();

        $page = Setting::getPage('discussions');
        $query = $request->query();

        return view('themes.'.$page['theme'].'.index', compact('page', 'discussions', 'daypicker', 'query'));
    }

    public function show(Request $request, $id, $slug)
    {
        $discussion = Discussion::select('discussions.*', 'users.nickname as nickname', 'users.name as owner_name', 'users2.name as modifier_name')
			->leftJoin('users', 'discussions.owned_by', '=', 'users.id')
			->leftJoin('users as users2', 'discussions.updated_by', '=', 'users2.id')
			->where('discussions.id', $id)->first();

        $page = Setting::getPage('discussion');

        if (!$discussion) {
            $page['name'] = '404';
            return view('themes.'.$page['theme'].'.index', compact('page'));
	}

	if (!$discussion->canAccess()) {
            $page['name'] = '403';
            return view('themes.'.$page['theme'].'.index', compact('page'));
	}

	//$settings = $discussion->getSettings();
        //$discussion->time_before_discussion = $discussion->getTimeBeforeDiscussion();
        //$metaData = $discussion->meta_data;
        //$discussion->settings = $discussion->getSettings();
        $segments = Setting::getSegments('Discussion');
        $daypicker = ($request->has('_day_picker')) ? $request->input('_day_picker') : 0;
	$query = array_merge($request->query(), ['id' => $id, 'slug' => $slug]);

        return view('themes.'.$page['theme'].'.index', compact('page', 'id', 'slug', 'discussion', 'segments', 'daypicker', 'query'));
    }

    public function create(Request $request)
    {
        $page = Setting::getPage('discussion.form');
        $except = (auth()->user()->canAccessAdmin()) ? ['updated_by', 'created_at', 'owner_name', 'updated_at'] : ['updated_by', 'created_at', 'updated_at', 'owned_by', 'owner_name', 'access_level'];
        $fields = $this->getFields($except);
	$query = $request->query();

        return view('themes.'.$page['theme'].'.index', compact('page', 'fields', 'query'));
    }

    /**
     * Checks the record back in.
     *
     * @param  Request  $request
     * @param  \App\Models\Discussion $discussion (optional)
     * @return Response
     */
    public function cancel(Request $request, Discussion $discussion = null)
    {
        if ($discussion && $discussion->checked_out == auth()->user()->id) {
            $discussion->safeCheckIn();

            return redirect()->route('discussions.show', array_merge($request->query(), ['id' => $discussion->id, 'slug' => $discussion->slug]));
        }

        return redirect()->route('site.index', $request->query());
    }

    public function edit(Request $request, int $id)
    {
        $discussion = $this->item = Discussion::select('discussions.*', 'users.name as owner_name', 'users2.name as modifier_name')
                                    ->leftJoin('users', 'discussions.owned_by', '=', 'users.id')
                                    ->leftJoin('users as users2', 'discussions.updated_by', '=', 'users2.id')
                                    ->findOrFail($id);

        if (!$discussion->canAccess()) {
            return redirect()->route('discussions.show', ['id' => $discussion->id, 'slug' => $discussion->slug])->with('error',  __('messages.generic.access_not_auth'));
        }

        if ($discussion->checked_out && $discussion->checked_out != auth()->user()->id && !$discussion->isUserSessionTimedOut()) {
            return redirect()->route('discussions.show', ['id' => $discussion->id, 'slug' => $discussion->slug])->with('error',  __('messages.generic.checked_out'));
        }

        $discussion->checkOut();

        $page = Setting::getPage('discussion.form');
        // Gather the needed data to build the form.
        //$except = (auth()->user()->getRoleLevel() > $discussion->getOwnerRoleLevel() || $discussion->owned_by == auth()->user()->id) ? ['owner_name'] : ['owned_by'];
        $except = (auth()->user()->canAccessAdmin()) ? [] : ['updated_by', 'created_at', 'updated_at', 'owned_by', 'owner_name', 'access_level'];
        $fields = $this->getFields($except);
        //$this->setFieldValues($fields, $discussion);
        $except = (!$discussion->canEdit()) ? ['destroy', 'save', 'saveClose'] : [];
        //$page = 'discussion.form';
        // Add the id parameter to the query.
        $query = array_merge($request->query(), ['discussion' => $id]);

        return view('themes.'.$page['theme'].'.index', compact('page', 'discussion', 'fields', 'query'));
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
            'access_level' => ($request->input('access_level', null)) ? $request->input('access_level') : 'public_ro', 
            'owned_by' => ($request->input('owned_by', null)) ? $request->input('owned_by') : auth()->user()->id,
            'platform' => $request->input('platform'),
            'discussion_link' => $request->input('discussion_link'),
            'discussion_date' => $request->input('_discussion_date'),
            'is_private' => $request->input('is_private'),
            'registering_alert' => $request->input('registering_alert'),
            'comment_alert' => $request->input('comment_alert'),
            'max_attendees' => $request->input('max_attendees'),
        ]);

        $discussion->category()->associate($request->input('category_id'));
        $discussion->save();

        //$category = Category::find($request->input('category_id'));
        //$category->discussions()->save($discussion);

        if ($request->input('groups') !== null) {
            $discussion->groups()->attach($request->input('groups'));
        }

        // The owner (organizer) is automatically registered to the discussion.
        $this->register($discussion);

        $request->session()->flash('success', __('messages.discussion.create_success'));

        if ($request->input('_close', null)) {
            $query = array_merge($request->query(), ['id' => $id, 'slug' => $discussion->slug]);
            return response()->json(['redirect' => route('discussions.show', $query)]);
        }

        // Redirect to the edit form.
        return response()->json(['redirect' => route('discussions.edit', array_merge($request->query(), ['discussion' => $discussion->id]))]);
    }

    /**
     * Update the specified discussion. (AJAX)
     *
     * @param  \App\Http\Requests\Discussion\UpdateRequest  $request
     * @param  \App\Models\Discussion $discussion
     * @return JSON
     */
    public function update(UpdateRequest $request, Discussion $discussion)
    {
        if ($discussion->checked_out != auth()->user()->id) {
            $request->session()->flash('error', __('messages.generic.user_id_does_not_match'));
            return response()->json(['redirect' => route('discussions.show', ['id' => $discussion->id, 'slug' => $discussion->slug])]);
        }

        if (!$discussion->canEdit()) {
            $request->session()->flash('error', __('messages.generic.edit_not_auth'));
            return response()->json(['redirect' => route('discussions.show', ['id' => $discussion->id, 'slug' => $discussion->slug])]);
        }

        $discussion->subject = $request->input('subject');
        //$discussion->slug = Str::slug($request->input('subject'), '-').'-'.$discussion->id;
        $discussion->description = $request->input('description');
        $discussion->media_link = $request->input('media_link');
        $discussion->discussion_date = $request->input('_discussion_date');
        $discussion->platform = $request->input('platform');
        $discussion->discussion_link = $request->input('discussion_link');
        $discussion->registering_alert = $request->input('registering_alert');
        $discussion->is_private = $request->input('is_private');
        $discussion->comment_alert = $request->input('comment_alert');
        $discussion->max_attendees = $request->input('max_attendees');
        //$discussion->meta_data = $request->input('meta_data');
        //$discussion->extra_fields = $request->input('extra_fields');
        //$discussion->settings = $request->input('settings');
        $discussion->updated_by = auth()->user()->id;
        //$layoutRefresh = LayoutItem::storeItems($discussion);
        // Prioritize layout items over regular content when storing raw content.
        //$discussion->raw_content = ($discussion->layoutItems()->exists()) ? $discussion->getLayoutRawContent() : strip_tags($request->input('content'));

        if ($discussion->canChangeAccessLevel()) {
            $discussion->access_level = ($request->input('access_level', null)) ? $request->input('access_level') : $discussion->access_level;

            // N.B: Get also the private groups (if any) that are not returned by the form as they're disabled.
            $groups = array_merge($request->input('groups', []), Group::getPrivateGroups($discussion));

            if (!empty($groups)) {
                $discussion->groups()->sync($groups);
            }
            else {
                // Remove all groups for this discussion.
                $discussion->groups()->sync([]);
            }
        }

        if ($discussion->canChangeAttachments()) {
            $discussion->owned_by = ($request->input('owned_by', null)) ? $request->input('owned_by') : $discussion->owned_by;
        }

        if ($discussion->canChangeStatus()) {
            $discussion->status = $request->input('status');
        }

        $discussion->category()->associate($request->input('category_id'));
        $discussion->save();

        //$category = Category::find($request->input('category_id'));
        //$category->discussions()->save($discussion);

        //$refresh = ['updated_at' => Setting::getFormattedDate($discussion->updated_at), 'updated_by' => auth()->user()->name, 'slug' => $discussion->slug];

        if ($request->input('_close', null)) {
            $discussion->safeCheckIn();
            // Store the message to be displayed on the list view after the redirect.
            $request->session()->flash('success', __('messages.discussion.update_success'));
            $query = array_merge($request->query(), ['id' => $id, 'slug' => $discussion->slug]);
            return response()->json(['redirect' => route('discussions.show', $query)]);
        }
        $refresh = [];

        return response()->json(['success' => __('messages.discussion.update_success'), 'refresh' => $refresh]);
    }

    /**
     * Remove the specified discussion from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Discussion $discussion
     * @return Response
     */
    public function destroy(Request $request, Discussion $discussion)
    {
        if (!$discussion->canDelete()) {
            return redirect()->route('discussions.edit', array_merge($request->query(), ['discussion' => $discussion->id]))->with('error',  __('messages.generic.delete_not_auth'));
        }

        $subject = $discussion->subject;
        $discussion->delete();

        return redirect()->route('site.index', $request->query())->with('success', __('messages.discussion.delete_success', ['subject' => $subject]));
    }

    public function register(Discussion $discussion)
    {
        if (!$discussion->isUserRegistered() && !$discussion->isUserOnWaitingList()) {
            $onWaitingList = ($discussion->isSoldOut()) ? true : false;
            $registration = new Registration(['user_id' => auth()->user()->id, 'on_waiting_list' => $onWaitingList]);
            $discussion->registrations()->save($registration);
        }

        return redirect()->route('discussions.show', ['id' => $discussion->id, 'slug' => $discussion->slug]);
    }

    public function unregister(Discussion $discussion)
    {
        $discussion->registrations()->where('user_id', auth()->user()->id)->delete();

        if (!$discussion->isSoldOut() && $discussion->getAttendeesOnWaitingList()->count()) {
            // Switch the first user on the waiting list in the main list.
            $registration = new Registration(['user_id' => $discussion->getAttendeesOnWaitingList()[0]->user_id, 'on_waiting_list' => false]);
            $discussion->registrations()->save($registration);
            // Remove this user from the waiting list.
            $discussion->registrations()->where(['user_id' => $discussion->getAttendeesOnWaitingList()[0]->user_id, 'on_waiting_list' => true])->delete();
        }

        return redirect()->route('discussions.show', ['id' => $discussion->id, 'slug' => $discussion->slug]);
    }

    public function saveComment(CommentStoreRequest $request, $id, $slug)
    {
        $comment = Comment::create([
            'text' => $request->input('comment-0'), 
            'owned_by' => Auth::id()
        ]);

        $discussion = Discussion::find($id);
        $discussion->comments()->save($comment);

        // Set variables used in the render.
        $comment->author = auth()->user()->name;
        $theme = Setting::getValue('website', 'theme', 'starter');
        $page = Setting::getPage('discussion');
        $count = $discussion->comments()->count();
        $key = $count - 1;

        if ($discussion->comment_alert && auth()->user()->id != $discussion->owned_by) {
            $author = User::find($discussion->owned_by);
            $discussion->recipient = $author->email;
            $discussion->post_author = $author->name;
            $discussion->comment_author = auth()->user()->name;
            $discussion->post_url = url('/').$discussion->getUrl();
            Email::sendEmail('comment-alert', $discussion);
        }

        return response()->json([
            'id' => $comment->id, 
            'action' => 'create', 
            'render' => view('themes.'.$theme.'.partials.discussion.comment', compact('comment', 'page', 'count', 'key'))->render(),
            'text' => $comment->text,
            'count' => $count,
            'key' => $key,
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
