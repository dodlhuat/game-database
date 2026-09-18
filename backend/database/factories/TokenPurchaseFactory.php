<?php

namespace Database\Factories;

use App\Models\TokenPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TokenPurchase>
 */
class TokenPurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_payment_intent_id' => 'pi_'.Str::random(24),
            'token_amount' => 20,
            'price_cents' => 50,
            'currency' => 'EUR',
            'status' => 'CREATED',
        ];
    }
}
