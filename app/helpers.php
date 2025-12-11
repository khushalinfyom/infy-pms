<?php

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use App\Models\Client;
use App\Models\Department;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Report;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Resources\RandomColor;
use Illuminate\Support\Facades\Auth;


if (!function_exists('getUserImageInitial')) {
    function getUserImageInitial($userId, $name)
    {
        $colors = ['329af0', 'fc6369', 'ffaa2e', '42c9af', '7d68f0'];

        $color = $colors[$userId % count($colors)];

        return 'https://ui-avatars.com/api/?name=' . urlencode($name)
            . '&size=64&rounded=true&color=fff&background=' . $color;
    }
}

if (! function_exists('getPhoneNumberFormate')) {
    function getPhoneNumberFormate($phoneNumber, $regionCode = null)
    {
        if (empty($phoneNumber)) {
            return 'N/A';
        }

        try {
            $region = $regionCode ?? 'IN';
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsedNumber = $phoneUtil->parse($phoneNumber, $region);
            return $phoneUtil->format($parsedNumber, PhoneNumberFormat::INTERNATIONAL);
        } catch (\Exception $e) {
            return $phoneNumber;
        }
    }
}

/**
 * @return int
 */
function getLoggedInUserId()
{
    return Auth::id();
}

/**
 * @return string
 */
function generateUniqueInvoiceNumber()
{
    return Invoice::generateUniqueInvoiceId();
}

/**
 * @param $number
 * @return false|float
 */
function formatNumber($number)
{
    return round(str_replace(',', '', $number), 2);
}

/**
 * @return User
 */
function getLoggedInUser()
{
    return Auth::user();
}

/**
 * @param  string  $str
 * @param  string  $delimiter
 * @return array
 */
function explode_trim_remove_empty_values_from_array($str, $delimiter = ',')
{
    $arr = explode($delimiter, trim($str));
    $arr = array_map('trim', $arr);
    $arr = array_filter($arr, function ($value) {
        return strlen($value);
    });

    return array_values($arr);
}

/**
 * @param  string  $datetime
 * @param  bool  $full
 * @return string
 *
 * @throws Exception
 */
function timeElapsedString($datetime, $full = false)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (! $full) {
        $string = array_slice($string, 0, 1);
    }

    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * @param  int  $totalMinutes
 */
function roundToQuarterHour($totalMinutes)
{
    $hours = intval($totalMinutes / 60);
    $minutes = $totalMinutes % 60;
    if ($hours > 0) {
        printf('%02d:%02d H', $hours, $minutes);
    } else {
        printf('%02d:%02d M', $hours, $minutes);
    }
}

/**
 * @param  int  $opacity
 * @param  string|null  $colorCode
 * @return string
 */
function getColor($opacity = 1, $colorCode = null)
{
    if (empty($colorCode)) {
        $colorCode = getColorCode();
    }

    $color = substr($colorCode, 0, -1);
    $color .= ', ' . $opacity . ')';

    return $color;
}

/**
 * @param  int  $opacity
 * @param  string  $colorType
 * @param  string  $colorFormat
 * @return array|string
 */
function getColorCode($opacity = 1, $colorType = 'bright', $colorFormat = 'rgbaCss')
{
    return RandomColor::one([
        'luminosity' => $colorType,
        'format' => $colorFormat,
        'opacity' => $opacity,
    ]);
}

/**
 * @param  int  $id
 * @return array|string
 */
function getColorRGBCode($id)
{
    $colorCodes = [
        'rgba(221, 255, 51)',
        'rgba(102, 255, 230)',
        'rgba(255, 102, 255)',
        'rgba(102, 204, 255)',
        'rgba(255, 99, 132)',
        'rgba(255, 159, 64)',
        'rgba(255, 205, 86)',
        'rgba(75, 192, 192)',
        'rgba(54, 162, 235)',
        'rgba(153, 102, 255)',
        'rgba(201, 203, 207)',
        'rgba(102, 230, 255)',
        'rgba(221, 255, 51)',
        'rgba(153, 221, 255)',
        'rgba(0, 255, 213)',
        'rgba(0, 230, 77)',
        'rgba(115, 230, 0)',
        'rgba(212, 255, 0)',
        'rgba(255, 213, 0)',
        'rgba(255, 102, 25)',
        'rgba(136, 204, 0)',
        'rgba(51, 255, 85)',
        'rgba(128, 255, 102)',
        'rgba(234, 255, 128)',
        'rgba(255, 221, 51)',
        'rgba(77, 255, 106)',
        'rgba(25, 255, 25)',
    ];
    $index = $id % 27;

    return $colorCodes[$index];
}

