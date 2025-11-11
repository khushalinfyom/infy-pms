<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $input = [
            [
                'name' => 'Web Development',
                'description' => '<p>My Department is best department</p>',
                'color' => '#f2c852',
            ],
            [
                'name' => 'Web Designing',
                'description' => '<p>My Department is best department</p>',
                'color' => '#f26d52',
            ],
            [
                'name' => 'Android Development',
                'description' => '<p>My Department is best department</p>',
                'color' => '#52f28f',
            ],
            [
                'name' => 'IOS Development',
                'description' => '<p>My Department is best department</p>',
                'color' => '#52d7f2',
            ],
        ];

        foreach ($input as $data) {
            Department::create($data);
        }
    }
}
