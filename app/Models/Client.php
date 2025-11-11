<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder as BuilderAlias;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * App\Models\Client.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $website
 * @property int $deleted_by
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $createdUser
 * @property-read mixed $avatar
 *
 * @method static BuilderAlias|Client newModelQuery()
 * @method static BuilderAlias|Client newQuery()
 * @method static BuilderAlias|Client query()
 * @method static BuilderAlias|Client whereCreatedAt($value)
 * @method static BuilderAlias|Client whereCreatedBy($value)
 * @method static BuilderAlias|Client whereEmail($value)
 * @method static BuilderAlias|Client whereId($value)
 * @method static BuilderAlias|Client whereName($value)
 * @method static BuilderAlias|Client whereUpdatedAt($value)
 * @method static BuilderAlias|Client whereWebsite($value)
 * @mixin Eloquent
 *
 * @property Carbon|null $deleted_at
 * @property-read Collection|Project[] $projects
 * @property-read int|null $projects_count
 *
 * @method static bool|null forceDelete()
 * @method static Builder|Client onlyTrashed()
 * @method static bool|null restore()
 * @method static BuilderAlias|Client whereDeletedAt($value)
 * @method static BuilderAlias|Client whereDeletedBy($value)
 * @method static Builder|Client withTrashed()
 * @method static Builder|Client withoutTrashed()
 *
 * @property int|null $department_id
 * @property-read Department|null $department
 *
 * @method static Builder|Client whereDepartmentId($value)
 *
 * @property-read Collection|Media[] $media
 * @property-read int|null $media_count
 * @property int|null $user_id
 * @property-read Collection|Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection|Role[] $roles
 * @property-read int|null $roles_count
 *
 * @method static Builder|Client permission($permissions)
 * @method static Builder|Client role($roles, $guard = null)
 * @method static Builder|Client whereUserId($value)
 *
 * @property-read Collection|Expense[] $expenses
 * @property-read int|null $expenses_count
 * @property-read mixed $project_progress
 * @property-read User|null $user
 */
class Client extends Model implements HasMedia
{
    use HasFactory;
    use softDeletes;
    use HasRoles;
    use InteractsWithMedia;

    public $table = 'clients';

    const IMAGE_PATH = 'clients';

    public const CLIENT_LOGIN_TYPE = 1;

    public $fillable = [
        'name',
        'email',
        'website',
        'department_id',
        'created_by',
        'deleted_by',
        'user_id',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'email' => 'string',
        'website' => 'string',
        'deleted_by' => 'integer',
        'department_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|max:250',
        'email' => 'nullable|unique:clients,email|email:filter',
        'website' => 'nullable|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
        'department_id' => 'required|integer',
        'password' => 'nullable|min:6|required_with:password_confirmation|same:password_confirmation',
    ];

    public static $editRules = [
        'name' => 'required|max:250',
        'website' => 'nullable|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
        'department_id' => 'required|integer',
        'password' => 'nullable|min:6|required_with:password_confirmation|same:password_confirmation',
    ];

    public static $messages = [
        'website.regex' => 'Please enter valid url.',
        'department_id.required' => 'Please select valid department.',
    ];

    /**
     * @var array
     */
    protected $appends = ['avatar', 'project_progress'];

    /**
     * @return mixed
     */
    public function getAvatarAttribute()
    {
        /** @var Media $media */
        $media = $this->getMedia(self::IMAGE_PATH)->first();
        if (! empty($media)) {
            return $media->getFullUrl();
        }

        return getUserImageInitial($this->id, $this->name);
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
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return BelongsTo
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return mixed
     */
    public function getProjectProgressAttribute()
    {
        $projects = $this->projects()->get();

        $data['completedProjects'] = $projects->where('status', '=', Project::STATUS_FINISHED)->count();
        $data['openProjects'] = $projects->where('status', '=', Project::STATUS_ONGOING)->count();
        $data['holdProjects'] = $projects->where('status', '=', Project::STATUS_ONHOLD)->count();
        $data['archivedProjects'] = $projects->where('status', '=', Project::STATUS_ARCHIVED)->count();

        return $data;
    }
}
