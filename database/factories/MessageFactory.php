<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Testing\Fakes\Fake;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'message' => fake()->text(1000),
            'details' => fake()->randomElement([null, self::fakeJson()]),
            'created_at' => fake()->dateTimeBetween('-3 month'),
        ];
    }

    private static function fakeJson() : array
    {
        return [
            'number' => fake()->randomNumber(7, true),
            'message' => fake()->text(100),
            'sender' => fake()->name(),
        ];
    }
}
