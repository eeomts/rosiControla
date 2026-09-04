<?php
// router do `php -S`: arquivo que existe em public/ e servido cru; o resto vai
// para o front controller, porque as URLs sao /ciclo/form/1
$arquivo = __DIR__ . '/public' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (is_file($arquivo)) {
    return false;
}

require __DIR__ . '/public/index.php';
