<?php

// config.php
return [
    'business' => [
        'name' => 'Patblacksonline',
        'email' => 'Accounts@patblacksonline.co.uk',
        'phone' => '07896174219',     // optional
        'address' => '30 upper high street'    // optional
    ],
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'username' => 'patblack@patblacksonline.co.uk',
        'password' => 'sraz resd bbly hfcq',
        'port' => 587,
        'secure' => 'tls', // tls or ssl
        'from_email' => 'patblack@patblacksonline.co.uk',
        'from_name' => 'Patblacksonline'
        
        
    ],
    'base_url' => 'https://your-ngrok-url.ngrok.io' // used for public links (optional)
];
