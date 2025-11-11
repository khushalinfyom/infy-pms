<?php

use App\Models\Tag;
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
        $softDeletedTags = Tag::onlyTrashed()->get();
        if (! empty($softDeletedTags)) {
            foreach ($softDeletedTags as $tags) {
                $tags->taskTags()->detach();
                $tags->forceDelete();
            }
        }
    }
};
