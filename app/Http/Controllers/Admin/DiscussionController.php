<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Discussion;
use App\Models\Discussion\Category;
use App\Models\User;
use App\Models\Setting;
use App\Traits\Form;
use Carbon\Carbon;
use App\Http\Requests\Discussion\StoreRequest;
use App\Http\Requests\Discussion\UpdateRequest;
use Illuminate\Support\Str;

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
        $this->middleware('auth');
        $this->middleware('admin.discussions');
        $this->model = new Discussion;
    }

    /**
     * Show the discussion list.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        // Gather the needed data to build the item list.
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $filters = $this->getFilters($request);
        $items = $this->model->getItems($request);
        $rows = $this->getRows($columns, $items);
        $query = $request->query();
        $url = ['route' => 'admin.discussions', 'item_name' => 'discussion', 'query' => $query];

        return view('admin.discussion.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    /**
     * Show the form for creating a new discussion.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create(Request $request)
    {
        // Gather the needed data to build the form.

        $fields = $this->getFields(['updated_by', 'created_at', 'updated_at', 'owner_name']);
        $actions = $this->getActions('form', ['destroy']);
        $query = $request->query();

        return view('admin.discussion.form', compact('fields', 'actions', 'query'));
    }

    /**
     * Show the form for editing the specified discussion.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
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

        // Gather the needed data to build the form.
        $except = (auth()->user()->getRoleLevel() > $discussion->getOwnerRoleLevel() || $discussion->owned_by == auth()->user()->id) ? ['owner_name'] : ['owned_by'];
        $fields = $this->getFields($except);
        $this->setFieldValues($fields, $discussion);
        $except = (!$discussion->canEdit()) ? ['destroy', 'save', 'saveClose'] : [];
        $actions = $this->getActions('form', $except);
        // Add the id parameter to the query.
        $query = array_merge($request->query(), ['discussion' => $id]);

        return view('admin.discussion.form', compact('discussion', 'fields', 'actions', 'query'));
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
        if ($discussion) {
            $menu->checkIn();
        }

        return redirect()->route('admin.discussions.index', $request->query());
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
            return response()->json(['redirect' => route('admin.discussions.index', $request->query())]);
        }

        if (!$discussion->canEdit()) {
            $request->session()->flash('error', __('messages.generic.edit_not_auth'));
            return response()->json(['redirect' => route('admin.discussions.index', $request->query())]);
        }

        $discussion->title = $request->input('title');
        $discussion->slug = Str::slug($request->input('title'), '-').'-'.$discussion->id;
        $discussion->description = $request->input('description');
        //$discussion->meta_data = $request->input('meta_data');
        //$discussion->extra_fields = $request->input('extra_fields');
        $discussion->settings = $request->input('settings');
        $discussion->updated_by = auth()->user()->id;
        //$layoutRefresh = LayoutItem::storeItems($discussion);
        // Prioritize layout items over regular content when storing raw content.
        //$discussion->raw_content = ($discussion->layoutItems()->exists()) ? $discussion->getLayoutRawContent() : strip_tags($request->input('content'));

        if ($discussion->canChangeAccessLevel()) {
            $discussion->access_level = $request->input('access_level');

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
            $discussion->owned_by = $request->input('owned_by');
        }

        if ($discussion->canChangeStatus()) {
            $discussion->status = $request->input('status');
        }

        $discussion->save();
        $discussion->category()->save($request->input('category'));

        $refresh = ['updated_at' => Setting::getFormattedDate($discussion->updated_at), 'updated_by' => auth()->user()->name, 'slug' => $discussion->slug];

        if ($request->input('_close', null)) {
            $discussion->checkIn();
            // Store the message to be displayed on the list view after the redirect.
            $request->session()->flash('success', __('messages.discussion.update_success'));
            return response()->json(['redirect' => route('admin.discussions.index', $request->query())]);
        }

        return response()->json(['success' => __('messages.discussion.update_success'), 'refresh' => $refresh]);
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
            'title' => $request->input('title'), 
            'status' => $request->input('status'), 
            'description' => $request->input('description'), 
            'access_level' => $request->input('access_level'), 
            'owned_by' => $request->input('owned_by'),
            'meta_data' => $request->input('meta_data'),
            'settings' => $request->input('settings'),
            'discussion_link' => $request->input('discussion_link'),
            'registering_alert' => $request->input('registering_alert'),
            'comment_alert' => $request->input('comment_alert'),
            'max_attendees' => $request->input('max_attendees'),
        ]);

        $discussion->slug = Str::slug($discussion->title, '-').'-'.$discussion->id;
        //$discussion->updated_by = auth()->user()->id;

        $discussion->save();

        $category = Category::find($request->input('category'));
        $category->discussions()->save($discussion);

        if ($request->input('groups') !== null) {
            $discussion->groups()->attach($request->input('groups'));
        }

        $request->session()->flash('success', __('messages.discussion.create_success'));

        if ($request->input('_close', null)) {
            return response()->json(['redirect' => route('admin.discussions.index', $request->query())]);
        }

        // Redirect to the edit form.
        return response()->json(['redirect' => route('admin.discussions.edit', array_merge($request->query(), ['discussion' => $discussion->id]))]);
    }

}
