-- =====================================================================
-- product_catalog_migration.sql
--
-- Cria a tabela `produtos` (catálogo + estoque) e migra os 4 itens que
-- antes viviam hardcoded em um array PHP (App\Models\ProductModel).
--
-- Por quê: controle de estoque real exige persistência. Um array PHP
-- estático "esquece" qualquer decremento assim que a requisição termina
-- (cada request recarrega o array do zero) — então não dá pra bloquear
-- venda de item sem estoque sem isso estar no banco.
--
-- Rode depois de loja.sql e checkout_guest_migration.sql.
-- =====================================================================

-- Garante que o cliente mysql envie/receba em utf8mb4, independente do
-- charset padrão configurado nele — sem isso, textos acentuados podem
-- ser gravados com mojibake (ex.: "Básica" virando "BÃ¡sica").
SET NAMES utf8mb4;

USE `loja`;

CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(150) NOT NULL,
  `preco` DECIMAL(10,2) NOT NULL,
  `imagem` VARCHAR(255) NOT NULL,
  `estoque` INT(11) NOT NULL DEFAULT 0,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed: os mesmos 4 produtos que antes estavam fixos no PHP.
-- IDs explícitos para bater com o que já está referenciado no front-end.
INSERT INTO `produtos` (`id`, `titulo`, `preco`, `imagem`, `estoque`, `ativo`) VALUES
  (1, 'Cesta Básica Ultra',  220.00, 'assets/images/cesta1.png', 50, 1),
  (2, 'Cesta Básica Top',    220.00, 'assets/images/cesta1.png', 50, 1),
  (3, 'Cesta Básica Slim',   120.00, 'assets/images/cesta1.png', 50, 1),
  (4, 'Cesta Social Básica',  85.00, 'assets/images/cesta1.png', 50, 1)
ON DUPLICATE KEY UPDATE
  titulo = VALUES(titulo),
  preco = VALUES(preco),
  imagem = VALUES(imagem);

-- Índice para listar rapidamente só os produtos ativos (vitrine)
CREATE INDEX `idx_produtos_ativo` ON `produtos` (`ativo`);

-- Rastreia qual produto do catálogo cada item do pedido corresponde,
-- necessário para decrementar estoque de forma confiável.
ALTER TABLE `itens_compra`
  ADD COLUMN `produto_id` INT(11) NULL AFTER `compra_id`,
  ADD CONSTRAINT `itens_compra_produto_fk` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

-- Valor do frete cobrado neste pedido específico (taxa fixa, ver .env FRETE_VALOR).
ALTER TABLE `compras`
  ADD COLUMN `frete` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `desconto`;
