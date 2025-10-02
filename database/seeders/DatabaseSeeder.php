<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Reading;
use App\Models\User;
use App\Models\Region;
use App\Models\Sensor;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    protected  bool $valueDesc = true;
    protected int $value = 100;
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
                 ['name' => 'Jardin' ,'region_id' => $region[0]->id],
                 ['name' => 'Tecnologico' ,'region_id' => $region[0]->id],
                 ['name' => 'Centenario' ,'region_id' => $region[0]->id],
            ))->count(3)->create();
        
        

        User::factory()
            ->state(new Sequence(
                fn ($sequence) => ['region_id' => Region::all()->random()->id]
            ))->count(5)->create();

        $locations = Location::all();
        for ($i=0; $i < $locations->count()-1 ; $i++) { 
            Sensor::factory()
                ->state(new Sequence(
                    fn ($sequence) => ['name'=>'cisterna','almacenamiento'=> 'cisterna','location_id' => $locations[$i]->id]
                ))->count(1)->create();
        }

        //$sensores = \App\Models\Sensor::all();
        /*for ($i=0; $i < $sensores->count()-1 ; $i++) { 
            Reading::factory()
            ->state(new Sequence(
                fn ($sequence) => ['sensor_id' => $sensores[$i]->id, 'value' => rand(100, 70) ]
            ))->count(10)->create();
        }*/
        
        // 2. Define el rango de fechas para el mes (ej. el mes anterior)
        //$startDate = Carbon::now()->startOfMonth();
        //$endDate = Carbon::now()->endOfMonth();
        // 3. Itera cada 30 minutos dentro del rango del mes
        //$currentDate = $startDate->copy(); // Usa copy() para no modificar el objeto original
        /*for ($i=0; $i < $sensores->count()-1 ; $i++) { 
            while ($currentDate->lessThanOrEqualTo($endDate)) {
            // Crea una lectura usando el factory, y sobrescribe los atributos
            // 'sensor_id', 'created_at' y 'updated_at' con los valores específicos
            Reading::factory()->create([
                'sensor_id' => $sensores[$i]->id,
                'value' => $this->getValueSensor(), // Genera un valor aleatorio entre 70 y 100
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ]);

            // Avanza 30 minutos para la siguiente lectura
            $currentDate->addMinutes(30);
            }
        }*/

        
        
        

        

        
    }

    function getValueSensor(): int {
        // Si el valor alcanza o supera 100, cambia la dirección a descendente
        if ($this->value >= 100) {
            $this->valueDesc = false;
        // Si el valor es igual o menor a 25, cambia la dirección a ascendente
        } else if ($this->value <= 25) {
            $this->valueDesc = true;
        }

        // Si la dirección es ascendente, incrementa el valor
        if ($this->valueDesc) {
            $this->value += rand(1, 20);
        // Si la dirección es descendente, decrementa el valor
        } else {
            $this->value -= rand(1, 20);
        }

        // Limita el valor entre 25 y 100
        if ($this->value > 100) $this->value = 100;
        if ($this->value < 25) $this->value = 25;

        return $this->value;
    }
}
