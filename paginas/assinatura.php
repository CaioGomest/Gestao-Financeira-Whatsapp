<?php
require_once __DIR__ . '/../funcoes/usuario.php';
require_once __DIR__ . '/../config/database.php';
verificarLogin();
global $database;

$usuarioId = obterUsuarioId();

// Planos ativos
$colsPlanos = $database->select("SELECT COLUMN_NAME as c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planos'");
$cmapPlanos = array_column($colsPlanos, 'c');
$selectWpp  = in_array('tem_whatsapp', $cmapPlanos) ? ', tem_whatsapp' : ', 0 AS tem_whatsapp';
$planos = $database->select("SELECT id, nome, descricao, preco, stripe_price_id, duracao_meses $selectWpp FROM planos WHERE ativo = 1 ORDER BY preco ASC");

// Assinatura atual com datas
$colsAss = $database->select("SELECT COLUMN_NAME as c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'assinaturas'");
$cmapAss = array_column($colsAss, 'c');
$selectData = in_array('data_inicio', $cmapAss) ? ", a.data_inicio" : ", NULL AS data_inicio";
$selectFim  = in_array('data_fim', $cmapAss)    ? ", a.data_fim"    : ", NULL AS data_fim";
$selectWppAss = in_array('tem_whatsapp', $cmapPlanos) ? ", p.tem_whatsapp" : ", 0 AS tem_whatsapp";
$assinRows = $database->select(
    "SELECT a.id, a.status, a.plano_id, a.metodo_pagamento, a.gateway_transacao_id, p.nome AS plano_nome, p.preco, p.duracao_meses $selectData $selectFim $selectWppAss
     FROM assinaturas a LEFT JOIN planos p ON p.id = a.plano_id
     WHERE a.usuario_id = ? ORDER BY a.id DESC LIMIT 1",
    [$usuarioId]
);
$assin = !empty($assinRows) ? $assinRows[0] : null;

$cfgRows = $database->select("SELECT chave, valor FROM configuracoes_sistema WHERE chave IN ('gateway_padrao','stripe_api_key')");
$cfg = [];
foreach ($cfgRows as $r) { $cfg[$r['chave']] = $r['valor']; }
$gatewayPadrao = $cfg['gateway_padrao'] ?? 'stripe';
$status = $_GET['status'] ?? '';

$planoAtualId = $assin ? (int)$assin['plano_id'] : 0;
$statusAtual  = $assin ? strtolower($assin['status']) : '';
$isAtiva = in_array($statusAtual, ['ativa', 'ativo', 'trialing']);
?>
<?php
$script_atual = basename($_SERVER['SCRIPT_NAME']);
$esta_no_index = ($script_atual === 'index.php');
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri_sem_query = strtok($uri, '?');
$base_app = $uri_sem_query;
if (substr($base_app, -10) === '/index.php') { $base_app = substr($base_app, 0, -10); }
$pos_paginas = strpos($base_app, '/paginas/');
if ($pos_paginas !== false) { $base_app = substr($base_app, 0, $pos_paginas); }
if (substr($base_app, -1) !== '/') { $base_app .= '/'; }
?>
<?php if (!$esta_no_index): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_app; ?>assets/css/style.css">
</head>
<body>
    <script>
    (function(){
        try{var escuro=localStorage.getItem('temaEscuro');if(escuro==='true'){document.body.classList.add('tema-escuro');}else{document.body.classList.add('tema-claro');}}
        catch(e){document.body.classList.add('tema-escuro');}
    })();
    </script>
<?php endif; ?>
<style>
.ass-page { display:flex; flex-direction:column; gap:20px; }

