<?php

namespace App\Core;

/**
 * Wrapper simples para manipulação de sessão.
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);

            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['SERVER_PORT'] ?? null) === '443';

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,   // só envia o cookie por HTTPS quando disponível
                'httponly' => true,     // JS não consegue ler o cookie de sessão (mitiga XSS)
                'samesite' => 'Lax',    // mitiga CSRF vindo de sites externos
            ]);

            session_start();
        }
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return self::has('user_id') && self::get('user_id') > 0;
    }

    public static function isAdmin(): bool
    {
        return self::get('is_admin', false) === true;
    }
}
