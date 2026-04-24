<?php

return [
    // Hardcoded to avoid dependence on .env
    'phone_number_id' => '739536875914713',
    'access_token' => 'EAAQcr5qk5cABQrGGmCkZCm1S4QrSHbeS9B0EzsRzAoVPzTi8OAIfyivmHQju1KyH8yEBZArs4Nf4NhFmiVnRYoKx3DMZBpjXZBMaS52jEXsKb02pIPWCa866Phg4jJRvcxOQutk1M3QIsfkZBijU3ZCSZAz0XTMfV5GkzwBImPialR44PqzP66RYb1DReSd5xLnSgZDZD',
    'business_phone_number' => env('WHATSAPP_BUSINESS_PHONE', ''),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'snoutiq_whatsapp_webhook'),
    'default_language' => 'en_US',
    'default_template' => 'hello_world',
];
