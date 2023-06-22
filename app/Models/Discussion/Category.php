<?php

namespace App\Models\Discussion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Discussion;
use App\Models\Cms\Document;
use App\Models\Setting;
use App\Traits\Node;
use App\Traits\TreeAccessLevel;
use App\Traits\CheckInCheckOut;
use Illuminate\Http\Request;

class Category extends Model
{
    use HasFactory, Node, TreeAccessLevel, CheckInCheckOut;

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
    ];

    /**
     * Get the discussions for the category.
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
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
    public function getItems(Request $request)
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
        return '/'.$segments['category'].'/'.$this->id.'/'.$this->slug;
    }

    /*
     * Returns discussions without pagination.
     */
    public function getAllDiscussions(Request $request)
    {
        $query = $this->getQuery($request);
        return $query->get();
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
            $query->where('discussions.title', 'like', '%'.$search.'%');
        }

        return $query->paginate($perPage);
    }

    /*
     * Builds the Discussion query.
     */
    private function getQuery(Request $request)
    {
        $query = Discussion::query();
        $query->select('discussions.*', 'users.name as owner_name')->leftJoin('users', 'discussions.owned_by', '=', 'users.id');
        // Join the role tables to get the owner's role level.
        $query->join('model_has_roles', 'discussions.owned_by', '=', 'model_id')->join('roles', 'roles.id', '=', 'role_id');

        // Get only the discussions related to this category. 
        $query->whereHas('categories', function ($query) {
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

                /*$groupIds = auth()->user()->getGroupIds();

                if (!empty($groupIds)) {
                    // Check for access through groups.
                    $query->orWhereHas('groups', function ($query)  use ($groupIds) {
                        $query->whereIn('id', $groupIds);
                    });
                }*/
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

            // Check for numerical sorting.
            if ($ordering[1] == 'order') {
                $query->join('ordering_category_discussion', function ($join) use ($ordering) { 
                    $join->on('discussions.id', '=', 'discussion_id')
                         ->where('category_id', '=', $this->id);
                })->orderBy('discussion_order', $ordering[2]);
            }
            // Regular sorting.
            else {
                $query->orderBy($ordering[1], $ordering[2]);
            }
        }

        return $query;
    }

    public function getParentIdOptions()
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
    }


}
