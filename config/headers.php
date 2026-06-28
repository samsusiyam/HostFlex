<?php
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

@ini_set('session.cookie_secure', $is_https ? '1' : '0');
@ini_set('session.cookie_httponly', '1');
@ini_set('session.cookie_samesite', 'Lax');
@ini_set('expose_php', 'Off');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $is_https,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (empty(headers_sent())) {
    header_remove('X-Powered-By');
    header_remove('Server');

    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://embed.tawk.to",
        "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdn.tailwindcss.com",
        "img-src 'self' data: https:",
        "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com https://cdn.jsdelivr.net",
        "connect-src 'self' https://embed.tawk.to wss://embed.tawk.to",
        "frame-src https://embed.tawk.to https://www.youtube.com https://www.google.com",
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
