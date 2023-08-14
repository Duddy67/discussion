<?php

namespace App\Models\Discussion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Discussion;
use App\Models\Cms\Document;
use App\Models\Cms\Setting;
use App\Models\User\Group;
use App\Models\Discussion\Setting as DiscussionSetting;
use App\Traits\Node;
use App\Traits\TreeAccessLevel;
use App\Traits\CheckInCheckOut;
use App\Traits\OptionList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class Category extends Model
{
    use HasFactory, Node, TreeAccessLevel, CheckInCheckOut, OptionList;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'discussion_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'owned_by',
        'description',
        'access_level',
        'parent_id',
        'meta_data',
        'alt_img',
        'settings',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'checked_out_time'
    ];

    /**
     * The attributes that should be casted.
     *
     * @var array
     */
    protected $casts = [
        'meta_data' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the discussions for the category.
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    /**
     * The groups that belong to the category.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'discussion_category_group');
    }

    /**
     * Get the image associated with the category.
     */
    public function image()
    {
        return $this->morphOne(Document::class, 'documentable')->where('field', 'image');
    }

    /**
     * Delete the model from the database (override).
     *
     * @return bool|null
     *
     * @throws \LogicException
     */
    public function delete()
    {
        if ($this->image) {
            $this->image->delete();
        }

        $this->discussions()->detach();

        parent::delete();
    }

    /*
     * Gets the category items as a tree.
     */
    public static function getCategories(Request $request)
    {
        $search = $request->input('search', null);

        if ($search !== null) {
            return Category::where('name', 'like', '%'.$search.'%')->get();
        }
        else {
            return Category::select('discussion_categories.*', 'users.name as owner_name')->leftJoin('users', 'discussion_categories.owned_by', '=', 'users.id')->defaultOrder()->get()->toTree();
        }
    }

    public function getUrl()
    {
        $segments = Setting::getSegments('Discussion');
        return '/'.$segments['categories'].'/'.$this->id.'/'.$this->slug;
    }

    /*
     * Returns discussions without pagination.
     */
    public function getAllDiscussions(Request $request)
    {
        $query = $this->getQuery($request);
        return $query->get();
    }

    public function getAll()
    {
        return Discussion::all();
    }

    /*
     * Returns filtered and paginated discussions.
     */
    public function getDiscussions(Request $request)
    {
        $perPage = $request->input('per_page', Setting::getValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $query = $this->getQuery($request);

        if ($search !== null) {
            $query->where('discussions.subject', 'like', '%'.$search.'%');
        }

        return $query->paginate($perPage);
    }

    /*
     * Builds the Discussion query.
     */
    private function getQuery(Request $request)
    {
        $query = Discussion::query();
        $query->select('discussions.*', 'users.nickname as organizer')->leftJoin('users', 'discussions.owned_by', '=', 'users.id');
        // Join the role tables to get the owner's role level.
        $query->join('model_has_roles', 'discussions.owned_by', '=', 'model_id')->join('roles', 'roles.id', '=', 'role_id');

        // Get only the discussions related to this category. 
        $query->whereHas('category', function ($query) {
            $query->where('id', $this->id);
        });

        if (Auth::check()) {

            // N.B: Put the following part of the query into brackets.
            $query->where(function($query) {

                // Check for access levels.
                $query->where(function($query) {
                    $query->where('roles.role_level', '<', auth()->user()->getRoleLevel())
                          ->orWhereIn('discussions.access_level', ['public_ro', 'public_rw'])
                          ->orWhere('discussions.owned_by', auth()->user()->id);
                });

                $groupIds = auth()->user()->getGroupIds();

                if (!empty($groupIds)) {
                    // Check for access through groups.
                    $query->orWhereHas('groups', function ($query)  use ($groupIds) {
                        $query->whereIn('id', $groupIds);
                    });
                }
            });
        }
        else {
            $query->whereIn('discussions.access_level', ['public_ro', 'public_rw']);
        }
 
        // Do not show unpublished discussions on front-end.
        $query->where('discussions.status', 'published');

        // Set discussion ordering.
        $settings = $this->getSettings();

        if ($settings['discussion_ordering'] != 'no_ordering') {
            // Extract the ordering name and direction from the setting value.
            preg_match('#^([a-z-0-9_]+)_(asc|desc)$#', $settings['discussion_ordering'], $ordering);
            $query->orderBy($ordering[1], $ordering[2]);
        }

        return $query;
    }

    /*public function getParentIdOptions()
    {
        $nodes = Category::get()->toTree();
        $options = [];
        // Defines the state of the current instance.
        $isNew = ($this->id) ? false : true;

        $traverse = function ($categories, $prefix = '-') use (&$traverse, &$options, $isNew) {

            foreach ($categories as $category) {
                if (!$isNew && $this->access_level != 'private') {
                    // A non private category cannot be a private category's children.
                    $extra = ($category->access_level == 'private') ? ['disabled'] : [];
                }
                elseif (!$isNew && $this->access_level == 'private' && $category->access_level == 'private') {
                      // Only the category's owner can access it.
                      $extra = ($category->owned_by == auth()->user()->id) ? [] : ['disabled'];
                }
                elseif ($isNew && $category->access_level == 'private') {
                      // Only the category's owner can access it.
                      $extra = ($category->owned_by == auth()->user()->id) ? [] : ['disabled'];
                }
                else {
                    $extra = [];
                }

                $options[] = ['value' => $category->id, 'text' => $prefix.' '.$category->name, 'extra' => $extra];

                $traverse($category->children, $prefix.'-');
            }
        };

        $traverse($nodes);

        return $options;
    }*/

    public function getOwnedByOptions()
    {
        $users = auth()->user()->getAssignableUsers(['assistant', 'registered']);
        $options = [];

        foreach ($users as $user) {
            $extra = [];

            // The user is a manager who doesn't or no longer have the create-post-category permission.
            if ($user->getRoleType() == 'manager' && !$user->can('create-post-category')) {
                // The user owns this category.
                // N.B: A new owner will be required when updating this category.
                if ($this->id && $this->access_level != 'private') {
                    // Don't show this user.
                    continue;
                }

                // If the user owns a private category his name will be shown until the category is no longer private.
            }

            $options[] = ['value' => $user->id, 'text' => $user->name, 'extra' => $extra];
        }

        return $options;
    }

    public function getSettings()
    {
        return Setting::getItemSettings($this, 'categories');
    }

    public function getDiscussionOrderingOptions()
    {
        return DiscussionSetting::getDiscussionOrderingOptions();
    }

    /*
     * Generic function that returns model values which are handled by select inputs.
     */
    public function getSelectedValue(\stdClass $field): mixed
    {
        if ($field->name == 'groups') {
            return $this->groups->pluck('id')->toArray();
        }

        if (isset($field->group) && $field->group == 'settings') {
            return (isset($this->settings[$field->name])) ? $this->settings[$field->name] : null;
        }

        return $this->{$field->name};
    }
}
