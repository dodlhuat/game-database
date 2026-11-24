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
            'provider' => 'mollie',
            'provider_payment_id' => 'tr_'.Str::random(10),
            'token_amount' => 20,
            'price_cents' => 50,
            'currency' => 'EUR',
            'status' => 'CREATED',
        ];
    }
}
