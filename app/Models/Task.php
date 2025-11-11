<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * App\Models\Task.
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property int $project_id
 * @property int $status
 * @property string $due_date
 * @property int|null $created_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $createdUser
 * @property-read Project $project
 * @property-read Collection|Tag[] $tags
 * @property-read Collection|User[] $taskAssignee
 * @property-read Collection|TimeEntry[] $timeEntries
 *
 * @method static bool|null forceDelete()
 * @method static Builder|\App\Models\Task newModelQuery()
 * @method static Builder|\App\Models\Task newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Task onlyTrashed()
 * @method static Builder|\App\Models\Task query()
 * @method static bool|null restore()
 * @method static Builder|\App\Models\Task whereCreatedAt($value)
 * @method static Builder|\App\Models\Task whereCreatedBy($value)
 * @method static Builder|\App\Models\Task whereDeletedAt($value)
 * @method static Builder|\App\Models\Task whereDeletedBy($value)
 * @method static Builder|\App\Models\Task whereDescription($value)
 * @method static Builder|\App\Models\Task whereDueDate($value)
 * @method static Builder|\App\Models\Task whereId($value)
 * @method static Builder|\App\Models\Task whereProjectId($value)
 * @method static Builder|\App\Models\Task whereStatus($value)
 * @method static Builder|\App\Models\Task whereTitle($value)
 * @method static Builder|\App\Models\Task whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Task withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Task withoutTrashed()
 * @mixin \Eloquent
 *
 * @property-read Collection|\App\Models\TaskAttachment[] $attachments
 *
 * @method static Builder|\App\Models\Task whereTaskNumber($value)
 *
 * @property string|null $task_number
 * @property string|null $priority
 * @property int $totalDuration
 * @property int $totalDurationMin
 *
 * @method static Builder|\App\Models\Task wherePriority($value)
 *
 * @property-read Collection|\App\Models\Comment[] $comments
 * @property-read mixed $prefix_task_number
 *
 * @method static Builder|\App\Models\Task ofProject($projectId)
 *
 * @property-read int|null $attachments_count
 * @property-read int|null $comments_count
 * @property-read int|null $tags_count
 * @property-read int|null $task_assignee_count
 * @property-read int|null $time_entries_count
 * @property-read Collection|\Spatie\MediaLibrary\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read string $task_duration
 * @property \Illuminate\Support\Carbon|null $completed_on
 * @property-read string $task_hours
 * @property-read string $task_total_hours
 * @property-read string $task_total_minutes
 *
 * @method static Builder|\App\Models\Task whereCompletedOn($value)
 *
 * @property string|null $estimate_time
 * @property int|null $estimate_time_type
 * @property-read mixed $status_text
 *
 * @method static Builder|Task whereEstimateTime($value)
 * @method static Builder|Task whereEstimateTimeType($value)
 */
