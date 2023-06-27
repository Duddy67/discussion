<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Discussion\Category;
use App\Models\Discussion\Subscription;
use App\Models\User\Group;
use App\Traits\AccessLevel;
use App\Traits\CheckInCheckOut;

class Discussion extends Model
{
    use HasFactory, AccessLevel, CheckInCheckOut;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subject',
        'status',
        'owned_by',
        'access_level',
        'description',
        'discussion_date',
        'discussion_link',
        'max_attendees',
        'is_private',
        'registering_alert',
        'comment_alert',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'discussion_date',
    ];

    const MAX_ATTENDEES = 10;

    /**
     * Get the category that owns the discussion.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The subscriptions that belong to the discussion.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * The groups that belong to the discussion.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /*
     * Gets the discussions according to the filter settings.
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', Setting::getValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $sortedBy = $request->input('sorted_by', null);
        $ownedBy = $request->input('owned_by', null);
        $category = $request->input('category', null);
        $groups = $request->input('groups', []);

        $query = Discussion::query();
        $query->select('discussions.*', 'users.name as owner_name')->leftJoin('users', 'discussions.owned_by', '=', 'users.id');
        // Join the role tables to get the owner's role level.
        $query->join('model_has_roles', 'discussions.owned_by', '=', 'model_id')->join('roles', 'roles.id', '=', 'role_id');

        if ($search !== null) {
            $query->where('discussions.subject', 'like', '%'.$search.'%');
        }

        if ($sortedBy !== null) {
            preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
            $query->orderBy($matches[1], $matches[2]);
        }

        if ($ownedBy !== null) {
            $query->whereIn('discussions.owned_by', $ownedBy);
        }

        if ($category !== null) {
            $query->whereIn('discussions.category_id', [$category]);
        }

        if (!empty($groups)) {
            $query->whereHas('groups', function($query) use($groups) {
                $query->whereIn('id', $groups);
            });
        }

        $query->where(function($query) {
            $query->where('roles.role_level', '<', auth()->user()->getRoleLevel())
                  ->orWhereIn('discussions.access_level', ['public_ro', 'public_rw'])
                  ->orWhere('discussions.owned_by', auth()->user()->id);
        });

        $groupIds = auth()->user()->getGroupIds();

        if (!empty($groupIds)) {
            $query->orWhereHas('groups', function ($query)  use ($groupIds) {
                $query->whereIn('id', $groupIds);
            });
        }

        return $query->paginate($perPage);
    }

    public function getMaxAttendeesOptions()
    {
        $options = [];

        for ($i = 0; $i < self::MAX_ATTENDEES; $i++) {
            $options[] = ['value' => $i + 1, 'text' => $i + 1];
        }

        return $options;
    }

    public function getPlatformOptions()
    {
        return [
            ['value' => 'zoom', 'text' => __('labels.discussion.zoom')],
            ['value' => 'skype', 'text' => __('labels.discussion.skype')],
            ['value' => 'google_meet', 'text' => __('labels.discussion.google_meet')],
        ];
    }

    public function getCategoryOptions()
    {
        $nodes = Category::get()->toTree();
        $options = [];
        $userGroupIds = auth()->user()->getGroupIds();

        $traverse = function ($categories, $prefix = '-') use (&$traverse, &$options, $userGroupIds) {
            foreach ($categories as $category) {
                // Check wether the current user groups match the category groups (if any).
                $belongsToGroups = (!empty(array_intersect($userGroupIds, $category->getGroupIds()))) ? true : false;
                // Set the category option accordingly.
                $extra = ($category->access_level == 'private' && $category->owned_by != auth()->user()->id && !$belongsToGroups) ? ['disabled'] : [];
                $options[] = ['value' => $category->id, 'text' => $prefix.' '.$category->name, 'extra' => $extra];

                $traverse($category->children, $prefix.'-');
            }
        };

        $traverse($nodes);

        return $options;
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue(\stdClass $field): mixed
    {
        if ($field->name == 'groups') {
            return $this->groups->pluck('id')->toArray();
        }

        if ($field->name == 'category') {
            return $this->category_id;
        }

        if (isset($field->group) && $field->group == 'settings') {
            return (isset($this->settings[$field->name])) ? $this->settings[$field->name] : null;
        }

        return $this->{$field->name};
    }

}
