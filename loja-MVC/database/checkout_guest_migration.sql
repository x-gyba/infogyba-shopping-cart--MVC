-- =====================================================================
-- checkout_guest_migration.sql
--
-- Migração para suportar CHECKOUT GUEST (sem login/cadastro) e o
-- rastreamento de pagamentos via PagBank (Checkout API).
--
-- Rode isso DEPOIS de importar o loja.sql original.
-- =====================================================================

SET NAMES utf8mb4;

USE `loja`;

-- `cliente_id` deixa de ser obrigatório: um pedido guest não tem cliente
-- cadastrado nas tabelas `clientes`/`login`.
ALTER TABLE `compras`
  MODIFY `cliente_id` INT(11) NULL;

-- Dados do comprador convidado (preenchidos no formulário de checkout.php)
ALTER TABLE `compras`
  ADD COLUMN `guest_nome`     VARCHAR(150)  NULL AFTER `cliente_id`,
  ADD COLUMN `guest_email`    VARCHAR(150)  NULL AFTER `guest_nome`,
  ADD COLUMN `guest_telefone` VARCHAR(20)   NULL AFTER `guest_email`,
  ADD COLUMN `guest_cpf`      VARCHAR(14)   NULL AFTER `guest_telefone`;

-- Endereço de entrega do pedido (checkout guest não usa pessoa_fisica/pessoa_juridica)
ALTER TABLE `compras`
  ADD COLUMN `endereco_rua`         VARCHAR(160) NULL AFTER `guest_cpf`,
  ADD COLUMN `endereco_numero`      VARCHAR(20)  NULL AFTER `endereco_rua`,
  ADD COLUMN `endereco_complemento` VARCHAR(40)  NULL AFTER `endereco_numero`,
  ADD COLUMN `endereco_bairro`      VARCHAR(60)  NULL AFTER `endereco_complemento`,
  ADD COLUMN `endereco_cidade`      VARCHAR(90)  NULL AFTER `endereco_bairro`,
  ADD COLUMN `endereco_estado`      VARCHAR(2)   NULL AFTER `endereco_cidade`,
  ADD COLUMN `endereco_cep`         VARCHAR(9)   NULL AFTER `endereco_estado`;

-- Rastreamento do pagamento via PagBank (Checkout API)
ALTER TABLE `compras`
  ADD COLUMN `reference_id`        VARCHAR(64)  NULL UNIQUE AFTER `endereco_cep`,
  ADD COLUMN `pagbank_checkout_id` VARCHAR(60)  NULL AFTER `reference_id`,
  ADD COLUMN `pagbank_charge_id`   VARCHAR(60)  NULL AFTER `pagbank_checkout_id`,
  ADD COLUMN `payment_status`      VARCHAR(30)  NOT NULL DEFAULT 'AGUARDANDO' AFTER `pagbank_charge_id`,
  ADD COLUMN `payment_method`      VARCHAR(30)  NULL AFTER `payment_status`,
  ADD COLUMN `updated_at`          TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `payment_method`;

-- Evidência do consentimento LGPD: quando o comprador aceitou a Política de
-- Privacidade para este pedido (checkbox obrigatório no checkout).
ALTER TABLE `compras`
  ADD COLUMN `aceite_privacidade_em` DATETIME NULL AFTER `updated_at`;

-- O enum `status` original (pendente/confirmado) passa a refletir apenas o
-- status do PEDIDO; quem controla o status do PAGAMENTO é `payment_status`
-- (valores esperados: AGUARDANDO, PAGO, RECUSADO, CANCELADO, EM_ANALISE).
ALTER TABLE `compras`
  MODIFY `status` ENUM('pendente','confirmado','cancelado') NOT NULL DEFAULT 'pendente';

-- Índice para localizar rapidamente um pedido a partir da notificação do webhook
CREATE INDEX `idx_compras_reference_id` ON `compras` (`reference_id`);
CREATE INDEX `idx_compras_pagbank_checkout_id` ON `compras` (`pagbank_checkout_id`);
