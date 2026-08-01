<?php

/**
 * tests/run.php
 *
 * Testes automatizados leves, sem depender de PHPUnit/Composer (o
 * ambiente deste projeto não tem acesso à internet liberado para baixar
 * pacotes em todo lugar onde ele possa rodar). Cobre a lógica que, se
 * quebrar num refactor futuro, reabriria alguma das falhas de segurança
 * já corrigidas neste projeto.
 *
 * Uso:
 *   php tests/run.php
 *
 * Sai com código 0 se tudo passar, 1 se algo falhar (dá para plugar em
 * CI: o exit code é o que importa).
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\Validation;
use App\Models\CartModel;

Session::start(); // precisa vir antes de qualquer echo

$falhas = 0;
$total = 0;

function testar(string $descricao, callable $condicao): void
{
    global $falhas, $total;
    $total++;
    try {
        $ok = $condicao();
    } catch (\Throwable $e) {
        $ok = false;
        $descricao .= ' (exceção: ' . $e->getMessage() . ')';
    }
    echo ($ok ? "  OK  " : "FALHOU") . " - {$descricao}\n";
    if (!$ok) {
        $falhas++;
    }
}

echo "== Validation::cpfValido() ==\n";
testar('CPF válido (111.444.777-35) é aceito', fn () => Validation::cpfValido('11144477735'));
testar('CPF com todos os dígitos iguais é rejeitado', fn () => !Validation::cpfValido('11111111111'));
testar('CPF com dígito verificador errado é rejeitado', fn () => !Validation::cpfValido('11144477736'));
testar('CPF com menos de 11 dígitos é rejeitado', fn () => !Validation::cpfValido('123456789'));
testar('CPF vazio é rejeitado', fn () => !Validation::cpfValido(''));

echo "\n== Validation::cepValido() ==\n";
testar('CEP com 8 dígitos é aceito', fn () => Validation::cepValido('25960000'));
testar('CEP com máscara é aceito (dígitos são extraídos)', fn () => Validation::cepValido('25960-000'));
testar('CEP incompleto é rejeitado', fn () => !Validation::cepValido('2596'));

echo "\n== App\\Core\\Csrf ==\n";
$token = Csrf::token();
testar('Token CSRF gerado não é vazio', fn () => is_string($token) && strlen($token) >= 32);
testar('Token correto é validado', fn () => Csrf::validate($token));
testar('Token errado é rejeitado', fn () => !Csrf::validate('token-forjado-qualquer'));
testar('Token vazio/nulo é rejeitado', fn () => !Csrf::validate(null) && !Csrf::validate(''));

echo "\n== App\\Core\\RateLimiter ==\n";
$chaveTeste = 'teste_unitario_' . bin2hex(random_bytes(4));
$permitidos = 0;
for ($i = 0; $i < 5; $i++) {
    if (RateLimiter::permitir($chaveTeste, 3, 60)) {
        $permitidos++;
    }
}
testar('Limite de 3 tentativas é respeitado (só 3 de 5 passam)', fn () => $permitidos === 3);

echo "\n== App\\Models\\CartModel (proteção contra preço/produto forjado) ==\n";
$cart = new CartModel();
$cart->clear();
$cart->replaceFromArray([
    ['produto_id' => 1, 'quantidade' => 2, 'preco' => 0.01], // preço forjado deve ser ignorado
    ['produto_id' => 999999, 'quantidade' => 5],              // produto inexistente deve ser ignorado
]);
$itens = $cart->getItensDetalhados();
testar('Produto inexistente (999999) não entra no carrinho', fn () => !array_filter($itens, fn ($i) => $i['produto_id'] === 999999));
testar('Preço do item vem do catálogo, não do payload forjado', function () use ($itens) {
    foreach ($itens as $item) {
        if ($item['produto_id'] === 1) {
            return $item['preco'] > 1; // catálogo tem preço real, bem acima do "0.01" forjado
        }
    }
    return false;
});
$cart->clear();

echo "\n----------------------------------------\n";
echo "{$total} teste(s), " . ($total - $falhas) . " passaram, {$falhas} falharam.\n";

exit($falhas > 0 ? 1 : 0);
