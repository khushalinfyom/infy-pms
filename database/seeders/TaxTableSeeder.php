<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxTableSeeder extends Seeder
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
                'name' => '5% of your taxable income',
                'tax' => 5,
            ],
            [
                'name' => '10% of your taxable salary',
                'tax' => 10,
            ],
            [
                'name' => '15% of your taxable expense',
                'tax' => 15,
            ],
        ];

        foreach ($input as $data) {
            Tax::create($data);
        }
    }
}
