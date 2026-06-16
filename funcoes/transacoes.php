<?php
// Funções relacionadas às transações - Versão com Banco de Dados

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/usuario.php';

// Inicializar conexão com o banco de dados
$database = new Database();

/**
 * Obtém todas as transações do banco de dados
 */
function obterTransacoes($usuario_id = 1) {
    global $database;
    
    $sql = "SELECT t.*, t.recorrencia, t.recorrencia_grupo_id,
                   c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo,
                   CASE WHEN t.observacoes LIKE 'TRANSFERENCIA:%' THEN 1 ELSE 0 END as eh_transferencia
            FROM transacoes t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            LEFT JOIN contas ct ON t.conta_id = ct.id
            WHERE t.usuario_id = ?
            ORDER BY t.data_transacao DESC, t.criado_em DESC";
    
    return $database->select($sql, [$usuario_id]);
}

/**
 * Adiciona uma nova transação
 */
function adicionarTransacao($tipo, $descricao, $valor, $categoria_id, $data, $conta_id, $observacoes = '', $usuario_id = 1, $recorrencia = null, $recorrencia_grupo_id = null, $origem_message_id = null) {
    global $database;

    $sql = "INSERT INTO transacoes (usuario_id, tipo, descricao, valor, categoria_id, conta_id, data_transacao, observacoes, recorrencia, recorrencia_grupo_id, origem_message_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [$usuario_id, $tipo, $descricao, floatval($valor), intval($categoria_id), intval($conta_id), $data, $observacoes, $recorrencia ?: null, $recorrencia_grupo_id ?: null, $origem_message_id ?: null];
    
    $id = $database->insert($sql, $params);
    
    if ($id) {
        // Retorna a transação criada
        $sql_select = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                              ct.nome as conta_nome, ct.tipo as conta_tipo
                       FROM transacoes t 
                       LEFT JOIN categorias c ON t.categoria_id = c.id 
                       LEFT JOIN contas ct ON t.conta_id = ct.id
                       WHERE t.id = ?";
        
        $resultado = $database->select($sql_select, [$id]);
        return $resultado[0] ?? null;
    }
    
    return false;
}

/**
 * Edita uma transação existente
 */
function editarTransacao($id, $dados_atualizados, $usuario_id = 1) {
    global $database;
    
    $campos = [];
    $params = [];
    
    if (isset($dados_atualizados['tipo'])) {
        $campos[] = "tipo = ?";
        $params[] = $dados_atualizados['tipo'];
    }
    if (isset($dados_atualizados['descricao'])) {
        $campos[] = "descricao = ?";
        $params[] = $dados_atualizados['descricao'];
    }
    if (isset($dados_atualizados['valor'])) {
        $campos[] = "valor = ?";
        $params[] = floatval($dados_atualizados['valor']);
    }
    if (isset($dados_atualizados['categoria_id'])) {
        $campos[] = "categoria_id = ?";
        $params[] = intval($dados_atualizados['categoria_id']);
    }
    if (isset($dados_atualizados['conta_id'])) {
        $campos[] = "conta_id = ?";
        $params[] = intval($dados_atualizados['conta_id']);
    }
    if (isset($dados_atualizados['data_transacao']) || isset($dados_atualizados['data'])) {
        $campos[] = "data_transacao = ?";
        $params[] = $dados_atualizados['data_transacao'] ?? $dados_atualizados['data'];
    }
    if (isset($dados_atualizados['observacoes'])) {
        $campos[] = "observacoes = ?";
        $params[] = $dados_atualizados['observacoes'];
    }
    
    if (empty($campos)) {
        return false;
    }
    
    $campos[] = "atualizado_em = NOW()";
    $params[] = intval($id);
    $params[] = $usuario_id;
    
    $sql = "UPDATE transacoes SET " . implode(', ', $campos) . " WHERE id = ? AND usuario_id = ?";
    
    return $database->update($sql, $params) > 0;
}

/**
 * Exclui uma transação
 */
