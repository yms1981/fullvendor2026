<?php

return [
    'currency' => env('STRIPE_CURRENCY', 'usd'),
    'invoice_due_days' => (int) env('STRIPE_INVOICE_DUE_DAYS', 30),
];
