<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/usuario.php';

define('MIGRATIONS_DIR', __DIR__ . '/../migrations');

/**
 * Garante que a tabela de controle de migrações existe
 */
function garantirTabelaMigrations() {
    global $database;
    $database->query("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        versao VARCHAR(20) NOT NULL UNIQUE,
        arquivo VARCHAR(100) NOT NULL,
        aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

/**
 * Retorna lista de arquivos de migração ordenados por versão
 */
function listarArquivosMigrations() {
    $dir = MIGRATIONS_DIR;
    if (!is_dir($dir)) return [];
    $arquivos = glob($dir . '/v*.sql');
    if (!$arquivos) return [];
    usort($arquivos, function($a, $b) {
        preg_match('/v(\d+)/', basename($a), $ma);
        preg_match('/v(\d+)/', basename($b), $mb);
        return (int)($ma[1] ?? 0) - (int)($mb[1] ?? 0);
    });
    return $arquivos;
}

/**
 * Retorna versões já aplicadas no banco
 */
function versoeAplicadas() {
    global $database;
    try {
        $rows = $database->select("SELECT versao, aplicada_em FROM migrations ORDER BY versao");
        $map = [];
        foreach ($rows as $r) {
            $map[$r['versao']] = $r['aplicada_em'];
        }
        return $map;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Lista todas as migrações com status
 */
function listarMigrations() {
    global $database;
    garantirTabelaMigrations();
    $aplicadas = versoeAplicadas();
    $arquivos = listarArquivosMigrations();

    $resultado = [];
    foreach ($arquivos as $path) {
        $arquivo = basename($path);
        preg_match('/^(v\d+)/', $arquivo, $m);
        $versao = $m[1] ?? $arquivo;
        $resultado[] = [
            'versao'      => $versao,
            'arquivo'     => $arquivo,
            'status'      => isset($aplicadas[$versao]) ? 'aplicada' : 'pendente',
            'aplicada_em' => $aplicadas[$versao] ?? null,
        ];
    }
    return $resultado;
}

/**
 * Erros MySQL que indicam "já existe" — não são fatais numa migração
 * 1060 = Duplicate column name
 * 1050 = Table already exists
 * 1061 = Duplicate key name
 * 1062 = Duplicate entry (unique constraint)
 */
function erroJaExiste(Exception $e) {
    $msg = $e->getMessage();
    foreach ([1060, 1050, 1061, 1062] as $code) {
        if (strpos($msg, (string)$code) !== false) return true;
    }
    // MariaDB/MySQL mensagem literal
    foreach (['Duplicate column', 'already exists', 'Table \'', "already exist"] as $needle) {
        if (stripos($msg, $needle) !== false) return true;
    }
    return false;
}

/**
 * Aplica todas as migrações pendentes
 */
function aplicarMigrations() {
    global $database;
    garantirTabelaMigrations();
    $aplicadas = versoeAplicadas();
    $arquivos = listarArquivosMigrations();
    $log = [];
    $erros = [];

    foreach ($arquivos as $path) {
        $arquivo = basename($path);
        preg_match('/^(v\d+)/', $arquivo, $m);
        $versao = $m[1] ?? $arquivo;

        if (isset($aplicadas[$versao])) {
            $log[] = "[{$versao}] já aplicada — pulando";
            continue;
        }

        $sql = file_get_contents($path);
        // Remove linhas de comentário e quebra em statements
        $linhas = array_filter(explode("\n", $sql), function($l) {
            return strpos(trim($l), '--') !== 0;
        });
        $statements = array_filter(array_map('trim', explode(';', implode("\n", $linhas))));

        $versaoOk = true;
        foreach ($statements as $stmt) {
            if (empty($stmt)) continue;
            try {
                $database->query($stmt);
            } catch (Exception $e) {
                if (erroJaExiste($e)) {
                    $log[] = "[{$versao}] aviso: " . strtok($e->getMessage(), "\n");
                } else {
                    $erros[] = "[{$versao}] ERRO: " . $e->getMessage();
                    $versaoOk = false;
                }
            }
        }

        if ($versaoOk) {
            try {
                $database->query(
                    "INSERT INTO migrations (versao, arquivo) VALUES (?, ?)",
                    [$versao, $arquivo]
                );
                $log[] = "[{$versao}] aplicada com sucesso";
            } catch (Exception $e) {
                $erros[] = "[{$versao}] ERRO ao registrar: " . $e->getMessage();
            }
        }
    }

    return [
        'sucesso' => empty($erros),
        'log'     => $log,
        'erros'   => $erros,
    ];
}

// ── API ──────────────────────────────────────────────────────────────────────
if (isset($_GET['api']) && $_GET['api'] === 'admin_migracoes') {
    header('Content-Type: application/json');

    if (!usuarioLogado() || ($_SESSION['perfil'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado']);
        exit;
    }

    $acao = $_GET['acao'] ?? '';

    if ($acao === 'listar') {
        echo json_encode(['sucesso' => true, 'migracoes' => listarMigrations()]);
        exit;
    }

    if ($acao === 'aplicar') {
        echo json_encode(aplicarMigrations());
        exit;
    }

    echo json_encode(['sucesso' => false, 'erro' => 'acao_invalida']);
    exit;
}
