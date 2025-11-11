<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UpdateEmailVerifiedAtAdminUser extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::whereEmail('admin@infyprojects.com')->first();

        if ($user) {
            $user->update(['email_verified_at' => Carbon::now()]);
        }
    }
}
