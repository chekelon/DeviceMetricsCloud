<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str; 
use App\Models\User;
use App\Models\Region;

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

        User::firstOrCreate(
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
    }
}
