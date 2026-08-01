<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CartModel;

/**
 * Substitui assets/php/savecart.php, remove-item.php e clear_cart.php.
 *
 * Todos os endpoints que alteram o carrinho exigem token CSRF
 * (App\Core\Csrf) e trabalham só com produto_id/quantidade — o preço
 * nunca é aceito vindo do cliente (ver App\Models\ProductModel).
 */
class CartController extends Controller
{
    private CartModel $cartModel;

    public function __construct()
    {
        $this->cartModel = new CartModel();
    }

    /** POST /cart/save — substitui o carrinho a partir de [{produto_id, quantidade}] */
    public function save(): void
    {
        $this->requireCsrf();

        $data = $this->jsonInput();
        $itens = $data['items'] ?? [];

        if (!is_array($itens)) {
            $this->json(['status' => 'error', 'message' => 'Dados inválidos.'], 400);
        }

        $this->cartModel->replaceFromArray($itens);

        $this->json([
            'status' => 'success',
            'total' => $this->cartModel->getTotal(),
            'totalFormatted' => number_format($this->cartModel->getTotal(), 2, ',', '.'),
        ]);
    }

    /** POST /cart/remove — remove um produto pelo produto_id */
    public function remove(): void
    {
        $this->requireCsrf();

        $input = $this->jsonInput();
        $produtoId = $input['produto_id'] ?? $input['itemId'] ?? null;

        if (!is_numeric($produtoId)) {
            $this->json(['success' => false, 'error' => 'ID do produto inválido.'], 400);
        }

        $result = $this->cartModel->removeItem((int) $produtoId);
        $this->json(array_merge(['success' => true], $result));
    }

    /** POST /cart/clear */
    public function clear(): void
    {
        $this->requireCsrf();
        $this->cartModel->clear();
        $this->json(['status' => 'success']);
    }
}
