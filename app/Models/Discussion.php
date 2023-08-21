<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Discussion\Category;
use App\Models\Discussion\Registration;
use App\Models\Discussion\WaitingList;
use App\Models\Discussion\Comment;
use App\Models\Cms\Setting;
use App\Models\User\Group;
use App\Traits\AccessLevel;
use App\Traits\OptionList;
use App\Traits\CheckInCheckOut;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Discussion extends Model
{
    use HasFactory, AccessLevel, CheckInCheckOut, OptionList;

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
        'platform',
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
    const DELAY_BEFORE_SHOWING_LINK = 15; // In minutes.
    const DELAY_BEFORE_HIDDING_LINK = 15; // In minutes.

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
        return $this->hasMany(Registration::class);
    }

    /**
     * The groups that belong to the discussion.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * The comments that belong to the discussion.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class)
                    ->leftJoin('users', 'users.id', '=', 'discussion_comments.owned_by')
                    ->select('discussion_comments.*', 'users.name AS author')
                    ->orderBy('discussion_comments.created_at', 'desc');
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::saved(function (Discussion $discussion) {
            $discussion->slug = Str::slug($discussion->subject, '-').'-'.$discussion->id;
            // Save without triggering any events and prevent infinite loop.
            $discussion->saveQuietly();
        });

        static::deleting(function (Discussion $discussion) {
            $discussion->groups()->detach();
            $discussion->registrations()->delete();
        });
    }

    /*
     * Gets the discussions according to the filter settings.
     */
    public static function getDiscussions(Request $request)
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

    public function getSettings()
    {
        return Setting::getItemSettings($this, 'discussions');
    }

    public function getTimeBeforeDiscussion(): ?\stdClass
    {
        if (!$dates = $this->getValidDateTimes()) {
            return null;
        }

        $time = new \stdClass();

        // IMPORTANT: Use the copy() function when adding days or the original now date will be modified.
        $time->days = $dates['now']->diffInDays($dates['discussion']);
        $time->hours = $dates['now']->copy()->addDays($time->days)->diffInHours($dates['discussion']);
        $time->minutes = $dates['now']->copy()->addDays($time->days)->addHours($time->hours)->diffInMinutes($dates['discussion']);

        return $time;
    }

    public function getTimeBeforeDiscussionInMinutes(): ?int
    {
        if (!$dates = $this->getValidDateTimes()) {
            return null;
        }

        return $dates['now']->diffInMinutes($dates['discussion']);
    }

    public function canShowDiscussionLink(): bool
    {
        $canShow = false;
        $dates = $this->getDateTimes();

        // IMPORTANT: Use the copy() function when adding and substracting minutes or the
        //            original discussion date will be modified.
        if ($dates['now']->gt($dates['discussion']->copy()->subMinutes(Discussion::DELAY_BEFORE_SHOWING_LINK)) &&
            $dates['now']->lt($dates['discussion']->copy()->addMinutes(Discussion::DELAY_BEFORE_HIDDING_LINK))) {
            $canShow = true;
        }

        return $canShow; 
    }

    private function attendees(bool $onWaitingList = false): \Illuminate\Database\Eloquent\Collection 
    {
        return $this->registrations()
                    ->join('users', 'users.id', '=', 'user_id')
                    ->select('discussion_registrations.*', 'users.nickname as nickname')
                    ->where('on_waiting_list', $onWaitingList)
                    ->orderBy('discussion_registrations.created_at', 'asc')->get();
    }

    public function getAttendees(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->attendees();
    }

    public function getAttendeesOnWaitingList(): \Illuminate\Database\Eloquent\Collection 
    {
        return $this->attendees(true);
    }

    public function isUserRegistered(): bool
    {
        return (!auth()->check()) ? false : $this->registrations()->where(['user_id' => auth()->user()->id, 'on_waiting_list' => false])->exists();
    }

    public function isUserOnWaitingList(): bool
    {
        return (!auth()->check()) ? false : $this->registrations()->where(['user_id' => auth()->user()->id, 'on_waiting_list' => true])->exists();
    }

    public function isSoldOut(): bool
    {
        return ($this->getAttendees()->count() >= $this->max_attendees) ? true : false;
    }

    /*
     * Returns the datetimes for now and the discussion and check if the discussion
     * datetime is still valid.
     */
    private function getValidDateTimes(): ?array
    {
        $dates = $this->getDateTimes();

        // Check the discussion date is still valid.
        if ($dates['now']->gt($dates['discussion']) || $dates['now']->eq($dates['discussion'])) {
            return null;
        }

        return $dates;
    }

    /*
     * Returns the datetimes for now and the discussion.
     */
    private function getDateTimes(): array
    {
        $timezone = Setting::getValue('app', 'timezone');
        $now = Carbon::parse(Carbon::now($timezone));
        $discussion = Carbon::parse($this->discussion_date)->tz($timezone);

        return ['now' => $now, 'discussion' => $discussion];
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue(\stdClass $field): mixed
    {
        // Multiple
        if ($field->name == 'groups') {
            return $this->groups->pluck('id')->toArray();
        }

        if (isset($field->group) && $field->group == 'settings') {
            return (isset($this->settings[$field->name])) ? $this->settings[$field->name] : null;
        }

        // Single
        return $this->{$field->name};
    }

}
