<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrderModel;
use App\Services\MailerService;
use App\Services\PagBankService;

/**
 * Endpoint público chamado pelo PagBank (server-to-server) quando o status
 * de um checkout/pagamento muda. Substitui assets/php/webhook.php.
 */
class WebhookController extends Controller
{
    private OrderModel $orderModel;
    private PagBankService $pagBank;
    private MailerService $mailer;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->pagBank = new PagBankService();
        $this->mailer = new MailerService();
    }

    /** POST /webhook/pagbank */
    public function pagbank(): void
    {
        $payloadBruto = file_get_contents('php://input');
        if ($payloadBruto === '' || $payloadBruto === false) {
            $this->json(['success' => false, 'message' => 'Corpo vazio.'], 400);
        }

        $assinaturaRecebida = $_SERVER['HTTP_X_AUTHENTICITY_TOKEN'] ?? '';

        if (!$this->pagBank->assinaturaValida($payloadBruto, $assinaturaRecebida)) {
            error_log('Webhook PagBank: assinatura ausente ou inválida.');
            $this->json(['success' => false, 'message' => 'Não autorizado.'], 401);
        }

        $payload = json_decode($payloadBruto, true);
        if (!is_array($payload)) {
            $this->json(['success' => false, 'message' => 'JSON inválido.'], 400);
        }

        $referenceId = $payload['reference_id']
            ?? $payload['charges'][0]['reference_id']
            ?? $payload['order']['reference_id']
            ?? null;

        $statusRecebido = strtoupper((string) ($payload['status'] ?? $payload['charges'][0]['status'] ?? ''));
        $chargeId = $payload['id'] ?? $payload['charges'][0]['id'] ?? null;
        $metodoPagamento = $payload['payment_method']['type'] ?? $payload['charges'][0]['payment_method']['type'] ?? null;

        if (!$referenceId) {
            error_log('Webhook PagBank: notificação sem reference_id. Payload: ' . $payloadBruto);
            // 200 mesmo assim, para o PagBank não ficar reenviando algo que não sabemos processar.
            $this->json(['success' => true, 'message' => 'Ignorado (sem reference_id).']);
        }

        $mapaStatus = [
            'PAID' => 'PAGO',
            'AUTHORIZED' => 'AUTORIZADO',
            'IN_ANALYSIS' => 'EM_ANALISE',
            'WAITING' => 'AGUARDANDO',
            'DECLINED' => 'RECUSADO',
            'CANCELED' => 'CANCELADO',
            'REFUNDED' => 'ESTORNADO',
        ];
        $paymentStatus = $mapaStatus[$statusRecebido] ?? 'AGUARDANDO';

        $statusPedido = 'pendente';
        if (in_array($paymentStatus, ['PAGO', 'AUTORIZADO'], true)) {
            $statusPedido = 'confirmado';
        } elseif (in_array($paymentStatus, ['RECUSADO', 'CANCELADO'], true)) {
            $statusPedido = 'cancelado';
        }

        try {
            $atualizado = $this->orderModel->atualizarStatusPagamento(
                $referenceId,
                $paymentStatus,
                $statusPedido,
                $chargeId,
                $metodoPagamento
            );
        } catch (\Throwable $e) {
            error_log('Erro ao atualizar pedido via webhook: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao atualizar o pedido.'], 500);
        }

        if (!$atualizado) {
            error_log("Webhook PagBank: nenhum pedido encontrado para reference_id={$referenceId}");
        } elseif (in_array($paymentStatus, ['PAGO', 'AUTORIZADO'], true)) {
            // E-mail de pagamento confirmado (melhor esforço).
            try {
                $pedido = $this->orderModel->buscarPorReferenceId($referenceId);
                if ($pedido) {
                    $this->mailer->enviarPagamentoConfirmado($pedido['guest_email'], $pedido['guest_nome'], $referenceId);
                }
            } catch (\Throwable $e) {
                error_log('Falha ao enviar e-mail de pagamento confirmado: ' . $e->getMessage());
            }
        }

        // O PagBank considera a notificação entregue com qualquer resposta 2xx.
        $this->json(['success' => true]);
    }
}
