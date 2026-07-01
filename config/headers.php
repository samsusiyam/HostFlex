<?php
@ini_set('expose_php', 'Off');

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.gc_maxlifetime', '28800');
    @ini_set('session.cookie_lifetime', '28800');
    session_start([
        'cookie_lifetime' => 28800,
        'gc_maxlifetime' => 28800,
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => 1,
        'cookie_httponly' => 1,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => 1,
    ]);
}

if (empty(headers_sent())) {
    header_remove('X-Powered-By');
    header_remove('Server');

    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://embed.tawk.to https://*.siyam.eu.cc",
        "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdn.tailwindcss.com",
        "img-src 'self' data: https:",
        "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com https://cdn.jsdelivr.net",
        "connect-src 'self' https://embed.tawk.to wss://embed.tawk.to wss://*.siyam.eu.cc https://*.siyam.eu.cc",
        "frame-src https://embed.tawk.to https://www.youtube.com https://www.google.com https://www.recaptcha.net https://*.siyam.eu.cc",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'"
    ]);
    header('Content-Security-Policy: ' . $csp);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