function excluirTransacao($id, $usuario_id = 1) {
    global $database;
    
    $sql = "DELETE FROM transacoes WHERE id = ? AND usuario_id = ?";
    return $database->delete($sql, [$id, $usuario_id]) > 0;
}

/**
 * Calcula resumo financeiro
 */
function calcularResumo($usuario_id = 1) {
    global $database;
    
    $sql = "SELECT 
                SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
                SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas,
                (SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) - 
                 SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END)) as saldo_atual
            FROM transacoes 
            WHERE usuario_id = ? AND (observacoes IS NULL OR observacoes NOT LIKE 'TRANSFERENCIA:%')";
    
    $resultado = $database->select($sql, [$usuario_id]);
    
    if (!empty($resultado)) {
        return [
            'total_receitas' => floatval($resultado[0]['total_receitas'] ?? 0),
            'total_despesas' => floatval($resultado[0]['total_despesas'] ?? 0),
            'saldo_atual' => floatval($resultado[0]['saldo_atual'] ?? 0),
            'ultima_atualizacao' => date('Y-m-d H:i:s')
        ];
    }
    
    return [
        'total_receitas' => 0,
        'total_despesas' => 0,
        'saldo_atual' => 0,
        'ultima_atualizacao' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtém transações por período
 */
function obterTransacoesPorPeriodo($data_inicio, $data_fim, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo,
                   CASE WHEN t.observacoes LIKE 'TRANSFERENCIA:%' THEN 1 ELSE 0 END as eh_transferencia
            FROM transacoes t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            LEFT JOIN contas ct ON t.conta_id = ct.id
            WHERE t.usuario_id = ? AND t.data_transacao BETWEEN ? AND ?
            ORDER BY t.data_transacao DESC, t.criado_em DESC";
    
    return $database->select($sql, [$usuario_id, $data_inicio, $data_fim]);
}

/**
 * Obtém transações por categoria
 */
function obterTransacoesPorCategoria($categoria_id, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo,
                   CASE WHEN t.observacoes LIKE 'TRANSFERENCIA:%' THEN 1 ELSE 0 END as eh_transferencia
            FROM transacoes t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            LEFT JOIN contas ct ON t.conta_id = ct.id
            WHERE t.usuario_id = ? AND t.categoria_id = ?
            ORDER BY t.data_transacao DESC, t.criado_em DESC";
    
    return $database->select($sql, [$usuario_id, $categoria_id]);
}

/**
 * Calcula total de receitas por mês
 */
function calcularTotalReceitas($mes, $ano, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT COALESCE(SUM(valor), 0) as total
            FROM transacoes 
            WHERE usuario_id = ? AND tipo = 'receita' 
            AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
            AND (observacoes IS NULL OR observacoes NOT LIKE 'TRANSFERENCIA:%')";
    
    $resultado = $database->select($sql, [$usuario_id, $mes, $ano]);
    return floatval($resultado[0]['total'] ?? 0);
}

/**
 * Calcula total de despesas por mês
 */
function calcularTotalDespesas($mes, $ano, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT COALESCE(SUM(valor), 0) as total
            FROM transacoes 
            WHERE usuario_id = ? AND tipo = 'despesa' 
            AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
            AND (observacoes IS NULL OR observacoes NOT LIKE 'TRANSFERENCIA:%')";
    
    $resultado = $database->select($sql, [$usuario_id, $mes, $ano]);
    return floatval($resultado[0]['total'] ?? 0);
}

/**
 * Obtém despesas por categoria
 */
function obterDespesasPorCategoria($mes, $ano, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT c.id as categoria_id, c.nome as categoria_nome, c.cor as categoria_cor,
                   COALESCE(SUM(t.valor), 0) as total,
                   COUNT(t.id) as quantidade_transacoes
            FROM categorias c
            LEFT JOIN transacoes t ON c.id = t.categoria_id 
                AND t.tipo = 'despesa' 
                AND t.usuario_id = ?
                AND MONTH(t.data_transacao) = ? 
                AND YEAR(t.data_transacao) = ?
                AND (t.observacoes IS NULL OR t.observacoes NOT LIKE 'TRANSFERENCIA:%')
            WHERE c.tipo = 'despesa' AND c.ativa = 1
            GROUP BY c.id, c.nome, c.cor
            HAVING total > 0
            ORDER BY total DESC";
    
    return $database->select($sql, [$usuario_id, $mes, $ano]);
}

/**
 * Obtém transações por mês
 */
function obterTransacoesPorMes($mes, $ano, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo,
                   CASE WHEN t.observacoes LIKE 'TRANSFERENCIA:%' THEN 1 ELSE 0 END as eh_transferencia
            FROM transacoes t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            LEFT JOIN contas ct ON t.conta_id = ct.id
            WHERE t.usuario_id = ? 
            AND MONTH(t.data_transacao) = ? AND YEAR(t.data_transacao) = ?
            ORDER BY t.data_transacao DESC, t.criado_em DESC";
    
    return $database->select($sql, [$usuario_id, $mes, $ano]);
}

/**
 * Calcula saldo total
 */
function calcularSaldoTotal($usuario_id = 1) {
    $resumo = calcularResumo($usuario_id);
    return $resumo['saldo_atual'];
}

function buscarTransacaoPorOrigemMessageId($usuario_id, $origem_message_id) {
    global $database;
    if (!$usuario_id || !$origem_message_id) {
        return null;
    }

    $sql = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo
            FROM transacoes t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            LEFT JOIN contas ct ON t.conta_id = ct.id
            WHERE t.usuario_id = ? AND t.origem_message_id = ?
            ORDER BY t.id DESC
            LIMIT 1";
    $resultado = $database->select($sql, [$usuario_id, $origem_message_id]);
    return $resultado[0] ?? null;
}

function obterContextoWhatsappPendente($usuario_id, $chave_origem) {
    global $database;
    $sql = "SELECT *
            FROM whatsapp_contextos
            WHERE usuario_id = ? AND chave_origem = ? AND estado = 'pendente' AND expira_em > NOW()
            ORDER BY id DESC
            LIMIT 1";
    $itens = $database->select($sql, [$usuario_id, $chave_origem]);
    if (empty($itens)) {
        return null;
    }

    $ctx = $itens[0];
    if (!empty($ctx['payload_parcial'])) {
        $decoded = json_decode($ctx['payload_parcial'], true);
        $ctx['payload_parcial'] = is_array($decoded) ? $decoded : [];
    } else {
        $ctx['payload_parcial'] = [];
    }
    return $ctx;
}

function salvarContextoWhatsapp($usuario_id, $chave_origem, $payload_parcial, $estado = 'pendente', $ttl_minutos = 20) {
    global $database;
    $payload_json = json_encode($payload_parcial, JSON_UNESCAPED_UNICODE);
    $ttl = max(1, intval($ttl_minutos));

    $sql = "INSERT INTO whatsapp_contextos (usuario_id, canal, chave_origem, estado, payload_parcial, expira_em, criado_em, atualizado_em)
            VALUES (?, 'whatsapp', ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              estado = VALUES(estado),
              payload_parcial = VALUES(payload_parcial),
              expira_em = VALUES(expira_em),
              atualizado_em = NOW()";
    return $database->query($sql, [$usuario_id, $chave_origem, $estado, $payload_json, $ttl]);
}

function limparContextoWhatsapp($usuario_id, $chave_origem) {
    global $database;
    $sql = "UPDATE whatsapp_contextos
            SET estado = 'concluido', atualizado_em = NOW()
            WHERE usuario_id = ? AND chave_origem = ? AND estado = 'pendente'";
    return $database->update($sql, [$usuario_id, $chave_origem]);
}

function expirarContextosWhatsappAntigos($usuario_id = null) {
    global $database;
    if ($usuario_id) {
        $sql = "UPDATE whatsapp_contextos
                SET estado = 'expirado', atualizado_em = NOW()
                WHERE usuario_id = ? AND estado = 'pendente' AND expira_em <= NOW()";
        return $database->update($sql, [$usuario_id]);
    }

    $sql = "UPDATE whatsapp_contextos
            SET estado = 'expirado', atualizado_em = NOW()
            WHERE estado = 'pendente' AND expira_em <= NOW()";
    return $database->update($sql);
}

function logMetricaWhatsapp($evento, $dados = []) {
    $payload = [
        'canal' => 'whatsapp',
        'evento' => $evento,
        'timestamp' => date('c'),
        'dados' => $dados
    ];
    error_log('WHATSAPP_METRICA ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
}

/**
 * Salva transação (adicionar ou editar)
 */
function salvarTransacao($dados, $usuario_id = 1) {
    if (isset($dados['id']) && $dados['id'] > 0) {
        // Editar transação existente
        return editarTransacao($dados['id'], $dados, $usuario_id);
    } else {
        $origem_message_id = trim((string)($dados['origem_message_id'] ?? ''));
        if ($origem_message_id !== '') {
            $transacaoExistente = buscarTransacaoPorOrigemMessageId($usuario_id, $origem_message_id);
            if (!empty($transacaoExistente)) {
                return $transacaoExistente;
            }
        }

        // Adicionar nova(s) transação(ões)
        $tipo = $dados['tipo'] ?? '';
        $descricao = $dados['descricao'] ?? '';
        $valor = $dados['valor'] ?? 0;
        $categoria_id = $dados['categoria_id'] ?? null;
        $data_base = $dados['data_transacao'] ?? $dados['data'] ?? date('Y-m-d');
        $conta_origem_id = $dados['conta_id'] ?? $dados['conta'] ?? null; // Compatibilidade
        $conta_destino_id = $dados['conta_destino_id'] ?? null;
        $observacoes_base = $dados['observacoes'] ?? '';

        // Dados de recorrência
        $recorrencia = $dados['recorrencia'] ?? '';
        $repeticoes = ($recorrencia && isset($dados['repeticoes'])) ? intval($dados['repeticoes']) : 1;
        $dias_vencimento = isset($dados['dias_vencimento']) ? $dados['dias_vencimento'] : null; // Array de dias
        $dias_semana = isset($dados['dias_semana']) ? $dados['dias_semana'] : null; // Array de dias da semana
        
        if ($repeticoes < 1) $repeticoes = 1;
        if ($repeticoes > 120) $repeticoes = 120; // Limite de segurança

        $primeiro_resultado = null;
        global $database;

        // Gerar UUID de grupo para recorrências com mais de 1 repetição
        $recorrencia_grupo_id = null;
        if ($recorrencia && $repeticoes > 1) {
            $recorrencia_grupo_id = sprintf(
                '%08x-%04x-%04x-%04x-%12s',
                mt_rand(0, 0xffffffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                bin2hex(random_bytes(6))
            );
        }

        // Loop para criar transações (Meses ou Semanas)
        for ($i = 0; $i < $repeticoes; $i++) {
            
            // Definir os dias a serem processados neste ciclo
            $dias_do_ciclo = [];
            
            try {
                if ($recorrencia === 'mensal' && is_array($dias_vencimento) && !empty($dias_vencimento)) {
                    // Para cada dia de vencimento selecionado
                    foreach ($dias_vencimento as $dia_venc) {
                        $dateObj = new DateTime($data_base);
                        $dateObj->modify("+$i month");
                        $ano = $dateObj->format('Y');
                        $mes = $dateObj->format('m');
                        $ultimo_dia = date('t', strtotime("$ano-$mes-01"));
                        $dia_final = min($dia_venc, $ultimo_dia);
                        $dateObj->setDate($ano, $mes, $dia_final);
                        $dias_do_ciclo[] = $dateObj->format('Y-m-d');
                    }
                } elseif ($recorrencia === 'semanal' && is_array($dias_semana) && !empty($dias_semana)) {
                    // Para cada dia da semana selecionado
                    foreach ($dias_semana as $dia_sem) {
                        $dateObj = new DateTime($data_base);
                        // A data base é o ponto de partida.
                        // Se $i=0, estamos na semana inicial.
                        // Precisamos encontrar a data que corresponde ao dia da semana desejado NAQUELA semana da iteração $i.
                        
                        // Primeiro, vamos para a semana correta
                        $dateObj->modify("+$i week");
                        
                        // Agora, ajustamos para o dia da semana desejado dentro dessa semana
                        // A lógica "modify" do PHP para dias da semana é relativa.
                        // "monday this week" ou "next monday".
                        
                        // Abordagem mais segura:
                        // Pegar o domingo da semana calculada e somar os dias
                        // Dia 0 (Dom) a 6 (Sab)
                        
                        // Obter o dia da semana atual da data calculada
                        $w = $dateObj->format('w');
                        // Voltar para o domingo (início da semana)
                        $dateObj->modify("-$w day");
                        // Avançar para o dia desejado
                        $dateObj->modify("+$dia_sem day");
                        
                        // Opcional: Se estivermos na semana 0 ($i=0), e a data calculada for ANTERIOR a data_base,
                        // significa que o dia da semana já passou. Devemos ignorar?
                        // Ex: Hoje é Quarta (data base). Usuário escolhe Seg e Sex.
                        // Semana 0:
                        //   Segunda (passado) -> Deve ser criada? Normalmente sim, se a ideia é "nessa semana".
                        //   Mas se for para criar apenas futuras, deveríamos filtrar.
                        //   Pela simplicidade e para manter histórico coerente com a regra, vamos criar todas da semana.
                        //   OU, se preferir estritamente futuro:
                        //   if ($i === 0 && $dateObj->format('Y-m-d') < $data_base) continue;
                        
                        $dias_do_ciclo[] = $dateObj->format('Y-m-d');
                    }
                } else {
                    // Lógica padrão (um único dia por ciclo, ou recorrência simples)
                    $dateObj = new DateTime($data_base);
                    if ($i > 0) {
                         switch ($recorrencia) {
                            case 'diario': $dateObj->modify("+$i day"); break;
                            case 'semanal': $dateObj->modify("+$i week"); break; // Fallback se não tiver array
                            case 'mensal': $dateObj->modify("+$i month"); break; // Fallback
                            case 'anual': $dateObj->modify("+$i year"); break;
                        }
                    }
                    $dias_do_ciclo[] = $dateObj->format('Y-m-d');
                }
            } catch (Exception $e) {
                continue;
            }
            
            // Ordenar dias do ciclo para inserir na ordem cronológica
            sort($dias_do_ciclo);
            
            // Processar cada data calculada
            foreach ($dias_do_ciclo as $idx_dia => $data_atual_ciclo) {
                // Descrição original sem contador (X/Y) para recorrências, pois não é parcelamento
                $desc_atual = $descricao;

                // Caso especial: transferência
                if ($tipo === 'transferencia' && $conta_origem_id && $conta_destino_id && intval($conta_origem_id) !== intval($conta_destino_id)) {
                    try {
                        $database->beginTransaction();
                        $saida = adicionarTransacao('despesa', $desc_atual, $valor, $categoria_id, $data_atual_ciclo, $conta_origem_id, 'TRANSFERENCIA:SAIDA' . ($observacoes_base ? " - $observacoes_base" : ""), $usuario_id, $recorrencia ?: null, $recorrencia_grupo_id, $origem_message_id);
                        $entrada = adicionarTransacao('receita', $desc_atual, $valor, $categoria_id, $data_atual_ciclo, $conta_destino_id, 'TRANSFERENCIA:ENTRADA' . ($observacoes_base ? " - $observacoes_base" : ""), $usuario_id, $recorrencia ?: null, $recorrencia_grupo_id, $origem_message_id);

                        if ($saida === false || $entrada === false) {
                            $database->rollback();
                            if ($i === 0 && $idx_dia === 0) return false;
                            continue;
                        }
                        $database->commit();
                        $resultado_atual = ['transferencia' => ['saida' => $saida, 'entrada' => $entrada]];
                    } catch (Exception $e) {
                        try { $database->rollback(); } catch (Exception $ignored) {}
                        if ($i === 0 && $idx_dia === 0) throw $e;
                        continue;
                    }
                } else {
                    $tipo_final = ($tipo === 'transferencia') ? 'despesa' : $tipo;
                    $resultado_atual = adicionarTransacao($tipo_final, $desc_atual, $valor, $categoria_id, $data_atual_ciclo, $conta_origem_id, $observacoes_base, $usuario_id, $recorrencia ?: null, $recorrencia_grupo_id, $origem_message_id);
                }
                
                if ($primeiro_resultado === null) $primeiro_resultado = $resultado_atual;
            }
        }

        return $primeiro_resultado;
    }
}

/**
 * Calcula totais do mês (receitas, despesas, saldo)
 */
function calcularTotaisMes($usuario_id, $mes_ano) {
    $partes = explode('-', $mes_ano);
    $ano = intval($partes[0]);
    $mes = intval($partes[1]);
    
    $receitas = calcularTotalReceitas($mes, $ano, $usuario_id);
    $despesas = calcularTotalDespesas($mes, $ano, $usuario_id);
    $saldo = $receitas - $despesas;
    
    return [
        'receitas' => $receitas,
        'despesas' => $despesas,
        'saldo' => $saldo
    ];
}

/**
 * Resolve o usuario_id via sessão PHP ou token de bot (n8n/WhatsApp).
 * O token é gerado como md5('whatsapp_bot_' . $uid . '_' . date('Y-m-d'))
 * e é renovado diariamente. Usado pelo n8n como ?uid=X&token=Y.
 */
function resolverUsuarioIdApi() {
    if (usuarioLogado()) {
        return obterUsuarioId();
    }
    $uid   = intval($_GET['uid'] ?? 0);
    $token = $_GET['token'] ?? '';
    if ($uid > 0 && $token !== '' && hash_equals(md5('whatsapp_bot_' . $uid . '_' . date('Y-m-d')), $token)) {
        return $uid;
    }
    return 0;
}

/**
 * API para salvar transação (POST) - deve vir antes da API GET
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['api']) && $_GET['api'] == 'transacoes') {
    header('Content-Type: application/json');

    $acao = $_GET['acao'] ?? '';
    $usuario_id = resolverUsuarioIdApi();

    if (!$usuario_id) {
        http_response_code(401);
        echo json_encode(['erro' => 'Não autorizado']);
        exit;
    }
    
    // Debug: log dos dados recebidos
    error_log("POST API transacoes - Acao: " . $acao);
    $input = file_get_contents('php://input');
    error_log("POST API transacoes - Input: " . $input);
    
    switch ($acao) {
        case 'salvar':
            $dados = json_decode($input, true);
            error_log("POST API transacoes - Dados decodificados: " . print_r($dados, true));
            
            if (!$dados) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados JSON inválidos']);
                exit;
            }
            
            try {
                $resultado = salvarTransacao($dados, $usuario_id);
                logMetricaWhatsapp('transacao_salvar', [
                    'usuario_id' => $usuario_id,
                    'origem_message_id' => $dados['origem_message_id'] ?? null,
                    'sucesso' => $resultado !== false
                ]);
                echo json_encode(['sucesso' => $resultado !== false, 'resultado' => $resultado]);
            } catch (Exception $e) {
                error_log("Erro ao salvar transação: " . $e->getMessage());
                logMetricaWhatsapp('transacao_erro', [
                    'usuario_id' => $usuario_id,
                    'erro' => $e->getMessage()
                ]);
                http_response_code(500);
                echo json_encode(['erro' => $e->getMessage()]);
            }
            break;

        case 'contexto_salvar':
            $dados = json_decode($input, true);
            if (!$dados) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados JSON inválidos']);
                exit;
            }

            $chave_origem = trim((string)($dados['chave_origem'] ?? ''));
            if ($chave_origem === '') {
                http_response_code(400);
                echo json_encode(['erro' => 'chave_origem é obrigatória']);
                exit;
            }

            $payload_parcial = is_array($dados['payload_parcial'] ?? null) ? $dados['payload_parcial'] : [];
            $estado = trim((string)($dados['estado'] ?? 'pendente'));
            $ttl_minutos = intval($dados['ttl_minutos'] ?? 20);
            if ($estado !== 'pendente') {
                logMetricaWhatsapp('contexto_salvar_ignorado', ['usuario_id' => $usuario_id, 'chave_origem' => $chave_origem]);
                echo json_encode(['sucesso' => true, 'ignorado' => true]);
                break;
            }
            salvarContextoWhatsapp($usuario_id, $chave_origem, $payload_parcial, $estado ?: 'pendente', $ttl_minutos);
            logMetricaWhatsapp('contexto_salvo', ['usuario_id' => $usuario_id, 'chave_origem' => $chave_origem, 'ttl_minutos' => $ttl_minutos]);
            echo json_encode(['sucesso' => true]);
            break;

        case 'contexto_limpar':
            $dados = json_decode($input, true);
            if (!$dados) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados JSON inválidos']);
                exit;
            }

            $chave_origem = trim((string)($dados['chave_origem'] ?? ''));
            if ($chave_origem === '') {
                http_response_code(400);
                echo json_encode(['erro' => 'chave_origem é obrigatória']);
                exit;
            }

            limparContextoWhatsapp($usuario_id, $chave_origem);
            logMetricaWhatsapp('contexto_limpo', ['usuario_id' => $usuario_id, 'chave_origem' => $chave_origem]);
            echo json_encode(['sucesso' => true]);
            break;
            
        case 'excluir':
            $dados = json_decode($input, true);
            if (!$dados) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados JSON inválidos']);
                exit;
            }
            
            try {
                $resultado = excluirTransacao($dados['id'], $usuario_id);
                echo json_encode(['sucesso' => $resultado]);
            } catch (Exception $e) {
                error_log("Erro ao excluir transação: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['erro' => $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não reconhecida: ' . $acao]);
    }
    
    exit;
}

/**
 * API para obter transações (GET)
 */
if (isset($_GET['api']) && $_GET['api'] == 'transacoes') {
    header('Content-Type: application/json');

    $usuario_id = resolverUsuarioIdApi();
    
    $acao = $_GET['acao'] ?? '';
    
    switch ($acao) {
        case 'listar':
            $transacoes = obterTransacoes($usuario_id);
            echo json_encode($transacoes);
            break;
            
        case 'obter_por_mes':
            $mes_ano = $_GET['mes'] ?? date('Y-m');
            $partes = explode('-', $mes_ano);
            $ano = intval($partes[0]);
            $mes = intval($partes[1]);
            $transacoes = obterTransacoesPorMes($mes, $ano, $usuario_id);
            echo json_encode($transacoes);
            break;
            
        case 'saldo_total':
            $saldo = calcularSaldoTotal($usuario_id);
            echo json_encode(['saldo' => $saldo]);
            break;
            
        case 'totais_mes':
            $mes = $_GET['mes'] ?? date('Y-m');
            $totais = calcularTotaisMes($usuario_id, $mes);
            echo json_encode($totais);
            break;

        case 'contexto_obter':
            $chave_origem = trim((string)($_GET['chave_origem'] ?? ''));
            if ($chave_origem === '') {
                http_response_code(400);
                echo json_encode(['erro' => 'chave_origem é obrigatória']);
                exit;
            }
            expirarContextosWhatsappAntigos($usuario_id);
            $contexto = obterContextoWhatsappPendente($usuario_id, $chave_origem);
            logMetricaWhatsapp('contexto_obter', [
                'usuario_id' => $usuario_id,
                'chave_origem' => $chave_origem,
                'tem_contexto' => !empty($contexto)
            ]);
            echo json_encode([
                'sucesso' => true,
                'tem_contexto' => !empty($contexto),
                'contexto' => $contexto
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não reconhecida']);
    }
    
    exit;
}

/**
 * API para salvar categorias (POST)
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['api']) && $_GET['api'] == 'categorias') {
    require_once 'categorias.php';
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    $usuario_id = resolverUsuarioIdApi();

    if (!$usuario_id) {
        http_response_code(401);
        echo json_encode(['erro' => 'Não autorizado']);
        exit;
    }
    
    switch ($acao) {
        case 'salvar':
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (!$dados) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados JSON inválidos']);
                exit;
            }
            
            try {
                $nome = $dados['nome'] ?? '';
                $tipo = $dados['tipo'] ?? '';
                $icone = $dados['icone'] ?? 'fas fa-tag';
                $cor = $dados['cor'] ?? '#666666';
                $id_categoria = isset($dados['id']) ? intval($dados['id']) : 0;

                if (empty($nome) || empty($tipo)) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'Nome e tipo são obrigatórios']);
                    exit;
                }

                if ($id_categoria > 0) {
                    // Atualizar categoria existente
                    $atualizado = editarCategoria($id_categoria, [
                        'nome' => $nome,
                        'tipo' => $tipo,
                        'icone' => $icone,
                        'cor' => $cor
                    ]);
                    echo json_encode(['sucesso' => $atualizado !== false, 'id' => $id_categoria]);
                } else {
                    // Verificar duplicidade antes de inserir
                    try {
                        $verificacao = $database->select(
                            "SELECT COUNT(*) AS total FROM categorias WHERE usuario_id = ? AND nome = ? AND tipo = ? AND ativa = 1",
                            [$usuario_id, $nome, $tipo]
                        );
                        $jaExiste = intval($verificacao[0]['total'] ?? 0) > 0;
                        if ($jaExiste) {
                            http_response_code(409);
                            echo json_encode(['erro' => 'Categoria já existe para este usuário e tipo']);
                            exit;
                        }
                    } catch (Exception $e) {
                        // Se der erro na verificação, seguimos com o insert e tratamos no catch externo
                    }

                    $resultado = adicionarCategoria($nome, $tipo, $icone, $cor, $usuario_id);
                    echo json_encode(['sucesso' => $resultado !== false, 'id' => $resultado]);
                }
            } catch (Exception $e) {
                error_log("Erro ao salvar categoria: " . $e->getMessage());
                // Detectar violação de chave única
                $mensagem = strpos($e->getMessage(), 'SQLSTATE[23000]') !== false
                    ? 'Categoria já existe para este usuário e tipo'
                    : $e->getMessage();
                http_response_code(strpos($e->getMessage(), 'SQLSTATE[23000]') !== false ? 409 : 500);
                echo json_encode(['erro' => $mensagem]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não reconhecida: ' . $acao]);
    }
    
    exit;
}

/**
 * API para obter categorias (GET)
 */
if (isset($_GET['api']) && $_GET['api'] == 'categorias') {
    require_once 'categorias.php';
    header('Content-Type: application/json');

    $usuario_id = resolverUsuarioIdApi();
    
    $acao = $_GET['acao'] ?? '';
    
    switch ($acao) {
        case 'listar':
            // Permitir filtro por tipo via query string
            $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
            if ($tipo) {
                echo json_encode(obterCategorias($tipo, $usuario_id));
            } else {
                echo json_encode(obterCategoriasPorUsuario($usuario_id));
            }
            break;
            
        case 'obter_por_id':
            $id = $_GET['id'] ?? 0;
            echo json_encode(obterCategoriaPorId($id));
            break;
    }
    
    exit;
}



// Função para compatibilidade com código antigo
function lerTransacoes() {
    // Usar usuário da sessão quando disponível; se não houver, não carregar dados
    $usuario_id = usuarioLogado() ? obterUsuarioId() : 0;

    if ($usuario_id > 0) {
        $transacoes = obterTransacoes($usuario_id);
        $totais = calcularTotaisMes($usuario_id, date('Y-m'));
        $resumo = [
            'total_receitas' => $totais['receitas'],
            'total_despesas' => $totais['despesas'],
            'saldo_atual'    => $totais['saldo'],
        ];
    } else {
        $transacoes = [];
        $resumo = [
            'total_receitas' => 0,
            'total_despesas' => 0,
            'saldo_atual' => 0,
        ];
    }

    return [
        'transacoes' => $transacoes,
        'proximo_id' => count($transacoes) + 1,
        'resumo' => $resumo
    ];
}
?>
