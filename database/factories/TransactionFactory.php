<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => 1,
            'is_purchase' => 1,
            'item_count' => 20,
            'total_item_price' => 400,
            'transaction_at' => '2024-04-04 12:00:00'
            //
        ];
    }
}
