<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>" />
    <title>myShop</title>
    <link
      rel="shortcut icon"
      href="/assets/images/favicon.ico"
      type="image/x-icon"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="stylesheet" href="/assets/css/style.css" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
  </head>
  <body>
    <header class="header">
      <a href="/" class="logo">
       <img src="/assets/images/logo.png" alt="logo">
        <ul class="navbar">
        <li class="nav-item"><a href="#home" class="nav-link">Início</a></li>
        <li class="nav-item"><a href="#about" class="nav-link">Sobre</a></li>
        <li class="nav-item"><a href="#menu" class="nav-link">Menu</a></li>
        <li class="nav-item"><a href="#home" class="nav-link" id="nav-abrir-video" data-open-video>Vídeo</a></li>
        <li class="nav-item"><a href="/rastrear-pedido" class="nav-link">Rastrear Pedido</a></li>
        <li class="nav-item"><a href="#contato" class="nav-link">Contato</a></li>
        <div class="icon">
         <i class="bx bx-shopping-bag" id="cart-btn"
            ><span class="carrinho-item-qtd" value="1">0</span></i
          >
        </div>
      </ul>
      <div class="nav-toggle">
        <i class="bx bx-menu"></i>
      </div>
      <!-- Carrinho -->
      <div class="carrinho">
        <div class="header-carrinho">
          <i class="bx bx-x-circle carrinho-close"></i>
          <h2>Meu Carrinho:</h2>
        </div>
        <div class="carrinho-items">
        </div>
        <div class="carrinho-total">
          <div class="lista">
            <strong>Total:</strong>
            <span class="carrinho-preco-total">R$ 0,00</span>
          </div>
          <button class="btn-checkout">
            <i class="bx bx-cart-alt"></i>Pagar
          </button>
        </div>
      </div>
    </header>

    <!-- Hero + vídeo de divulgação -->
    <section class="hero" id="home">
      <div class="hero-grid">
        <div class="hero-content">
          <span class="hero-tag"><i class="bx bx-leaf"></i> Fresquinho, direto pra sua porta</span>
          <h1>Cestas prontas, do jeito que sua casa (ou empresa) precisa</h1>
          <p>Monte seu pedido em minutos, sem precisar criar conta, e pague com Pix, cartão ou boleto.</p>
          <div class="hero-actions">
            <a href="#menu" class="btn-primary"><i class="bx bx-basket"></i> Ver cestas</a>
            <button type="button" class="btn-play-video" id="btn-abrir-video" data-open-video>
              <i class="bx bx-play-circle"></i> Assistir vídeo
            </button>
          </div>
          <ul class="hero-benefits">
            <li><i class="bx bx-shield-quarter"></i> Pagamento seguro</li>
            <li><i class="bx bx-package"></i> Sem cadastro</li>
            <li><i class="bx bx-map-pin"></i> Rastreio do pedido</li>
          </ul>
        </div>
        <div class="hero-visual" aria-hidden="true">
          <div class="hero-blob"></div>
          <div class="hero-card hero-card--price">
            <i class="bx bx-basket"></i>
            <div>
              <strong>Cestas selecionadas</strong>
              <span>Feitas na hora</span>
            </div>
          </div>
          <div class="hero-card hero-card--pix">
            <i class="bx bx-qr"></i>
            <div>
              <strong>Pix, cartão ou boleto</strong>
              <span>Você escolhe</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Modal do vídeo de divulgação -->
    <div class="video-modal" id="video-modal" aria-hidden="true">
      <div class="video-modal-backdrop" data-close-video></div>
      <div class="video-modal-content">
        <button type="button" class="video-modal-close" data-close-video aria-label="Fechar vídeo">
          <i class="bx bx-x"></i>
        </button>
        <video id="video-divulgacao" controls playsinline preload="none">
          <source src="/assets/video/divulgacao.mp4" type="video/mp4" />
          Seu navegador não suporta vídeo em HTML5.
        </video>
      </div>
    </div>

    <section class="menu" id="menu">
      <div class="section-header">
        <span class="section-eyebrow">Nosso menu</span>
        <h2 class="section-title">Escolha sua cesta</h2>
        <p class="section-subtitle">Todas montadas com produtos selecionados e prontas para envio.</p>
      </div>
      <div class="menu-grid">
        <?php foreach ($produtos as $produto): ?>
          <div class="menu-items">
            <div class="item" data-produto-id="<?= (int) $produto['id'] ?>">
              <div class="item-img-wrap">
                <img src="/<?= htmlspecialchars($produto['imagem']) ?>" alt="cesta" class="img-item" />
              </div>
              <span class="titulo-item"><?= htmlspecialchars($produto['titulo']) ?></span>
              <div class="item-footer">
                <span class="preco-item">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></span>
                <button class="add-cart" data-produto-id="<?= (int) $produto['id'] ?>">
                  <i class="bx bx-plus"></i> Adicionar
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="contato" id="contato">
      <div class="contato-grid">
        <div class="contato-info">
          <span class="section-eyebrow">Fale com a gente</span>
          <h2 class="contato-titulo">Fale conosco</h2>
          <p class="contato-subtitulo">Dúvidas, sugestões ou parcerias — é só mandar uma mensagem.</p>
          <ul class="contato-lista">
            <li><i class="bx bx-envelope"></i> infogyba@gmail.com</li>
            <li><i class="bx bx-time-five"></i> Resposta em até 1 dia útil</li>
          </ul>
        </div>

        <!--
          Formulário via Formspree. Antes de publicar:
          1. Crie uma conta em https://formspree.io usando infogyba@gmail.com.
          2. Crie um novo formulário (Formspree gera um ID, ex.: "xandnwqz").
          3. Troque SEU_FORM_ID abaixo por esse ID.
          As mensagens enviadas por este formulário chegarão em infogyba@gmail.com.
        -->
        <form action="https://formspree.io/f/SEU_FORM_ID" method="POST" class="form-contato">
          <input type="text" name="nome" placeholder="Seu nome" required>
          <input type="email" name="_replyto" placeholder="Seu e-mail" required>
          <textarea name="mensagem" placeholder="Sua mensagem" rows="5" required></textarea>
          <input type="hidden" name="_subject" value="Nova mensagem — Cestas Online">
          <button type="submit">Enviar mensagem</button>
        </form>
      </div>
    </section>

    <footer class="rodape">
      <div class="rodape-grid">
        <div class="rodape-marca">
          <span class="logo-footer">Cestas Online</span>
          <p>Usamos apenas cookies essenciais ao funcionamento do carrinho.</p>
        </div>
        <ul class="rodape-links">
          <li><a href="/privacidade">Política de Privacidade</a></li>
          <li><a href="/termos">Termos de Uso</a></li>
          <li><a href="/trocas-e-devolucao">Trocas e Devolução</a></li>
        </ul>
      </div>
    </footer>

    <script src="/assets/js/script.js"></script>
    <script src="/assets/js/cart.js"></script>
  </body>
</html>