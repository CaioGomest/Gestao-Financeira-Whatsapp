-- v5: Integração com WhatsApp nos planos
ALTER TABLE planos
  ADD COLUMN tem_whatsapp TINYINT(1) NOT NULL DEFAULT 0;
