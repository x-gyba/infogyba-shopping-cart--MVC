<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrderModel;

/**
 * Acompanhamento de pedido para comprador guest (sem login).
 *
 * O reference_id funciona como uma "senha de consulta": só quem tem o
 * link recebido por e-mail (ou o retorno do PagBank) consegue ver o
 * status. Ele tem 64 bits de aleatoriedade (ver CheckoutController),
 * então não é adivinhável por força bruta.
 */
class OrderController extends Controller
{
    private OrderModel $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    /** GET /pedido/{referenceId} */
    public function acompanhar(string $referenceId): void
    {
        $pedido = $this->orderModel->buscarPorReferenceId($referenceId);

        $this->render('pedido/index', [
            'pedido' => $pedido,
            'referenceId' => $referenceId,
        ]);
    }

    /**
     * GET /rastrear-pedido — formulário simples onde o comprador guest
     * digita o código do pedido (reference_id) recebido por e-mail e é
     * redirecionado para /pedido/{referenceId}. Existe porque não há
     * login/"meus pedidos" nesse checkout guest.
     */
    public function rastrear(): void
    {
        $this->render('pedido/rastrear', []);
    }
}
