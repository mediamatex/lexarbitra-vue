<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CaseReference>
 */
class CaseReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'database_name' => 'test_case_'.$this->faker->unique()->randomNumber(6),
            'database_user' => 'test_user',
            'database_password' => '',
            'database_host' => '/tmp/test_case_'.$this->faker->unique()->randomNumber(6).'.sqlite',
            'connection_name' => 'test_case_'.$this->faker->unique()->randomNumber(6),
            'is_active' => true,
            'tenant_case_id' => null,
        ];
    }
}
