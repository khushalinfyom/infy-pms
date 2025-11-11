<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AddClientRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'name' => 'Client',
            'display_name' => 'Client',
            'description' => '<p>Client</p>',
        ]);

        $clientRole = Role::whereName('Client')->first();

        $clients = Client::all();
        foreach ($clients as $client) {
            $client->roles()->sync([$clientRole->id]);
        }
    }
}
