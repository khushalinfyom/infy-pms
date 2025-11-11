<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Status
 *
 * @property int $id
 * @property int $status
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|Status newModelQuery()
 * @method static Builder|Status newQuery()
 * @method static Builder|Status query()
 * @method static Builder|Status whereCreatedAt($value)
 * @method static Builder|Status whereId($value)
 * @method static Builder|Status whereName($value)
 * @method static Builder|Status whereStatus($value)
 * @method static Builder|Status whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Status extends Model
{
    /**
     * @var string
     */
    protected $table = 'status';

    /**
     * @var string[]
     */
    protected $fillable = [
        'status',
        'name',
        'order',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'status' => 'integer',
        'name' => 'string',
        'order' => 'double',
    ];

    /**
     * @var string[]
     */
    public static $rules = [
        'name' => 'required|max:250|unique:status,name',
        'order' => 'integer|min:0|unique:status,order',

    ];
}
