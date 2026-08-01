<?php
/**
 * router-dev.php
 *
 * O servidor embutido do PHP (`php -S`) não lê .htaccess. Este router faz
 * o mesmo papel do public/.htaccess só para desenvolvimento local: se a
 * URL corresponder a um arquivo real (CSS/JS/imagem), serve o arquivo
 * direto; caso contrário, delega para o front controller (index.php).
 *
 * Uso:
 *   cd public
 *   php -S localhost:8000 router-dev.php
 *
 * Em produção (Apache/Nginx) isso NÃO é usado — lá quem faz esse papel é
 * o public/.htaccess (Apache) ou a configuração de location do Nginx.
 */

$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $path;

if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // deixa o servidor embutido servir o arquivo estático normalmente
}

require __DIR__ . '/index.php';
