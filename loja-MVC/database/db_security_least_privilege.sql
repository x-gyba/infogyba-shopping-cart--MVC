-- =====================================================================
-- db_security_least_privilege.sql
--
-- MySQL/MariaDB NÃO tem Row Level Security (RLS) nativo como o
-- PostgreSQL — não existe "CREATE POLICY" aqui. O equivalente prático
-- que dá pra fazer no MySQL, e que este script implementa, é:
--
--   1. Um usuário de aplicação (`loja_app`) com o MÍNIMO de privilégio
--      necessário — só SELECT/INSERT/UPDATE nas 3 tabelas que o checkout
--      guest realmente usa (produtos, compras, itens_compra).
--   2. Nenhum acesso às tabelas legadas de cliente cadastrado/login
--      (clientes, login, pessoa_fisica, pessoa_juridica) — o checkout
--      guest não usa essas tabelas, então a conta da aplicação nem
--      enxerga essas linhas. Isso é o mais próximo de "isolamento de
--      linha" que dá pra garantir sem RLS nativo: isolamento por TABELA.
--   3. Sem DROP/ALTER/CREATE/FILE/GRANT — mesmo numa falha de segurança
--      na aplicação (ex.: uma injeção que escapasse dos prepared
--      statements), essa conta não consegue apagar tabelas, mudar o
--      schema ou ler arquivos do servidor.
--
-- O verdadeiro controle de "quem vê a linha de quem" já é feito na
-- APLICAÇÃO (App\Models\OrderModel::buscarPorReferenceId só retorna UM
-- pedido, buscado por um reference_id de 64 bits de aleatoriedade — não
-- existe nenhum endpoint que liste todos os pedidos).
--
-- Se blindagem de RLS de verdade a nível de banco for um requisito
-- obrigatório, a alternativa é migrar para PostgreSQL, que suporta
-- `CREATE POLICY` nativamente — nesse caso, peça que eu escreva as
-- policies equivalentes.
-- =====================================================================

-- TROQUE a senha abaixo antes de rodar (nunca deixe a senha de exemplo
-- em produção, e nunca commite a senha real em lugar nenhum do código —
-- ela só deve existir no .env do servidor de produção).
CREATE USER IF NOT EXISTS 'loja_app'@'localhost' IDENTIFIED BY 'TROQUE_ESTA_SENHA_ANTES_DE_USAR';

-- Só as 3 tabelas que o checkout guest realmente lê/escreve:
GRANT SELECT, INSERT, UPDATE ON `loja`.`produtos`      TO 'loja_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON `loja`.`compras`        TO 'loja_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON `loja`.`itens_compra`   TO 'loja_app'@'localhost';

-- Nenhum GRANT para: clientes, login, pessoa_fisica, pessoa_juridica
-- (tabelas legadas do fluxo com login, não usadas pelo checkout guest —
-- ficam invisíveis para esta conta).

-- Nenhum DELETE (o app nunca apaga pedido; cancelamento é um UPDATE de
-- status). Se um recurso futuro precisar de DELETE em alguma tabela
-- específica, conceda só ali, nunca de forma genérica.

FLUSH PRIVILEGES;

-- Depois de rodar isto, atualize o .env de PRODUÇÃO para:
--   DB_USER=loja_app
--   DB_PASS=<a senha que você definiu acima>
-- Em desenvolvimento local, usar root (como já está no .env.example) é
-- aceitável, já que não há dado real em jogo.
