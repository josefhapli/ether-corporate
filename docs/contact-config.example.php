<?php
// Copy OUTSIDE public_html to ~/ether-private/contact-config.php, permissions 0600.
// Create ~/ether-private/rate-limit with permissions 0700.
// Do not enable until the new corporate form and its notification settings are verified.
return [
    'enabled' => false,
    'portal_id' => '247280970',
    'form_id' => '4d984f04-b21b-4cee-8e26-5d7597f495dd', // Dedicated Ether Corporate Inquiry form GUID, NOT the Catalyst form.
    'allowed_origins' => ['https://www.etherstudios.net', 'https://etherstudios.net'],
    'rate_directory' => __DIR__ . '/rate-limit',
    'rate_salt' => '', // Generate with: php -r 'echo bin2hex(random_bytes(32));'
];
