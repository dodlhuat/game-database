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
    | NOTE: currently set to low test amounts (see Phase 0 decision) — adjust
    | before going live with real pricing.
    |
    */

    'packages' => [
        20 => 50,
        30 => 100,
        40 => 150,
    ],

    'currency' => 'EUR',

];