/**
 * return random color.
 *
 * @param  int  $userId
 * @return string
 */
function getRandomColor($userId)
{
    $colors = ['329af0', 'fc6369', 'ffaa2e', '42c9af', '7d68f0'];
    $index = $userId % 5;

    return $colors[$index];
}

/**
 * return avatar url.
 *
 * @return string
 */
function getAvatarUrl()
{
    return 'https://ui-avatars.com/api/';
}

/**
 * return avatar full url.
 *
 * @param  int  $userId
 * @param  string  $name
 * @return string
 */
function getUserImageInitial($userId, $name)
{
    return getAvatarUrl() . "?name=$name&size=64&rounded=true&color=fff&background=" . getRandomColor($userId);
}

/**
 * This function return bool value if auth user has either or not permission.
 *
 * @param $permissionName
 * @return bool
 */
// function authUserHasPermission($permissionName)
// {
//     if (Auth::user()->can($permissionName)) {
//         return true;
//     }

//     return false;
// }

/**
 * @return array
 */
function getUserLanguages()
{
    $language = User::LANGUAGES;
    asort($language);

    return  $language;
}

/**
 * @param  string|null  $currency
 * @return string
 */
function getCurrenciesIcon($currency = null)
{
    if (! $currency) {
        $currency = Setting::where('key', 'current_currency')->first()->value;
    }

    switch ($currency) {
        case 'inr':
            return 8377;
        case 'aud':
            return 36;
        case 'usd':
            return 36;
        case 'eur':
            return 8364;
        case 'jpy':
            return 165;
        case 'gbp':
            return 163;
        case 'cad':
            return 36;
    }
}

/**
 * @param $key
 * @return string
 */
function getCurrencyIcon($key)
{
    switch ($key) {
        case 1:
            return 8377;
        case 2:
        case 3:
        case 4:
            return 36;
        case 5:
            return 8364;
        case 6:
            return 163;
        default:
            return 8377;
    }
}

/**
 * @return mixed
 */
function getCurrentCurrency()
{
    /** @var Setting $currentCurrency */
    static $currentCurrency;

    if (empty($currentCurrency)) {
        $currentCurrency = Setting::where('key', 'current_currency')->first();
    }

    return $currentCurrency->value;
}

/**
 * @return mixed
 */
function getAppName()
{
    /** @var Setting $appName */
    static $appName;

    if (empty($appName)) {
        $appName = Setting::where('key', 'app_name')->first();
    }

    return $appName->value;
}

/**
 * @return mixed
 */
function getWorkingDayOfMonth()
{
    /** @var Setting $workingDayOfMonth */
    static $workingDayOfMonth;

    if (empty($workingDayOfMonth)) {
        $workingDayOfMonth = Setting::where('key', 'working_days_of_month')->first();
    }

    return $workingDayOfMonth->value;
}

/**
 * @return mixed
 */
function getWorkingHourOfDay()
{
    /** @var Setting $workingHourOfDay */
    static $workingHourOfDay;

    if (empty($workingHourOfDay)) {
        $workingHourOfDay = Setting::where('key', 'working_hours_of_day')->first();
    }

    return $workingHourOfDay->value;
}

/**
 * @param  string|null  $currency
 * @return string
 */
function getCurrenciesClass($currency = null)
{
    static $defaultCurrency;

    if (empty($defaultCurrency)) {
        if (! $currency) {
            $defaultCurrency = Setting::where('key', 'current_currency')->first()->value;
        }
    }

    switch ($defaultCurrency) {
        case 'inr':
            return 'fas fa-rupee-sign';
        case 'aud':
            return 'fas fa-dollar-sign';
        case 'usd':
            return 'fas fa-dollar-sign';
        case 'eur':
            return 'fas fa-euro-sign';
        case 'jpy':
            return 'fas fa-yen-sign';
        case 'gbp':
            return 'fas fa-pound-sign';
        case 'cad':
            return 'fas fa-dollar-sign';
        default:
            return 'fas fa-dollar-sign';
    }
}

/**
 * @param $taskId
 * @return mixed
 */
function getTaskTitle($taskId)
{
    $task = Task::whereId($taskId)->first();

    return $task->prefix_task_number;
}

/**
 * @param $projectId
 * @return mixed
 */
function getProjectName($projectId)
{
    return Project::whereId($projectId)->value('name');
}

/**
 * @param $totalMinutes
 * @return string
 */
function roundToQuarterHourAll($totalMinutes)
{
    $hours = intval($totalMinutes / 60);
    $minutes = $totalMinutes % 60;
    if ($hours > 0) {
        printf('%02d:%02d H', $hours, $minutes);
    } else {
        printf('%02d:%02d M', $hours, $minutes);
    }
}

