<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity as ActivityModel;

/**
 * App\Models\ProjectActivity
 *
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property Collection|null $properties
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|\Eloquent $causer
 * @property-read User|null $createdBy
 * @property-read Collection $changes
 * @property-read Model|\Eloquent $subject
 *
 * @method static Builder|ActivityModel causedBy(Model $causer)
 * @method static Builder|ActivityModel forSubject(Model $subject)
 * @method static Builder|ActivityModel inLog($logNames)
 * @method static Builder|ProjectActivity newModelQuery()
 * @method static Builder|ProjectActivity newQuery()
 * @method static Builder|ProjectActivity query()
 * @method static Builder|ProjectActivity whereCauserId($value)
 * @method static Builder|ProjectActivity whereCauserType($value)
 * @method static Builder|ProjectActivity whereCreatedAt($value)
 * @method static Builder|ProjectActivity whereDescription($value)
 * @method static Builder|ProjectActivity whereId($value)
 * @method static Builder|ProjectActivity whereLogName($value)
 * @method static Builder|ProjectActivity whereProperties($value)
 * @method static Builder|ProjectActivity whereSubjectId($value)
 * @method static Builder|ProjectActivity whereSubjectType($value)
 * @method static Builder|ProjectActivity whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectActivity extends ActivityModel
{
    protected $table = 'activity_log';

    /**
     * @return BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
