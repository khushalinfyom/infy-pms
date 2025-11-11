<?php

use App\Models\Task;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $taskIds = Task::onlyTrashed()->pluck('id')->toArray();
        foreach ($taskIds as $taskId) {
            DB::table('task_tags')->where('task_id', '=', $taskId)->delete();
        }
    }
};
