<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Discussion;
use App\Models\Discussion\Category;
use App\Models\User;
use App\Models\User\Group;
use App\Models\Setting;
use App\Traits\Form;
use App\Traits\CheckInCheckOut;
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
        $rows = $this->getRows($columns, $items, ['category_id', 'attendees']);
        $this->setRowValues($rows, $columns, $items);
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
        //$this->setFieldValues($fields, $discussion);
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
        if ($discussion && $discussion->checked_out == auth()->user()->id) {
            $discussion->safeCheckIn();
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

        $discussion->subject = $request->input('subject');
        $discussion->slug = Str::slug($request->input('subject'), '-').'-'.$discussion->id;
        $discussion->description = $request->input('description');
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
        //$discussion->category()->save($request->input('category'));
        $category = Category::find($request->input('category_id'));
        $category->discussions()->save($discussion);

        $refresh = ['updated_at' => Setting::getFormattedDate($discussion->updated_at), 'updated_by' => auth()->user()->name, 'slug' => $discussion->slug];

        if ($request->input('_close', null)) {
            $discussion->safeCheckIn();
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
            'subject' => $request->input('subject'), 
            'status' => $request->input('status'), 
            'description' => $request->input('description'), 
            'access_level' => $request->input('access_level'), 
            'owned_by' => $request->input('owned_by'),
            //'meta_data' => $request->input('meta_data'),
            'settings' => $request->input('settings'),
            'platform' => $request->input('platform'),
            'discussion_link' => $request->input('discussion_link'),
            'discussion_date' => $request->input('_discussion_date'),
            'registering_alert' => $request->input('registering_alert'),
            'comment_alert' => $request->input('comment_alert'),
            'max_attendees' => $request->input('max_attendees'),
        ]);

        $discussion->slug = Str::slug($discussion->subject, '-').'-'.$discussion->id;
        //$discussion->updated_by = auth()->user()->id;

        $discussion->save();

        $category = Category::find($request->input('category_id'));
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
            return redirect()->route('admin.discussions.edit', array_merge($request->query(), ['discussion' => $discussion->id]))->with('error',  __('messages.generic.delete_not_auth'));
        }

        $name = $discussion->name;
        $discussion->delete();

        return redirect()->route('admin.discussions.index', $request->query())->with('success', __('messages.discussion.delete_success', ['name' => $name]));
    }

    /**
     * Removes one or more discussions from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massDestroy(Request $request)
    {
        $deleted = 0;

        // Remove the discussions selected from the list.
        foreach ($request->input('ids') as $id) {
            $discussion = Discussion::findOrFail($id);

            if (!$discussion->canDelete()) {
              return redirect()->route('admin.discussions.index', $request->query())->with(
                  [
                      'error' => __('messages.generic.delete_not_auth'), 
                      'success' => __('messages.discussion.delete_list_success', ['number' => $deleted])
                  ]);
            }

            $discussion->delete();

            $deleted++;
        }

        return redirect()->route('admin.discussions.index', $request->query())->with('success', __('messages.discussion.delete_list_success', ['number' => $deleted]));
    }

    /**
     * Checks in one or more discussions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massCheckIn(Request $request)
    {
        $messages = CheckInCheckOut::checkInMultiple($request->input('ids'), '\\App\\Models\\Discussion');

        return redirect()->route('admin.discussions.index', $request->query())->with($messages);
    }

    /**
     * Show the batch form (into an iframe).
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function batch(Request $request)
    {
        $fields = $this->getSpecificFields(['access_level', 'owned_by', 'groups']);
        $actions = $this->getActions('batch');
        $query = $request->query();
        $route = 'admin.discussions';

        return view('admin.share.batch', compact('fields', 'actions', 'query', 'route'));
    }

    /**
     * Updates the access_level and owned_by parameters of one or more discussions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massUpdate(Request $request)
    {
        $updates = 0;
        $messages = [];

        foreach ($request->input('ids') as $key => $id) {
            $discussion = Discussion::findOrFail($id);
            $updated = false;

            // Check for authorisation.
            if (!$discussion->canEdit()) {
                $messages['error'] = __('messages.generic.mass_update_not_auth');
                continue;
            }

            if ($request->input('owned_by') !== null && $discussion->canChangeAttachments()) {
                $discussion->owned_by = $request->input('owned_by');
                $updated = true;
            }

            if ($request->input('access_level') !== null && $discussion->canChangeAccessLevel()) {
                $discussion->access_level = $request->input('access_level');
                $updated = true;
            }

            if ($request->input('groups') !== null && $discussion->canChangeAccessLevel()) {
                if ($request->input('_selected_groups') == 'add') {
                    $discussion->groups()->syncWithoutDetaching($request->input('groups'));
                }
                else {
                    // Remove the selected groups from the current groups and get the remaining groups.
                    $groups = array_diff($discussion->getGroupIds(), $request->input('groups'));
                    $discussion->groups()->sync($groups);
                }

                $updated = true;
            }

            if ($updated) {
                $discussion->save();
                $updates++;
            }
        }

        if ($updates) {
            $messages['success'] = __('messages.generic.mass_update_success', ['number' => $updates]);
        }

        return redirect()->route('admin.discussions.index')->with($messages);
    }

    /**
     * Publishes one or more discussions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massPublish(Request $request)
    {
        $published = 0;

        foreach ($request->input('ids') as $id) {
            $discussion = Discussion::findOrFail($id);

            if (!$discussion->canChangeStatus()) {
              return redirect()->route('admin.discussions.index', $request->query())->with(
                  [
                      'error' => __('messages.generic.mass_publish_not_auth'), 
                      'success' => __('messages.discussion.publish_list_success', ['number' => $published])
                  ]);
            }

            $discussion->status = 'published';

            $discussion->save();

            $published++;
        }

        return redirect()->route('admin.discussions.index', $request->query())->with('success', __('messages.discussion.publish_list_success', ['number' => $published]));
    }

    /**
     * Unpublishes one or more discussions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massUnpublish(Request $request)
    {
        $unpublished = 0;

        foreach ($request->input('ids') as $id) {
            $discussion = Discussion::findOrFail($id);

            if (!$discussion->canChangeStatus()) {
              return redirect()->route('admin.discussions.index', $request->query())->with(
                  [
                      'error' => __('messages.generic.mass_unpublish_not_auth'), 
                      'success' => __('messages.discussion.unpublish_list_success', ['number' => $unpublished])
                  ]);
            }

            $discussion->status = 'unpublished';

            $discussion->save();

            $unpublished++;
        }

        return redirect()->route('admin.discussions.index', $request->query())->with('success', __('messages.discussion.unpublish_list_success', ['number' => $unpublished]));
    }

    /*
     * Sets the row values specific to the Discussion model.
     *
     * @param  Array  $rows
     * @param  Array of stdClass Objects  $columns
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $discussions
     * @return void
     */
    private function setRowValues(&$rows, $columns, $discussions)
    {
        foreach ($discussions as $key => $discussion) {
            foreach ($columns as $column) {
                if ($column->name == 'category') {
                    $rows[$key]->category = $discussion->category->name;
                }

                if ($column->name == 'attendees') {
                    $rows[$key]->attendees = $discussion->subscriptions->count().'/'.$discussion->max_attendees;
                }
            }
        }
    }

    /*
     * Sets field values specific to the Discussion model.
     *
     * @param  Array of stdClass Objects  $fields
     * @param  \App\Models\Discussion $discussion
     * @return void
     */
    /*private function setFieldValues(array &$fields, Discussion $discussion = null)
    {
        $globalSettings = DiscussionSetting::getDataByGroup('discussions');
        foreach ($globalSettings as $key => $value) {
            if (str_starts_with($key, 'alias_extra_field_')) {
                foreach ($fields as $field) {
                    if ($field->name == $key) {
                        $field->value = ($value) ? $value : __('labels.generic.none');
                    }
                }
            }
        }
    }*/


}
