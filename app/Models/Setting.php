<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * App\Models\Setting
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property int $group
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read mixed $logo_url
 * @property-read Collection|Media[] $media
 * @property-read int|null $media_count
 *
 * @method static Builder|Setting newModelQuery()
 * @method static Builder|Setting newQuery()
 * @method static Builder|Setting query()
 * @method static Builder|Setting whereCreatedAt($value)
 * @method static Builder|Setting whereId($value)
 * @method static Builder|Setting whereKey($value)
 * @method static Builder|Setting whereUpdatedAt($value)
 * @method static Builder|Setting whereValue($value)
 * @method static Builder|Setting whereGroup($value)
 * @mixin Model
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting whereCompanyName($value)
 *
 * @property-read string|null $logo_path
 */
class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    const GROUP_GENERAL = 1;

    const INVOICE_TEMPLATE = 2;

    const GOOGLE_RECAPTCHA = 3;

    const GROUP_ARRAY = [
        'general' => self::GROUP_GENERAL,
        'invoice_template' => self::INVOICE_TEMPLATE,
        'google_recaptcha' => self::GOOGLE_RECAPTCHA,
    ];

    const INVOICE__TEMPLATE_ARRAY = [
        'defaultTemplate' => 'Default',
        'newYorkTemplate' => 'New York',
        'torontoTemplate' => 'Toronto',
        'rioTemplate' => 'Rio',
        'londonTemplate' => 'London',
        'istanbulTemplate' => 'Istanbul',
        'mumbaiTemplate' => 'Mumbai',
        'hongKongTemplate' => 'Hong Kong',
        'tokyoTemplate' => 'Tokyo',
        'sydneyTemplate' => 'Sydney',
        'parisTemplate' => 'Paris',
    ];

    public $table = 'settings';

    public const PATH = 'settings';

    const CURRENCIES = [
        'eur' => 'Euro (EUR)',
        'aud' => 'Australia Dollar (AUD)',
        'inr' => 'India Rupee (INR)',
        'usd' => 'USA Dollar (USD)',
        'jpy' => 'Japanese Yen (JPY)',
        'gbp' => 'British Pound (GBP)',
        'cad' => 'Canadian Dollar (CAD)',
    ];

    public $fillable = [
        'key',
        'value',
        'group',
    ];

    public $appends = ['logo_url', 'logo_path'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'key' => 'string',
        'value' => 'string',
        'group' => 'string',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'app_name' => 'required|max:250|string',
        'company_name' => 'required|max:250|string',
        'app_logo' => 'nullable|mimes:jpg,jpeg,png',
        'company_phone' => 'required|numeric|',
        'working_days_of_month' => 'required',
        'working_hours_of_day' => 'required',
        'company_email' => 'required|email:filter',
        'company_address' => 'required',
    ];

    /**
     * @return mixed
     */
    public function getLogoUrlAttribute()
    {
        /** @var Media $media */
        $media = $this->getMedia(self::PATH)->first();
        if (! empty($media)) {
            return $media->getFullUrl();
        }

        return $this->value;
    }

    /**
     * @return string|null
     */
    public function getLogoPathAttribute()
    {
        /** @var Media $media */
        $media = $this->getMedia(self::PATH)->first();
        if (! empty($media)) {
            return $media->getFullUrl();
        }

        return asset('assets/img/logo-red-black.png');
    }
}
