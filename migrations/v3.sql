-- v3: Campos de recorrência nas transações (tipo e grupo UUID)
ALTER TABLE transacoes
  ADD COLUMN recorrencia VARCHAR(20) NULL DEFAULT NULL,
  ADD COLUMN recorrencia_grupo_id VARCHAR(36) NULL DEFAULT NULL;

CREATE INDEX idx_recorrencia_grupo ON transacoes(recorrencia_grupo_id);
