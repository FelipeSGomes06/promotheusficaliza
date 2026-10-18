# ADR-0003 — Características por tipo em JSON validado por esquema

Data: 18/10/2026 · Situação: aceita

**Contexto.** Radar, câmera, balança etc. têm atributos diferentes; não queremos um campo de observação livre nem EAV.
**Decisão.** Coluna `SPECS` (BLOB texto) com JSON validado no servidor por um esquema declarado em `EquipmentCatalog`
(tipos, faixas, opções, obrigatoriedade condicional). O mesmo esquema gera o formulário e a ficha.
**Consequências.** Novo tipo = entrada no catálogo + migração do `CHECK` + ícone; validação forte na aplicação, mas sem
consulta SQL por atributo nem `CHECK` no banco para cada característica (aceitável no volume da demonstração).
**Alternativas.** Tabela de detalhe por tipo (7 tabelas e CRUDs); EAV genérico (validação fraca).
