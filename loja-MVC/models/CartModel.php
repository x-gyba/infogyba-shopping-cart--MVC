<?php

namespace App\Models;

use App\Core\Session;

/**
 * Model do carrinho de compras.
 *
 * CORREÇÃO DE SEGURANÇA (vulnerabilidade crítica): antes, o carrinho
 * guardava em sessão o TEXTO e o TOTAL que o próprio navegador enviava
 * ("Cesta X R$ 220,00 x1"), e o preço cobrado era extraído desse texto —
 * ou seja, o comprador controlava o valor final. Agora a sessão guarda
 * apenas `produto_id => quantidade`; preço e nome vêm sempre de
 * ProductModel (catálogo do servidor), nunca do cliente.
 */
class CartModel
{
    private const SESSION_KEY = 'cart';

    /** @return array<int,int> [produto_id => quantidade] */
    private function raw(): array
    {
        $cart = Session::get(self::SESSION_KEY, []);
        return is_array($cart) ? $cart : [];
    }

    private function save(array $cart): void
    {
        Session::set(self::SESSION_KEY, $cart);
    }

    /** Adiciona (ou incrementa) um produto válido do catálogo. */
    public function addItem(int $produtoId, int $quantidade = 1): bool
    {
        if ($quantidade < 1 || !ProductModel::find($produtoId)) {
            return false; // produto inexistente ou quantidade inválida — ignorado
        }

        $cart = $this->raw();
        $cart[$produtoId] = ($cart[$produtoId] ?? 0) + $quantidade;
        $this->save($cart);
        return true;
    }

    /** Define a quantidade exata de um produto (usado pelos botões +/-). */
    public function setQuantidade(int $produtoId, int $quantidade): bool
    {
        if (!ProductModel::find($produtoId)) {
            return false;
        }

        $cart = $this->raw();

        if ($quantidade <= 0) {
            unset($cart[$produtoId]);
        } else {
            $cart[$produtoId] = $quantidade;
        }

        $this->save($cart);
        return true;
    }

    /** Remove um produto do carrinho. */
    public function removeItem(int $produtoId): array
    {
        $cart = $this->raw();
        unset($cart[$produtoId]);
        $this->save($cart);

        return [
            'newTotal' => number_format($this->getTotal(), 2, '.', ''),
            'newTotalFormatted' => number_format($this->getTotal(), 2, ',', '.'),
            'isEmpty' => empty($cart),
            'itemCount' => count($cart),
        ];
    }

    public function clear(): void
    {
        Session::remove(self::SESSION_KEY);
        Session::remove('discount');
    }

    /**
     * Substitui o carrinho inteiro a partir de uma lista [{produto_id, quantidade}],
     * ignorando qualquer produto que não exista no catálogo (nunca confia
     * em nome/preço vindos do cliente).
     */
    public function replaceFromArray(array $itens): bool
    {
        $cart = [];
        foreach ($itens as $item) {
            $produtoId = isset($item['produto_id']) ? (int) $item['produto_id'] : 0;
            $quantidade = isset($item['quantidade']) ? (int) $item['quantidade'] : 0;

            if ($produtoId <= 0 || $quantidade <= 0 || !ProductModel::find($produtoId)) {
                continue;
            }
            $cart[$produtoId] = ($cart[$produtoId] ?? 0) + $quantidade;
        }

        $this->save($cart);
        return true;
    }

    /**
     * Carrinho "resolvido": junta cada produto_id da sessão com os dados
     * reais do catálogo (nome, preço, imagem), já com subtotal calculado.
     *
     * @return array<int, array{produto_id:int, titulo:string, preco:float, imagem:string, quantidade:int, subtotal:float}>
     */
    public function getItensDetalhados(): array
    {
        $detalhados = [];
        foreach ($this->raw() as $produtoId => $quantidade) {
            $produto = ProductModel::find($produtoId);
            if (!$produto) {
                continue; // produto removido do catálogo — ignora silenciosamente
            }
            $detalhados[] = [
                'produto_id' => $produtoId,
                'titulo' => $produto['titulo'],
                'preco' => $produto['preco'],
                'imagem' => $produto['imagem'],
                'quantidade' => $quantidade,
                'subtotal' => round($produto['preco'] * $quantidade, 2),
            ];
        }
        return $detalhados;
    }

    /** Total do carrinho, sempre recalculado a partir do catálogo. */
    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->getItensDetalhados() as $item) {
            $total += $item['subtotal'];
        }
        return round($total, 2);
    }

    public function isEmpty(): bool
    {
        return empty($this->raw());
    }

    public function itemCount(): int
    {
        return count($this->raw());
    }
}
