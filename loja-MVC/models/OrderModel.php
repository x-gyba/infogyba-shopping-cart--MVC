<?php

namespace App\Models;

use App\Core\Model;
use PDOException;

/**
 * Lançada quando algum item do pedido não tem estoque suficiente no
 * momento da gravação (checagem atômica, feita dentro da transação).
 */
class InsufficientStockException extends \RuntimeException
{
}

/**
 * Pedido de um checkout guest: grava em `compras` (cliente_id = NULL) e
 * `itens_compra`, e é atualizado depois por webhook.php com o status do
 * pagamento vindo do PagBank.
 */
class OrderModel extends Model
{
    /**
     * Cria o pedido + itens em uma transação. Retorna o id do pedido.
     *
     * @throws \RuntimeException se a gravação falhar.
     */
    public function criarPedidoGuest(array $guest, array $endereco, array $itens, float $total, float $desconto, string $referenceId, float $frete = 0.0): int
    {
        if (!$this->db) {
            throw new \RuntimeException('Não foi possível conectar ao banco de dados.');
        }

        $finalTotal = round($total - $desconto + $frete, 2);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'INSERT INTO compras (
                    cliente_id, guest_nome, guest_email, guest_telefone, guest_cpf,
                    endereco_rua, endereco_numero, endereco_complemento, endereco_bairro,
                    endereco_cidade, endereco_estado, endereco_cep,
                    reference_id, payment_status, total, desconto, frete, total_com_desconto,
                    data_compra, status, aceite_privacidade_em
                ) VALUES (
                    NULL, :nome, :email, :telefone, :cpf,
                    :rua, :numero, :complemento, :bairro,
                    :cidade, :estado, :cep,
                    :reference_id, :payment_status, :total, :desconto, :frete, :total_com_desconto,
                    NOW(), :status, NOW()
                )'
            );
            $stmt->execute([
                ':nome' => $guest['nome'],
                ':email' => $guest['email'],
                ':telefone' => $guest['telefone'],
                ':cpf' => $guest['cpf'],
                ':rua' => $endereco['rua'],
                ':numero' => $endereco['numero'],
                ':complemento' => $endereco['complemento'],
                ':bairro' => $endereco['bairro'],
                ':cidade' => $endereco['cidade'],
                ':estado' => $endereco['estado'],
                ':cep' => $endereco['cep'],
                ':reference_id' => $referenceId,
                ':payment_status' => 'AGUARDANDO',
                ':total' => $total,
                ':desconto' => $desconto,
                ':frete' => $frete,
                ':total_com_desconto' => $finalTotal,
                ':status' => 'pendente',
            ]);

            $pedidoId = (int) $this->db->lastInsertId();

            $stmtItem = $this->db->prepare(
                'INSERT INTO itens_compra (compra_id, produto_id, item, quantidade, preco_unitario)
                 VALUES (:compra_id, :produto_id, :item, :quantidade, :preco_unitario)'
            );
            // Decremento atômico: só desconta se ainda houver estoque suficiente
            // no exato momento do UPDATE (evita overselling em corrida entre
            // dois compradores simultâneos pela última unidade).
            $stmtEstoque = $this->db->prepare(
                'UPDATE produtos SET estoque = estoque - :quantidade
                 WHERE id = :produto_id AND estoque >= :quantidade'
            );

            foreach ($itens as $item) {
                if (!empty($item['produto_id'])) {
                    $stmtEstoque->execute([
                        ':produto_id' => $item['produto_id'],
                        ':quantidade' => $item['quantidade'],
                    ]);
                    if ($stmtEstoque->rowCount() !== 1) {
                        throw new InsufficientStockException(
                            'Estoque insuficiente para "' . $item['nome'] . '".'
                        );
                    }
                }

                $stmtItem->execute([
                    ':compra_id' => $pedidoId,
                    ':produto_id' => $item['produto_id'] ?? null,
                    ':item' => $item['nome'],
                    ':quantidade' => $item['quantidade'],
                    ':preco_unitario' => $item['preco_unitario'],
                ]);
            }

            $this->db->commit();
            return $pedidoId;
        } catch (InsufficientStockException $e) {
            $this->db->rollBack();
            throw $e;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Erro ao gravar pedido guest: ' . $e->getMessage());
            throw new \RuntimeException('Erro ao gravar o pedido.');
        }
    }

    /**
     * Pedidos com checkout PagBank criado, mas ainda "AGUARDANDO" há mais
     * de X minutos — candidatos à reconciliação (ver scripts/reconcile-pagbank.php).
     */
    public function buscarAguardandoAntigos(int $minutos): array
    {
        if (!$this->db) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT id, reference_id, pagbank_checkout_id
             FROM compras
             WHERE payment_status = 'AGUARDANDO'
               AND pagbank_checkout_id IS NOT NULL
               AND data_compra < DATE_SUB(NOW(), INTERVAL :minutos MINUTE)"
        );
        $stmt->execute([':minutos' => $minutos]);
        return $stmt->fetchAll();
    }

    /**
     * Busca um pedido (+itens) pelo reference_id — usado na página pública
     * de acompanhamento do pedido (/pedido/{reference_id}), já que o
     * comprador guest não tem login para consultar um "meus pedidos".
     */
    public function buscarPorReferenceId(string $referenceId): ?array
    {
        if (!$this->db) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM compras WHERE reference_id = :reference_id LIMIT 1');
        $stmt->execute([':reference_id' => $referenceId]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            return null;
        }

        $stmtItens = $this->db->prepare('SELECT item, quantidade, preco_unitario FROM itens_compra WHERE compra_id = :compra_id');
        $stmtItens->execute([':compra_id' => $pedido['id']]);
        $pedido['itens'] = $stmtItens->fetchAll();

        return $pedido;
    }

    /** Salva o id do checkout PagBank vinculado ao pedido. */
    public function salvarCheckoutId(int $pedidoId, ?string $checkoutId): void
    {
        $stmt = $this->db->prepare('UPDATE compras SET pagbank_checkout_id = :checkout_id WHERE id = :id');
        $stmt->execute([':checkout_id' => $checkoutId, ':id' => $pedidoId]);
    }

    /**
     * Marca o pedido como cancelado (ex.: falha ao criar o checkout no
     * PagBank) e RESTAURA o estoque decrementado — sem isso, um pedido que
     * falha depois de já ter descontado o estoque "perderia" essas
     * unidades para sempre, mesmo sem nenhum pagamento ter acontecido.
     */
    public function cancelar(int $pedidoId): void
    {
        try {
            $this->db->beginTransaction();

            $stmtItens = $this->db->prepare('SELECT produto_id, quantidade FROM itens_compra WHERE compra_id = :compra_id AND produto_id IS NOT NULL');
            $stmtItens->execute([':compra_id' => $pedidoId]);

            $stmtRestaura = $this->db->prepare('UPDATE produtos SET estoque = estoque + :quantidade WHERE id = :produto_id');
            foreach ($stmtItens->fetchAll() as $item) {
                $stmtRestaura->execute([
                    ':quantidade' => $item['quantidade'],
                    ':produto_id' => $item['produto_id'],
                ]);
            }

            $stmt = $this->db->prepare("UPDATE compras SET status = 'cancelado', payment_status = 'CANCELADO' WHERE id = :id");
            $stmt->execute([':id' => $pedidoId]);

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Erro ao cancelar pedido / restaurar estoque: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza o status de pagamento de um pedido a partir de uma notificação
     * (webhook) do PagBank, localizando-o pelo reference_id.
     *
     * @return bool true se algum pedido foi atualizado.
     */
    public function atualizarStatusPagamento(string $referenceId, string $paymentStatus, string $statusPedido, ?string $chargeId, ?string $paymentMethod): bool
    {
        if (!$this->db) {
            throw new \RuntimeException('Não foi possível conectar ao banco de dados.');
        }

        $stmt = $this->db->prepare(
            'UPDATE compras
             SET payment_status = :payment_status,
                 status = :status,
                 pagbank_charge_id = COALESCE(:charge_id, pagbank_charge_id),
                 payment_method = COALESCE(:payment_method, payment_method)
             WHERE reference_id = :reference_id'
        );
        $stmt->execute([
            ':payment_status' => $paymentStatus,
            ':status' => $statusPedido,
            ':charge_id' => $chargeId,
            ':payment_method' => $paymentMethod,
            ':reference_id' => $referenceId,
        ]);

        return $stmt->rowCount() > 0;
    }
}
