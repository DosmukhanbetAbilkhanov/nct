<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            'Almaty',
            'Astana',
            'Shymkent',
            'Aktobe',
            'Karaganda',
            'Taraz',
            'Pavlodar',
            'Oskemen',
            'Semey',
            'Oral',
            'Atyrau',
            'Kostanay',
            'Kyzylorda',
            'Aktau',
            'Taldykorgan',
            'Ekibastuz',
            'Rudny',
            'Zhezkazgan',
            'Turkistan',
            'Kokshetau',
        ];

        foreach ($cities as $city) {
            \App\Models\City::create(['name' => $city]);
        }
    }
}
