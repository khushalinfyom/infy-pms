<?php

use App\Models\Setting;
use App\Models\Status;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Setting::create(['key' => 'show_recaptcha', 'value' => '0',
            'group' => Setting::GOOGLE_RECAPTCHA,
        ]);
        Setting::create(['key' => 'google_recaptcha_site_key', 'value' => null,
            'group' => Setting::GOOGLE_RECAPTCHA,
        ]);
        Setting::create(['key' => 'google_recaptcha_secret_key', 'value' => null,
            'group' => Setting::GOOGLE_RECAPTCHA,
        ]);

        $status = Status::where('status', '0')->first();
        $taskStatus = Status::first();
        $defaultStatus = ! empty($taskStatus->status) ? $taskStatus->status : '0';

        Setting::create(['key' => 'default_task_status', 'value' => ! empty($status) ? '0' : $defaultStatus,
            'group' => Setting::GROUP_GENERAL,
        ]);
    }
};
