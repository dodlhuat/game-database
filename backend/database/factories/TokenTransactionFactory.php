<?php

namespace Database\Factories;

use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TokenTransaction>
 */
class TokenTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'loan_id' => null,
            'token_purchase_id' => null,
            'type' => 'ADMIN_ADJUSTMENT',
            'amount' => 10,
            'description' => null,
        ];
    }
}
