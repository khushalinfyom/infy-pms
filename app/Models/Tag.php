<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Tag.
 *
 * @property int $id
 * @property string $name
 * @property int|null $created_by
 * @property int $deleted_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $createdUser
 *
 * @method static Builder|\App\Models\Tag newModelQuery()
 * @method static Builder|\App\Models\Tag newQuery()
 * @method static Builder|\App\Models\Tag query()
 * @method static Builder|\App\Models\Tag whereCreatedAt($value)
 * @method static Builder|\App\Models\Tag whereCreatedBy($value)
 * @method static Builder|\App\Models\Tag whereId($value)
 * @method static Builder|\App\Models\Tag whereName($value)
 * @method static Builder|\App\Models\Tag whereUpdatedAt($value)
 * @mixin \Eloquent
 *
 * @property Carbon|null $deleted_at
 *
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tag onlyTrashed()
 * @method static bool|null restore()
 * @method static Builder|\App\Models\Tag whereDeletedAt($value)
 * @method static Builder|\App\Models\Tag whereDeletedBy($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tag withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tag withoutTrashed()
 *
 * @property-read Collection|Task[] $taskTags
 * @property-read int|null $task_tags_count
 */
class Tag extends Model
{
    use HasFactory;
    use softDeletes;

    public $table = 'tags';

    public $fillable = [
        'name',
        'created_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'created_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|max:160|unique:tags,name',
    ];

    /**
     * @return BelongsTo
     */
    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany
     */
    public function taskTags()
    {
        return $this->belongsToMany(Task::class, 'task_tags', 'tag_id', 'task_id');
    }
}
