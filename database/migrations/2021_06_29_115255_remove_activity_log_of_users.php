<?php

use App\Models\ProjectActivity;
use App\Models\User;
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
        $userActivityLogIds = ProjectActivity::where('causer_type', User::class)->pluck('causer_id')->toArray();
        $userIds = User::pluck('id')->toArray();
        foreach ($userActivityLogIds as $userActivityLogId) {
            if (! in_array($userActivityLogId, $userIds)) {
                ProjectActivity::whereCauserId($userActivityLogId)->where('causer_type', User::class)->delete();
            }
        }
    }
};
