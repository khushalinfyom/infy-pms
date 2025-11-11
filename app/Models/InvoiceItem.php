<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\InvoiceItem
 *
 * @property int $id
 * @property int $owner_id
 * @property string $owner_type
 * @property string $item_name
 * @property int|null $task_id
 * @property int $quantity
 * @property float $task_amount
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Task|null $task
 *
 * @method static Builder|\App\Models\InvoiceItem newModelQuery()
 * @method static Builder|\App\Models\InvoiceItem newQuery()
 * @method static Builder|\App\Models\InvoiceItem query()
 * @method static Builder|\App\Models\InvoiceItem whereCreatedAt($value)
 * @method static Builder|\App\Models\InvoiceItem whereDescription($value)
 * @method static Builder|\App\Models\InvoiceItem whereId($value)
 * @method static Builder|\App\Models\InvoiceItem whereItemName($value)
 * @method static Builder|\App\Models\InvoiceItem whereOwnerId($value)
 * @method static Builder|\App\Models\InvoiceItem whereOwnerType($value)
 * @method static Builder|\App\Models\InvoiceItem whereQuantity($value)
 * @method static Builder|\App\Models\InvoiceItem whereTaskAmount($value)
 * @method static Builder|\App\Models\InvoiceItem whereTaskId($value)
 * @method static Builder|\App\Models\InvoiceItem whereUpdatedAt($value)
 * @mixin \Eloquent
 *
 * @property int $hours
 *
 * @method static Builder|\App\Models\InvoiceItem whereHours($value)
 *
 * @property float|null $fix_rate
 *
 * @method static Builder|\App\Models\InvoiceItem whereFixRate($value)
 *
 * @property int|null $item_project_id
 *
 * @method static Builder|\App\Models\InvoiceItem whereItemProjectId($value)
 *
 * @property-read Project|null $projects
 */
class InvoiceItem extends Model
{
    /**
     * @var string
     */
    public $table = 'invoice_items';

    /**
     * @var array
     */
    public $fillable = [
        'owner_id',
        'owner_type',
        'item_name',
        'task_id',
        'description',
        'hours',
        'task_amount',
        'fix_rate',
        'item_project_id',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'owner_type' => 'string',
        'item_name' => 'string',
        'task_id' => 'integer',
        'item_project_id' => 'integer',
        'description' => 'string',
        'hours' => 'string',
        'task_amount' => 'double',
        'fix_rate' => 'double',
    ];

    /**
     * @return mixed
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * @return mixed
     */
    public function projects()
    {
        return $this->belongsTo(Project::class, 'item_project_id');
    }
}