/* Current plan hero */
.ass-hero { background:var(--cor-fundo-secundario); border:1px solid var(--cor-borda); border-radius:20px; padding:24px; display:flex; flex-direction:column; gap:16px; }
.ass-hero-top { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.ass-hero-left { display:flex; align-items:center; gap:14px; }
.ass-plano-icone { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,rgba(245,166,35,.2),rgba(247,124,10,.1)); border:1px solid rgba(245,166,35,.3); display:flex; align-items:center; justify-content:center; font-size:1.3rem; color:#F5A623; flex-shrink:0; }
.ass-plano-icone.pro { background:linear-gradient(135deg,rgba(130,80,255,.2),rgba(100,50,240,.1)); border-color:rgba(130,80,255,.3); color:#a374ff; }
.ass-plano-icone.empresarial { background:linear-gradient(135deg,rgba(33,150,243,.2),rgba(10,100,200,.1)); border-color:rgba(33,150,243,.3); color:#64b5f6; }
.ass-plano-nome { font-size:1.1rem; font-weight:700; color:var(--cor-texto); }
.ass-plano-sub  { font-size:.83rem; color:var(--cor-texto-secundario); margin-top:2px; }

.ass-badge-atual { display:inline-flex; align-items:center; gap:5px; padding:5px 14px; border-radius:20px; font-size:.75rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; background:rgba(245,166,35,.12); border:1px solid rgba(245,166,35,.35); color:#F5A623; }
.ass-badge-ativa { background:rgba(76,175,80,.12); border-color:rgba(76,175,80,.35); color:#4CAF50; }
.ass-badge-pendente { background:rgba(255,193,7,.12); border-color:rgba(255,193,7,.35); color:#d4a017; }
.ass-badge-cancelada { background:rgba(244,67,54,.12); border-color:rgba(244,67,54,.3); color:#F44336; }
.ass-badge-sem-plano { background:rgba(150,150,150,.1); border-color:rgba(150,150,150,.25); color:var(--cor-texto-secundario); }

/* WhatsApp badge */
.ass-wpp-badge { display:inline-flex; align-items:center; gap:7px; padding:7px 14px; border-radius:20px; font-size:.83rem; font-weight:600; }
.ass-wpp-badge.sim { background:rgba(37,211,102,.1); border:1px solid rgba(37,211,102,.3); color:#25D366; }
.ass-wpp-badge.nao { background:rgba(150,150,150,.08); border:1px solid rgba(150,150,150,.18); color:var(--cor-texto-secundario); opacity:.6; }

/* Planos cards */
.ass-planos-titulo { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--cor-texto-secundario); opacity:.65; padding:0 2px; }
.ass-planos-grid { display:flex; flex-direction:column; gap:12px; }
.ass-plano-card { background:var(--cor-fundo-secundario); border:1px solid var(--cor-borda); border-radius:16px; padding:20px; transition:border-color .2s, transform .15s; }
.ass-plano-card:hover { border-color:rgba(245,166,35,.3); transform:translateY(-1px); }
.ass-plano-card.destaque { border-color:rgba(245,166,35,.4); background:linear-gradient(135deg,rgba(245,166,35,.04),var(--cor-fundo-secundario)); }
.ass-plano-card.is-atual { border-color:rgba(76,175,80,.35); }
.ass-plano-card-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
.ass-plano-card-info { display:flex; align-items:center; gap:12px; }
.ass-plano-preco { font-size:1.3rem; font-weight:700; color:var(--cor-texto); }
.ass-plano-preco span { font-size:.8rem; font-weight:400; color:var(--cor-texto-secundario); }
.ass-plano-desc { font-size:.82rem; color:var(--cor-texto-secundario); margin-bottom:14px; }

/* Botões */
.botao-assinar { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 22px; border-radius:12px; font-size:.88rem; font-weight:600; cursor:pointer; border:none; transition:opacity .2s, transform .15s; white-space:nowrap; }
.botao-assinar:hover { opacity:.88; transform:translateY(-1px); }
.botao-assinar.primario { background:linear-gradient(135deg,#F5A623,#f77c0a); color:#111; }
.botao-assinar.atual-btn { background:rgba(76,175,80,.1); border:1px solid rgba(76,175,80,.35); color:#4CAF50; cursor:default; }
.botao-assinar.atual-btn:hover { transform:none; opacity:1; }
.botao-assinar.outline { background:transparent; border:1px solid var(--cor-borda); color:var(--cor-texto); }

.ass-info-box { display:flex; align-items:center; gap:10px; padding:12px 16px; background:rgba(245,166,35,.06); border:1px solid rgba(245,166,35,.15); border-radius:12px; font-size:.84rem; color:var(--cor-texto-secundario); }
.ass-info-box i { color:#F5A623; flex-shrink:0; }

.ass-periodo { font-size:.78rem; color:var(--cor-texto-secundario); margin-top:4px; }
</style>

<?php if (!$esta_no_index) echo '<div class="conteudo">'; ?>
<div class="container ass-page">

    <div class="dashboard-header">
        <div class="dashboard-background"></div>
        <div class="header-content">
            <div class="header-info">
                <h1><i class="fas fa-receipt"></i> Assinatura</h1>
                <p>Gerencie seu plano e pagamentos</p>
            </div>
        </div>
    </div>

    <?php if ($status === 'sucesso'): ?>
        <div class="ass-info-box" style="background:rgba(76,175,80,.08);border-color:rgba(76,175,80,.25);">
            <i class="fas fa-check-circle" style="color:#4CAF50;"></i>
            <span>Pagamento confirmado! Seu plano foi ativado.</span>
        </div>
    <?php elseif ($status === 'cancelado'): ?>
        <div class="ass-info-box" style="background:rgba(244,67,54,.08);border-color:rgba(244,67,54,.2);">
            <i class="fas fa-times-circle" style="color:#F44336;"></i>
            <span>Pagamento cancelado. Nenhuma cobrança foi realizada.</span>
        </div>
    <?php endif; ?>

    <!-- Plano atual -->
    <div class="ass-hero">
        <div class="ass-hero-top">
            <div class="ass-hero-left">
                <?php
                $nomeAtual = $assin ? ($assin['plano_nome'] ?? '') : '';
                $wppAtual  = $assin ? (int)($assin['tem_whatsapp'] ?? 0) : 0;
                ?>
                <div class="ass-plano-icone">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <div class="ass-plano-nome">
                        <?php echo $assin ? htmlspecialchars($nomeAtual ?: 'Plano desconhecido') : 'Sem plano ativo'; ?>
                    </div>
                    <?php if ($assin && !empty($assin['data_inicio'])): ?>
                        <div class="ass-periodo">
                            Desde <?php echo date('d/m/Y', strtotime($assin['data_inicio'])); ?>
                            <?php if (!empty($assin['data_fim'])): ?>
                                · Vence <?php echo date('d/m/Y', strtotime($assin['data_fim'])); ?>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!$assin): ?>
                        <div class="ass-plano-sub">Escolha um plano abaixo para começar</div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                <?php if (!$assin): ?>
                    <span class="ass-badge-atual ass-badge-sem-plano"><i class="fas fa-circle" style="font-size:.5rem;"></i> Nenhum plano</span>
                <?php elseif ($isAtiva): ?>
                    <span class="ass-badge-atual ass-badge-ativa"><i class="fas fa-check-circle" style="font-size:.75rem;"></i> Ativa</span>
                <?php elseif ($statusAtual === 'pendente'): ?>
                    <span class="ass-badge-atual ass-badge-pendente"><i class="fas fa-clock" style="font-size:.75rem;"></i> Pendente</span>
                <?php elseif ($statusAtual === 'cancelada' || $statusAtual === 'cancelado'): ?>
                    <span class="ass-badge-atual ass-badge-cancelada"><i class="fas fa-ban" style="font-size:.75rem;"></i> Cancelada</span>
                <?php else: ?>
                    <span class="ass-badge-atual"><i class="fas fa-circle" style="font-size:.5rem;"></i> <?php echo htmlspecialchars($statusAtual); ?></span>
                <?php endif; ?>
                <?php if ($assin && !empty($assin['preco'])): ?>
                    <div style="font-size:.85rem;color:var(--cor-texto-secundario);">R$ <?php echo number_format((float)$assin['preco'],2,',','.'); ?>/mês</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- WhatsApp do plano atual -->
        <?php if ($assin): ?>
        <div style="padding-top:12px;border-top:1px solid var(--cor-borda);">
            <?php if ($wppAtual): ?>
                <span class="ass-wpp-badge sim"><i class="fab fa-whatsapp"></i> Integração com WhatsApp incluída</span>
            <?php else: ?>
                <span class="ass-wpp-badge nao"><i class="fab fa-whatsapp"></i> Sem integração com WhatsApp</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Planos disponíveis -->
    <?php if (!empty($planos)): ?>
    <div class="ass-planos-titulo">Planos disponíveis</div>
    <div class="ass-planos-grid">
        <?php foreach ($planos as $p):
            $isAtualCard = ($planoAtualId === (int)$p['id'] && $isAtiva);
            $temWppCard  = (int)($p['tem_whatsapp'] ?? 0);
        ?>
        <div class="ass-plano-card <?php echo $isAtualCard ? 'is-atual' : ''; ?>">
            <div class="ass-plano-card-header">
                <div class="ass-plano-card-info">
                    <div class="ass-plano-icone" style="width:40px;height:40px;font-size:1.1rem;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:.95rem;color:var(--cor-texto);"><?php echo htmlspecialchars($p['nome']); ?></div>
                        <?php if (!empty($p['descricao'])): ?>
                            <div style="font-size:.8rem;color:var(--cor-texto-secundario);margin-top:2px;"><?php echo htmlspecialchars($p['descricao']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                    <div class="ass-plano-preco">
                        R$ <?php echo number_format((float)$p['preco'],2,',','.'); ?><span>/mês</span>
                    </div>
                    <?php if ($isAtualCard): ?>
                        <button class="botao-assinar atual-btn" disabled>
                            <i class="fas fa-check"></i> Plano atual
                        </button>
                    <?php elseif ($gatewayPadrao === 'stripe'): ?>
                        <button class="botao-assinar primario" onclick="assinar(<?php echo (int)$p['id']; ?>)">
                            <i class="fas fa-rocket"></i> Assinar
                        </button>
                    <?php else: ?>
                        <span class="badge status-error" style="font-size:.78rem;padding:5px 12px;">Gateway indisponível</span>
                    <?php endif; ?>
                </div>
            </div>
            <!-- WhatsApp do card -->
            <div style="padding-top:10px;border-top:1px solid var(--cor-borda);">
                <?php if ($temWppCard): ?>
                    <span class="ass-wpp-badge sim" style="font-size:.8rem;padding:5px 12px;"><i class="fab fa-whatsapp"></i> Integração com WhatsApp</span>
                <?php else: ?>
                    <span class="ass-wpp-badge nao" style="font-size:.8rem;padding:5px 12px;"><i class="fab fa-whatsapp"></i> Sem WhatsApp</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="card">
            <div class="estado-vazio" style="margin:0;">
                <div class="icone-vazio"><i class="fas fa-box-open"></i></div>
                <h3>Nenhum plano disponível</h3>
                <p>Aguarde novos planos em breve.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($assin && $isAtiva && !empty($assin['gateway_transacao_id'])): ?>
    <div class="ass-info-box">
        <i class="fas fa-info-circle"></i>
        <span>Para cancelar sua assinatura, entre em contato com o suporte.</span>
    </div>
    <?php endif; ?>

</div>
<?php if (!$esta_no_index) echo '</div>'; ?>
<script>
function obterUrl(caminho) {
    var caminhoNormalizado = (caminho || '').replace(/^\//, '');
    var path = window.location.pathname || '';
    var base = path;
    if (base.endsWith('/index.php')) base = base.replace('/index.php', '');
    if (base.includes('/paginas/')) base = base.split('/paginas/')[0];
    if (!base.endsWith('/')) base += '/';
    return base + caminhoNormalizado;
}
function assinar(planoId) {
    var btn = event.currentTarget;
    var textoOriginal = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aguarde...';
    fetch(obterUrl('funcoes/stripe.php?api=stripe&acao=criar_checkout&plano_id=' + planoId))
        .then(function(r) { if (!r.ok) throw new Error('Falha na solicitação'); return r.json(); })
        .then(function(d) {
            if (d.url) {
                window.location.href = d.url;
            } else {
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
                var msg = d.mensagem ? ('\nMotivo: ' + d.mensagem) : '';
                alert('Não foi possível iniciar o checkout' + msg);
            }
        })
        .catch(function(e) {
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
            alert('Erro ao iniciar checkout. Verifique o gateway.');
            console.error(e);
        });
}
function assinarGratuito(planoId){
    fetch(obterUrl('funcoes/admin_assinaturas.php?api=admin_assinaturas&acao=criar_gratuita'), {
        method: 'POST',
        body: new URLSearchParams({ plano_id: String(planoId) })
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d && d.sucesso) {
            alert('Assinatura gratuita ativada');
            window.location.reload();
        } else {
            alert('Não foi possível ativar: ' + (d.erro || 'erro'));
        }
    })
    .catch(function(){ alert('Erro de rede ao ativar assinatura gratuita'); });
}
</script>
<?php if (!$esta_no_index): ?>
</body>
<?php endif; ?>
