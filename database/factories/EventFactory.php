<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $categories = ['Futsal', 'Basket', 'Bulutangkis', 'Lari & Trail', 'Padel / Tenis', 'Bersepeda', 'E-Sports', 'Olahraga Lainnya'];
        $eventDate = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'organizer_id' => Organizer::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(3),
            'location' => fake()->city() . ', Manado',
            'event_date' => $eventDate,
            'registration_deadline' => (clone $eventDate)->modify('-7 days'),
            'max_participants' => fake()->numberBetween(10, 100),
            'ticket_price' => fake()->randomElement([0, 25000, 50000, 100000]),
            'category' => fake()->randomElement($categories),
            'status' => 'pending',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
