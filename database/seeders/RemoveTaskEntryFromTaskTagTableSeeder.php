<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RemoveTaskEntryFromTaskTagTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $taskIds = Task::onlyTrashed()->pluck('id')->toArray();
        foreach ($taskIds as $taskId) {
            DB::table('task_tags')->where('task_id', '=', $taskId)->delete();
        }
    }
}
