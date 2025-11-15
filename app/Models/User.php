<?php

namespace App\Models;

use App\Notifications\MailResetPasswordNotification;
use App\Traits\ImageTrait;
use Eloquent;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 * App\Models\User.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property bool $set_password
 * @property int $is_email_verified
 * @property int $is_active
 * @property string|null $activation_code
 * @property int|null $created_by
 * @property string|null $remember_token
 * @property int $deleted_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $createdUser
 * @property-read DatabaseNotificationCollection|DatabaseNotification[]
 *     $notifications
 * @property-read Collection|Project[] $projects
 *
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereActivationCode($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereCreatedBy($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereEmailVerifiedAt($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereIsActive($value)
 * @method static Builder|User whereIsEmailVerified($value)
 * @method static Builder|User whereName($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User wherePhone($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereSetPassword($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static Builder|User whereLanguage($value)
 *
 * @property string $image_path
 * @property string $language
 *
 * @method static Builder|User whereImagePath($value)
 * @mixin Eloquent
 *
 * @property-read Collection|Role[] $roles
 *
 * @method static Builder|User withRole($role)
 *
 * @property-read mixed $img_avatar
 * @property int|null $salary
 * @property Carbon|null $deleted_at
 * @property-read int|null $notifications_count
 * @property-read Collection|Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read int|null $projects_count
 * @property-read int|null $roles_count
 *
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|User onlyTrashed()
 * @method static Builder|User permission($permissions)
 * @method static bool|null restore()
 * @method static Builder|User role($roles, $guard = null)
 * @method static Builder|User whereDeletedAt($value)
 * @method static Builder|User whereDeletedBy($value)
 * @method static Builder|User whereSalary($value)
 * @method static \Illuminate\Database\Query\Builder|User withTrashed()
 * @method static \Illuminate\Database\Query\Builder|User withoutTrashed()
 *
 * @property-read string $role_names
 *
 * @method static Builder|User active()
 *
 * @property-read Collection|Media[] $media
 * @property-read int|null $media_count
 * @property-read Collection|Report[] $reports
 * @property-read int|null $reports_count
 * @property-read Collection|Task[] $taskAssignee
 * @property-read int|null $task_assignee_count
 * @property-read Collection|TimeEntry[] $timeEntries
 * @property-read int|null $time_entries_count
 * @property int|null $owner_id
 * @property string|null $owner_type
 * @property-read Collection|Task[] $userActiveTask
 * @property-read int|null $user_active_task_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereOwnerType($value)
 *
 * @property-read mixed $img_avatar_client
 * @property-read Client|null $client
 * @property-read Collection|ProjectActivity[] $activityLogs
 * @property-read int|null $activity_logs_count
 * @property-read Collection|Comment[] $comments
 * @property-read int|null $comments_count
 * @property-read Collection|Invoice[] $invoices
 * @property-read int|null $invoices_count
 *
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 */
class User extends Authenticatable implements MustVerifyEmail, HasMedia, FilamentUser, HasAvatar
{
    use HasFactory;
    use Notifiable;
    use ImageTrait;
    use softDeletes;
    use HasRoles;
    use ImageTrait {
        deleteImage as traitDeleteImage;
    }
    use InteractsWithMedia;

    public $table = 'users';

    const IMAGE_PATH = 'users';

    protected $appends = ['img_avatar', 'role_names'];

    const LANGUAGES = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'ru' => 'Russian',
        'pt' => 'Portuguese',
        'ar' => 'Arabic',
        'zh' => 'Chinese',
        'tr' => 'Turkish',
    ];
    const ADMIN = 'Admin';
    const DEVELOPER = 'Developer';
    const ACTIVE = 1;

    const DEACTIVE = 0;

    const ARCHIVED = 2;

    const STATUS = [
        self::ACTIVE => 'Active',
        self::DEACTIVE => 'Deactive',
        self::ARCHIVED => 'Archived',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'region_code',
        'phone',
        'set_password',
        'created_by',
        'email_verified_at',
        'is_email_verified',
        'activation_code',
        'is_active',
        'image_path',
        'deleted_by',
        'salary',
        'language',
        'owner_id',
        'owner_type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'email' => 'string',
        'password' => 'string',
        'phone' => 'string',
        'created_by' => 'integer',
        'email_verified_at' => 'datetime',
        'is_email_verified' => 'integer',
        'activation_code' => 'string',
        'is_active' => 'integer',
        'set_password' => 'integer',
        'image_path' => 'string',
        'deleted_by' => 'integer',
        'salary' => 'string',
        'language' => 'string',
        'owner_id' => 'integer',
        'owner_type' => 'string',
        'remember_token' => 'string',
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|max:250',
        'email' => 'required|email:filter|unique:users,email',
        'phone' => 'nullable|numeric',
        'role_id' => 'required',
        'salary' => 'max:7',
        'password' => 'nullable|min:6|required_with:password_confirmation|same:password_confirmation',
        'password_confirmation' => 'nullable|min:6',
        'photo' => 'nullable|mimes:jpg,jpeg,png',
    ];

    public static $messages = [
        'phone.digits' => 'The phone number must be 10 digits long.',
        'photo.mimes' => 'The profile image must be a file of type: jpeg, jpg, png.',
        'role_id.required' => 'Please select user role.',
        'salary.max' => 'The salary may not be greater than 7 digits.',
    ];

    public static $setPasswordRules = [
        'user_id' => 'required',
        'password' => 'min:6|required_with:password_confirmation|same:password_confirmation',
        'password_confirmation' => 'min:6',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_url;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new MailResetPasswordNotification($token));
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * @return BelongsToMany
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user', 'user_id', 'project_id');
    }

    /**
     * @return BelongsTo
     */
    public function createdUser()
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    /**
     * @return string
     */
    public function getImgAvatarAttribute()
    {
        /** @var Media $media */
        $media = $this->getMedia(self::IMAGE_PATH)->first();
        if (! empty($media)) {
            return $media->getFullUrl();
        }

        return getUserImageInitial($this->id, $this->name);
    }

    /**
     * @param $value
     * @return string
     */
    public function getImagePathAttribute($value)
    {
        if (! empty($value)) {
            return $this->imageUrl(self::IMAGE_PATH . DIRECTORY_SEPARATOR . $value);
        }

        return getUserImageInitial($this->id, $this->name);
    }

    /**
     * @return bool
     */
    public function deleteImage()
    {
        $image = $this->getOriginal('image_path');
        if (empty($image)) {
            return true;
        }

        return $this->traitDeleteImage(self::IMAGE_PATH . DIRECTORY_SEPARATOR . $image);
    }

    /**
     * @return string
     */
    public function getRoleNamesAttribute()
    {
        return implode(',', $this->roles->pluck('name')->toArray());
    }

    /**
     * @return BelongsToMany
     */
    public function taskAssignee()
    {
        return $this->belongsToMany(Task::class, 'task_assignees', 'user_id', 'task_id');
    }

    /**
     * @return BelongsToMany
     */
    public function userActiveTask()
    {
        return $this->belongsToMany(Task::class, 'task_assignees', 'user_id', 'task_id')->where(
            'status',
            Task::$status['STATUS_ACTIVE']
        );
    }

    /**
     * @return HasMany
     */
    public function reports()
    {
        return $this->hasMany(Report::class, 'owner_id');
    }

    /**
     * @return HasMany
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'user_id');
    }

    /**
     * @return HasOne
     */
    public function client()
    {
        return $this->hasOne(Client::class, 'user_id');
    }

    /**
     * @return HasMany
     */
    public function activityLogs()
    {
        return $this->hasMany(ProjectActivity::class, 'causer_id');
    }

    /**
     * @return HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'created_by');
    }

    /**
     * @return HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    /**
     * @return HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * @return HasMany
     */
    public function usersProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * @return HasMany
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    /**
     * @return HasMany
     */
    public function activityType()
    {
        return $this->hasMany(ActivityType::class, 'created_by');
    }


    /**
     * @return HasMany
     */
    public function tags()
    {
        return $this->hasMany(Tag::class, 'created_by');
    }
}
