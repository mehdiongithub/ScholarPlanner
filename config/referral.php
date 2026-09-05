<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Referral Discount Reservation TTL
    |--------------------------------------------------------------------------
    |
    | The time-to-live in minutes for a referral first-payment discount
    | reservation during checkout. If checkout is abandoned, the reservation
    | expires after this time so the user can claim the discount on a new checkout.
    | Default is 120 minutes (2 hours).
    |
    */
    'reservation_ttl_minutes' => null,
];