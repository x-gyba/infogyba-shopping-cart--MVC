<?php

namespace App\Services;

/**
 * Envio de e-mails transacionais (confirmação de pedido).
 *
 * Implementação com a função nativa mail() do PHP — funciona out-of-the-box
 * em hospedagens com sendmail/Postfix configurado (comum em cPanel/shared
 * hosting), sem exigir nenhuma biblioteca extra. Em produção, para melhor
 * taxa de entrega (evitar cair em spam), o recomendado é trocar o método
 * enviar() por um provedor SMTP dedicado (SendGrid, Amazon SES, Mailgun,
 * PHPMailer com SMTP) — a assinatura pública da classe não mudaria, então
 * essa troca não afeta quem já chama MailerService.
 *
 * Todas as chamadas são "best-effort": falha de e-mail nunca deve
 * derrubar o fluxo de compra, por isso os métodos capturam o próprio erro
 * e apenas registram no log.
 */
class MailerService
{
    private function remetente(): string
    {
        return MAIL_FROM !== '' ? MAIL_FROM : ('nao-responda@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    }

    private function enviar(string $destinatario, string $assunto, string $corpoHtml): bool
    {
        if (!MAIL_ENABLED) {
            // E-mail desligado (ex.: ambiente de desenvolvimento sem SMTP configurado)
            error_log("[mail:desligado] Para={$destinatario} Assunto={$assunto}");
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: Cestas Online <' . $this->remetente() . '>',
        ];

        try {
            return mail($destinatario, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpoHtml, implode("\r\n", $headers));
        } catch (\Throwable $e) {
            error_log('Falha ao enviar e-mail: ' . $e->getMessage());
            return false;
        }
    }

    private function layout(string $titulo, string $conteudoHtml): string
    {
        return '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"></head>' .
            '<body style="font-family: Arial, sans-serif; background:#f5f5f5; padding:24px;">' .
            '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:8px;padding:24px;">' .
            '<h2 style="color:#1A5D3A;margin-top:0;">' . htmlspecialchars($titulo) . '</h2>' .
            $conteudoHtml .
            '<p style="color:#999;font-size:12px;margin-top:32px;">Cestas Online — este é um e-mail automático, não é preciso responder.</p>' .
            '</div></body></html>';
    }

    /** Enviado assim que o pedido é criado (antes da confirmação do pagamento). */
    public function enviarPedidoRecebido(string $emailDestino, string $nome, string $referenceId, array $itens, float $total): void
    {
        $linhasItens = '';
        foreach ($itens as $item) {
            $linhasItens .= '<li>' . (int) $item['quantidade'] . 'x ' . htmlspecialchars($item['nome']) .
                ' — R$ ' . number_format($item['preco_unitario'], 2, ',', '.') . '</li>';
        }

        $corpo = '<p>Olá, ' . htmlspecialchars($nome) . '!</p>' .
            '<p>Recebemos seu pedido <strong>' . htmlspecialchars($referenceId) . '</strong> e estamos aguardando a confirmação do pagamento.</p>' .
            '<ul>' . $linhasItens . '</ul>' .
            '<p><strong>Total: R$ ' . number_format($total, 2, ',', '.') . '</strong></p>' .
            '<p>Você pode acompanhar o status do seu pedido a qualquer momento em: ' .
            '<a href="' . APP_URL . '/pedido/' . urlencode($referenceId) . '">' . APP_URL . '/pedido/' . htmlspecialchars($referenceId) . '</a></p>';

        $this->enviar($emailDestino, 'Recebemos seu pedido ' . $referenceId, $this->layout('Pedido recebido!', $corpo));
    }

    /** Enviado quando o webhook do PagBank confirma o pagamento. */
    public function enviarPagamentoConfirmado(string $emailDestino, string $nome, string $referenceId): void
    {
        $corpo = '<p>Olá, ' . htmlspecialchars($nome) . '!</p>' .
            '<p>Seu pagamento do pedido <strong>' . htmlspecialchars($referenceId) . '</strong> foi confirmado. Já vamos preparar sua entrega!</p>' .
            '<p>Acompanhe em: <a href="' . APP_URL . '/pedido/' . urlencode($referenceId) . '">' . APP_URL . '/pedido/' . htmlspecialchars($referenceId) . '</a></p>';

        $this->enviar($emailDestino, 'Pagamento confirmado — pedido ' . $referenceId, $this->layout('Pagamento confirmado ✅', $corpo));
    }
}
