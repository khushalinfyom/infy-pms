<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * App\Models\Project.
 *
 * @property int $id
 * @property string $name
 * @property int|null $client_id
 * @property string $description
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Client|null $client
 * @property-read User|null $createdUser
 * @property-read Collection|User[] $users
 * @property int $deleted_by
 *
 * @method static Builder|\App\Models\Project newModelQuery()
 * @method static Builder|\App\Models\Project newQuery()
 * @method static Builder|\App\Models\Project query()
 * @method static Builder|\App\Models\Project whereClientId($value)
 * @method static Builder|\App\Models\Project whereCreatedAt($value)
 * @method static Builder|\App\Models\Project whereCreatedBy($value)
 * @method static Builder|\App\Models\Project whereDescription($value)
 * @method static Builder|\App\Models\Project whereId($value)
 * @method static Builder|\App\Models\Project whereName($value)
 * @method static Builder|\App\Models\Project whereUpdatedAt($value)
 * @mixin \Eloquent
 *
 * @property string $prefix
 *
 * @method static Builder|\App\Models\Project wherePrefix($value)
 *
 * @property Carbon|null $deleted_at
 * @property-read Collection|Task[] $tasks
 * @property-read int|null $tasks_count
 * @property-read int|null $users_count
 *
 * @method static bool|null forceDelete()
 * @method static Builder|\App\Models\Project onlyTrashed()
 * @method static bool|null restore()
 * @method static Builder|\App\Models\Project whereDeletedAt($value)
 * @method static Builder|\App\Models\Project whereDeletedBy($value)
 * @method static Builder|\App\Models\Project withTrashed()
 * @method static Builder|\App\Models\Project withoutTrashed()
 *
 * @property-read Collection|Task[] $openTasks
 * @property-read int|null $open_tasks_count
 * @property int $price
 * @property int $currency
 * @property string $color
 * @property int $budget_type
 *
 * @method static Builder|\App\Models\Project whereBudgetType($value)
 * @method static Builder|\App\Models\Project whereColor($value)
 * @method static Builder|\App\Models\Project whereCurrency($value)
 * @method static Builder|\App\Models\Project wherePrice($value)
 *
 * @property int $status
 *
 * @method static Builder|\App\Models\Project whereStatus($value)
 *
 * @property-read Collection|Expense[] $expenses
 * @property-read int|null $expenses_count
 */
class Project extends Model implements HasMedia
{
    use HasFactory;
    use softDeletes;
    use InteractsWithMedia;

    const TEAM_ARR = ['1' => 'Backend', '2' => 'Frontend', '3' => 'Mobile', '4' => 'QA'];

    const CURRENCY = [
        1 => 'INR',
        2 => 'AUD',
        3 => 'USD',
        4 => 'CAD',
        5 => 'EUR',
        6 => 'GBP',
    ];

    const STATUS = [
        self::STATUS_All => 'All',
        self::STATUS_ARCHIVED => 'Archived',
        self::STATUS_FINISHED => 'Finished',
        self::STATUS_ONGOING => 'Ongoing',
        self::STATUS_ONHOLD => 'OnHold',
    ];

    const STATUS_All = 0;

    const STATUS_ONGOING = 1;

    const STATUS_FINISHED = 2;

    const STATUS_ONHOLD = 3;

    const STATUS_ARCHIVED = 4;
    const PATH = 'attachments';

    const STATUS_BADGE = [
        self::STATUS_ONGOING => 'badge-primary',
        self::STATUS_FINISHED => 'badge-success',
        self::STATUS_ONHOLD => 'badge-warning',
        self::STATUS_ARCHIVED => 'badge-info',
    ];

    const BUDGET_TYPE = [
        0 => 'Hourly',
        1 => 'Fixed Cost',
    ];

    const HOURLY = 0;

    const FIXED_COST = 1;

    public $table = 'projects';

    public $fillable = [
        'name',
        'team',
        'description',
        'client_id',
        'created_by',
        'deleted_by',
        'prefix',
        'price',
        'currency',
        'color',
        'budget_type',
        'status',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'team' => 'integer',
        'description' => 'string',
        'prefix' => 'string',
        'color' => 'string',
        'client_id' => 'integer',
        'created_by' => 'integer',
        'deleted_by' => 'integer',
        'price' => 'integer',
        'currency' => 'integer',
        'budget_type' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|unique:projects,name|max:160',
        'client_id' => 'required',
        'color' => 'required',
    ];

    public static $editRules = [
        'client_id' => 'required',
        'color' => 'required',
        'price' => 'required',
        'currency' => 'required',
        'budget_type' => 'required',
    ];

    public static $messages = [
        'name.unique' => 'Project with same name already exist.',
    ];

    /**
     * @param $value
     */
    public function setPrefixAttribute($value)
    {
        $this->attributes['prefix'] = strtoupper($value);
    }

    /**
     * @return BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class)->withTimestamps();
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
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany
     */
    public function openTasks()
    {
        return $this->tasks()->where('status', '!=', Task::$status['STATUS_COMPLETED']);
    }

    /**
     * @param $key
     * @return string
     */
    public static function getCurrencyClass($key)
    {
        switch ($key) {
            case 1:
                return 'fas fa-rupee-sign';
            case 5:
                return 'fas fa-euro-sign';
            case 6:
                return 'fas fa-pound-sign';
            default:
                return 'fas fa-dollar-sign';
        }
    }

    /**
     * @return float|int
     */
    public function projectProgress()
    {
        $completedTasks = $this->tasks->where('status', '=', Task::STATUS_COMPLETED);
        if ($completedTasks->count() != 0) {
            return $completedTasks->count() * 100 / $this->tasks->count();
        }

        return 0;
    }

    /**
     * @return Media
     */
    public function getAttachmentsAttribute()
    {
        /** @var Media $media */
        $media = $this->media;
        if (!empty($media)) {
            return $media;
        }
    }
}
