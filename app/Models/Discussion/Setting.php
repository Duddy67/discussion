<?php

namespace App\Models\Discussion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\OptionList;

class Setting extends Model
{
    use HasFactory, OptionList;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'discussion_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    /**
     * No timestamps.
     *
     * @var boolean
     */
    public $timestamps = false;


    public static function getDiscussionOrderingOptions(): array
    {
      return [
	  ['value' => 'discussion_date_asc', 'text' => __('labels.discussion.discussion_date_asc')],
	  ['value' => 'discussion_date_desc', 'text' => __('labels.discussion.discussion_date_desc')],
	  ['value' => 'subject_asc', 'text' => __('labels.discussion.subject_asc')],
	  ['value' => 'subject_desc', 'text' => __('labels.discussion.subject_desc')],
	  ['value' => 'created_at_asc', 'text' => __('labels.generic.created_at_asc')],
	  ['value' => 'created_at_desc', 'text' => __('labels.generic.created_at_desc')],
	  ['value' => 'updated_at_asc', 'text' => __('labels.generic.updated_at_asc')],
	  ['value' => 'updated_at_desc', 'text' => __('labels.generic.updated_at_desc')],
	  ['value' => 'no_ordering', 'text' => __('labels.generic.no_ordering')],
      ];
    }
}
