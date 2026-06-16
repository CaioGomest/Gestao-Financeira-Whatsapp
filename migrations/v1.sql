-- v1: Schema base - perfil de usuário, planos, assinaturas, configurações
ALTER TABLE usuarios ADD COLUMN perfil ENUM('admin','usuario') DEFAULT 'usuario';
ALTER TABLE planos ADD COLUMN duracao_meses INT NOT NULL DEFAULT 1;
ALTER TABLE planos ADD COLUMN stripe_price_id VARCHAR(100) NULL;
ALTER TABLE planos ADD COLUMN stripe_product_id VARCHAR(100) NULL;
ALTER TABLE assinaturas ADD COLUMN customer_id VARCHAR(100) NULL;
CREATE TABLE IF NOT EXISTS configuracoes_sistema (
    chave VARCHAR(100) PRIMARY KEY,
    valor TEXT NOT NULL,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
UPDATE usuarios SET perfil = 'admin' WHERE id = (SELECT MIN(id) FROM (SELECT id FROM usuarios) AS t) AND NOT EXISTS (SELECT 1 FROM (SELECT id FROM usuarios WHERE perfil = 'admin') AS a);
