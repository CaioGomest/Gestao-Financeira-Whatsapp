<?php
/**
 * Funções para manipulação de dados do usuário e autenticação
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/email.php';

// Inicializar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar conexão com banco
$database = new Database();

/**
 * Autentica um usuário
 * @param string $email Email do usuário
 * @param string $senha Senha do usuário
 * @return array|false Dados do usuário ou false se falhou
 */
if (!function_exists('autenticarUsuario')) {
function autenticarUsuario($email, $senha) {
    global $database;
    
    $sql = "SELECT * FROM usuarios WHERE email = ? AND status = 'ativo'";
    $usuarios = $database->select($sql, [$email]);
    
    if (!empty($usuarios)) {
        $usuario = $usuarios[0];
        // Para simplicidade, vamos usar senha em texto simples
        // Em produção, use password_verify() com hash
        if ($usuario['senha_hash'] === $senha || password_verify($senha, $usuario['senha_hash'])) {
            return $usuario;
        }
    }
    
    return false;
}
}

if (!function_exists('cadastrarUsuario')) {
function cadastrarUsuario($nome, $email, $senha) {
    global $database;
    $existe = $database->select("SELECT id FROM usuarios WHERE email = ?", [$email]);
    if (!empty($existe)) {
        return ['erro' => 'email_existe'];
    }
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $id = $database->insert(
        "INSERT INTO usuarios (nome, email, senha_hash, status, perfil, data_cadastro) VALUES (?, ?, ?, 'ativo', 'usuario', NOW())",
        [$nome, $email, $hash]
    );
    return $id ? ['sucesso' => true, 'id' => $id] : ['erro' => 'falha'];
}
}

/**
 * Faz login do usuário
 * @param string $email Email do usuário
 * @param string $senha Senha do usuário
 * @return bool Sucesso do login
 */
if (!function_exists('fazerLogin')) {
function fazerLogin($email, $senha) {
    $usuario = autenticarUsuario($email, $senha);
    
    if ($usuario) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['perfil'] = isset($usuario['perfil']) ? $usuario['perfil'] : 'usuario';
        $_SESSION['logado'] = true;
        
        // Atualizar último acesso
        global $database;
        $sql = "UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?";
        $database->update($sql, [$usuario['id']]);
        
        return true;
    }
    
    return false;
}
}

/**
 * Faz logout do usuário
 */
if (!function_exists('fazerLogout')) {
function fazerLogout() {
    session_destroy();
    session_start();
}
}

/**
 * Verifica se o usuário está logado
 * @return bool True se logado
 */
if (!function_exists('usuarioLogado')) {
function usuarioLogado() {
    return isset($_SESSION['logado']) && $_SESSION['logado'] === true;
}
}

/**
 * Obtém ID do usuário logado
 * @return int|null ID do usuário ou null se não logado
 */
if (!function_exists('obterUsuarioId')) {
function obterUsuarioId() {
    return usuarioLogado() ? $_SESSION['usuario_id'] : null;
}
}

/**
 * Obtém dados completos do usuário logado
 * @return array|null Dados do usuário ou null se não logado
 */
if (!function_exists('obterDadosUsuario')) {
function obterDadosUsuario() {
    if (!usuarioLogado()) {
        return null;
    }
    
    global $database;
    try {
        $sql = "SELECT id, nome, email, foto_perfil, plano_id, data_cadastro, ultimo_acesso, telefone, cpf, whatsapp_numero FROM usuarios WHERE id = ?";
        $usuarios = $database->select($sql, [$_SESSION['usuario_id']]);
    } catch (Exception $e) {
        $sql = "SELECT id, nome, email, foto_perfil, data_cadastro, ultimo_acesso FROM usuarios WHERE id = ?";
        $usuarios = $database->select($sql, [$_SESSION['usuario_id']]);
    }

    $usuario = !empty($usuarios) ? $usuarios[0] : null;
    if ($usuario && isset($usuario['perfil'])) {
        $_SESSION['perfil'] = $usuario['perfil'];
    }
    return $usuario;
}
}

/**
 * Atualiza dados do usuário
 * @param array $dados Dados para atualizar
 * @return bool Sucesso da operação
 */
