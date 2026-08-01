<?php

namespace App\Core;

/**
 * Cabeçalhos de segurança HTTP.
 *
 * Desenvolvimento:
 * - permite HTTP normalmente;
 * - não força HTTPS;
 * - não envia HSTS.
 *
 * Produção:
 * - força HTTPS;
 * - envia HSTS somente após conexão HTTPS.
 */
class SecurityHeaders
{
    public static function apply(): void
    {
        // Headers básicos de segurança (HTTP ou HTTPS)
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // Content Security Policy
        header(
            "Content-Security-Policy: default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' https://unpkg.com; " .
            "font-src 'self' https://unpkg.com data:; " .
            "img-src 'self' data:; " .
            "connect-src 'self';"
        );

        /*
         * HTTPS obrigatório somente em produção.
         *
         * No seu ambiente atual:
         * http://infogyba.com.br
         * APP_ENV=development
         *
         * Portanto NÃO redireciona para https.
         */
        if (!defined('APP_ENV') || APP_ENV !== 'production') {
            return;
        }

        $isHttps =
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        // Produção: força HTTPS
        if (!$isHttps) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $uri  = $_SERVER['REQUEST_URI'] ?? '/';

            if ($host !== '') {
                header(
                    'Location: https://' . $host . $uri,
                    true,
                    301
                );
                exit;
            }

            return;
        }

        // Produção HTTPS: ativa HSTS
        header(
            'Strict-Transport-Security: max-age=31536000; includeSubDomains'
        );
    }
}