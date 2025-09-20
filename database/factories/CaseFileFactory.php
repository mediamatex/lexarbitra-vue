<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CaseFile>
 */
class CaseFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_number' => 'Az. ' . $this->faker->numberBetween(1, 999) . '/' . $this->faker->year(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['draft', 'active', 'initiated', 'pending']),
            'procedure_type' => 'main_procedure',
            'dispute_value' => $this->faker->randomFloat(2, 10000, 1000000),
            'currency' => 'EUR',
            'initiated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'jurisdiction' => $this->faker->randomElement(['Germany', 'Austria', 'Switzerland']),
            'case_category' => $this->faker->randomElement(['Construction', 'Trade', 'Employment', 'Finance']),
            'complexity_level' => $this->faker->randomElement(['low', 'medium', 'high']),
            'urgency_level' => $this->faker->randomElement(['normal', 'urgent', 'very_urgent']),
        ];
    }
}