if (!function_exists('atualizarUsuario')) {
function atualizarUsuario($dados) {
    if (!usuarioLogado()) {
        return false;
    }
    
    global $database;
    // Garantir colunas necessárias na tabela usuarios
    try {
        $colTel = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'telefone'");
        if (empty($colTel)) {
            $database->query("ALTER TABLE usuarios ADD COLUMN telefone VARCHAR(20) NULL AFTER email");
        }
        $colCpf = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'cpf'");
        if (empty($colCpf)) {
            $database->query("ALTER TABLE usuarios ADD COLUMN cpf VARCHAR(14) NULL AFTER telefone");
        }
        $colAtualizadoEm = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'atualizado_em'");
        if (empty($colAtualizadoEm)) {
            $database->query("ALTER TABLE usuarios ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER cpf");
        }
        $colWhatsapp = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'whatsapp_numero'");
        if (empty($colWhatsapp)) {
            $database->query("ALTER TABLE usuarios ADD COLUMN whatsapp_numero VARCHAR(20) NULL");
            $database->query("CREATE INDEX IF NOT EXISTS idx_usuarios_whatsapp ON usuarios(whatsapp_numero)");
        }
    } catch (Exception $e) {
        // Continua mesmo que não consiga criar colunas
    }
    $campos = [];
    $params = [];
    
    if (isset($dados['nome'])) {
        $campos[] = "nome = ?";
        $params[] = $dados['nome'];
    }
    
    if (isset($dados['email'])) {
        $campos[] = "email = ?";
        $params[] = $dados['email'];
    }
    // Campos opcionais conforme existência no banco
    if (isset($dados['telefone'])) {
        $campos[] = "telefone = ?";
        $params[] = $dados['telefone'];
    }
    if (isset($dados['cpf'])) {
        $campos[] = "cpf = ?";
        $params[] = $dados['cpf'];
    }
    if (isset($dados['whatsapp_numero'])) {
        // Normaliza: remove tudo que não é dígito
        $wnum = preg_replace('/\D/', '', $dados['whatsapp_numero']);
        $campos[] = "whatsapp_numero = ?";
        $params[] = $wnum ?: null;
    }
    if (isset($dados['foto_perfil'])) {
        $campos[] = "foto_perfil = ?";
        $params[] = $dados['foto_perfil'];
    }
    
    if (empty($campos)) {
        return false;
    }

    $params[] = $_SESSION['usuario_id'];
    
    $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
    $resultado = $database->update($sql, $params);
    return $resultado !== false;
}
}

/**
 * Redireciona para login se não estiver logado
 */
if (!function_exists('verificarLogin')) {
function verificarLogin() {
    if (!usuarioLogado()) {
        header('Location: ../login.php?erro=acesso');
        exit;
    }
}
}

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'login':
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            
            if (fazerLogin($email, $senha)) {
                header('Location: index.php');
                exit;
            } else {
                header('Location: login.php?erro=credenciais');
                exit;
            }
            break;
            
        case 'logout':
            fazerLogout();
            header('Location: ../login.php');
            exit;
            break;
    }
}

