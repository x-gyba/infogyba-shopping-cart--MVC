<?php

/**
 * scripts/reconcile-pagbank.php
 *
 * CORREÇÃO (lacuna citada): se o webhook do PagBank falhar em chegar
 * (rede instável, PagBank fora do ar por alguns minutos, etc.), o pedido
 * ficava travado em "AGUARDANDO" para sempre — nada consultava o PagBank
 * de novo depois. Este script consulta a API para cada pedido parado há
 * mais de N minutos e atualiza o status.
 *
 * Uso (linha de comando, não é uma rota HTTP):
 *   php scripts/reconcile-pagbank.php
 *
 * Sugestão de cron (a cada 10 minutos, ajuste o caminho do projeto):
 *   0,10,20,30,40,50 * * * * php /caminho/para/o/projeto/scripts/reconcile-pagbank.php >> /caminho/para/o/projeto/storage/logs/reconcile.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado via linha de comando.');
}

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\OrderModel;
use App\Services\PagBankService;

$orderModel = new OrderModel();
$pagBank = new PagBankService();

$mapaStatus = [
    'PAID' => 'PAGO',
    'AUTHORIZED' => 'AUTORIZADO',
    'IN_ANALYSIS' => 'EM_ANALISE',
    'WAITING' => 'AGUARDANDO',
    'DECLINED' => 'RECUSADO',
    'CANCELED' => 'CANCELADO',
    'REFUNDED' => 'ESTORNADO',
];

$pendentes = $orderModel->buscarAguardandoAntigos(15); // parados há mais de 15 minutos

echo '[' . date('Y-m-d H:i:s') . '] Reconciliação: ' . count($pendentes) . " pedido(s) encontrados.\n";

foreach ($pendentes as $pedido) {
    try {
        $checkout = $pagBank->consultarCheckout($pedido['pagbank_checkout_id']);

        $charges = $checkout['charges'] ?? [];
        $statusRecebido = strtoupper((string) ($charges[0]['status'] ?? $checkout['status'] ?? ''));
        $paymentStatus = $mapaStatus[$statusRecebido] ?? null;

        if (!$paymentStatus) {
            echo "  - {$pedido['reference_id']}: sem status conclusivo ainda, mantém AGUARDANDO.\n";
            continue;
        }

        $statusPedido = 'pendente';
        if (in_array($paymentStatus, ['PAGO', 'AUTORIZADO'], true)) {
            $statusPedido = 'confirmado';
        } elseif (in_array($paymentStatus, ['RECUSADO', 'CANCELADO'], true)) {
            $statusPedido = 'cancelado';
        }

        $orderModel->atualizarStatusPagamento(
            $pedido['reference_id'],
            $paymentStatus,
            $statusPedido,
            $charges[0]['id'] ?? null,
            $charges[0]['payment_method']['type'] ?? null
        );

        echo "  - {$pedido['reference_id']}: atualizado para {$paymentStatus}.\n";
    } catch (\Throwable $e) {
        echo "  - {$pedido['reference_id']}: erro ao consultar ({$e->getMessage()}).\n";
        error_log('Reconciliação PagBank falhou para ' . $pedido['reference_id'] . ': ' . $e->getMessage());
    }
}

echo "Reconciliação concluída.\n";
