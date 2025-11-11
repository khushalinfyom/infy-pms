<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Tax
 *
 * @property int $id
 * @property string $name
 * @property float $tax
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tax newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tax newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tax query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tax whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tax whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tax whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tax whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tax whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Tax extends Model
{
    /**
     * @var string
     */
    public $table = 'taxes';

    /**
     * @var array
     */
    public $fillable = [
        'name',
        'tax',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'tax' => 'double',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|max:250|unique:taxes,name',
        'tax' => 'required|numeric|min:1|max:100',
    ];
}
