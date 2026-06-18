-- =====================================================
-- SETUP COMPLETO DO BANCO "fina"
-- Execute este arquivo no HeidiSQL com o servidor Localhost selecionado
-- =====================================================

CREATE DATABASE IF NOT EXISTS fina CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fina;

-- =====================================================
-- TABELAS BASE
-- =====================================================

CREATE TABLE IF NOT EXISTS planos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    limite_transacoes INT DEFAULT NULL,
    limite_categorias INT DEFAULT NULL,
    recursos JSON,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- migrations v1, v4, v5
    duracao_meses INT NOT NULL DEFAULT 1,
    stripe_price_id VARCHAR(100) NULL,
    stripe_product_id VARCHAR(100) NULL,
    dias_teste_gratis INT NOT NULL DEFAULT 0,
    tem_whatsapp TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255),
    plano_id INT DEFAULT 1,
    status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo',
    email_verificado BOOLEAN DEFAULT FALSE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- migrations v1, v2
    perfil ENUM('admin','usuario') DEFAULT 'usuario',
    telefone VARCHAR(20) NULL,
    cpf VARCHAR(14) NULL,
    whatsapp_numero VARCHAR(20) NULL,
    FOREIGN KEY (plano_id) REFERENCES planos(id)
);

CREATE TABLE IF NOT EXISTS assinaturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    plano_id INT NOT NULL,
    status ENUM('ativa', 'cancelada', 'expirada', 'pendente', 'trialing') DEFAULT 'ativa',
    data_inicio DATE NOT NULL,
    data_fim DATE,
    valor_pago DECIMAL(10,2),
    metodo_pagamento VARCHAR(50),
    gateway_transacao_id VARCHAR(100),
    -- migration v1
    customer_id VARCHAR(100) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (plano_id) REFERENCES planos(id)
);

CREATE TABLE IF NOT EXISTS tokens_auth (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    tipo ENUM('login', 'reset_senha', 'verificacao_email') NOT NULL,
    expira_em TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('corrente', 'poupanca', 'cartao_credito', 'cartao_debito', 'dinheiro', 'investimento') NOT NULL,
    banco VARCHAR(100),
    saldo_inicial DECIMAL(15,2) DEFAULT 0.00,
    saldo_atual DECIMAL(15,2) DEFAULT 0.00,
    cor VARCHAR(7) DEFAULT '#2196F3',
    ativa BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    icone VARCHAR(50) DEFAULT 'fas fa-circle',
    cor VARCHAR(7) DEFAULT '#666666',
    ativa BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_categoria_usuario (usuario_id, nome, tipo)
);

CREATE TABLE IF NOT EXISTS transacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    categoria_id INT,
    conta_id INT NOT NULL,
    data_transacao DATE NOT NULL,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- migrations v3, v6
    recorrencia VARCHAR(20) NULL DEFAULT NULL,
    recorrencia_grupo_id VARCHAR(36) NULL DEFAULT NULL,
    origem_message_id VARCHAR(120) NULL DEFAULT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS configuracoes_usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    moeda VARCHAR(3) DEFAULT 'BRL',
    simbolo_moeda VARCHAR(5) DEFAULT 'R$',
    formato_data VARCHAR(10) DEFAULT 'd/m/Y',
    tema ENUM('claro', 'escuro') DEFAULT 'escuro',
    mostrar_saldo BOOLEAN DEFAULT TRUE,
    notificacoes_email BOOLEAN DEFAULT TRUE,
    notificacoes_push BOOLEAN DEFAULT FALSE,
    lembretes BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_config_usuario (usuario_id)
);

CREATE TABLE IF NOT EXISTS orcamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    valor_limite DECIMAL(15,2) NOT NULL,
    periodo ENUM('mensal', 'anual') DEFAULT 'mensal',
    mes INT,
    ano INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_orcamento (usuario_id, categoria_id, periodo, mes, ano)
);

