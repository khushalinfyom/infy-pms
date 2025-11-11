<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $imageUrl = 'assets/img/logo-red-black.png';
        $faviconUrl = 'assets/img/favicon.png';

        Setting::create(['key' => 'app_name', 'value' => 'InfyProject']);
        Setting::create(['key' => 'app_logo', 'value' => $imageUrl]);
        Setting::create(['key' => 'favicon', 'value' => $faviconUrl]);
        Setting::create(['key' => 'company_name', 'value' => 'InfyOmLabs']);
        Setting::create(['key' => 'current_currency', 'value' => 'inr']);
        Setting::create(['key' => 'company_address', 'value' => '16/A saint Joseph Park']);
        Setting::create(['key' => 'company_email', 'value' => 'infytracker@gmail.com']);
        Setting::create(['key' => 'company_phone', 'value' => '1234567890']);
        Setting::create(['key' => 'working_days_of_month', 'value' => '24']);
        Setting::create(['key' => 'working_hours_of_day', 'value' => '8']);
    }
}
