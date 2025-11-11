<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class Department.
 *
 * @version April 8, 2020, 10:51 am UTC
 *
 * @property int         $id
 * @property string      $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static Builder|Department newModelQuery()
 * @method static Builder|Department newQuery()
 * @method static Builder|Department query()
 * @method static Builder|Department whereCreatedAt($value)
 * @method static Builder|Department whereDeletedAt($value)
 * @method static Builder|Department whereId($value)
 * @method static Builder|Department whereName($value)
 * @method static Builder|Department whereUpdatedAt($value)
 * @mixin Model
 *
 * @property string|null $description
 * @property string $color
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Department whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Department whereDescription($value)
 */
class Department extends Model
{
    use HasFactory;

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:160|unique:departments,name',
        'color' => 'string|required',
    ];

    public $table = 'departments';

    public $fillable = [
        'name',
        'color',
        'description',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'color' => 'string',
        'description' => 'string',
    ];
}
