<?php
declare(strict_types=1);

return [
    // Fill these in when your SMTP account is ready.
    'host' => 'smtp.gmail.com',
    'username' => 'leimersarte02@gmail.com',
    'password' => '',
    'port' => 587,
    'encryption' => 'tls', // tls, ssl, or empty string

    'from_email' => 'no-reply@localhost',
    'from_name' => 'School Records Database',

    // Keep false for production. Turn on only while testing SMTP setup.
    'debug' => false,
];

