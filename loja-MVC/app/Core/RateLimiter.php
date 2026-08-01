<?php

namespace App\Core;

/**
 * Rate limiter simples (sem Redis/Memcached), baseado em arquivo, por IP.
 *
 * CORREÇÃO: /checkout/process não tinha nenhum limite — um script
 * conseguiria gerar pedidos/checkouts no PagBank em massa. Isso é
 * suficiente para um projeto de porte pequeno/médio; em alta escala,
 * trocar por Redis é o próximo passo natural (a interface abaixo não
 * mudaria).
 */
class RateLimiter
{
    private static function dir(): string
    {
        $dir = APP_ROOT . '/storage/ratelimit';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function clientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    }

    /**
     * Registra uma tentativa e diz se ela deve ser permitida.
     *
     * @param string $chave         Identifica a ação (ex.: "checkout_process").
     * @param int    $maxTentativas Máximo de tentativas permitidas na janela.
     * @param int    $janelaSegundos Duração da janela deslizante, em segundos.
     */
    public static function permitir(string $chave, int $maxTentativas, int $janelaSegundos): bool
    {
        $arquivo = self::dir() . '/' . preg_replace('/[^a-z0-9_]/i', '_', $chave . '_' . self::clientIp()) . '.json';

        $agora = time();
        $tentativas = [];

        if (file_exists($arquivo)) {
            $conteudo = json_decode((string) file_get_contents($arquivo), true);
            $tentativas = is_array($conteudo) ? $conteudo : [];
        }

        // Mantém só as tentativas dentro da janela deslizante
        $tentativas = array_values(array_filter($tentativas, fn ($t) => ($agora - $t) < $janelaSegundos));

        if (count($tentativas) >= $maxTentativas) {
            return false;
        }

        $tentativas[] = $agora;
        file_put_contents($arquivo, json_encode($tentativas), LOCK_EX);

        return true;
    }
}
