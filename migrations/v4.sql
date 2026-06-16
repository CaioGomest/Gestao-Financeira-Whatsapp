-- v4: Período de teste gratuito nos planos
ALTER TABLE planos
  ADD COLUMN dias_teste_gratis INT NOT NULL DEFAULT 0;