/**
 * @return mixed
 */
function getLogoUrl()
{
    static $appLogo;

    if (empty($appLogo)) {
        $appLogo = Setting::where('key', '=', 'app_logo')->first();
    }

    return $appLogo->logo_url;
}

/**
 * @param $key
 * @return mixed
 */
function getSettingValue($key)
{
    return Setting::where('key', $key)->value('value');
}

/**
 * @param $number
 * @return string|string[]
 */
function removeCommaFromNumbers($number)
{
    return (gettype($number) == 'string' && ! empty($number)) ? str_replace(',', '', $number) : $number;
}

/**
 * @param $index
 * @return string
 */
function getBadgeColor($index)
{
    $colors = [
        'primary',
        'secondary',
        'success',
        'warning',
        'danger',
        'info',
        'dark',
        'light',
    ];
    $index = $index % 8;

    return $colors[$index];
}

function strip_single_html_tags($tag, $string)
{
    $string = preg_replace('~</?' . $tag . '[^>]*>~', '', $string);

    return $string;
}

/**
 * Check time entry available or not for department id.
 *
 * @param $id
 * @return mixed
 */
function checkTimeEntryExist($id)
{
    $clientIds = Client::whereIn('department_id', [$id])->pluck('id');
    $projectIds = Project::whereIn('client_id', $clientIds)->pluck('id');
    $taskIds = Task::whereIn('project_id', $projectIds)->pluck('id');
    $timeEntryStatus = TimeEntry::whereIn('task_id', $taskIds)->exists();

    return $timeEntryStatus;
}

/**
 * @param $invoice
 * @return int|string
 */
function getCurrencyIconForInvoicePDF($invoice)
{
    if (count($invoice->invoiceItems) > 0 && isset($invoice->invoiceItems[0]->projects->currency)) {
        return getCurrencyIcon($invoice->invoiceItems[0]->projects->currency);
    } else {
        return getCurrencyIcon(1);
    }
}

function getStatus()
{
    return \App\Models\Status::pluck('id', 'name')->toArray();
}

/**
 * @param $invoice_id
 */
function deleteInvoice($invoice_id)
{
    /** @var Invoice $invoice */
    $invoice = Invoice::find($invoice_id);
    if (! empty($invoice)) {
        $invoice->invoiceClients()->detach();     //delete invoice Clients
        $invoice->invoiceProjects()->detach();    //delete invoice Projects.
        $invoice->invoiceItems()->delete();       //delete invoice Items.

        $invoice->delete();

        $reportId = $invoice->invoiceReport()->value('report_id');
        /** @var Report $report */
        $report = Report::whereId($reportId)->first();
        $report->update(['invoice_generate' => 0]);
        $report->reportInvoice()->detach();     //delete report invoice.
    }
}

/**
 * @param $minutes
 * @return int|string
 */
function staticTaskTotalHours($minutes)
{
    $hours = 0;
    if ($minutes > 1) {
        $hours = number_format($minutes / 60, 2);
    }

    return $hours;
}

/**
 * @param $count
 * @return int
 */
function totalCountForClientDashboard($count)
{
    if (empty($count)) {
        return 1;
    }

    return $count;
}

/**
 * @return mixed
 */
function getCurrentLanguageName()
{
    return User::whereId(Auth::id())->first()->language;
}

/**
 * @param $url
 * @return string
 */
function mediaUrlEndsWith($url)
{
    if (substr($url, -strlen('pdf')) === 'pdf') {
        return asset('/assets/img/pdf_icon.png');
    } elseif (substr($url, -strlen('doc')) === 'doc' || substr($url, -strlen('docx')) === 'docx') {
        return asset('/assets/img/doc_icon.png');
    } elseif (substr($url, -strlen('xls')) === 'xls' || substr($url, -strlen('xlsx')) === 'xlsx') {
        return asset('/assets/img/xls_icon.png');
    } else {
        return $url;
    }
}

/**
 * @param $htmlCode
 * @return float|int
 */
function HTMLToRGB($htmlCode)
{
    if ($htmlCode[0] == '#') {
        $htmlCode = substr($htmlCode, 1);
    }

    if (strlen($htmlCode) == 3) {
        $htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
    }

    $r = hexdec($htmlCode[0] . $htmlCode[1]);
    $g = hexdec($htmlCode[2] . $htmlCode[3]);
    $b = hexdec($htmlCode[4] . $htmlCode[5]);

    return $b + ($g << 0x8) + ($r << 0x10);
}

