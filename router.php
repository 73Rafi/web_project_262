<?php
// Used only by: php -S 127.0.0.1:8000 router.php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = str_replace('\\', '/', $path);
if (preg_match('~(^|/)(backend|tests|\.[^/]*)(/|$)~i', $path) || str_contains($path, '..')) {
    http_response_code(403);
    exit('Forbidden');
}
if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}
return false;
