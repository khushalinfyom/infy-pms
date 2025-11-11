<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

/**
 * App\Models\Invoice
 *
 * @property int $id
 * @property string $invoice_number
 * @property \Illuminate\Support\Carbon $issue_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property float|null $discount
 * @property int|null $tax_id
 * @property int $project_id
 * @property int $client_id
 * @property int $status
 * @property float $amount
 * @property float $sub_total
 * @property mixed|null $notes
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client $client
 * @property-read string $status_text
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\InvoiceItem[] $invoiceItems
 * @property-read int|null $invoice_items_count
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\Tax|null $tax
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereUpdatedAt($value)
 * @mixin \Eloquent
 *
 * @property-read string $status_text_color
 * @property-read \App\Models\Report|null $report
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Invoice[] $invoiceReport
 * @property-read int|null $invoice_report_count
 * @property int|null $discount_type
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereDiscountType($value)
 *
 * @property string $name
 * @property string $total_hour
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Client[] $invoiceClients
 * @property-read int|null $invoice_clients_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Project[] $invoiceProjects
 * @property-read int|null $invoice_projects_count
 * @property-read int|null $report_count
 *
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Invoice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invoice whereTotalHour($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Invoice withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Invoice withoutTrashed()
 */
class Invoice extends Model
{
    use softDeletes;

    const STATUS = [
        0 => 'DRAFT',
        1 => 'SENT',
        2 => 'PAID',
    ];

    const CLIENT_STATUS = [
        1 => 'SENT',
        2 => 'PAID',
    ];

    const STATUS_DRAFT = 0;

    const STATUS_SENT = 1;

    const STATUS_PAID = 2;

    const ORDER_BY_DUE_DATE = [
        1 => 'Due Date (Asc) ',
        2 => 'Due Date (Desc)',
    ];

    const DUE_DATE_ASC = 1;

    const DUE_DATE_DESC = 2;

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'project_id' => 'required',
        'invoice_number' => 'required',
        'issue_date' => 'required',
        'name' => 'required|max:250',
    ];

    public static $messages = [
        'project_id.required' => 'The project field is required',
    ];

    public $table = 'invoices';

    public $fillable = [
        'name',
        'invoice_number',
        'issue_date',
        'due_date',
        'discount',
        'tax_id',
        'status',
        'amount',
        'sub_total',
        'notes',
        'created_by',
        'discount_type',
        'total_hour',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'invoice_number' => 'string',
        'issue_date' => 'date',
        'due_date' => 'date',
        'discount' => 'double',
        'tax_id' => 'integer',
        'status' => 'integer',
        'amount' => 'double',
        'sub_total' => 'double',
        'created_by' => 'integer',
        'discount_type' => 'integer',
        'total_hour' => 'string',
        'notes' => 'string',
    ];

    /**
     * @return string
     */
    public static function generateUniqueInvoiceId()
    {
        $invoiceId = mb_strtoupper(Str::random(6));
        while (true) {
            $isExist = self::whereInvoiceNumber($invoiceId)->exists();
            if ($isExist) {
                self::generateUniqueInvoiceId();
            }
            break;
        }

        return $invoiceId;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOwnerType()
    {
        return self::class;
    }

    /**
     * @return mixed
     */
    public function report()
    {
        return $this->belongsToMany(Report::class, 'report_invoices', 'invoice_id', 'report_id');
    }

    /**
     * @return BelongsToMany
     */
    public function invoiceClients()
    {
        return $this->belongsToMany(Client::class, 'invoice_clients', 'invoice_id', 'client_id');
    }

    /**
     * @return BelongsToMany
     */
    public function invoiceProjects()
    {
        return $this->belongsToMany(Project::class, 'invoice_projects', 'invoice_id', 'project_id');
    }

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphMany
     */
    public function invoiceItems()
    {
        return $this->morphMany(InvoiceItem::class, 'owner');
    }

    /**
     * @return BelongsTo
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    /**
     * @return string
     */
    public function getStatusTextAttribute()
    {
        return self::STATUS[$this->status];
    }

    /**
     * @return string
     */
    public function getStatusTextColorAttribute()
    {
        if ($this->status == self::STATUS_DRAFT) {
            return 'danger';
        } elseif ($this->status == self::STATUS_SENT) {
            return 'primary';
        }

        return 'success';
    }

    /**
     * @return belongsToMany
     */
    public function invoiceReport()
    {
        return $this->belongsToMany(Report::class, 'report_invoices', 'invoice_id', 'report_id');
    }
}
