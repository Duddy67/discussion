<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Discussion\Category;
use App\Models\Discussion\Registration;
use App\Models\Discussion\WaitingList;
use App\Models\Setting;
use App\Models\User\Group;
use App\Traits\AccessLevel;
use App\Traits\CheckInCheckOut;
use Carbon\Carbon;

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
        'media_link',
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
     * The registrations that belong to the discussion.
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class)->where('on_waiting_list', false);
    }

    /**
     * The registrations that belong to the discussion.
     */
    public function registrationsOnWaitingList()
    {
        return $this->hasMany(Registration::class)->where('on_waiting_list', true);
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
        $groups = $request->input('groups', []);
        $categories = $request->input('categories', []);

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

        // Filter by categories
        if (!empty($categories)) {
            $query->whereIn('discussions.category_id', $categories);
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

    public function getUrl()
    {
        $segments = Setting::getSegments('Discussion');
        return '/'.$segments['discussions'].'/'.$this->id.'/'.$this->slug;
    }

    public function getMediaThumbnail()
    {
        if (str_starts_with($this->media_link, 'https://youtu.be/')) {
            preg_match('#^https\:\/\/youtu\.be\/([a-zA-Z0-9_-]+)$#', $this->media_link, $matches);
            $code = $matches[1];

            return 'https://img.youtube.com/vi/'.$code.'/mqdefault.jpg'; 
        }
    }

    public function getTimeBeforeDiscussion(): ?\stdClass
    {
        if (!$dates = $this->getDateTimes()) {
            return null;
        }

        $time = new \stdClass();

        $time->days = $dates['now']->diffInDays($dates['discussion']);
        $time->hours = $dates['now']->copy()->addDays($time->days)->diffInHours($dates['discussion']);
        $time->minutes = $dates['now']->copy()->addDays($time->days)->addHours($time->hours)->diffInMinutes($dates['discussion']);

        return $time;
    }

    public function getTimeBeforeDiscussionInMinutes(): ?int
    {
        if (!$dates = $this->getDateTimes()) {
            return null;
        }

        return $dates['now']->diffInMinutes($dates['discussion']);
    }

    public function isUserRegistered(): bool
    {
        return (!auth()->check()) ? false : $this->registrations()->where(['user_id' => auth()->user()->id, 'on_waiting_list' => false])->exists();
    }

    public function isUserOnWaitingList(): bool
    {
        return (!auth()->check()) ? false : $this->registrations()->where(['user_id' => auth()->user()->id, 'on_waiting_list' => true])->exists();
    }

    /*
     * Returns the datetimes for now and the discussion.
     */
    private function getDateTimes(): ?array
    {
        $now = Carbon::parse(Carbon::now());
        $discussion = Carbon::parse($this->discussion_date);

        // Check the discussion date is still valid.
        if ($now->gt($discussion) || $now->eq($discussion)) {
            return null;
        }

        return ['now' => $now, 'discussion' => $discussion];
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
