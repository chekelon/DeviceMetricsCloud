<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Reading;
use App\Models\User;
use App\Models\Region;
use App\Models\Sensor;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        Region::factory()->state(new Sequence(
            ['name' => 'Region Laguna'],
            ['name' => 'Region Laguna 2']
        ))->count(2)->create();
        

        $region=Region::all();
        
            Location::factory()
            ->state(new Sequence(
                 ['name' => 'Jardin - sensor_nivel_agua_cisterna' ,'region_id' => $region[0]->id],
                 ['name' => 'Tecnologico - sensor_nivel_agua_cisterna' ,'region_id' => $region[0]->id],
                 ['name' => 'Centenario - sensor_nivel_agua_cisterna' ,'region_id' => $region[0]->id],
            ))->count(3)->create();
        
        

        User::factory()
            ->state(new Sequence(
                fn ($sequence) => ['region_id' => Region::all()->random()->id]
            ))->count(5)->create();

        $locations = Location::all();
        for ($i=0; $i < $locations->count()-1 ; $i++) { 
            Sensor::factory()
                ->state(new Sequence(
                    fn ($sequence) => ['name'=>'cisterna','location_id' => $locations[$i]->id]
                ))->count(1)->create();
        }

        $sensores = \App\Models\Sensor::all();
        for ($i=0; $i < $sensores->count()-1 ; $i++) { 
            Reading::factory()
            ->state(new Sequence(
                fn ($sequence) => ['sensor_id' => $sensores[$i]->id, 'value' => rand(100, 70) ]
            ))->count(10)->create();
        }
        

        

        
    }
}
