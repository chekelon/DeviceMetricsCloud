<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sensor>
 */
class SensorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $location = \App\Models\Location::all()->random();
        return [
            //'name' => $this->faker->unique()->word() . '_sensor_nivel_agua',
            'location_id' => $location->id,
        ];
    }
}
