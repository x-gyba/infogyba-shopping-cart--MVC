<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\Validation;
use App\Models\CartModel;
use App\Models\InsufficientStockException;
use App\Models\OrderModel;
use App\Services\MailerService;
use App\Services\PagBankService;
use Throwable;

/**
 * Checkout GUEST: sem login. Substitui assets/php/checkout.php (exibição)
 * e assets/php/process.php (gravação do pedido + criação do pagamento).
 */
class CheckoutController extends Controller
{
    private CartModel $cartModel;
    private OrderModel $orderModel;
    private PagBankService $pagBank;
    private MailerService $mailer;

    public function __construct()
    {
        $this->cartModel = new CartModel();
        $this->orderModel = new OrderModel();
        $this->pagBank = new PagBankService();
        $this->mailer = new MailerService();
    }

    private function validateDiscountCode(string $code): bool
    {
        return $code === DISCOUNT_CODE;
    }

    /** GET|POST /checkout — página de checkout guest (dados + entrega + pagamento) */
    public function index(): void
    {
        $total = $this->cartModel->getTotal();
        $discount = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discount_code'])) {
            // Formulário tradicional (não-AJAX) — também exige CSRF.
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                http_response_code(403);
                echo 'Token CSRF inválido. Recarregue a página e tente novamente.';
                return;
            }
            if ($this->validateDiscountCode($_POST['discount_code'])) {
                $discount = round($total * DISCOUNT_PERCENT, 2);
                Session::set('discount', $discount);
            }
        } elseif (Session::has('discount')) {
            $discount = (float) Session::get('discount');
        }

        $finalTotal = round($total - $discount + FRETE_VALOR, 2);

        $this->render('checkout/index', [
            'total' => $total,
            'discount' => $discount,
            'frete' => FRETE_VALOR,
            'finalTotal' => $finalTotal,
            'cartItens' => $this->cartModel->getItensDetalhados(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    /**
     * Limpa apenas espaços e caracteres de controle. Propositalmente NÃO usa
     * htmlspecialchars() aqui — escapar é responsabilidade de quem EXIBE o
     * dado em HTML (na view), não de quem o recebe/grava. Fazer isso na
     * entrada corromperia o valor gravado no banco e enviado à API do
     * PagBank (ex.: "José" viraria "Jos&eacute;").
     */
    private function cleanInput(string $value): string
    {
        $value = trim($value);
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    }

    /** POST /checkout/process — valida os dados, grava o pedido e cria o checkout PagBank */
    public function process(): void
    {
        $this->requireCsrf();

        // CORREÇÃO: limita tentativas por IP (antes não havia nenhum limite
        // — um script conseguia gerar pedidos/checkouts em massa no PagBank).
        if (!RateLimiter::permitir('checkout_process', 8, 600)) {
            $this->json(['success' => false, 'message' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.'], 429);
        }

        // Fonte da verdade do carrinho: sempre o catálogo do servidor,
        // nunca o que o cliente envia no corpo da requisição.
        $itensCarrinho = $this->cartModel->getItensDetalhados();
        $cartTotal = $this->cartModel->getTotal();
        $discount = Session::has('discount') ? (float) Session::get('discount') : 0;

        if (empty($itensCarrinho) || $cartTotal <= 0) {
            $this->json(['success' => false, 'message' => 'Carrinho vazio ou inválido.'], 400);
        }

        $input = $this->jsonInput();

        $nome = $this->cleanInput($input['nome'] ?? '');
        $email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $cpf = preg_replace('/\D/', '', $input['cpf'] ?? '');
        $telefone = preg_replace('/\D/', '', $input['telefone'] ?? '');

        $rua = $this->cleanInput($input['rua'] ?? '');
        $numero = $this->cleanInput($input['numero'] ?? '');
        $complemento = $this->cleanInput($input['complemento'] ?? '');
        $bairro = $this->cleanInput($input['bairro'] ?? '');
        $cidade = $this->cleanInput($input['cidade'] ?? '');
        $estado = strtoupper($this->cleanInput($input['estado'] ?? ''));
        $cep = preg_replace('/\D/', '', $input['cep'] ?? '');

        $erros = [];
        if ($nome === '') $erros[] = 'Nome é obrigatório.';
        if (!$email) $erros[] = 'E-mail inválido.';
        // CORREÇÃO: antes só checava o tamanho (11 dígitos); agora valida os
        // dígitos verificadores de verdade (App\Core\Validation::cpfValido).
        if (!Validation::cpfValido($cpf)) $erros[] = 'CPF inválido.';
        if (strlen($telefone) < 10) $erros[] = 'Telefone inválido.';
        if ($rua === '' || $numero === '' || $bairro === '' || $cidade === '') $erros[] = 'Endereço incompleto.';
        if (strlen($estado) !== 2) $erros[] = 'UF inválida.';
        if (!Validation::cepValido($cep)) $erros[] = 'CEP inválido.';
        if (empty($input['aceite_privacidade'])) $erros[] = 'É preciso aceitar a Política de Privacidade.';

        if (!empty($erros)) {
            $this->json(['success' => false, 'message' => implode(' ', $erros)], 422);
        }

        // Itens do pedido: nome/preço/produto_id vêm do catálogo (ProductModel),
        // nunca do texto do cliente. produto_id é usado para decrementar
        // estoque de forma atômica em OrderModel::criarPedidoGuest().
        $itensPedido = array_map(
            fn ($item) => [
                'produto_id' => $item['produto_id'],
                'nome' => $item['titulo'],
                'quantidade' => $item['quantidade'],
                'preco_unitario' => $item['preco'],
            ],
            $itensCarrinho
        );

        // 8 bytes aleatórios (64 bits) — bem mais difícil de adivinhar/colidir que os 24 bits anteriores.
        $referenceId = 'PED-' . date('YmdHis') . '-' . bin2hex(random_bytes(8));

        try {
            $pedidoId = $this->orderModel->criarPedidoGuest(
                ['nome' => $nome, 'email' => $email, 'telefone' => $telefone, 'cpf' => $cpf],
                ['rua' => $rua, 'numero' => $numero, 'complemento' => $complemento, 'bairro' => $bairro, 'cidade' => $cidade, 'estado' => $estado, 'cep' => $cep],
                $itensPedido,
                $cartTotal,
                $discount,
                $referenceId,
                FRETE_VALOR
            );
        } catch (InsufficientStockException $e) {
            // CORREÇÃO: falta de estoque agora é checada de forma atômica
            // dentro da transação — sem isso, dava para "comprar" mais
            // unidades do que existiam.
            $this->json(['success' => false, 'message' => $e->getMessage()], 409);
            return;
        } catch (Throwable $e) {
            error_log('Erro ao gravar pedido: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Não foi possível gravar o pedido. Tente novamente.'], 500);
            return;
        }

        // E-mail de "pedido recebido" (melhor esforço — falha aqui nunca
        // deve impedir o comprador de seguir para o pagamento).
        try {
            $this->mailer->enviarPedidoRecebido($email, $nome, $referenceId, $itensPedido, round($cartTotal - $discount + FRETE_VALOR, 2));
        } catch (Throwable $e) {
            error_log('Falha ao enviar e-mail de pedido recebido: ' . $e->getMessage());
        }

        // CORREÇÃO DE SEGURANÇA: URL fixa vinda do .env (APP_URL), nunca do
        // header Host (que o cliente controla e pode forjar).
        $baseUrl = APP_URL !== '' ? APP_URL : ($_SERVER['HTTP_HOST'] ?? '');

        try {
            $checkoutResponse = $this->pagBank->criarCheckout([
                'reference_id' => $referenceId,
                'itens' => $itensPedido,
                'desconto' => $discount,
                'cliente' => ['nome' => $nome, 'email' => $email, 'cpf' => $cpf, 'telefone' => $telefone],
                'endereco' => ['rua' => $rua, 'numero' => $numero, 'complemento' => $complemento, 'bairro' => $bairro, 'cidade' => $cidade, 'estado' => $estado, 'cep' => $cep],
                'redirect_url' => $baseUrl . '/pedido/' . urlencode($referenceId),
                'return_url' => $baseUrl . '/checkout',
                'notification_urls' => [$baseUrl . '/webhook/pagbank'],
                'payment_notification_urls' => [$baseUrl . '/webhook/pagbank'],
            ]);

            $linkPagamento = $this->pagBank->extrairLinkPagamento($checkoutResponse);
            if (!$linkPagamento) {
                throw new \RuntimeException('A API do PagBank não retornou um link de pagamento.');
            }

            $this->orderModel->salvarCheckoutId($pedidoId, $checkoutResponse['id'] ?? null);

            Session::set('pedido_id', $pedidoId);
            Session::set('pedido_reference_id', $referenceId);

            $this->json(['success' => true, 'redirect' => $linkPagamento, 'reference_id' => $referenceId]);
        } catch (Throwable $e) {
            error_log('Erro ao criar checkout PagBank: ' . $e->getMessage());
            $this->orderModel->cancelar($pedidoId);
            $this->json(['success' => false, 'message' => 'Não foi possível iniciar o pagamento. Tente novamente em instantes.'], 502);
        }
    }
}
