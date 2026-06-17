<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/usuario.php';

function cfg() {
    global $database;
    $rows = $database->select("SELECT chave, valor FROM configuracoes_sistema");
    $m = [];
    foreach ($rows as $r) { $m[$r['chave']] = $r['valor']; }
    return $m;
}

if (!function_exists('listarAssinaturas')) {
function listarAssinaturas($inicio = null, $fim = null) {
    global $database;
    // detectar coluna de criação
    $cols = $database->select("SELECT COLUMN_NAME as c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'assinaturas'");
    $cmap = array_column($cols, 'c');
    $campoData = null;
    foreach (['criada_em','data_criacao','created_at','criado_em','data_assinatura'] as $c) {
        if (in_array($c, $cmap, true)) { $campoData = $c; break; }
    }
    $select = "SELECT a.id, a.usuario_id, COALESCE(u.nome, CONCAT('#', a.usuario_id)) AS usuario_nome, a.plano_id, COALESCE(p.nome, CONCAT('#', a.plano_id)) AS plano_nome, a.status, a.gateway_transacao_id";
    if ($campoData) { $select .= ", a.$campoData AS criada_em"; }
    $where = "";
    $params = [];
    if ($campoData && $inicio && $fim) {
        $where = " WHERE DATE(a.$campoData) BETWEEN ? AND ?";
        $params = [$inicio, $fim];
    }
    $sql = $select . " FROM assinaturas a LEFT JOIN usuarios u ON u.id = a.usuario_id LEFT JOIN planos p ON p.id = a.plano_id" . $where . " ORDER BY a.id DESC";
    return $database->select($sql, $params);
}
}

if (!function_exists('revogarAssinatura')) {
function revogarAssinatura($id) {
    global $database;
    $ass = $database->select("SELECT id, gateway_transacao_id FROM assinaturas WHERE id = ?", [$id]);
    if (empty($ass)) return false;
    $subscriptionId = $ass[0]['gateway_transacao_id'];
    $c = cfg();
    if (!empty($subscriptionId) && ($c['gateway_padrao'] ?? 'stripe') === 'stripe' && !empty($c['stripe_api_key'])) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/subscriptions/" . urlencode($subscriptionId));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $c['stripe_api_key']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        curl_close($ch);
    }
    $database->update("UPDATE assinaturas SET status = 'cancelada', atualizado_em = NOW() WHERE id = ?", [$id]);
    return true;
}
}

if (!function_exists('atualizarAssinatura')) {
function atualizarAssinatura($id, $status, $plano_id) {
    global $database;
    $statusValidos = ['ativa', 'cancelada', 'pendente', 'suspensa'];
    if (!in_array($status, $statusValidos, true)) return false;
    $plano_id = (int)$plano_id;
    $set = "status = ?";
    $params = [$status];
    if ($plano_id > 0) { $set .= ", plano_id = ?"; $params[] = $plano_id; }
    // atualizado_em é opcional – tenta, ignora se coluna não existir
    try {
        $database->update("UPDATE assinaturas SET $set, atualizado_em = NOW() WHERE id = ?", array_merge($params, [$id]));
    } catch (\Exception $e) {
        $database->update("UPDATE assinaturas SET $set WHERE id = ?", array_merge($params, [$id]));
    }
    return true;
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_assinaturas') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    switch ($acao) {
        case 'listar':
            $inicio = $_GET['inicio'] ?? null;
            $fim    = $_GET['fim']    ?? null;
            echo json_encode(listarAssinaturas($inicio, $fim));
            break;
        case 'revogar':
            $id = (int)($_POST['id'] ?? 0);
            echo json_encode(['sucesso' => $id ? revogarAssinatura($id) : false]);
            break;
        case 'atualizar':
            $id      = (int)($_POST['id']      ?? 0);
            $status  = trim($_POST['status']   ?? '');
            $plano   = (int)($_POST['plano_id'] ?? 0);
            echo json_encode(['sucesso' => $id ? atualizarAssinatura($id, $status, $plano) : false]);
            break;
        case 'criar':
            $usuario_id = (int)($_POST['usuario_id'] ?? 0);
            $plano_id   = (int)($_POST['plano_id']   ?? 0);
            $status     = trim($_POST['status']      ?? 'ativa');
            $statusValidos = ['ativa', 'cancelada', 'pendente', 'suspensa'];
            if (!$usuario_id || !$plano_id || !in_array($status, $statusValidos, true)) {
                http_response_code(400);
                echo json_encode(['erro' => 'dados_invalidos']);
                break;
            }
            $cols2 = $database->select("SELECT COLUMN_NAME as c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'assinaturas'");
            $cmap2 = array_column($cols2, 'c');
            $campoData2 = null;
            foreach (['criada_em','data_criacao','created_at','criado_em','data_assinatura'] as $c) {
                if (in_array($c, $cmap2, true)) { $campoData2 = $c; break; }
            }
            $campos = ['usuario_id', 'plano_id', 'status'];
            $vals   = [$usuario_id, $plano_id, $status];
            if ($campoData2) { $campos[] = $campoData2; $vals[] = date('Y-m-d H:i:s'); }
            $ph = implode(',', array_fill(0, count($campos), '?'));
            $novoId = $database->insert("INSERT INTO assinaturas (" . implode(',', $campos) . ") VALUES ($ph)", $vals);
            echo json_encode(['sucesso' => $novoId > 0, 'id' => $novoId]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}

?>
