<?php

namespace App\Core;

/**
 * Carregador simples de variáveis de ambiente a partir de um arquivo .env.
 * Evita a necessidade de credenciais hardcoded no código-fonte.
 *
 * Uso:
 *   Env::load(APP_ROOT . '/.env');
 *   Env::get('DB_HOST', '127.0.0.1');
 */
class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                // Ignora comentários e linhas vazias/sem "="
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove aspas simples ou duplas ao redor do valor
                if (strlen($value) >= 2) {
                    $first = $value[0];
                    $last = $value[strlen($value) - 1];
                    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                        $value = substr($value, 1, -1);
                    }
                }

                if (getenv($key) === false) {
                    putenv("{$key}={$value}");
                }
                $_ENV[$key] = $_ENV[$key] ?? $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Retorna o valor de uma variável de ambiente ou o padrão informado.
     */
    public static function get(string $key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? null;
        }

        return ($value !== null && $value !== '') ? $value : $default;
    }
}
