<?php
/**
 * views/checkout/index.php
 * Recebe do CheckoutController::index(): total, discount, finalTotal,
 * cartItens (já resolvidos via ProductModel — produto_id, titulo, preco,
 * imagem, quantidade, subtotal) e csrfToken.
 */
function formatMoney(float $value): string
{
    return number_format($value, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>Checkout</title>
    <link rel="stylesheet" href="/assets/css/checkout.css" />
</head>
<body>
<div class="main-container">
    <div class="checkout-container">

        <div class="cart-summary">
            <?php if ($total > 0) : ?>
                <h2 class="form-title">Resumo do Carrinho</h2>
                <div class="cart-summary-container">
                    <div class="total-title" id="total-title">
                        <div>Subtotal: R$ <?= formatMoney($total) ?></div>
                        <?php if ($frete > 0): ?>
                            <div>Frete: R$ <?= formatMoney($frete) ?></div>
                        <?php else: ?>
                            <div>Frete: Grátis</div>
                        <?php endif; ?>
                        <?php if ($discount > 0): ?>
                            <div>Desconto: -R$ <?= formatMoney($discount) ?></div>
                        <?php endif; ?>
                        <div><strong>Total:&nbsp;</strong> R$ <?= formatMoney($finalTotal) ?></div>
                    </div>

                    <div class="cart-items" id="cart-items">
                        <?php foreach ($cartItens as $item) :
                            $quantityDisplay = ($item['quantidade'] == 1) ? "x1" : "x" . (int) $item['quantidade'];
                        ?>
                            <div class="cart-item" data-produto-id="<?= (int) $item['produto_id'] ?>" style="display: flex; align-items: center; margin-bottom: 8px;">
                                <div style="flex: 0 0 auto; margin-right: 5px;">
                                    <img src="/<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" style="max-width: 70px; height: auto;" />
                                </div>
                                <div class="qtd-item" style="flex: 1;">
                                    <?= htmlspecialchars($item['titulo']) ?> — R$ <?= formatMoney($item['preco']) ?> <?= $quantityDisplay ?>
                                </div>
                                <button type="button" class="remove-btn" onclick="removeItem(<?= (int) $item['produto_id'] ?>)" aria-label="Remover item">
                                    <i class="bx bxs-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="discount-form-container">
                        <form class="discount-form" method="POST" action="/checkout" id="discount-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="text" name="discount_code" class="discount-input" placeholder="Código de desconto" required autocomplete="off" <?= $discount > 0 ? 'disabled' : '' ?>>
                            <button class="discount-btn" type="submit" <?= $discount > 0 ? 'disabled' : '' ?>>Aplicar</button>
                        </form>
                    </div>
                    <div class="discount-message-container">
                        <?php if ($discount > 0): ?>
                            <div class="discount-message" style="display:block;">
                                Desconto de R$ <?= formatMoney($discount) ?> aplicado!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div>
                    <h3>Seu carrinho está vazio. Você será redirecionado em instantes...</h3>
                    <script>
                        setTimeout(function () { window.location.href = '/'; }, 2000);
                    </script>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total > 0): ?>
        <div class="container-steps">
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-bar-inner"></div>
                </div>
                <div class="circle active" id="step1-icon">
                    <i class='bx bx-user'></i>
                    <div class="step-name">Seus dados</div>
                </div>
                <div class="circle" id="step2-icon">
                    <i class='bx bx-home'></i>
                    <div class="step-name">Entrega</div>
                </div>
                <div class="circle" id="step3-icon">
                    <i class='bx bx-credit-card'></i>
                    <div class="step-name">Pagamento</div>
                </div>
            </div>
        </div>

        <!-- Checkout GUEST: sem login. Coletamos os dados do comprador direto aqui. -->
        <form id="guest-checkout-form" autocomplete="on">

            <div id="step1">
                <h2 class="form-title">Seus dados</h2>
                <div class="input-group">
                    <i class='bx bx-user'></i>
                    <input type="text" id="nome" name="nome" placeholder="Nome completo" required>
                    <label for="nome">Nome completo</label>
                    <span class="error-message" id="nome-error"></span>
                </div>
                <div class="input-group">
                    <i class='bx bx-envelope'></i>
                    <input type="email" id="email" name="email" placeholder="Seu e-mail" required>
                    <label for="email">E-mail</label>
                    <span class="error-message" id="email-error"></span>
                </div>
                <div class="input-group">
                    <i class='bx bx-notepad'></i>
                    <input type="text" id="cpf" name="cpf" placeholder="CPF" required>
                    <label for="cpf">CPF</label>
                    <span class="error-message" id="cpf-error"></span>
                </div>
                <div class="input-group">
                    <i class='bx bx-mobile-vibration'></i>
                    <input type="text" id="telefone" name="telefone" placeholder="Celular com DDD" required>
                    <label for="telefone">Celular</label>
                    <span class="error-message" id="telefone-error"></span>
                </div>
                <button type="button" class="auth-btn" id="btn-avancar-entrega">Continuar para entrega</button>
            </div>

            <div id="step2" style="display:none;">
                <h2 class="form-title">Endereço de entrega</h2>
                <div class="input-group">
                    <i class='bx bx-map'></i>
                    <input type="text" id="cep" name="cep" placeholder="CEP" required>
                    <label for="cep">CEP</label>
                    <span class="error-message" id="cep-error"></span>
                </div>
                <div class="input-group">
                    <i class='bx bx-home'></i>
                    <input type="text" id="rua" name="rua" placeholder="Rua" required>
                    <label for="rua">Rua</label>
                    <span class="error-message" id="rua-error"></span>
                </div>
                <div class="input-group">
                    <i class='bx bx-notepad'></i>
                    <input type="text" id="numero" name="numero" placeholder="Número" required>
                    <label for="numero">Número</label>
                    <span class="error-message" id="numero-error"></span>
                </div>
                <div class="input-group">
                    <i class='bx bx-notepad'></i>
                    <input type="text" id="complemento" name="complemento" placeholder="Complemento (opcional)">
                    <label for="complemento">Complemento</label>
                </div>
                <div class="input-group">
                    <i class='bx bx-notepad'></i>
                    <input type="text" id="bairro" name="bairro" placeholder="Bairro" required>
                    <label for="bairro">Bairro</label>
                    <span class="error-message" id="bairro-error"></span>
                </div>
                <div class="input-group">
                    <i class='bx bx-notepad'></i>
                    <input type="text" id="cidade" name="cidade" placeholder="Cidade" required>
                    <label for="cidade">Cidade</label>
                    <span class="error-message" id="cidade-error"></span>
                </div>
                <div class="input-group">
                    <i class='bx bx-notepad'></i>
                    <input type="text" id="estado" name="estado" placeholder="UF" maxlength="2" required>
                    <label for="estado">UF</label>
                    <span class="error-message" id="estado-error"></span>
                </div>
                <button type="button" class="auth-btn" id="btn-avancar-pagamento">Continuar para pagamento</button>
            </div>

            <div id="step3" style="display:none;">
                <h2 class="form-title">Pagamento</h2>
                <p>Ao confirmar, você será redirecionado para o ambiente seguro do PagBank para concluir o pagamento (cartão, Pix ou boleto).</p>
                <div class="input-group" style="display:flex; align-items:flex-start; gap:8px;">
                    <input type="checkbox" id="aceite-privacidade" name="aceite_privacidade" required style="width:auto; margin-top:4px;">
                    <label for="aceite-privacidade" style="position:static; font-size:0.9rem;">
                        Li e concordo com a
                        <a href="/privacidade" target="_blank" rel="noopener">Política de Privacidade</a>,
                        e autorizo o uso dos meus dados para processar este pedido.
                    </label>
                </div>
                <div id="checkout-error-message" class="discount-message" style="display:none; color: #c0392b;"></div>
                <button type="submit" class="auth-btn" id="btn-finalizar-compra">
                    <i class="bx bx-lock-alt"></i> Finalizar compra
                </button>
            </div>

        </form>

        <p style="text-align:center; margin: 16px 0; font-size: 0.85rem; color:#777;">
            🔒 Seus dados são usados só para processar este pedido.
            <a href="/privacidade">Política de Privacidade</a> ·
            <a href="/termos">Termos de Uso</a> ·
            <a href="/trocas-e-devolucao">Trocas e Devolução</a>
        </p>
        <?php endif; ?>

    </div>
</div>

<script src="/assets/js/checkout.js"></script>
</body>
</html>
