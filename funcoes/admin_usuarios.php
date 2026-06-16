<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('listarUsuarios')) {
function listarUsuarios() {
    global $database;
    return $database->select("SELECT id, nome, email, perfil, status, data_cadastro FROM usuarios ORDER BY id DESC", []);
}
}

if (!function_exists('criarUsuario')) {
function criarUsuario($nome, $email, $senha, $perfil) {
    global $database;
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (nome, email, senha_hash, perfil, status, data_cadastro) VALUES (?, ?, ?, ?, 'ativo', NOW())";
    $id = $database->insert($sql, [$nome, $email, $hash, $perfil]);
    $database->insert("INSERT INTO configuracoes_usuario (usuario_id) VALUES (?)", [$id]);
    return $id;
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_usuarios') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    switch ($acao) {
        case 'listar':
            echo json_encode(listarUsuarios());
            break;
        case 'atualizar':
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $perfil = $_POST['perfil'] ?? 'usuario';
            $status = $_POST['status'] ?? 'ativo';
            if (!$id || $nome === '' || $email === '') {
                http_response_code(400);
                echo json_encode(['erro' => 'dados_invalidos']);
                break;
            }
            try {
                global $database;
                $dup = $database->select("SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1", [$email, $id]);
                if (!empty($dup)) {
                    http_response_code(409);
                    echo json_encode(['erro' => 'email_duplicado']);
                    break;
                }
                $ok = $database->update("UPDATE usuarios SET nome = ?, email = ?, perfil = ?, status = ?, atualizado_em = NOW() WHERE id = ?", [$nome, $email, $perfil, $status, $id]) >= 0;
                echo json_encode(['sucesso' => $ok]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['erro' => 'falha_atualizacao']);
            }
            break;
        case 'criar':
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $perfil = $_POST['perfil'] ?? 'usuario';
            if (!$nome || !$email || !$senha) {
                http_response_code(400);
                echo json_encode(['erro' => 'dados_invalidos']);
                break;
            }
            try {
                global $database;
                $existente = $database->select("SELECT id FROM usuarios WHERE email = ? LIMIT 1", [$email]);
                if (!empty($existente)) {
                    http_response_code(409);
                    echo json_encode(['erro' => 'email_duplicado']);
                    break;
                }
                $id = criarUsuario($nome, $email, $senha, $perfil);
                echo json_encode(['sucesso' => true, 'id' => $id]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['erro' => 'falha_criacao']);
            }
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}

?>
