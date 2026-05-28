<?php

namespace Database\Factories;

use App\Models\CollectedBag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectedBag>
 */
class CollectedBagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bag_id' => $this->faker->unique()->numberBetween(1000, 9999),
            'collection_stop_id' => \App\Models\CollectionStop::factory(),
            'status' => $this->faker->randomElement(['collected', 'pending', 'failed']),
            'collected_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
