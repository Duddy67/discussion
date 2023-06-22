<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Discussion\Category;
use App\Models\Discussion\Subscription;

class Discussion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        //'slug',
        'status',
        'owned_by',
        'category_id',
        'description',
        'discussion_date',
        'discussion_link',
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

    /*
     * Gets the discussions according to the filter settings.
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', Setting::getValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $sortedBy = $request->input('sorted_by', null);
        $ownedBy = $request->input('owned_by', null);
        //$groups = $request->input('groups', []);

        $query = Discussion::query();
        $query->select('discussions.*', 'users.name as owner_name')->leftJoin('users', 'discussions.owned_by', '=', 'users.id');
        // Join the role tables to get the owner's role level.
        $query->join('model_has_roles', 'discussions.owned_by', '=', 'model_id')->join('roles', 'roles.id', '=', 'role_id');

        if ($search !== null) {
            $query->where('discussions.title', 'like', '%'.$search.'%');
        }

        if ($sortedBy !== null) {
            preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
            $query->orderBy($matches[1], $matches[2]);
        }

        if ($ownedBy !== null) {
            $query->whereIn('discussions.owned_by', $ownedBy);
        }

        /*if (!empty($groups)) {
            $query->whereHas('groups', function($query) use($groups) {
                $query->whereIn('id', $groups);
            });
        }*/

        $query->where(function($query) {
            $query->where('roles.role_level', '<', auth()->user()->getRoleLevel())
                  ->orWhereIn('discussions.access_level', ['public_ro', 'public_rw'])
                  ->orWhere('discussions.owned_by', auth()->user()->id);
        });

        /*$groupIds = auth()->user()->getGroupIds();

        if(!empty($groupIds)) {
            $query->orWhereHas('groups', function ($query)  use ($groupIds) {
                $query->whereIn('id', $groupIds);
            });
        }*/

        return $query->paginate($perPage);
    }

    public function getMaxAttendeesOptions()
    {
        return [
            ['value' => 1, 'text' => 1],
            ['value' => 2, 'text' => 2],
            ['value' => 3, 'text' => 3],
            ['value' => 4, 'text' => 4],
            ['value' => 5, 'text' => 5],
            ['value' => 6, 'text' => 6],
            ['value' => 7, 'text' => 7],
            ['value' => 8, 'text' => 8],
            ['value' => 9, 'text' => 9],
            ['value' => 10, 'text' => 10],
        ];
    }

}