/**
 * @param $RGB
 * @return object
 */
function RGBToHSL($RGB)
{
    $r = 0xFF & ($RGB >> 0x10);
    $g = 0xFF & ($RGB >> 0x8);
    $b = 0xFF & $RGB;

    $r = ((float) $r) / 255.0;
    $g = ((float) $g) / 255.0;
    $b = ((float) $b) / 255.0;

    $maxC = max($r, $g, $b);
    $minC = min($r, $g, $b);

    $l = ($maxC + $minC) / 2.0;

    if ($maxC == $minC) {
        $s = 0;
        $h = 0;
    } else {
        if ($l < .5) {
            $s = ($maxC - $minC) / ($maxC + $minC);
        } else {
            $s = ($maxC - $minC) / (2.0 - $maxC - $minC);
        }
        if ($r == $maxC) {
            $h = ($g - $b) / ($maxC - $minC);
        }
        if ($g == $maxC) {
            $h = 2.0 + ($b - $r) / ($maxC - $minC);
        }
        if ($b == $maxC) {
            $h = 4.0 + ($r - $g) / ($maxC - $minC);
        }

        $h = $h / 6.0;
    }

    $h = (int) round(255.0 * $h);
    $s = (int) round(255.0 * $s);
    $l = (int) round(255.0 * $l);

    return (object) ['hue' => $h, 'saturation' => $s, 'lightness' => $l];
}

/**
 * @param $model
 * @return string
 */
function activityLogIcon($model)
{
    if ($model == Department::class) {
        return 'fas fa-building';
    } elseif ($model == Client::class) {
        return 'fas fa-user-tie';
    } elseif ($model == Role::class) {
        return 'fas fa-user';
    } elseif ($model == Project::class) {
        return 'fas fa-folder-open';
    } elseif ($model == Task::class) {
        return 'fas fa-tasks';
    } elseif ($model == Report::class) {
        return 'fas fa-file';
    } elseif ($model == Invoice::class) {
        return 'fas fa-file-invoice';
    } elseif ($model == Event::class) {
        return 'fas fa-calendar-day';
    } elseif ($model == User::class) {
        return 'fas fa-users';
    } elseif ($model == 'N/A') {
        return 'fas fa-history';
    }
}

function getSettings($key)
{
    return Setting::where('key', $key)->first();
}

function getAllSettings()
{
    return Setting::toBase()->pluck('value', 'key')->toArray();
}

function getCurrentVersion()
{
    $composerFile = file_get_contents('../composer.json');
    $composerData = json_decode($composerFile, true);

    return $composerData['version'];
}


if (! function_exists('checkPermission')) {
    function checkPermission($permission)
    {
        /** @var \App\Models\User */
        $user = getLoggedInUser();
        return $user->hasPermissionTo($permission);
    }
}

function authUserHasPermission($permissionName)
{
    $user = Auth::user();
    if (!$user) return false;

    foreach ($user->roles as $role) {
        foreach ($role->permissions as $perm) {
            if ($perm->name === $permissionName) {
                return true;
            }
        }
    }

    return false;
}

function decimalHoursToHHMM($value, $isMinutes = false)
{
    if ($value === null || $value === '') {
        return '00:00';
    }

    if ($isMinutes) {
        $totalMinutes = (int) round($value);
    } else {
        $val = (float) str_replace(',', '', $value);

        $hours = floor($val);
        $fraction = $val - $hours;
        $minutesFromFraction = (int) round($fraction * 60);

        $totalMinutes = $hours * 60 + $minutesFromFraction;
    }

    if ($totalMinutes < 0) {
        $totalMinutes = 0;
    }

    $hours = intdiv($totalMinutes, 60);
    $minutes = $totalMinutes % 60;

    return sprintf('%02d:%02d', $hours, $minutes);
}

function currencyEntityForInvoice($invoice = null)
{
    if ($invoice) {
        $code = getCurrencyIconForInvoicePDF($invoice);
    } else {
        $code = getCurrencyIcon(getCurrentCurrency() ? getCurrentCurrency() : 1);
    }

    $code = (int) $code;

    return '&#' . $code . ';';
}

if (! function_exists('enableCaptcha')) {
    function enableCaptcha()
    {
        $secret = getSettingValue('google_recaptcha_secret_key');
        $sitekey = getSettingValue('google_recaptcha_site_key');
        config([
            'captcha.secret' => $secret,
            'captcha.sitekey' => $sitekey,
        ]);
        return ! empty($secret) && !empty($sitekey) ? getSettingValue('show_recaptcha') : false;
    }
}
