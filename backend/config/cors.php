return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://tttn-2.onrender.com'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];