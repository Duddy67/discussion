<?php

namespace App\Models\Discussion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Discussion;

class Comment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'discussion_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'text',
        'owned_by',
    ];


    /**
     * Get the post that owns the comment.
     */
    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }
}
