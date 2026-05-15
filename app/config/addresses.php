<?php

/*
|--------------------------------------------------------------------------
| Vietnamese administrative addresses
|--------------------------------------------------------------------------
| Endpoints for the two address sources used by `addresses:sync`:
|  - AddressKit (cas.so): 2-level standard after 2025 reform.
|  - provinces.open-api.vn: 3-level legacy standard.
*/
return [
    'cas_base_url' => env('CAS_ADDRESSKIT_BASE_URL', 'https://addresskit.cas.so'),
    'open_api_base_url' => env('OPEN_API_ADDRESSES_BASE_URL', 'https://provinces.open-api.vn'),
];