/**
 * API para obter dados do usuário (GET)
 */
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['api']) && $_GET['api'] == 'usuario') {
    header('Content-Type: application/json');
    
    $acao = $_GET['acao'] ?? '';
    
    switch ($acao) {
        case 'obter':
            if (usuarioLogado()) {
                echo json_encode(obterDadosUsuario());
            } else {
                // Modo desenvolvimento: retornar dados do usuário padrão
                global $database;
        $temPerfil = false;
        try {
            $col = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'perfil'");
            $temPerfil = !empty($col);
        } catch (Exception $e) {
            $temPerfil = false;
        }
        if (!$temPerfil) {
            try {
                $database->query("ALTER TABLE usuarios ADD COLUMN perfil ENUM('usuario','admin') DEFAULT 'usuario' AFTER email");
                $temPerfil = true;
            } catch (Exception $e) {}
        }
        $sql = "SELECT id, nome, email, foto_perfil, plano_id, data_cadastro, ultimo_acesso" . ($temPerfil ? ", perfil" : "") . " FROM usuarios WHERE id = 1";
        $usuarios = $database->select($sql);
        $usr = !empty($usuarios) ? $usuarios[0] : ['id' => 1, 'nome' => 'Usuário Teste', 'email' => 'teste@teste.com'];
        if ($temPerfil && isset($usr['perfil'])) $_SESSION['perfil'] = $usr['perfil'];
        echo json_encode($usr);
            }
            break;
        
        case 'verificar_login':
            echo json_encode(['logado' => usuarioLogado()]);
            break;

        case 'buscar_por_whatsapp':
            $numero = preg_replace('/\D/', '', $_GET['numero'] ?? '');
            if (!$numero) {
                http_response_code(400);
                echo json_encode(['erro' => 'Numero obrigatorio']);
                break;
            }
            global $database;
            $sql = "SELECT u.id, u.nome, u.email, u.whatsapp_numero,
                           CASE WHEN a.id IS NOT NULL THEN 'ativo' ELSE 'inativo' END AS plano_status,
                           p.nome AS plano_nome,
                           (SELECT c.id FROM contas c WHERE c.usuario_id = u.id AND c.ativa = 1 ORDER BY c.id LIMIT 1) AS conta_id_padrao
                    FROM usuarios u
                    LEFT JOIN assinaturas a ON a.usuario_id = u.id AND a.status IN ('ativo','trialing','ativa')
                    LEFT JOIN planos p ON p.id = a.plano_id
                    WHERE u.whatsapp_numero = ? AND u.status = 'ativo'
                    LIMIT 1";
            $rows = $database->select($sql, [$numero]);
            if (empty($rows)) {
                echo json_encode(null);
                break;
            }
            $u = $rows[0];
            // Normaliza plano_status e adiciona flag booleana para o n8n
            $s = strtolower(trim($u['plano_status'] ?? ''));
            $u['plano_status'] = ($s === 'ativo' || $s === 'ativa' || $s === 'trialing') ? 'ativo' : $s;
            $u['plano_ativo']  = ($u['plano_status'] === 'ativo');
            // Cria/retoma sessão dedicada para este usuário (usada pelo n8n nas chamadas subsequentes)
            $sessaoId = md5('whatsapp_bot_' . $u['id'] . '_' . date('Y-m-d'));
            session_write_close();
            session_id($sessaoId);
            session_start();
            $_SESSION['usuario_id']    = $u['id'];
            $_SESSION['usuario_nome']  = $u['nome'];
            $_SESSION['usuario_email'] = $u['email'];
            $_SESSION['logado']        = true;
            session_write_close();
            $u['sessao_php'] = $sessaoId;
            echo json_encode($u);
            break;

        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não reconhecida']);
            break;
    }
    
    exit;
}