-- migration v1
CREATE TABLE IF NOT EXISTS configuracoes_sistema (
    chave VARCHAR(100) PRIMARY KEY,
    valor TEXT NOT NULL,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- migration v6
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

-- =====================================================
-- ÍNDICES
-- =====================================================

CREATE INDEX idx_transacoes_usuario_data ON transacoes(usuario_id, data_transacao);
CREATE INDEX idx_transacoes_categoria ON transacoes(categoria_id);
CREATE INDEX idx_transacoes_conta ON transacoes(conta_id);
CREATE INDEX idx_transacoes_tipo ON transacoes(tipo);
CREATE INDEX idx_recorrencia_grupo ON transacoes(recorrencia_grupo_id);
CREATE INDEX idx_transacoes_origem_message_id ON transacoes(origem_message_id);
CREATE INDEX idx_categorias_usuario_tipo ON categorias(usuario_id, tipo);
CREATE INDEX idx_contas_usuario ON contas(usuario_id);
CREATE INDEX idx_tokens_usuario ON tokens_auth(usuario_id);
CREATE INDEX idx_tokens_expiracao ON tokens_auth(expira_em);

-- =====================================================
-- TRIGGERS DE SALDO
-- =====================================================

DELIMITER //
CREATE TRIGGER atualizar_saldo_insert AFTER INSERT ON transacoes
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'receita' THEN
        UPDATE contas SET saldo_atual = saldo_atual + NEW.valor WHERE id = NEW.conta_id;
    ELSEIF NEW.tipo = 'despesa' THEN
        UPDATE contas SET saldo_atual = saldo_atual - NEW.valor WHERE id = NEW.conta_id;
    END IF;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER atualizar_saldo_update AFTER UPDATE ON transacoes
FOR EACH ROW
BEGIN
    IF OLD.tipo = 'receita' THEN
        UPDATE contas SET saldo_atual = saldo_atual - OLD.valor WHERE id = OLD.conta_id;
    ELSEIF OLD.tipo = 'despesa' THEN
        UPDATE contas SET saldo_atual = saldo_atual + OLD.valor WHERE id = OLD.conta_id;
    END IF;
    IF NEW.tipo = 'receita' THEN
        UPDATE contas SET saldo_atual = saldo_atual + NEW.valor WHERE id = NEW.conta_id;
    ELSEIF NEW.tipo = 'despesa' THEN
        UPDATE contas SET saldo_atual = saldo_atual - NEW.valor WHERE id = NEW.conta_id;
    END IF;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER atualizar_saldo_delete AFTER DELETE ON transacoes
FOR EACH ROW
BEGIN
    IF OLD.tipo = 'receita' THEN
        UPDATE contas SET saldo_atual = saldo_atual - OLD.valor WHERE id = OLD.conta_id;
    ELSEIF OLD.tipo = 'despesa' THEN
        UPDATE contas SET saldo_atual = saldo_atual + OLD.valor WHERE id = OLD.conta_id;
    END IF;
END//
DELIMITER ;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

INSERT INTO planos (nome, descricao, preco, limite_transacoes, limite_categorias, recursos) VALUES
('Gratuito',    'Plano básico gratuito',              0.00,  100,  10,   '{"relatorios_basicos": true, "backup": false, "suporte": "comunidade"}'),
('Premium',     'Plano premium com recursos avançados', 19.90, NULL, NULL, '{"relatorios_avancados": true, "backup": true, "suporte": "prioritario", "metas": true, "orcamentos": true}'),
('Empresarial', 'Plano para pequenas empresas',        49.90, NULL, NULL, '{"multi_usuarios": true, "relatorios_avancados": true, "backup": true, "suporte": "dedicado", "api": true}');

-- Usuário admin padrão (senha: 123456)
INSERT INTO usuarios (nome, email, senha_hash, plano_id, perfil, status, email_verificado) VALUES
('Admin', 'usuario@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'admin', 'ativo', TRUE);

SET @uid = LAST_INSERT_ID();

INSERT INTO configuracoes_usuario (usuario_id, moeda, simbolo_moeda, formato_data, tema, mostrar_saldo) VALUES
(@uid, 'BRL', 'R$', 'd/m/Y', 'escuro', TRUE);

INSERT INTO contas (usuario_id, nome, tipo, banco, saldo_inicial, saldo_atual, cor) VALUES
(@uid, 'Conta Corrente',   'corrente',      'Banco Principal', 0.00, 0.00, '#2196F3'),
(@uid, 'Conta Poupança',   'poupanca',      'Banco Principal', 0.00, 0.00, '#4CAF50'),
(@uid, 'Cartão de Crédito','cartao_credito','Banco Principal', 0.00, 0.00, '#FF5722'),
(@uid, 'Dinheiro',         'dinheiro',      NULL,              0.00, 0.00, '#795548');

INSERT INTO categorias (usuario_id, nome, tipo, icone, cor) VALUES
(@uid, 'Salário',       'receita', 'fas fa-money-bill-wave',  '#4CAF50'),
(@uid, 'Freelance',     'receita', 'fas fa-laptop-code',      '#2196F3'),
(@uid, 'Investimentos', 'receita', 'fas fa-chart-line',       '#FF9800'),
(@uid, 'Outros',        'receita', 'fas fa-plus-circle',      '#607D8B'),
(@uid, 'Alimentação',   'despesa', 'fas fa-utensils',         '#F44336'),
(@uid, 'Moradia',       'despesa', 'fas fa-home',             '#E91E63'),
(@uid, 'Transporte',    'despesa', 'fas fa-car',              '#673AB7'),
(@uid, 'Saúde',         'despesa', 'fas fa-heartbeat',        '#3F51B5'),
(@uid, 'Educação',      'despesa', 'fas fa-graduation-cap',   '#009688'),
(@uid, 'Lazer',         'despesa', 'fas fa-gamepad',          '#FF5722'),
(@uid, 'Compras',       'despesa', 'fas fa-shopping-bag',     '#795548'),
(@uid, 'Serviços',      'despesa', 'fas fa-tools',            '#607D8B'),
(@uid, 'Outros',        'despesa', 'fas fa-minus-circle',     '#9E9E9E');

SELECT 'Banco fina criado com sucesso!' AS resultado;
SELECT CONCAT('Login: usuario@email.com  |  Senha: 123456  |  Perfil: admin') AS acesso;
