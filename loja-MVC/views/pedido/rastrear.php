<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Rastrear pedido — Cestas Online</title>
    <link rel="stylesheet" href="/assets/css/style.css" />
    <style>
        .rastrear-container { max-width: 480px; margin: 60px auto; padding: 0 24px; text-align:center; }
        .rastrear-container input {
            width: 100%; padding: 14px; font-size: 1.6rem; margin: 16px 0 8px;
            border: 1px solid #ccc; border-radius: 8px;
        }
        .rastrear-container button {
            width: 100%; padding: 14px; font-size: 1.6rem; border: none; border-radius: 8px;
            background: var(--red, #d0342c); color: #fff; cursor: pointer;
        }
        .rastrear-container p.ajuda { color: #777; font-size: 1.3rem; margin-top: 8px; }
    </style>
</head>
<body>
    <header class="header">
        <a href="/" class="logo">
            <img src="/assets/images/logo.png" alt="logo" />
            <span>Cestas Online</span>
        </a>
    </header>

    <main class="rastrear-container">
        <h1>Rastrear pedido</h1>
        <p>Digite o código do pedido que você recebeu por e-mail (começa com "PED-").</p>

        <form id="form-rastrear">
            <input type="text" id="reference_id" placeholder="Ex.: PED-20260727100821-60e8cfe601c89cfe" required>
            <button type="submit">Consultar pedido</button>
        </form>
        <p class="ajuda">Não achou o código? Confira o e-mail de confirmação da compra.</p>
    </main>

    <script>
        document.getElementById('form-rastrear').addEventListener('submit', function (e) {
            e.preventDefault();
            var codigo = document.getElementById('reference_id').value.trim();
            if (codigo) {
                window.location.href = '/pedido/' + encodeURIComponent(codigo);
            }
        });
    </script>
</body>
</html>
