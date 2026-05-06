-- ============================================================
-- MIGRAÇÕES SIGEF BAMRJ — Executar na ordem apresentada
-- Dados existentes NÃO são apagados (ALTER TABLE / UPDATE)
-- ============================================================

-- 1. Adiciona coluna substituto_ativo na tabela users (persistência do modo substituto)
ALTER TABLE users ADD COLUMN IF NOT EXISTS substituto_ativo BOOLEAN DEFAULT FALSE;

-- 2. Adiciona coluna data_envio_protocolo na tabela de_lotes (para a capa de impressão)
ALTER TABLE de_lotes ADD COLUMN IF NOT EXISTS data_envio_protocolo TIMESTAMP;

-- 3. Atualiza registros existentes que não têm data_envio_protocolo preenchido
UPDATE de_lotes SET data_envio_protocolo = criado_em WHERE data_envio_protocolo IS NULL;

-- 4. Garante que a coluna origem_setor existe (proteção contra ambientes antigos)
ALTER TABLE users ADD COLUMN IF NOT EXISTS origem_setor VARCHAR(128) DEFAULT 'BAMRJ';

-- 5. Garante que a coluna must_change_password existe
ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password BOOLEAN DEFAULT TRUE;

-- 6. Garante que a coluna empresa_nome existe em de_itens
ALTER TABLE de_itens ADD COLUMN IF NOT EXISTS empresa_nome VARCHAR(255) DEFAULT 'Não Informado';

-- ============================================================
-- FIM DAS MIGRAÇÕES SIGEF
-- Para reverter: ALTER TABLE users DROP COLUMN substituto_ativo;
-- ============================================================