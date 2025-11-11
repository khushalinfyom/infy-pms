<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\UserNotification
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $type
 * @property string|null $description
 * @property string|null $read_at
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|UserNotification newModelQuery()
 * @method static Builder|UserNotification newQuery()
 * @method static Builder|UserNotification query()
 * @method static Builder|UserNotification whereCreatedAt($value)
 * @method static Builder|UserNotification whereDescription($value)
 * @method static Builder|UserNotification whereId($value)
 * @method static Builder|UserNotification whereReadAt($value)
 * @method static Builder|UserNotification whereTitle($value)
 * @method static Builder|UserNotification whereType($value)
 * @method static Builder|UserNotification whereUpdatedAt($value)
 * @method static Builder|UserNotification whereUserId($value)
 * @mixin \Eloquent
 */
class UserNotification extends Model
{
    public $table = 'user_notifications';

    public $fillable = [
        'title',
        'type',
        'description',
        'read_at',
        'user_id',
    ];

    protected $casts = [
        'title' => 'string',
        'type' => 'string',
        'description' => 'string',
        'read_at' => 'datetime',
        'user_id' => 'integer',
    ];
}
