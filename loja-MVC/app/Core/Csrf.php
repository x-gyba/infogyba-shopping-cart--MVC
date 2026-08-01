<?php

namespace App\Core;

/**
 * Proteção CSRF simples baseada em token de sessão.
 * Usado pelos endpoints que alteram estado: /cart/save, /cart/remove,
 * /cart/clear e /checkout/process.
 */
class Csrf
{
    public static function token(): string
    {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf_token');
    }

    public static function validate(?string $token): bool
    {
        $expected = Session::get('csrf_token');
        return is_string($expected) && is_string($token) && $token !== '' && hash_equals($expected, $token);
    }
}
