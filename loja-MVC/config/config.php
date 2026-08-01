<?php

/**
 * Configurações gerais da aplicação.
 * Nenhuma credencial fica aqui — tudo vem do arquivo .env (que NÃO deve
 * ser versionado; veja .env.example para o modelo a ser preenchido).
 */

require_once dirname(__DIR__) . '/app/Core/Env.php';

use App\Core\Env;

// Raiz do projeto (usada por Router/Controller para localizar views, etc.)
define('APP_ROOT', dirname(__DIR__));

// Carrega o .env (se não existir, os valores caem nos defaults abaixo)
Env::load(APP_ROOT . '/.env');

// Ambiente: 'development' exibe erros, 'production' oculta.
define('APP_ENV', Env::get('APP_ENV', 'production'));

// URL base da aplicação (sem barra no final). Ex: http://localhost/infogyba
define('BASE_URL', Env::get('BASE_URL', ''));

// URL pública e fixa da aplicação (ex.: https://loja.seudominio.com.br).
// CORREÇÃO DE SEGURANÇA: usada para montar redirect_url/return_url/
// notification_urls enviadas ao PagBank. Nunca usar $_SERVER['HTTP_HOST']
// diretamente para isso — esse header é enviado pelo cliente e pode ser
// forjado (Host header injection), o que permitiria redirecionar o
// comprador ou o webhook para um domínio arbitrário.
define('APP_URL', rtrim(Env::get('APP_URL', ''), '/'));

// Banco de dados — valores reais ficam apenas no .env local, nunca aqui.
define('DB_HOST', Env::get('DB_HOST', '127.0.0.1'));
define('DB_NAME', Env::get('DB_NAME', ''));
define('DB_USER', Env::get('DB_USER', ''));
define('DB_PASS', Env::get('DB_PASS', ''));
define('DB_CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));

// Sessão
define('SESSION_NAME', Env::get('SESSION_NAME', 'infogyba_session'));

// Regras de desconto do checkout
define('DISCOUNT_CODE', Env::get('DISCOUNT_CODE', 'DESCONTO10'));
define('DISCOUNT_PERCENT', (float) Env::get('DISCOUNT_PERCENT', '0.10'));

// Frete: valor fixo aplicado a todo pedido. 0.00 = frete grátis.
// EVOLUÇÃO: antes o frete estava hardcoded como "FREE" na integração com
// o PagBank; agora é configurável sem precisar mexer em código.
define('FRETE_VALOR', (float) Env::get('FRETE_VALOR', '0.00'));

// PagBank / PagSeguro — Checkout API (checkout guest). Token real só no .env.
define('PAGBANK_ENV', Env::get('PAGBANK_ENV', 'sandbox'));
define('PAGBANK_TOKEN', Env::get('PAGBANK_TOKEN', ''));

// E-mail transacional (confirmação de pedido). Desligado por padrão em dev
// (a maioria dos ambientes locais não tem sendmail/SMTP configurado).
define('MAIL_ENABLED', filter_var(Env::get('MAIL_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN));
define('MAIL_FROM', Env::get('MAIL_FROM', ''));

if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}