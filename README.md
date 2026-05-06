# SIGEF BAMRJ — Sistema Integrado de Gestão e Execução Financeira

> Base de Abastecimento da Marinha no Rio de Janeiro

## 🏗️ Arquitetura

- **Backend:** PHP 8.2+ (Puro, sem framework)
- **Banco de Dados:** PostgreSQL (Railway / Local)
- **Frontend:** HTML5 + CSS3 + JavaScript Vanilla
- **Deploy:** Railway (Dockerfile / Procfile)

## 📋 Funcionalidades

### Módulos Principais
- **Dashboard** — Visão por perfil com radar de inbox
- **Protocolo** — Recebimento físico, devolução e impressão de capa A4
- **Execução Financeira** — Filas de Receber, NP, LF, Atendimento, OP, RAP, OB, Aval Canc
- **Assinador** — Aprovação/rejeição hierárquica com modo substituto persistente
- **Administração** — CRUD de utilizadores e migrações web

### 🆕 Melhorias Implementadas (Missão HEAVY MAX)

| # | Feature | Descrição |
|---|---------|-----------|
| 1 | **Substituto Persistente** | Estado gravado no BD (coluna `substituto_ativo`). Sobrevive a reinicializações de navegador/PC. Banner visual permanente no topo quando ativo. |
| 2 | **Sem Restrições de Senha** | Removidos limites mínimos/máximos e regras de complexidade. O utilizador escolhe livremente. |
| 3 | **Trocar Senha** | Botão "🔑 Trocar Senha" no menu do utilizador logado. |
| 4 | **Filtros em Tempo Real** | Campos de pesquisa instantânea nas filas de Protocolo e Execução Financeira (todas as abas). |
| 5 | **Ordenação RAP por OP Crescente** | A fila RAP agora ordena por OP em ordem crescente, facilitando a assinatura pelo oficial. |
| 6 | **Impressão de Capa (Protocolo)** | Nova view otimizada para A4 Retrato com cabeçalho, tabela de rubricas e primeira linha automática. |
| 7 | **Design Unificado** | CSS e header padronizados entre SIGEF e Assinador (mesma paleta, botões, badges). |

## 🗄️ Migrações de Banco de Dados

**IMPORTANTE:** Execute o ficheiro `migracoes.sql` no seu servidor PostgreSQL ANTES de fazer deploy do novo código:

```bash
psql -h SEU_HOST -U SEU_USER -d sigef_bamrj -f migracoes.sql
```

As migrações usam `ADD COLUMN IF NOT EXISTS` e são seguras para executar múltiplas vezes.

### Migrações Incluídas
- `substituto_ativo BOOLEAN` na tabela `users`
- `data_envio_protocolo TIMESTAMP` na tabela `de_lotes`
- `empresa_nome VARCHAR(255)` na tabela `de_itens`
- `origem_setor VARCHAR(128)` na tabela `users`

## 🚀 Deploy

1. Configure a variável `DATABASE_URL` no Railway
2. Execute as migrações SQL
3. Push para o repositório — o Railway faz o resto

## 🔐 Segurança

- Senhas com `password_hash()` (bcrypt)
- Prepared statements em todas as queries (anti-SQL injection)
- Controle de acesso por perfil (role-based)
- Sessão protegida contra hijacking