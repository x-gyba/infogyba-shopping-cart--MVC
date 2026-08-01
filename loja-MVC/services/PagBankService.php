<?php

namespace App\Services;

use RuntimeException;

/**
 * Integração com a API de Checkout do PagBank (PagSeguro).
 * Equivalente ao antigo assets/php/pagseguro.php, agora como classe de
 * serviço (fora de app/, assim como Models, Views e Routes).
 *
 * Credenciais vêm de config/config.php -> .env (PAGBANK_TOKEN), nunca
 * hardcoded.
 *
 * Docs: https://developer.pagbank.com.br/reference/criar-checkout
 */
class PagBankService
{
    private function baseUrl(): string
    {
        return PAGBANK_ENV === 'production'
            ? 'https://api.pagseguro.com'
            : 'https://sandbox.api.pagseguro.com';
    }

    private function paraCentavos(float $valor): int
    {
        return (int) round($valor * 100);
    }

    /**
     * Cria um Checkout PagBank (modelo "Redirecionamento") para um pedido.
     *
     * @param array $pedido {
     *   reference_id, itens[], desconto, cliente[nome,email,cpf,telefone],
     *   endereco[rua,numero,complemento,bairro,cidade,estado,cep],
     *   redirect_url, return_url, notification_urls[], payment_notification_urls[]
     * }
     *
     * Frete: taxa fixa única, configurada em FRETE_VALOR no .env (0 = grátis).
     * Não há cálculo por distância/CEP/peso — para isso, integrar uma API de
     * frete (Correios, Melhor Envio etc.) e substituir esse valor fixo.
     * @throws RuntimeException em caso de falha de rede ou erro da API.
     */
    public function criarCheckout(array $pedido): array
    {
        if (!PAGBANK_TOKEN) {
            throw new RuntimeException('PAGBANK_TOKEN não configurado no .env.');
        }

        $telefoneDigitos = preg_replace('/\D/', '', $pedido['cliente']['telefone'] ?? '');
        $ddd = substr($telefoneDigitos, 0, 2);
        $numero = substr($telefoneDigitos, 2);

        $items = [];
        foreach ($pedido['itens'] as $item) {
            $items[] = [
                'name' => mb_substr($item['nome'], 0, 100),
                'quantity' => (int) $item['quantidade'],
                'unit_amount' => $this->paraCentavos((float) $item['preco_unitario']),
            ];
        }

        if (!empty($pedido['desconto']) && $pedido['desconto'] > 0 && count($items) > 0) {
            // A API aplica o desconto no primeiro item da lista.
            $items[0]['discount_amount'] = $this->paraCentavos((float) $pedido['desconto']);
        }

        $freteValor = defined('FRETE_VALOR') ? (float) FRETE_VALOR : 0.0;

        $payload = [
            'reference_id' => $pedido['reference_id'],
            'customer_modifiable' => false,
            'customer' => [
                'name' => mb_substr($pedido['cliente']['nome'], 0, 120),
                'email' => $pedido['cliente']['email'],
                'tax_id' => preg_replace('/\D/', '', $pedido['cliente']['cpf'] ?? ''),
                'phone' => [
                    'country' => '+55',
                    'area' => $ddd,
                    'number' => $numero,
                ],
            ],
            'items' => $items,
            'shipping' => array_merge(
                [
                    'address_modifiable' => false,
                    'address' => [
                        'street' => mb_substr($pedido['endereco']['rua'], 0, 160),
                        'number' => mb_substr($pedido['endereco']['numero'], 0, 20),
                        'complement' => mb_substr($pedido['endereco']['complemento'] ?? '', 0, 40) ?: 'N/A',
                        'locality' => mb_substr($pedido['endereco']['bairro'], 0, 60),
                        'city' => mb_substr($pedido['endereco']['cidade'], 0, 90),
                        'region_code' => strtoupper($pedido['endereco']['estado']),
                        'country' => 'BRA',
                        'postal_code' => preg_replace('/\D/', '', $pedido['endereco']['cep']),
                    ],
                ],
                $freteValor > 0
                    ? ['type' => 'FIXED', 'amount' => $this->paraCentavos($freteValor)]
                    : ['type' => 'FREE']
            ),
            'payment_methods' => [
                ['type' => 'CREDIT_CARD'],
                ['type' => 'DEBIT_CARD'],
                ['type' => 'PIX'],
                ['type' => 'BOLETO'],
            ],
            'redirect_url' => $pedido['redirect_url'],
            'return_url' => $pedido['return_url'],
            'notification_urls' => $pedido['notification_urls'] ?? [],
            'payment_notification_urls' => $pedido['payment_notification_urls'] ?? [],
        ];

        $ch = curl_init($this->baseUrl() . '/checkouts');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . PAGBANK_TOKEN,
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $respostaBruta = curl_exec($ch);
        $erroCurl = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respostaBruta === false) {
            throw new RuntimeException('Falha de rede ao chamar a API do PagBank: ' . $erroCurl);
        }

        $resposta = json_decode($respostaBruta, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            $mensagem = $resposta['error_messages'][0]['description']
                ?? ('Erro HTTP ' . $statusCode . ' na API do PagBank.');
            error_log('Erro PagBank (criar checkout): ' . $respostaBruta);
            throw new RuntimeException($mensagem);
        }

        return $resposta;
    }

    /**
     * Consulta o status atual de um checkout diretamente na API do PagBank
     * (usado pelo script de reconciliação, para pedidos cujo webhook pode
     * ter falhado em chegar).
     *
     * @throws RuntimeException em caso de falha de rede ou erro da API.
     */
    public function consultarCheckout(string $checkoutId): array
    {
        if (!PAGBANK_TOKEN) {
            throw new RuntimeException('PAGBANK_TOKEN não configurado no .env.');
        }

        $ch = curl_init($this->baseUrl() . '/checkouts/' . urlencode($checkoutId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . PAGBANK_TOKEN,
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $respostaBruta = curl_exec($ch);
        $erroCurl = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respostaBruta === false) {
            throw new RuntimeException('Falha de rede ao consultar checkout no PagBank: ' . $erroCurl);
        }

        $resposta = json_decode($respostaBruta, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('Erro HTTP ' . $statusCode . ' ao consultar checkout no PagBank.');
        }

        return $resposta;
    }

    /**
     * Extrai o link de pagamento (rel = PAY) da resposta de criarCheckout().
     */
    public function extrairLinkPagamento(array $checkoutResponse): ?string
    {
        foreach ($checkoutResponse['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'PAY') {
                return $link['href'];
            }
        }
        return null;
    }

    /**
     * Verifica a assinatura de uma notificação (webhook) do PagBank.
     * SHA-256({PAGBANK_TOKEN}-{payload_bruto}) deve bater com o header
     * `x-authenticity-token`.
     */
    public function assinaturaValida(string $payloadBruto, string $assinaturaRecebida): bool
    {
        if (!PAGBANK_TOKEN || !$assinaturaRecebida) {
            return false;
        }
        $esperada = hash('sha256', PAGBANK_TOKEN . '-' . $payloadBruto);
        return hash_equals($esperada, $assinaturaRecebida);
    }
}
