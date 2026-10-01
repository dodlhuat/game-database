<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token Packages
    |--------------------------------------------------------------------------
    |
    | Server-side source of truth for what a token package costs. Never trust
    | a price sent by the client — always look it up here by token amount.
    | Values are in cents to avoid float rounding issues.
    |
    */

    'packages' => [
        20 => 1000,
        30 => 1450,
        40 => 1850,
    ],

    // Nominalwert eines Tokens in Cent (Mitgliedschaft, Bonus-Berechnung)
    'token_value_cents' => 50,

    'currency' => 'EUR',

];
