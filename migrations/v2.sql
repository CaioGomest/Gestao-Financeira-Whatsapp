-- v2: Campos extras do perfil do usuário (telefone, cpf, whatsapp, atualizado_em)
ALTER TABLE usuarios ADD COLUMN telefone VARCHAR(20) NULL;
ALTER TABLE usuarios ADD COLUMN cpf VARCHAR(14) NULL;
ALTER TABLE usuarios ADD COLUMN whatsapp_numero VARCHAR(20) NULL;
ALTER TABLE usuarios ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
