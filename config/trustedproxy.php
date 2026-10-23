<?php

return [
    // Laravel's existing middleware accepts comma-separated proxy IPs/CIDRs.
    'proxies' => env('TRUSTED_PROXIES') ?: [],
];
