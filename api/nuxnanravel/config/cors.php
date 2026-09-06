<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*',
        'auth/*',
        'courses/*',
        'users/*',
        'profiles/*',
        'attendances/*',
        'storage/*',
        'typing/*',
        'game/*',
        'admin/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://www.nuxnan.com',
        'https://nuxnan.com',
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    // เบราว์เซอร์อ่าน response header ข้าม origin ได้แค่ CORS-safelisted 7 ตัว
    // ต้องประกาศ Content-Disposition ตรงนี้ ไม่งั้น api.getBlob() อ่านชื่อไฟล์ไม่ได้
    // แล้วไฟล์ที่ดาวน์โหลดจะชื่อ "download" ไม่มีนามสกุล
    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 0,

    'supports_credentials' => true,

];
