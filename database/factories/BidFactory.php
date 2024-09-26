<?php

namespace Database\Factories;

use App\Models\Bid;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bid>
 */
class BidFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'price' => Bid::latest()->value('price') + fake()->randomFloat(2, 1, 1000),
            'user_id' => User::all()->random()->id
        ];
    }
}
