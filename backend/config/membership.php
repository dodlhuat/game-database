<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mitgliedschaft
    |--------------------------------------------------------------------------
    |
    | Server-side source of truth. Alle Beträge in Cent.
    |
    */

    'fee_cents' => 2400,

    // Normale Token (unbegrenzt gültig, erstattbar) für Vollmitglieder
    'tokens' => 20,

    // Geschenkte Token (verfallen, nicht erstattbar) für Vollmitglieder
    'bonus_tokens' => 20,
    'bonus_valid_months' => 12,

    // Rückzahlung bei Kündigung
    'refund_fee_cents' => 200,
    'purchase_refund_wait_days' => 30,

];
