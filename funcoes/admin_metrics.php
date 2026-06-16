<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('resumoMetrics')) {
function resumoMetrics($inicio, $fim) {
    global $database;
    $inicio = $inicio ?: date('Y-m-01');
    $fim = $fim ?: date('Y-m-t');

    $receita = $database->select(
        "SELECT COALESCE(SUM(valor_pago),0) AS total FROM assinaturas WHERE atualizado_em BETWEEN ? AND ?",
        [$inicio . ' 00:00:00', $fim . ' 23:59:59']
    );
    $cancelamentos = $database->select(
        "SELECT COUNT(*) AS total FROM assinaturas WHERE status = 'cancelada' AND data_fim BETWEEN ? AND ?",
        [$inicio, $fim]
    );
    $novas = $database->select(
        "SELECT COUNT(*) AS total FROM assinaturas WHERE data_inicio BETWEEN ? AND ?",
        [$inicio, $fim]
    );
    $ativas = $database->select(
        "SELECT COUNT(*) AS total FROM assinaturas WHERE status = 'ativa'", []
    );
    $usuariosNovos = $database->select(
        "SELECT COUNT(*) AS total FROM usuarios WHERE data_cadastro BETWEEN ? AND ?",
        [$inicio . ' 00:00:00', $fim . ' 23:59:59']
    );
    $usuariosInativados = $database->select(
        "SELECT COUNT(*) AS total FROM usuarios WHERE status = 'inativo' AND atualizado_em BETWEEN ? AND ?",
        [$inicio . ' 00:00:00', $fim . ' 23:59:59']
    );
    $planosTot   = $database->select("SELECT COUNT(*) AS total FROM planos", []);
    $planosAtivos = $database->select("SELECT COUNT(*) AS total FROM planos WHERE ativo = 1", []);

    $cfgRows = $database->select("SELECT chave, valor FROM configuracoes_sistema WHERE chave IN ('gateway_padrao','stripe_api_key')");
    $cfg = [];
    foreach ($cfgRows as $r) { $cfg[$r['chave']] = $r['valor']; }
    $gwNome = isset($cfg['gateway_padrao']) ? strtoupper($cfg['gateway_padrao']) : '—';
    $gwCon  = !empty($cfg['stripe_api_key']);

    return [
        'sucesso'             => true,
        'inicio'              => $inicio,
        'fim'                 => $fim,
        'receita_total'       => (float)$receita[0]['total'],
        'cancelamentos'       => (int)$cancelamentos[0]['total'],
        'novas_assinaturas'   => (int)$novas[0]['total'],
        'assinaturas_ativas'  => (int)$ativas[0]['total'],
        'usuarios_novos'      => (int)$usuariosNovos[0]['total'],
        'usuarios_inativados' => (int)$usuariosInativados[0]['total'],
        'gateway_nome'        => $gwNome,
        'gateway_conectado'   => $gwCon,
        'planos_total'        => (int)$planosTot[0]['total'],
        'planos_ativos'       => (int)$planosAtivos[0]['total'],
    ];
}
}

if (!function_exists('graficosMetrics')) {
function graficosMetrics($inicio, $fim) {
    global $database;
    // Sempre usar últimos 6 meses como range dos gráficos
    $fimDate   = $fim   ? new DateTime($fim)   : new DateTime();
    $inicioDate = clone $fimDate;
    $inicioDate->modify('-5 months');
    $inicioDate->modify('first day of this month');

    $inicioStr = $inicioDate->format('Y-m-01');
    $fimStr    = $fimDate->format('Y-m-t');

    // Gerar labels dos meses no range
    $meses = [];
    $cur = clone $inicioDate;
    $nomesMeses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    while ($cur <= $fimDate) {
        $meses[$cur->format('Y-m')] = $nomesMeses[(int)$cur->format('m') - 1] . '/' . $cur->format('y');
        $cur->modify('+1 month');
    }

    // Receita por mês
    $receitaRows = $database->select(
        "SELECT DATE_FORMAT(data_inicio,'%Y-%m') AS mes, COALESCE(SUM(valor_pago),0) AS valor
         FROM assinaturas WHERE data_inicio BETWEEN ? AND ? GROUP BY mes ORDER BY mes ASC",
        [$inicioStr, $fimStr]
    );
    $receitaMap = [];
    foreach ($receitaRows as $r) { $receitaMap[$r['mes']] = (float)$r['valor']; }

    // Novas e canceladas por mês
    $novasRows = $database->select(
        "SELECT DATE_FORMAT(data_inicio,'%Y-%m') AS mes, COUNT(*) AS total
         FROM assinaturas WHERE data_inicio BETWEEN ? AND ? GROUP BY mes ORDER BY mes ASC",
        [$inicioStr, $fimStr]
    );
    $novasMap = [];
    foreach ($novasRows as $r) { $novasMap[$r['mes']] = (int)$r['total']; }

    $canceladasRows = $database->select(
        "SELECT DATE_FORMAT(data_fim,'%Y-%m') AS mes, COUNT(*) AS total
         FROM assinaturas WHERE status = 'cancelada' AND data_fim BETWEEN ? AND ? GROUP BY mes ORDER BY mes ASC",
        [$inicioStr, $fimStr]
    );
    $canceladasMap = [];
    foreach ($canceladasRows as $r) { $canceladasMap[$r['mes']] = (int)$r['total']; }

    // Usuários novos por mês
    $usuariosRows = $database->select(
        "SELECT DATE_FORMAT(data_cadastro,'%Y-%m') AS mes, COUNT(*) AS total
         FROM usuarios WHERE data_cadastro BETWEEN ? AND ? GROUP BY mes ORDER BY mes ASC",
        [$inicioStr . ' 00:00:00', $fimStr . ' 23:59:59']
    );
    $usuariosMap = [];
    foreach ($usuariosRows as $r) { $usuariosMap[$r['mes']] = (int)$r['total']; }

    // Assinantes por plano (total ativo)
    $porPlanoRows = $database->select(
        "SELECT p.nome AS plano, COUNT(a.id) AS total
         FROM assinaturas a JOIN planos p ON p.id = a.plano_id
         WHERE a.status = 'ativa' GROUP BY a.plano_id, p.nome ORDER BY total DESC",
        []
    );

    // Montar arrays com todos os meses preenchidos (0 se não houver dado)
    $labels            = array_values($meses);
    $receitaMensal     = [];
    $assinaturasMensal = [];
    $usuariosMensal    = [];

    foreach (array_keys($meses) as $m) {
        $receitaMensal[]     = $receitaMap[$m]    ?? 0;
        $assinaturasMensal[] = [
            'novas'      => $novasMap[$m]      ?? 0,
            'canceladas' => $canceladasMap[$m] ?? 0,
        ];
        $usuariosMensal[]    = $usuariosMap[$m]   ?? 0;
    }

    return [
        'sucesso'              => true,
        'labels'               => $labels,
        'receita_mensal'       => $receitaMensal,
        'assinaturas_por_mes'  => $assinaturasMensal,
        'usuarios_por_mes'     => $usuariosMensal,
        'assinaturas_por_plano'=> $porPlanoRows,
    ];
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_metrics') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    switch ($acao) {
        case 'resumo':
            $inicio = $_GET['inicio'] ?? null;
            $fim = $_GET['fim'] ?? null;
            echo json_encode(resumoMetrics($inicio, $fim));
            break;
        case 'graficos':
            $inicio = $_GET['inicio'] ?? null;
            $fim    = $_GET['fim']    ?? null;
            echo json_encode(graficosMetrics($inicio, $fim));
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}
?>
