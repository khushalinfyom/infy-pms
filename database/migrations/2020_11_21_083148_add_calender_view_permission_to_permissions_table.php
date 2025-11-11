<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            Permission::create([
                'name' => 'manage_calendar_view',
                'display_name' => 'Manage Calendar View',
                'description' => '<p>Able to access Setting tab.</p>',
            ]);
        });
    }
};
