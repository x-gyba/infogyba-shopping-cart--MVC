<?php

/**
 * Definição das rotas da aplicação.
 * @var \App\Core\Router $router
 */

use App\Controllers\HomeController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\WebhookController;
use App\Controllers\LegalController;
use App\Controllers\OrderController;

// Página inicial (menu / vitrine de produtos)
$router->get('/', [HomeController::class, 'index']);

// Páginas legais (LGPD / CDC)
$router->get('/privacidade', [LegalController::class, 'privacidade']);
$router->get('/termos', [LegalController::class, 'termos']);
$router->get('/trocas-e-devolucao', [LegalController::class, 'trocasEDevolucao']);

// Carrinho de compras (endpoints AJAX consumidos por assets/js/cart.js)
$router->post('/cart/save', [CartController::class, 'save']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->post('/cart/clear', [CartController::class, 'clear']);

// Checkout GUEST (sem login)
$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout/process', [CheckoutController::class, 'process']);

// Acompanhamento do pedido (guest, via reference_id)
$router->get('/rastrear-pedido', [OrderController::class, 'rastrear']);
$router->get('/pedido/{referenceId}', [OrderController::class, 'acompanhar']);

// Notificações do PagBank (server-to-server)
$router->post('/webhook/pagbank', [WebhookController::class, 'pagbank']);
