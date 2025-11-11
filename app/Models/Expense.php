<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * App\Models\Expense
 *
 * @property int $id
 * @property string $description
 * @property int $amount
 * @property Carbon $date
 * @property int $project_id
 * @property int $client_id
 * @property int $category
 * @property int $billable
 * @property int|null $created_by
 * @property int|null $deleted_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Client $client
 * @property-read mixed $currency
 * @property-read false|string $expense_attachments
 * @property-read Collection|Media[] $media
 * @property-read int|null $media_count
 * @property-read Project $project
 * @property-read User|null $user
 *
 * @method static Builder|Expense newModelQuery()
 * @method static Builder|Expense newQuery()
 * @method static Builder|Expense onlyTrashed()
 * @method static Builder|Expense query()
 * @method static Builder|Expense whereAmount($value)
 * @method static Builder|Expense whereBillable($value)
 * @method static Builder|Expense whereCategory($value)
 * @method static Builder|Expense whereClientId($value)
 * @method static Builder|Expense whereCreatedAt($value)
 * @method static Builder|Expense whereCreatedBy($value)
 * @method static Builder|Expense whereDate($value)
 * @method static Builder|Expense whereDeletedAt($value)
 * @method static Builder|Expense whereDeletedBy($value)
 * @method static Builder|Expense whereDescription($value)
 * @method static Builder|Expense whereId($value)
 * @method static Builder|Expense whereProjectId($value)
 * @method static Builder|Expense whereUpdatedAt($value)
 * @method static Builder|Expense withTrashed()
 * @method static Builder|Expense withoutTrashed()
 * @mixin \Eloquent
 */
class Expense extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    public const ATTACHMENT_PATH = 'expenses';

    const CATEGORY_OTHERS = 1;

    const CATEGORY_SERVICES = 2;

    const CATEGORY_WAGES = 3;

    const CATEGORY_ACCOUNTING_SERVICES = 4;

    const CATEGORY_OFFICE_SUPPLIES = 5;

    const CATEGORY_COMMUNICATION = 6;

    const CATEGORY_TRAVEL = 7;

    const CATEGORY = [
        self::CATEGORY_ACCOUNTING_SERVICES => 'Accounting Services',
        self::CATEGORY_COMMUNICATION => 'Communication',
        self::CATEGORY_OFFICE_SUPPLIES => 'Office Supplies',
        self::CATEGORY_OTHERS => 'Others',
        self::CATEGORY_SERVICES => 'Services',
        self::CATEGORY_TRAVEL => 'Travel',
        self::CATEGORY_WAGES => 'Wages',
    ];

    public $table = 'expenses';

    public $fillable = [
        'description',
        'amount',
        'date',
        'project_id',
        'client_id',
        'category',
        'billable',
        'created_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'description' => 'string',
        'amount' => 'double',
        'date' => 'datetime',
        'project_id' => 'integer',
        'client_id' => 'integer',
        'created_by' => 'integer',
        'deleted_by' => 'integer',
        'category' => 'integer',
        'billable' => 'integer',
    ];

    public static $rules = [
        'amount' => 'required',
        'client_id' => 'required',
        'project_id' => 'required',
    ];

    /**
     * @var array
     */
    protected $appends = ['currency', 'expense_attachments'];

    /**
     * @return mixed
     */
    public function getCurrencyAttribute()
    {
        return $this->project()->value('currency');
    }

    /**
     * @return false|string
     */
    public function getExpenseAttachmentsAttribute()
    {
        /** @var Media $media */
        $media = $this->getMedia(self::ATTACHMENT_PATH)->first();
        if (! empty($media)) {
            return $media->getFullUrl();
        }

        return false;
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * @return BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
