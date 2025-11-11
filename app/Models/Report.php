<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Report.
 *
 * @property int                             $id
 * @property string                          $name
 * @property int                             $owner_id
 * @property \Illuminate\Support\Carbon      $start_date
 * @property \Illuminate\Support\Carbon      $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder|Report newModelQuery()
 * @method static Builder|Report newQuery()
 * @method static Builder|Report query()
 * @method static Builder|Report whereCreatedAt($value)
 * @method static Builder|Report whereEndDate($value)
 * @method static Builder|Report whereId($value)
 * @method static Builder|Report whereName($value)
 * @method static Builder|Report whereOwnerId($value)
 * @method static Builder|Report whereStartDate($value)
 * @method static Builder|Report whereUpdatedAt($value)
 * @mixin Eloquent
 *
 * @property-read Collection|Project[] $projects
 * @property-read mixed $formatted_date
 * @property-read User $user
 * @property-read int|null $projects_count
 * @property int $report_type
 * @property array|null $report_data
 *
 * @method static Builder|Report whereReportData($value)
 * @method static Builder|Report whereReportType($value)
 *
 * @property bool|null $invoice_generate
 *
 * @method static Builder|Report whereInvoiceGenerate($value)
 *
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Collection|Invoice[] $reportInvoice
 * @property-read int|null $report_invoice_count
 *
 * @method static \Illuminate\Database\Query\Builder|Report onlyTrashed()
 * @method static Builder|Report whereDeletedAt($value)
 * @method static \Illum    inate\Database\Query\Builder|Report withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Report withoutTrashed()
 *
 * @property array $meta
 *
 * @method static Builder|Report whereMeta($value)
 */
class Report extends Model
{
    use HasFactory;
    use softDeletes;

    const DYNAMIC_REPORT = 1;

    const STATIC_REPORT = 2;

    public $table = 'reports';

    protected $appends = ['formatted_date'];

    public $fillable = [
        'name',
        'owner_id',
        'start_date',
        'end_date',
        'report_type',
        'report_data',
        'invoice_generate',
        'meta',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'owner_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'report_type' => 'integer',
        'report_data' => 'array',
        'invoice_generate' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|max:250',
        'start_date' => 'required',
        'end_date' => 'required',
    ];

    /**
     * @var string[]
     */
    public static $messages = [
        'department_id.required' => 'The department field is required.',
    ];

    /**
     * @return BelongsToMany
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'report_filters', 'report_id', 'param_id');
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return string
     */
    public function getFormattedDateAttribute()
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $startOfMonth = Carbon::parse($startDate)->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::parse($endDate)->endOfMonth()->format('Y-m-d');

        if ($startDate == $endDate) {
            return Carbon::parse($this->start_date)->format('jS M Y');
        } elseif ($startDate->format('Y-m-d') == $startOfMonth && $endDate->format('Y-m-d') == $endOfMonth && $startDate->month == $endDate->month) {
            return $startDate->format('M Y');
        } elseif ($startDate->month == $endDate->month) {
            return $startDate->format('jS').' - '.$endDate->format('jS M Y');
        } elseif ($startDate->month != $endDate->month) {
            return $startDate->format('jS M').' - '.$endDate->format('jS M Y');
        }
    }

    /**
     * @return belongsToMany
     */
    public function reportInvoice()
    {
        return $this->belongsToMany(Invoice::class, 'report_invoices', 'report_id', 'invoice_id');
    }
}
