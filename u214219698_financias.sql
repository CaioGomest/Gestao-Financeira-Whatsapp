-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 28/11/2025 às 13:33
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `fina`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `assinaturas`
--

CREATE TABLE `assinaturas` (
  `id` INT(11) NOT NULL,
  `usuario_id` INT(11) NOT NULL,
  `plano_id` INT(11) NOT NULL,
  `status` ENUM('ativa','cancelada','expirada','pendente') DEFAULT 'ativa',
  `data_inicio` DATE NOT NULL,
  `data_fim` DATE DEFAULT NULL,
  `valor_pago` DECIMAL(10,2) DEFAULT NULL,
  `metodo_pagamento` VARCHAR(50) DEFAULT NULL,
  `gateway_transacao_id` VARCHAR(100) DEFAULT NULL,
  `criado_em` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL,
  `icone` varchar(50) DEFAULT 'fas fa-circle',
  `cor` varchar(7) DEFAULT '#666666',
  `ativa` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `usuario_id`, `nome`, `tipo`, `icone`, `cor`, `ativa`, `criado_em`, `atualizado_em`) VALUES
(1, 0, 'Salário', 'receita', 'fas fa-money-bill-wave', '#4CAF50', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(2, 0, 'Freelance', 'receita', 'fas fa-laptop-code', '#2196F3', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(3, 0, 'Investimentos', 'receita', 'fas fa-chart-line', '#FF9800', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(4, 0, 'Vendas', 'receita', 'fas fa-shopping-cart', '#9C27B0', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(5, 0, 'Alimentação', 'despesa', 'fas fa-utensils', '#F44336', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(6, 0, 'Moradia', 'despesa', 'fas fa-home', '#795548', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(7, 0, 'Transporte', 'despesa', 'fas fa-car', '#607D8B', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(8, 0, 'Saúde', 'despesa', 'fas fa-heartbeat', '#E91E63', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(9, 0, 'Educação', 'despesa', 'fas fa-graduation-cap', '#3F51B5', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(10, 0, 'Lazer', 'despesa', 'fas fa-gamepad', '#FF5722', 1, '2024-01-01 00:00:00', '2025-10-29 17:57:17'),
(11, 2, 'Uber', 'despesa', 'car', '#000000', 1, '2025-11-03 03:04:10', '2025-11-03 03:04:10'),
(12, 1, 'Uber', 'despesa', 'car', '#000000', 1, '2025-11-03 12:53:01', '2025-11-03 12:54:42'),
(13, 1, 'Salário Dazsoft', 'receita', 'money-bill-wave', '#00ff08', 1, '2025-11-03 12:54:38', '2025-11-03 12:54:38'),
(14, 1, 'Projetos', 'receita', 'money-bill-wave', '#145c00', 1, '2025-11-07 02:39:40', '2025-11-07 02:39:40'),
(15, 1, 'DFG', 'receita', 'money-bill-wave', '#3ebda5', 1, '2025-11-07 16:40:11', '2025-11-07 16:40:11'),
(16, 1, 'Casa', 'despesa', 'home', '#9e0000', 1, '2025-11-07 16:45:54', '2025-11-07 16:45:54'),
(17, 1, 'Cartão - Saída', 'despesa', 'shopping-cart', '#6b0000', 1, '2025-11-07 16:48:32', '2025-11-07 16:51:59'),
(18, 1, 'Cartão - Entrada', 'receita', 'shopping-cart', '#00703c', 1, '2025-11-07 16:51:46', '2025-11-07 16:51:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_usuario`
--

CREATE TABLE `configuracoes_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `moeda` varchar(3) DEFAULT 'BRL',
  `simbolo_moeda` varchar(5) DEFAULT 'R$',
  `formato_data` varchar(10) DEFAULT 'd/m/Y',
  `tema` enum('claro','escuro') DEFAULT 'escuro',
  `mostrar_saldo` tinyint(1) DEFAULT 1,
  `notificacoes_email` tinyint(1) DEFAULT 1,
  `notificacoes_push` tinyint(1) DEFAULT 0,
  `lembretes` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes_usuario`
--

INSERT INTO `configuracoes_usuario` (`id`, `usuario_id`, `moeda`, `simbolo_moeda`, `formato_data`, `tema`, `mostrar_saldo`, `notificacoes_email`, `notificacoes_push`, `lembretes`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 'BRL', 'R$', 'd/m/Y', 'escuro', 1, 1, 0, 1, '2025-10-29 17:57:58', '2025-10-29 17:57:58'),
(2, 2, 'BRL', 'R$', 'd/m/Y', 'escuro', 1, 1, 0, 1, '2025-11-03 03:12:53', '2025-11-03 03:12:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas`
--

CREATE TABLE `contas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('corrente','poupanca','cartao_credito','cartao_debito','dinheiro','investimento') NOT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `saldo_inicial` decimal(15,2) DEFAULT 0.00,
  `saldo_atual` decimal(15,2) DEFAULT 0.00,
  `cor` varchar(7) DEFAULT '#2196F3',
  `ativa` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `contas`
--

INSERT INTO `contas` (`id`, `usuario_id`, `nome`, `tipo`, `banco`, `saldo_inicial`, `saldo_atual`, `cor`, `ativa`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 'Conta Corrente', 'corrente', 'Banco Principal', 0.00, 0.00, '#2196F3', 1, '2025-10-29 20:57:17', '2025-10-29 17:57:17'),
(2, 1, 'Conta Poupança', 'poupanca', 'Banco Principal', 0.00, 0.00, '#4CAF50', 1, '2025-10-29 20:57:17', '2025-10-29 17:57:17'),
(3, 1, 'Cartão de Crédito', '', 'Banco Cartão', 0.00, 0.00, '#FF9800', 1, '2025-10-29 20:57:17', '2025-10-29 17:57:17'),
(4, 1, 'Conta Corrente', 'corrente', 'Banco Principal', 0.00, 0.00, '#2196F3', 1, '2025-10-29 20:57:58', '2025-10-29 17:57:58'),
(5, 1, 'Conta Poupança', 'poupanca', 'Banco Principal', 0.00, 0.00, '#4CAF50', 1, '2025-10-29 20:57:58', '2025-10-29 17:57:58'),
(6, 1, 'Cartão de Crédito', '', 'Banco Cartão', 0.00, 0.00, '#FF9800', 1, '2025-10-29 20:57:58', '2025-10-29 17:57:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `orcamentos`
--

CREATE TABLE `orcamentos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `valor_limite` decimal(15,2) NOT NULL,
  `periodo` enum('mensal','anual') DEFAULT 'mensal',
  `mes` int(11) DEFAULT NULL,
  `ano` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `limite_transacoes` int(11) DEFAULT NULL,
  `limite_categorias` int(11) DEFAULT NULL,
  `recursos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recursos`)),
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `planos`
--

INSERT INTO `planos` (`id`, `nome`, `descricao`, `preco`, `limite_transacoes`, `limite_categorias`, `recursos`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'Gratuito', 'Plano básico gratuito', 0.00, 100, 10, '{\"backup\": false, \"suporte\": \"comunidade\", \"relatorios_basicos\": true}', 1, '2025-10-29 17:56:02', '2025-10-29 17:56:02'),
(2, 'Premium', 'Plano premium com recursos avançados', 19.90, NULL, NULL, '{\"metas\": true, \"backup\": true, \"suporte\": \"prioritario\", \"orcamentos\": true, \"relatorios_avancados\": true}', 1, '2025-10-29 17:56:02', '2025-10-29 17:56:02'),
(3, 'Empresarial', 'Plano para pequenas empresas', 49.90, NULL, NULL, '{\"api\": true, \"backup\": true, \"suporte\": \"dedicado\", \"multi_usuarios\": true, \"relatorios_avancados\": true}', 1, '2025-10-29 17:56:02', '2025-10-29 17:56:02'),
(4, 'Gratuito', 'Plano básico gratuito', 0.00, 100, 10, '{\"backup\": false, \"suporte\": \"comunidade\", \"relatorios_basicos\": true}', 1, '2025-10-29 18:16:59', '2025-10-29 18:16:59'),
(5, 'Premium', 'Plano premium com recursos avançados', 19.90, NULL, NULL, '{\"metas\": true, \"backup\": true, \"suporte\": \"prioritario\", \"orcamentos\": true, \"relatorios_avancados\": true}', 1, '2025-10-29 18:16:59', '2025-10-29 18:16:59'),
(6, 'Empresarial', 'Plano para pequenas empresas', 49.90, NULL, NULL, '{\"api\": true, \"backup\": true, \"suporte\": \"dedicado\", \"multi_usuarios\": true, \"relatorios_avancados\": true}', 1, '2025-10-29 18:16:59', '2025-10-29 18:16:59');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tokens_auth`
--

CREATE TABLE `tokens_auth` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `tipo` enum('login','reset_senha','verificacao_email') NOT NULL,
  `expira_em` timestamp NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes`
--

CREATE TABLE `transacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `conta_id` int(11) NOT NULL,
  `data_transacao` date NOT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `transacoes`
--

INSERT INTO `transacoes` (`id`, `usuario_id`, `tipo`, `descricao`, `valor`, `categoria_id`, `conta_id`, `data_transacao`, `observacoes`, `criado_em`, `atualizado_em`) VALUES
(22, 1, 'receita', 'Script Gateway', 79.89, 15, 2, '2025-11-14', '', '2025-11-07 16:43:34', '2025-11-07 16:43:34'),
(27, 1, 'despesa', 'Cintia G. Cartão ', 300.00, 18, 1, '2025-11-04', '', '2025-11-07 16:53:25', '2025-11-07 16:53:25'),
(11, 1, 'despesa', 'Uber', 11.20, 12, 1, '2025-11-03', '', '2025-11-03 12:53:30', '2025-11-03 12:53:30'),
(25, 1, 'despesa', 'Fatura Cartão', 2725.55, 17, 1, '2025-11-01', '', '2025-11-07 16:49:21', '2025-11-07 16:49:21'),
(14, 1, 'receita', 'Salário', 2500.00, 13, 2, '2025-11-03', '', '2025-11-03 12:56:19', '2025-11-03 12:56:19'),
(16, 1, 'receita', 'Script Gateway', 79.89, 15, 2, '2025-11-07', '', '2025-11-07 16:41:14', '2025-11-07 16:41:14'),
(18, 1, 'receita', 'Script Gateway', 79.89, 15, 2, '2025-11-11', '', '2025-11-07 16:41:49', '2025-11-07 16:41:49'),
(23, 1, 'despesa', 'Casa', 700.00, 16, 1, '2025-11-02', '', '2025-11-07 16:46:19', '2025-11-07 16:46:19'),
(28, 1, 'receita', 'Cintia G. Cartão ', 300.00, 18, 2, '2025-11-04', '', '2025-11-07 16:53:25', '2025-11-07 16:53:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `perfil` enum('usuario','admin') DEFAULT 'usuario',
  `senha_hash` varchar(255) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `plano_id` int(11) DEFAULT 1,
  `status` enum('ativo','inativo','suspenso') DEFAULT 'ativo',
  `email_verificado` tinyint(1) DEFAULT 0,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `perfil`, `senha_hash`, `foto_perfil`, `plano_id`, `status`, `email_verificado`, `data_cadastro`, `ultimo_acesso`, `criado_em`, `atualizado_em`) VALUES
(1, 'usuario', 'caio@gmail.com', 'admin', '$2a$12$Owi9SFg883JfNvjc42Qq3.F9REB8oS78QdDTrSIjXoejAjN0ov4Ka', '', 1, 'ativo', 0, '2025-10-29 17:57:17', '2025-11-28 13:31:07', '2024-01-01 00:00:00', '2025-11-28 13:31:07');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `plano_id` (`plano_id`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_categoria_usuario` (`usuario_id`,`nome`,`tipo`),
  ADD KEY `idx_categorias_usuario_tipo` (`usuario_id`,`tipo`);

--
-- Índices de tabela `configuracoes_usuario`
--
ALTER TABLE `configuracoes_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_config_usuario` (`usuario_id`);

--
-- Índices de tabela `contas`
--
ALTER TABLE `contas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contas_usuario` (`usuario_id`);

--
-- Índices de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_orcamento` (`usuario_id`,`categoria_id`,`periodo`,`mes`,`ano`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tokens_auth`
--
ALTER TABLE `tokens_auth`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tokens_usuario` (`usuario_id`),
  ADD KEY `idx_tokens_expiracao` (`expira_em`);

--
-- Índices de tabela `transacoes`
--
ALTER TABLE `transacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transacoes_usuario_data` (`usuario_id`,`data_transacao`),
  ADD KEY `idx_transacoes_categoria` (`categoria_id`),
  ADD KEY `idx_transacoes_conta` (`conta_id`),
  ADD KEY `idx_transacoes_tipo` (`tipo`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `plano_id` (`plano_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `configuracoes_usuario`
--
ALTER TABLE `configuracoes_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `contas`
--
ALTER TABLE `contas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `tokens_auth`
--
ALTER TABLE `tokens_auth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `transacoes`
--
ALTER TABLE `transacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
