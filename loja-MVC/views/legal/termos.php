<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso — Cestas Online</title>
    <link rel="stylesheet" href="/assets/css/style.css" />
    <style>
        .legal-container { max-width: 760px; margin: 40px auto; padding: 0 24px; line-height: 1.6; }
        .legal-container h1 { margin-bottom: 4px; }
        .legal-container .atualizado { color: #666; font-size: 0.9rem; margin-bottom: 32px; }
        .legal-container h2 { margin-top: 32px; font-size: 1.2rem; }
        .legal-container a { color: inherit; text-decoration: underline; }
    </style>
</head>
<body>
    <header class="header">
        <a href="/" class="logo">
            <img src="/assets/images/logo.png" alt="logo" />
            <span>Cestas Online</span>
        </a>
    </header>

    <main class="legal-container">
        <h1>Termos de Uso</h1>
        <p class="atualizado">Última atualização: <?= htmlspecialchars($atualizadoEm) ?></p>

        <h2>1. Sobre este site</h2>
        <p>
            Este site é uma loja virtual operada por [RAZÃO SOCIAL DA EMPRESA],
            inscrita no CNPJ nº [00.000.000/0000-00]. Ao usar este site e
            realizar uma compra, você concorda com estes Termos de Uso.
        </p>

        <h2>2. Compra sem cadastro (checkout guest)</h2>
        <p>
            Você pode comprar sem criar uma conta. Os dados fornecidos no
            checkout (nome, e-mail, CPF, telefone e endereço) são usados
            apenas para processar o seu pedido específico — veja detalhes na
            <a href="/privacidade">Política de Privacidade</a>.
        </p>

        <h2>3. Preços e disponibilidade</h2>
        <p>
            Os preços exibidos estão em reais (R$) e podem ser alterados sem
            aviso prévio, valendo o preço vigente no momento da confirmação
            do pedido. A disponibilidade dos produtos está sujeita a estoque.
        </p>

        <h2>4. Pagamento</h2>
        <p>
            Os pagamentos são processados por um parceiro especializado
            (PagBank/PagSeguro), que aceita cartão de crédito, débito, Pix e
            boleto. Não armazenamos dados de cartão em nossos servidores.
        </p>

        <h2>5. Entrega</h2>
        <p>
            O prazo e a forma de entrega são informados no checkout antes da
            confirmação do pedido.
        </p>

        <h2>6. Trocas, devoluções e arrependimento</h2>
        <p>
            Consulte nossa <a href="/trocas-e-devolucao">Política de Trocas e
            Devolução</a> para os prazos e condições, incluindo o direito de
            arrependimento previsto no Código de Defesa do Consumidor.
        </p>

        <h2>7. Contato</h2>
        <p>
            Dúvidas sobre estes Termos: <a href="mailto:infogyba@ymail.com">infogyba@ymail.com</a>.
        </p>
    </main>
</body>
</html>
