<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Region;
use App\Models\Role;
use App\Models\SensorType;
use App\Models\Sensor;
use App\Models\Location;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear región por defecto si no existe
        $region = Region::firstOrCreate(
            ['name' => 'Region Default']
        );

        $role = Role::firstOrCreate(
            ['name'=> 'Admin']
        );

        $sensor_type = SensorType::firstOrCreate(
            ['name'=> 'distancia',
            'description' => 'mide el nivel de agua']
        );

        $location = Location::firstOrCreate(
            [ 
             'name'=>'Casa',
             'region_id'=> $region->id
            ]
        );

        $sensor = Sensor::firstOrCreate(
            [
                'type_sensor_id'=> $sensor_type->id,
                'name'=> 'tinaco',
                'almacenamiento'=>'tinaco',
                'min_value'=>20,
                'max_value'=>90,
                'capacidad'=>450,
                'location_id'=> $location->id,
                'alert_min_value'=>30,
                'alert_max_value'=>80,
                'alert_notification_interval'=>1,
                'interval_reading'=>60

            ],
        );

        $user = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL')],
            [
                'name' => 'Administrador',
                'email_verified_at' => now(),
                'password' => bcrypt(env('ADMIN_PASSWORD')),
                'region_id' => $region->id,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'remember_token' => Str::random(10),
                'profile_photo_path' => null,
                'current_team_id' => null,
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

    }
}