<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\Traits\HasPermissions;

/**
 * App\Models\Role.
 *
 * @property int $id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $description
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|\Spatie\Permission\Models\Permission[] $permissions
 * @property-read int|null $permissions_count
 *
 * @method static Builder|\App\Models\Role newModelQuery()
 * @method static Builder|\App\Models\Role newQuery()
 * @method static Builder|\App\Models\Role permission($permissions)
 * @method static Builder|\App\Models\Role query()
 * @method static Builder|\App\Models\Role whereCreatedAt($value)
 * @method static Builder|\App\Models\Role whereDescription($value)
 * @method static Builder|\App\Models\Role whereDisplayName($value)
 * @method static Builder|\App\Models\Role whereGuardName($value)
 * @method static Builder|\App\Models\Role whereId($value)
 * @method static Builder|\App\Models\Role whereName($value)
 * @method static Builder|\App\Models\Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Role extends Model implements RoleContract
{
    use HasFactory;
    use HasPermissions;

    public $table = 'roles';

    public $fillable = [
        'name',
        'display_name',
        'description',
        'guard_name',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'display_name' => 'string',
        'description' => 'string',
        'guard_name' => 'string',
    ];

    protected $with = ['permissions'];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|max:250|unique:roles,name',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function (self $role) {
            if (empty($role->guard_name)) {
                $role->guard_name = Guard::getDefaultName(static::class);
            }
        });
    }

    /**
     * A role may be given various permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    /**
     * A role belongs to some users of the model associated with its guard.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name']),
            'model',
            config('permission.table_names.model_has_roles'),
            'role_id',
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * @param  string  $name
     * @param  null|string  $guardName
     * @return RoleContract|Builder
     */
    public static function findByName(string $name, $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::where('name', $name)->where('guard_name', $guardName)->first();

        if (! $role) {
            throw RoleDoesNotExist::named($name);
        }

        return $role;
    }

    /**
     * @param  string|int  $id
     * @param  string|null  $guardName
     * @return RoleContract|Builder
     */
    public static function findById(string|int $id, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::where('id', $id)->where('guard_name', $guardName)->first();

        if (! $role) {
            throw RoleDoesNotExist::withId($id);
        }

        return $role;
    }

    /**
     * Find or create role by its name (and optionally guardName).
     *
     * @param  string  $name
     * @param  string|null  $guardName
     * @return RoleContract|Builder
     */
    public static function findOrCreate(string $name, $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::where('name', $name)->where('guard_name', $guardName)->first();

        if (! $role) {
            return static::query()->create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $role;
    }
}
