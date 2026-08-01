<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Catálogo de produtos — agora persistido na tabela `produtos`.
 *
 * CORREÇÃO DE SEGURANÇA (herdada): preço e nome de cada item sempre vêm
 * daqui, nunca do texto que o navegador envia — isso já valia com o
 * catálogo em array PHP.
 *
 * EVOLUÇÃO (controle de estoque real): o catálogo passou de um array PHP
 * estático para a tabela `produtos`. Um array na memória do PHP não
 * serve para controlar estoque de verdade, porque cada requisição
 * recarrega o array do zero — qualquer decremento "esquecido" ao fim da
 * requisição. Com o catálogo no banco, dá pra decrementar e checar
 * estoque de forma atômica (ver OrderModel::criarPedidoGuest).
 *
 * A interface pública (all/find) continua estática de propósito, para
 * não quebrar quem já chama ProductModel::all() / ProductModel::find($id)
 * em CartModel, HomeController e CheckoutController.
 */
class ProductModel
{
    /** Cache simples por requisição, para não repetir a mesma query. */
    private static ?array $cacheAll = null;

    /** @return array<int, array{id:int, titulo:string, preco:float, imagem:string, estoque:int}> */
    public static function all(): array
    {
        if (self::$cacheAll !== null) {
            return self::$cacheAll;
        }

        $db = Database::getConnection();
        if (!$db) {
            return [];
        }

        $stmt = $db->query('SELECT id, titulo, preco, imagem, estoque FROM produtos WHERE ativo = 1 ORDER BY id ASC');
        $produtos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $produtos[] = [
                'id' => (int) $row['id'],
                'titulo' => $row['titulo'],
                'preco' => (float) $row['preco'],
                'imagem' => $row['imagem'],
                'estoque' => (int) $row['estoque'],
            ];
        }

        self::$cacheAll = $produtos;
        return $produtos;
    }

    /** @return array{titulo:string, preco:float, imagem:string, estoque:int}|null */
    public static function find(int $id): ?array
    {
        foreach (self::all() as $produto) {
            if ($produto['id'] === $id) {
                return $produto;
            }
        }
        return null;
    }

    /** Verifica (sem reservar) se há estoque suficiente no momento da checagem. */
    public static function temEstoque(int $id, int $quantidade): bool
    {
        $produto = self::find($id);
        return $produto !== null && $produto['estoque'] >= $quantidade;
    }
}
