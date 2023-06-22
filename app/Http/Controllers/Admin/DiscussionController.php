<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Discussion;
use App\Models\User;
use App\Models\Setting;
use App\Traits\Form;
use Carbon\Carbon;

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
        $discussion = $this->item = Post::select('discussions.*', 'users.name as owner_name', 'users2.name as modifier_name')
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

}
