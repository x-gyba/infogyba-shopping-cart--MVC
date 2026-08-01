<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar pedido — Cestas Online</title>
    <link rel="stylesheet" href="/assets/css/style.css" />
    <style>
        .pedido-container { max-width: 640px; margin: 40px auto; padding: 0 24px; }
        .status-badge { display:inline-block; padding: 6px 14px; border-radius: 999px; font-weight: bold; font-size: 0.85rem; }
        .status-pago { background:#e3f7e9; color:#1a7a3d; }
        .status-aguardando { background:#fff4e5; color:#8a5b00; }
        .status-cancelado { background:#fdeaea; color:#a12626; }
        table.itens { width:100%; border-collapse: collapse; margin-top: 16px; }
        table.itens th, table.itens td { text-align:left; padding: 8px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <header class="header">
        <a href="/" class="logo">
            <img src="/assets/images/logo.png" alt="logo" />
            <span>Cestas Online</span>
        </a>
    </header>

    <main class="pedido-container">
        <?php if (!$pedido): ?>
            <h1>Pedido não encontrado</h1>
            <p>Não encontramos nenhum pedido com esse código. Confira o link recebido por e-mail.</p>
        <?php else:
            $statusMap = [
                'PAGO' => ['label' => 'Pagamento confirmado', 'classe' => 'status-pago'],
                'AUTORIZADO' => ['label' => 'Pagamento autorizado', 'classe' => 'status-pago'],
                'EM_ANALISE' => ['label' => 'Pagamento em análise', 'classe' => 'status-aguardando'],
                'AGUARDANDO' => ['label' => 'Aguardando pagamento', 'classe' => 'status-aguardando'],
                'RECUSADO' => ['label' => 'Pagamento recusado', 'classe' => 'status-cancelado'],
                'CANCELADO' => ['label' => 'Pedido cancelado', 'classe' => 'status-cancelado'],
                'ESTORNADO' => ['label' => 'Pagamento estornado', 'classe' => 'status-cancelado'],
            ];
            $statusInfo = $statusMap[$pedido['payment_status']] ?? ['label' => $pedido['payment_status'], 'classe' => 'status-aguardando'];
        ?>
            <h1>Pedido <?= htmlspecialchars($referenceId) ?></h1>
            <p><span class="status-badge <?= $statusInfo['classe'] ?>"><?= htmlspecialchars($statusInfo['label']) ?></span></p>

            <p>
                <strong>Feito em:</strong> <?= htmlspecialchars(date('d/m/Y \à\s H:i', strtotime($pedido['data_compra']))) ?><br>
                <strong>Total:</strong> R$ <?= number_format((float) $pedido['total_com_desconto'], 2, ',', '.') ?><br>
                <strong>Entrega:</strong>
                <?= htmlspecialchars($pedido['endereco_rua']) ?>, <?= htmlspecialchars($pedido['endereco_numero']) ?>
                — <?= htmlspecialchars($pedido['endereco_bairro']) ?>, <?= htmlspecialchars($pedido['endereco_cidade']) ?>/<?= htmlspecialchars($pedido['endereco_estado']) ?>
            </p>

            <table class="itens">
                <thead><tr><th>Item</th><th>Qtd.</th><th>Preço</th></tr></thead>
                <tbody>
                <?php foreach ($pedido['itens'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item']) ?></td>
                        <td><?= (int) $item['quantidade'] ?></td>
                        <td>R$ <?= number_format((float) $item['preco_unitario'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:24px;">
                <a href="/">← Voltar para a loja</a>
            </p>
        <?php endif; ?>
    </main>
</body>
</html>
