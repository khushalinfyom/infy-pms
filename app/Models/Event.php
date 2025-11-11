<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ActivityType.
 *
 * @property int $id
 * @property string $name
 * @property int|null $created_by
 * @property int $deleted_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $createdUser
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType whereUpdatedAt($value)
 * @mixin \Eloquent
 *
 * @property Carbon|null $deleted_at
 * @property-read Collection|TimeEntry[] $timeEntries
 * @property-read int|null $time_entries_count
 *
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ActivityType onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ActivityType whereDeletedBy($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ActivityType withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ActivityType withoutTrashed()
 *
 * @property string|null $title
 * @property int|null $description
 * @property int|null $added_by
 * @property int|null $start_date
 * @property int|null $end_date
 * @property int|null $type
 * @property-read User|null $createdBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereType($value)
 */
class Event extends Model
{
    public $table = 'events';

    public $fillable = [
        'title',
        'description',
        'added_by',
        'start_date',
        'end_date',
        'type',
    ];

    protected $casts = [
        'title' => 'string',
        'description' => 'string',
        'added_by' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'type' => 'integer',
    ];

    const EVENT = 1;

    const HOLIDAY = 2;

    const EVENTS = [
        self::EVENT => 'Event',
        self::HOLIDAY => 'Holiday',
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'title' => 'required|max:250',
        'start_date' => 'required',
        'end_date' => 'required',
        'type' => 'required',
    ];

    /**
     * @return BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