/**
 * API para salvar dados do usuário
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['api']) && $_GET['api'] == 'usuario') {
    header('Content-Type: application/json');
    
    if (!usuarioLogado()) {
        http_response_code(401);
        echo json_encode(['erro' => 'Não autenticado']);
        exit;
    }
    
    $acao = $_GET['acao'] ?? '';
    
    switch ($acao) {
        case 'atualizar':
            try {
                $corpo = file_get_contents('php://input');
                $dados = json_decode($corpo, true);
                if ($dados === null && json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'JSON inválido', 'detalhe' => json_last_error_msg()]);
                    break;
                }
                $dados = $dados ?? [];

                // Verificação de e-mail: se mudou, exige código
                if (isset($dados['email'])) {
                    $emailAtual = $_SESSION['usuario_email'] ?? '';
                    $novoEmail  = trim($dados['email']);
                    if ($novoEmail !== $emailAtual) {
                        $sessEmail = $_SESSION['reset_email'] ?? null;
                        $codigoInformado = $dados['codigo_email'] ?? '';
                        if (!$sessEmail || time() > ($sessEmail['expira'] ?? 0)
                            || $sessEmail['codigo'] !== $codigoInformado
                            || $sessEmail['email'] !== $novoEmail) {
                            http_response_code(400);
                            echo json_encode(['erro' => 'codigo_invalido']);
                            break;
                        }
                        unset($_SESSION['reset_email']);
                        // Atualiza email na sessão
                        $_SESSION['usuario_email'] = $novoEmail;
                    }
                }

                $ok = atualizarUsuario($dados);
                echo json_encode(['sucesso' => (bool)$ok]);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['erro' => 'Falha ao atualizar', 'detalhe' => $e->getMessage()]);
            }
            break;

        case 'enviar_codigo_email':
            try {
                $dados = json_decode(file_get_contents('php://input'), true);
                $novoEmail = trim($dados['novo_email'] ?? '');
                if (!$novoEmail || !filter_var($novoEmail, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'Email inválido']);
                    break;
                }
                // Verifica se já está em uso
                $existe = $database->select("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$novoEmail, $_SESSION['usuario_id']]);
                if (!empty($existe)) {
                    http_response_code(409);
                    echo json_encode(['erro' => 'Email já cadastrado']);
                    break;
                }
                $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['reset_email'] = ['email' => $novoEmail, 'codigo' => $codigo, 'expira' => time() + 900];
                $ok = enviar_email_codigo($novoEmail, $codigo);
                echo json_encode(['sucesso' => (bool)$ok]);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['erro' => 'Falha ao enviar código', 'detalhe' => $e->getMessage()]);
            }
            break;
        case 'enviar_codigo_senha':
            $email = $_SESSION['usuario_email'] ?? '';
            if (!$email) {
                http_response_code(400);
                echo json_encode(['erro' => 'Email não disponível']);
                break;
            }
            $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['reset_senha'] = [
                'codigo' => $codigo,
                'expira' => time() + 15 * 60
            ];
            $ok = enviar_email_codigo($email, $codigo);
            echo json_encode(['sucesso' => (bool)$ok]);
            break;
        case 'trocar_senha':
            $dados = json_decode(file_get_contents('php://input'), true);
            $codigo = $dados['codigo'] ?? '';
            $nova = $dados['nova_senha'] ?? '';
            $session = $_SESSION['reset_senha'] ?? null;
            if (!$session || time() > ($session['expira'] ?? 0) || $session['codigo'] !== $codigo) {
                http_response_code(400);
                echo json_encode(['erro' => 'Código inválido ou expirado']);
                break;
            }
            if (strlen($nova) < 6) {
                http_response_code(400);
                echo json_encode(['erro' => 'Senha muito curta']);
                break;
            }
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            $ok = $database->update("UPDATE usuarios SET senha_hash = ?, atualizado_em = NOW() WHERE id = ?", [$hash, $_SESSION['usuario_id']]);
            unset($_SESSION['reset_senha']);
            echo json_encode(['sucesso' => $ok > 0]);
            break;
        case 'enviar_codigo_senha_login':
            $dados = json_decode(file_get_contents('php://input'), true);
            $email = $dados['email'] ?? '';
            if (!$email) { http_response_code(400); echo json_encode(['erro' => 'Informe o email']); break; }
            $existe = $database->select("SELECT id FROM usuarios WHERE email = ? AND status = 'ativo'", [$email]);
            if (empty($existe)) { http_response_code(404); echo json_encode(['erro' => 'Email não encontrado']); break; }
            $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            if (!isset($_SESSION['reset_login'])) $_SESSION['reset_login'] = [];
            $_SESSION['reset_login'][$email] = ['codigo' => $codigo, 'expira' => time() + 15 * 60, 'usuario_id' => $existe[0]['id']];
            $ok = enviar_email_codigo($email, $codigo);
            echo json_encode(['sucesso' => (bool)$ok]);
            break;
        case 'trocar_senha_login':
            $dados = json_decode(file_get_contents('php://input'), true);
            $email = $dados['email'] ?? '';
            $codigo = $dados['codigo'] ?? '';
            $nova = $dados['nova_senha'] ?? '';
            $sess = $_SESSION['reset_login'][$email] ?? null;
            if (!$sess || time() > ($sess['expira'] ?? 0) || $sess['codigo'] !== $codigo) { http_response_code(400); echo json_encode(['erro' => 'Código inválido ou expirado']); break; }
            if (strlen($nova) < 6) { http_response_code(400); echo json_encode(['erro' => 'Senha muito curta']); break; }
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            $ok = $database->update("UPDATE usuarios SET senha_hash = ?, atualizado_em = NOW() WHERE id = ?", [$hash, $sess['usuario_id']]);
            unset($_SESSION['reset_login'][$email]);
            echo json_encode(['sucesso' => $ok > 0]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não reconhecida']);
            break;
    }
    
    exit;
}
?>
