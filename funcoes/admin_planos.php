<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('colunaExiste')) {
function colunaExiste($tabela, $coluna) {
    global $database;
    $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?";
    $res = $database->select($sql, [$tabela, $coluna]);
    return !empty($res);
}
}

if (!function_exists('listarPlanos')) {
function listarPlanos($inicio = null, $fim = null) {
    global $database;
    $temDuracao = colunaExiste('planos', 'duracao_meses');
    $campoData = null;
    foreach (['criado_em','data_criacao','created_at'] as $c) {
        if (colunaExiste('planos', $c)) { $campoData = $c; break; }
    }
    $temTrial = colunaExiste('planos', 'dias_teste_gratis');
    $temWpp   = colunaExiste('planos', 'tem_whatsapp');
    $selectBase = $temDuracao
        ? "SELECT id, nome, descricao, preco, duracao_meses, stripe_price_id, stripe_product_id, ativo" . ($temTrial ? ", dias_teste_gratis" : ", 0 AS dias_teste_gratis") . ($temWpp ? ", tem_whatsapp" : ", 0 AS tem_whatsapp")
        : "SELECT id, nome, descricao, preco, 1 AS duracao_meses, stripe_price_id, stripe_product_id, ativo" . ($temTrial ? ", dias_teste_gratis" : ", 0 AS dias_teste_gratis") . ($temWpp ? ", tem_whatsapp" : ", 0 AS tem_whatsapp");
    if ($campoData) { $selectBase .= ", $campoData AS criado_em"; }
    $sql = $selectBase . " FROM planos WHERE ativo = 1 ORDER BY id DESC";
    return $database->select($sql, []);
}
}

if (!function_exists('atualizarPriceId')) {
function atualizarPriceId($id, $priceId) {
    global $database;
    $sql = "UPDATE planos SET stripe_price_id = ?, atualizado_em = NOW() WHERE id = ?";
    return $database->update($sql, [$priceId, $id]) > 0;
}
}

if (!function_exists('atualizarBasicoPlano')) {
function atualizarBasicoPlano($id, $preco, $duracaoMeses, $temWhatsapp = null) {
    global $database;
    $temDuracao = colunaExiste('planos', 'duracao_meses');
    $temWpp     = colunaExiste('planos', 'tem_whatsapp');
    $sets  = ['preco = ?'];
    $vals  = [$preco];
    if ($temDuracao) { $sets[] = 'duracao_meses = ?'; $vals[] = $duracaoMeses; }
    if ($temWpp && $temWhatsapp !== null) { $sets[] = 'tem_whatsapp = ?'; $vals[] = $temWhatsapp ? 1 : 0; }
    $sets[] = 'atualizado_em = NOW()';
    $vals[] = $id;
    $sql = "UPDATE planos SET " . implode(', ', $sets) . " WHERE id = ?";
    return $database->update($sql, $vals) > 0;
}
}

if (!function_exists('inativarPlano')) {
function inativarPlano($id) {
    global $database;
    $sql = "UPDATE planos SET ativo = 0, atualizado_em = NOW() WHERE id = ?";
    return $database->update($sql, [$id]) > 0;
}
}

if (!function_exists('criarPlano')) {
function criarPlano($nome, $descricao, $preco, $duracaoMeses, $priceId, $diasTeste = 0, $temWhatsapp = 0) {
    global $database;
    $temDuracao = colunaExiste('planos', 'duracao_meses');
    $temAtivo   = colunaExiste('planos', 'ativo');
    $temStripe  = colunaExiste('planos', 'stripe_price_id');
    $temTrial   = colunaExiste('planos', 'dias_teste_gratis');
    $temWpp     = colunaExiste('planos', 'tem_whatsapp');
    $campos = ['nome','descricao','preco'];
    $vals   = [$nome, $descricao, $preco];
    if ($temDuracao) {
        $campos[] = 'duracao_meses';
        $vals[] = $duracaoMeses;
    }
    if ($temStripe && $priceId !== '') {
        $campos[] = 'stripe_price_id';
        $vals[] = $priceId;
    }
    if ($temTrial) {
        $campos[] = 'dias_teste_gratis';
        $vals[] = max(0, (int)$diasTeste);
    }
    if ($temWpp) {
        $campos[] = 'tem_whatsapp';
        $vals[] = $temWhatsapp ? 1 : 0;
    }
    if ($temAtivo) {
        $campos[] = 'ativo';
        $vals[] = 1;
    }
    $placeholders = implode(',', array_fill(0, count($campos), '?'));
    $sql = "INSERT INTO planos (" . implode(',', $campos) . ") VALUES ($placeholders)";
    return (int)$database->insert($sql, $vals);
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_planos') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    switch ($acao) {
        case 'listar':
            $inicio = $_GET['inicio'] ?? null;
            $fim = $_GET['fim'] ?? null;
            echo json_encode(listarPlanos($inicio, $fim));
            break;
        case 'criar':
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $preco = isset($_POST['preco']) ? floatval($_POST['preco']) : null;
            $duracao = (int)($_POST['duracao_meses'] ?? 0);
            $priceId = trim($_POST['stripe_price_id'] ?? '');
            $diasTeste = max(0, (int)($_POST['dias_teste_gratis'] ?? 0));
            $temWhatsapp = !empty($_POST['tem_whatsapp']) ? 1 : 0;
            if ($nome === '' || $preco === null || $preco < 0 || $duracao <= 0) {
                http_response_code(400);
                echo json_encode(['erro' => 'dados_invalidos']);
                break;
            }
            $novoId = criarPlano($nome, $descricao, $preco, $duracao, $priceId, $diasTeste, $temWhatsapp);
            echo json_encode(['sucesso' => $novoId > 0, 'id' => $novoId]);
            break;
        case 'atualizar_price':
            $id = (int)($_POST['id'] ?? 0);
            $priceId = $_POST['stripe_price_id'] ?? '';
            if (!$id || !$priceId) {
                http_response_code(400);
                echo json_encode(['erro' => 'dados_invalidos']);
                break;
            }
            echo json_encode(['sucesso' => atualizarPriceId($id, $priceId)]);
            break;
        case 'atualizar_basico':
            $id = (int)($_POST['id'] ?? 0);
            $preco = isset($_POST['preco']) ? floatval($_POST['preco']) : null;
            $duracao = (int)($_POST['duracao_meses'] ?? 0);
            $temWhatsapp = isset($_POST['tem_whatsapp']) ? (int)$_POST['tem_whatsapp'] : null;
            if (!$id || $preco === null || $duracao <= 0) {
                http_response_code(400);
                echo json_encode(['erro' => 'dados_invalidos']);
                break;
            }
            echo json_encode(['sucesso' => atualizarBasicoPlano($id, $preco, $duracao, $temWhatsapp)]);
            break;
        case 'inativar':
            $id = (int)($_POST['id'] ?? 0);
            echo json_encode(['sucesso' => $id ? inativarPlano($id) : false]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}

?>
