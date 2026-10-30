<?php

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizerFactory extends Factory
{
    protected $model = Organizer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_name' => fake()->company() . ' Community',
            'description' => fake()->paragraph(2),
            'phone' => fake()->phoneNumber(),
            'status' => 'approved',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
