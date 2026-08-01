<?php
/**
 * Autoloader simples (sem Composer), com mapeamento explícito por prefixo
 * de namespace, já que Models, Views, Rotas e Services ficam fora de app/
 * (fora do padrão "tudo dentro de app/").
 *
 * Importante: "App\" aqui é só o nome-raiz do NAMESPACE (lógico), não tem
 * relação com a pasta física "app/". Cada sub-prefixo do namespace é
 * mapeado explicitamente para a pasta física real — por isso App\Models\
 * aponta para models/ e App\Services\ aponta para services/, ambos fora
 * de app/.
 *
 *   App\Controllers\*  -> app/Controllers/
 *   App\Core\*         -> app/Core/
 *   App\Models\*       -> models/
 *   App\Services\*     -> services/
 */
spl_autoload_register(function ($class) {
    $prefix = 'App\\';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return; // não é uma classe do nosso namespace
    }

    $map = [
        'App\\Controllers\\' => APP_ROOT . '/app/Controllers/',
        'App\\Core\\'        => APP_ROOT . '/app/Core/',
        'App\\Models\\'      => APP_ROOT . '/models/',
        'App\\Services\\'    => APP_ROOT . '/services/',
    ];

    foreach ($map as $namespacePrefix => $baseDir) {
        $len = strlen($namespacePrefix);
        if (strncmp($namespacePrefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
            }
            return;
        }
    }
});
