-- v6: Contexto conversacional WhatsApp + idempotencia por message_id

ALTER TABLE transacoes
  ADD COLUMN origem_message_id VARCHAR(120) NULL DEFAULT NULL;

CREATE INDEX idx_transacoes_origem_message_id ON transacoes(origem_message_id);

CREATE TABLE IF NOT EXISTS whatsapp_contextos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  canal VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
  chave_origem VARCHAR(40) NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'pendente',
  payload_parcial JSON NULL,
  expira_em DATETIME NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_whatsapp_contexto_usuario_origem (usuario_id, chave_origem),
  KEY idx_whatsapp_contextos_expira (expira_em),
  KEY idx_whatsapp_contextos_lookup (usuario_id, chave_origem, estado)
);