class Task extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    public static $statusArr = [];

    public static $status = [
        'STATUS_ALL' => 99,
    ];

    const STATUS_PENDING = 0;

    const STATUS_COMPLETED = 1;

    const PRIORITY = [
        'highest' => 'HIGHEST', 'high' => 'HIGH', 'medium' => 'MEDIUM', 'low' => 'LOW', 'lowest' => 'LOWEST',
    ];

    const PATH = 'attachments';

    public $table = 'tasks';

    protected $appends = [
        'task_duration', 'prefix_task_number', 'task_hours', 'task_total_hours', 'task_total_minutes', 'attachments', 'status_text',
    ];

    const PRIORITY_BADGE = [
        'highest' => 'badge-danger',
        'high' => 'badge-warning',
        'medium' => 'badge-primary',
        'low' => 'badge-info',
        'lowest' => 'badge-success',
    ];

    const CREATED_AT_ASC = 1;

    const CREATED_AT_DESC = 2;

    const COMPLETED_ON_ASC = 3;

    const COMPLETED_ON_DESC = 4;

    const DUE_DATE_ASC = 5;

    const DUE_DATE_DESC = 6;

    const TASK_FILTER_OPTION = [
        self::CREATED_AT_ASC => 'Created At (asc)',
        self::CREATED_AT_DESC => 'Created At (desc)',
        self::COMPLETED_ON_ASC => 'Completed On (asc)',
        self::COMPLETED_ON_DESC => 'Completed On (desc)',
        self::DUE_DATE_ASC => 'Due Date (asc)',
        self::DUE_DATE_DESC => 'Due Date (desc)',
    ];

    const PER_PAGE_OPTION = [
        10 => '10',
        25 => '25',
        50 => '50',
        100 => '100',
    ];

    const IN_HOURS = 0;

    const IN_DAYS = 1;

    public $fillable = [
        'title',
        'description',
        'project_id',
        'status',
        'due_date',
        'deleted_by',
        'created_by',
        'task_number',
        'priority',
        'completed_on',
        'estimate_time',
        'estimate_time_type',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'title' => 'string',
        'priority' => 'string',
        'estimate_time' => 'string',
        'description' => 'string',
        'project_id' => 'integer',
        'status' => 'integer',
        'due_date' => 'date',
        'task_number' => 'integer',
        'deleted_by' => 'integer',
        'estimate_time_type' => 'integer',
        'created_by' => 'integer',
        'completed_on' => 'date',
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'title' => 'required|max:250',
        'project_id' => 'required',
    ];

    /**
     * @return BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'task_tags', 'task_id', 'tag_id')->withTrashed();
    }

    /**
     * @return BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

    /**
     * @param  string  $value
     * @return string
     */
    public function getDueDateAttribute($value)
    {
        if (! empty($value)) {
            return Carbon::parse($value)->toDateString();
        }
    }

    /**
     * @return string
     */
    public function getPrefixTaskNumberAttribute()
    {
        if (str_contains(\URL::current(), 'tasks')) {
            return '#'.$this->project->prefix.'-'.$this->task_number;
        }

        return '';
    }

    /**
     * @return HasMany
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'task_id')->latest();
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function (self $task) {
            $task->timeEntries()->update(['deleted_by' => getLoggedInUserId()]);
            $task->timeEntries()->delete();
        });

        self::$statusArr = Status::toBase()->orderBy('name', 'asc')->pluck('name', 'status')->toArray();
        foreach (self::$statusArr as $key => $status) {
            $status = $status == 'Pending' ? 'Active' : $status;
            self::$status['STATUS_'.strtoupper($status)] = $key;
        }
    }

    /**
     * @return BelongsToMany
     */
    public function taskAssignee()
    {
        return $this->belongsToMany(User::class, 'task_assignees', 'task_id', 'user_id')->where('is_active', '=', true);
    }

    /**
     * @return BelongsTo
     */
    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany
     */
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id');
    }

    /**
     * @return HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'task_id')->orderBy('id');
    }

    /**
     * @param  Builder  $query
     * @param  int  $projectId
     * @return Builder
     */
    public function scopeOfProject(Builder $query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * @return string
     */
    public function getTaskDurationAttribute()
    {
        $minutes = $this->timeEntries->pluck('duration')->sum();
        $totalDuration = '0 Minutes';
        if ($minutes > 1) {
            if (ceil($minutes / 60) > 0) {
                $totalDuration = sprintf('%02d Hours and %02d Minutes', floor($minutes / 60), $minutes % 60);
            }

            if ($minutes / 1440 >= 1) {
                $days = floor($minutes / 1440);
                $isMoreThanDay = false;
                if ($minutes % 1440 > 0) {
                    $isMoreThanDay = true;
                }
                $showPlus = $isMoreThanDay ? '+' : '';
                $totalDuration = sprintf('%d%s Day(s)', $days, $showPlus);

                if ($days >= 7) {
                    $weeks = floor($days / 7);
                    $extraDays = $days % 7;
                    if ($extraDays > 0) {
                        $totalDuration = sprintf('%d Week(s) %d Day(s)', $weeks, $extraDays);
                    } else {
                        $totalDuration = sprintf('%d Week(s)', $weeks);
                    }

                    if ($weeks >= 4) {
                        $months = floor($days / 28);
                        if ($extraDays > 0) {
                            $totalDuration = sprintf('%d Month(s) %d Day(s)', $months, $extraDays);
                        } else {
                            $totalDuration = sprintf('%d Month(s)', $months);
                        }
                    }
                }
            }
        }

        return $totalDuration;
    }

    /**
     * @return string
     */
    public function getTaskHoursAttribute()
    {
        $minutes = $this->timeEntries->pluck('duration')->sum();
        $totalHours = 0;
        if ($minutes > 1) {
            $totalHours = sprintf('%02d Hours and %02d Minutes', floor($minutes / 60), $minutes % 60);
        }

        return $totalHours;
    }

    /**
     * @return string
     */
    public function getTaskTotalMinutesAttribute()
    {
        $totalMinutes = $this->timeEntries->pluck('duration')->sum();

        return $totalMinutes;
    }

    /**
     * @return string
     */
    public function getTaskTotalHoursAttribute()
    {
        $minutes = $this->timeEntries->pluck('duration')->sum();
        $hours = 0;
        if ($minutes > 1) {
            $hours = number_format($minutes / 60, 2);
        }

        return $hours;
    }

    /**
     * @param $value
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        return htmlspecialchars_decode($value);
    }

    /**
     * @return Media
     */
    public function getAttachmentsAttribute()
    {
        /** @var Media $media */
        $media = $this->media;
        if (! empty($media)) {
            return $media;
        }
    }

    /**
     * @return mixed
     */
    public function getStatusTextAttribute()
    {
        return self::$statusArr[$this->status];
    }
}
