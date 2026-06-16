<?php
require_once __DIR__ . '/../funcoes/usuario.php';
verificarLogin();
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    http_response_code(403);
    echo '<div class="container"><h2>Acesso negado</h2></div>';
    exit;
}
?>
<div class="container admin-container">

    <div class="admin-page-header">
        <div class="admin-page-header-left">
            <span class="admin-page-icon"><i class="fas fa-tools"></i></span>
            <div>
                <h1 class="admin-page-title">Painel Admin</h1>
                <p class="admin-page-sub">Usuários, planos, assinaturas e integrações</p>
            </div>
        </div>
    </div>

    <div class="admin-toolbar">
        <div class="filtros-lista" id="tabs-admin">
            <button class="filtro-btn" data-aba="usuarios" onclick="mostrarAba('usuarios')"><i class="fas fa-users"></i><span>Usuários</span></button>
            <button class="filtro-btn" data-aba="planos" onclick="mostrarAba('planos')"><i class="fas fa-layer-group"></i><span>Planos</span></button>
            <button class="filtro-btn" data-aba="assinaturas" onclick="mostrarAba('assinaturas')"><i class="fas fa-credit-card"></i><span>Assinaturas</span></button>
            <button class="filtro-btn" data-aba="gateway" onclick="mostrarAba('gateway')"><i class="fas fa-plug"></i><span>Gateway</span></button>
            <button class="filtro-btn" data-aba="ia" onclick="mostrarAba('ia')"><i class="fas fa-robot"></i><span>IA</span></button>
            <button class="filtro-btn" data-aba="migracoes" onclick="mostrarAba('migracoes')"><i class="fas fa-database"></i><span>Migrações</span></button>
            <button class="filtro-btn" data-aba="email" onclick="mostrarAba('email')"><i class="fas fa-envelope"></i><span>Email</span></button>
            <button class="filtro-btn" data-aba="relatorios" onclick="mostrarAba('relatorios')"><i class="fas fa-chart-bar"></i><span>Relatórios</span></button>
        </div>
        <div class="toolbar-direita">
            <div class="seletor-bonito" id="seletor-bonito-periodo">
                <button id="btn-seletor-periodo" class="seletor-botao">
                    <span id="texto-seletor-periodo">Mês Atual</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="seletor-painel" id="painel-seletor-periodo" style="display:none;">
                    <div class="painel-topo">
                        <button class="painel-nav" id="painel-prev"><i class="fas fa-chevron-left"></i></button>
                        <button class="mes-nav-botao" id="mes-nav-botao"><span id="mes-nav-texto"></span></button>
                        <button class="painel-nav" id="painel-next"><i class="fas fa-chevron-right"></i></button>
                        <div></div>
                    </div>
                    <div class="painel-calendario" id="painel-calendario">
                        <div class="calendario-cabecalho">
                            <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
                        </div>
                        <div class="calendario-dias" id="calendario-dias"></div>
                        <div class="painel-acoes">
                            <button class="acao-cancelar" id="acao-cancelar-calendario">Cancelar</button>
                            <button class="acao-padrao" id="acao-aplicar-calendario">Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="metrics-container" class="admin-metrics"></div>

    <!-- Aba: Usuários -->
    <div id="aba-usuarios" class="aba">
        <div class="card">
            <h3 class="titulo-secao">Criar Usuário</h3>
            <form id="form-criar-usuario" class="admin-novo-form">
                <div class="admin-novo-campos">
                    <input class="form-input" type="text" name="nome" placeholder="Nome" required>
                    <input class="form-input" type="email" name="email" placeholder="Email" required>
                    <input class="form-input" type="password" name="senha" placeholder="Senha" required>
                    <select class="form-select" name="perfil">
                        <option value="usuario">Usuário</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button class="botao botao-primario" type="submit">
                    <i class="fas fa-plus"></i> Criar usuário
                </button>
            </form>
            <div id="lista-usuarios" class="lista-tabela"></div>
        </div>
    </div>

    <!-- Aba: Planos -->
    <div id="aba-planos" class="aba" style="display:none">
        <div class="card">
            <div class="admin-card-header">
                <h3 class="titulo-secao">Planos</h3>
                <button class="botao botao-primario botao-sm" onclick="abrirModalCriarPlano()">
                    <i class="fas fa-plus"></i> Criar plano
                </button>
            </div>
            <div id="lista-planos" class="lista-tabela"></div>
        </div>
    </div>

    <!-- Aba: Assinaturas -->
    <div id="aba-assinaturas" class="aba" style="display:none">
        <div class="card">
            <div class="admin-card-header">
                <h3 class="titulo-secao">Assinaturas</h3>
                <button class="botao botao-primario botao-sm" onclick="abrirModalCriarAssinatura()">
                    <i class="fas fa-plus"></i> Criar assinatura
                </button>
            </div>
            <div id="lista-assinaturas" class="lista-tabela"></div>
        </div>
    </div>

    <!-- Aba: Gateway -->
    <div id="aba-gateway" class="aba" style="display:none">
        <div class="card">
            <h3 class="titulo-secao">Gateway de Pagamento</h3>
            <form id="form-gateway">
                <div class="admin-form-row">
                    <div class="form-grupo">
                        <label class="form-label">Gateway padrão</label>
                        <select class="form-select" name="gateway_padrao">
                            <option value="stripe">Stripe</option>
                        </select>
                    </div>
                    <div class="form-grupo">
                        <label class="form-label">Chave secreta</label>
                        <input class="form-input" type="text" name="stripe_api_key" placeholder="sk_test_...">
                    </div>
                    <div class="form-grupo">
                        <label class="form-label">Webhook secret</label>
                        <input class="form-input" type="text" name="stripe_webhook_secret" placeholder="whsec_...">
                    </div>
                </div>
                <div class="admin-form-acoes">
                    <button class="botao botao-primario" type="submit">Salvar configuração</button>
                    <button class="botao botao-secundario" id="btn-testar-gateway" type="button">
                        <i class="fas fa-plug"></i> Testar conexão
                    </button>
                    <span id="status-gateway" class="admin-status"></span>
                </div>
            </form>
        </div>
    </div>

    <!-- Aba: IA -->
    <div id="aba-ia" class="aba" style="display:none">
        <div class="card">
            <h3 class="titulo-secao">Configuração de IA</h3>
            <p class="admin-descricao">Use a chave da API do GPT para classificar automaticamente categorias durante importações.</p>
            <form id="form-ia">
                <div class="form-grupo">
                    <label class="form-label">Chave da API OpenAI</label>
                    <input class="form-input" type="text" name="openai_api_key" placeholder="sk-...">
                </div>
                <div class="admin-checkbox-grupo">
                    <label class="admin-checkbox-label">
                        <input type="checkbox" name="ai_auto_categorizar" class="admin-checkbox">
                        <span>Ativar categorização automática</span>
                    </label>
                </div>
                <div class="admin-form-acoes">
                    <button class="botao botao-primario" type="submit">Salvar</button>
                    <span id="status-ia" class="admin-status"></span>
                </div>
            </form>
        </div>
    </div>

    <!-- Aba: Email / SMTP -->
    <div id="aba-email" class="aba" style="display:none">
        <div class="card">
            <h3 class="titulo-secao">Configuração de E-mail (SMTP)</h3>
            <p class="admin-descricao">Configure o servidor SMTP para envio de e-mails de verificação e notificações.</p>
            <form id="form-email">
                <div class="admin-form-row">
                    <div class="form-grupo">
                        <label class="form-label">Host SMTP</label>
                        <input class="form-input" type="text" name="smtp_host" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-grupo">
                        <label class="form-label">Porta</label>
                        <input class="form-input" type="number" name="smtp_port" placeholder="587">
                    </div>
                    <div class="form-grupo">
                        <label class="form-label">Criptografia</label>
                        <select class="form-select" name="smtp_encryption">
                            <option value="tls">TLS (porta 587)</option>
                            <option value="ssl">SSL (porta 465)</option>
                            <option value="none">Nenhuma (porta 25)</option>
                        </select>
                    </div>
                </div>
                <div class="admin-form-row">
                    <div class="form-grupo">
                        <label class="form-label">Usuário</label>
                        <input class="form-input" type="text" name="smtp_usuario" placeholder="seu@email.com">
                    </div>
                    <div class="form-grupo">
                        <label class="form-label">Senha</label>
                        <input class="form-input" type="password" name="smtp_senha" placeholder="••••••••" autocomplete="new-password">
                    </div>
                </div>
                <div class="admin-form-row">
                    <div class="form-grupo">
                        <label class="form-label">E-mail remetente (From)</label>
                        <input class="form-input" type="email" name="smtp_from" placeholder="noreply@seudominio.com">
                    </div>
                    <div class="form-grupo">
                        <label class="form-label">Nome remetente</label>
                        <input class="form-input" type="text" name="smtp_from_nome" placeholder="Peak Finanças">
                    </div>
                </div>
                <div class="admin-form-acoes">
                    <button class="botao botao-primario" type="submit">Salvar configuração</button>
                    <button class="botao botao-secundario" id="btn-testar-email" type="button">
                        <i class="fas fa-paper-plane"></i> Testar envio
                    </button>
                    <span id="status-email" class="admin-status"></span>
                </div>
            </form>
        </div>
        <!-- Modal para testar envio -->
        <div id="modal-testar-email" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;">
            <div class="card" style="max-width:400px;width:90%;margin:0 auto;padding:24px;">
                <h3 class="titulo-secao" style="margin-bottom:16px">Testar envio de e-mail</h3>
                <div class="form-grupo">
                    <label class="form-label">Enviar e-mail de teste para:</label>
                    <input class="form-input" type="email" id="email-teste-destino" placeholder="destino@exemplo.com">
                </div>
                <div style="display:flex;gap:10px;margin-top:16px">
                    <button class="botao botao-secundario" onclick="fecharModalTestarEmail()">Cancelar</button>
                    <button class="botao botao-primario" onclick="confirmarTesteEmail()">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Aba: Relatórios -->
    <div id="aba-relatorios" class="aba" style="display:none">
        <style>
        .rel-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media(max-width:700px){ .rel-grid { grid-template-columns:1fr; } }
        .rel-card { background:var(--cor-fundo-secundario); border:1px solid var(--cor-borda); border-radius:16px; padding:20px; }
        .rel-titulo { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--cor-texto-secundario); opacity:.65; margin-bottom:14px; }
        .rel-canvas-wrap { position:relative; height:220px; }
        </style>
        <div class="rel-grid">
            <div class="rel-card">
                <div class="rel-titulo"><i class="fas fa-dollar-sign" style="margin-right:5px"></i>Receita mensal</div>
                <div class="rel-canvas-wrap"><canvas id="rel-chart-receita"></canvas></div>
            </div>
            <div class="rel-card">
                <div class="rel-titulo"><i class="fas fa-exchange-alt" style="margin-right:5px"></i>Novas vs Canceladas</div>
                <div class="rel-canvas-wrap"><canvas id="rel-chart-assinaturas"></canvas></div>
            </div>
            <div class="rel-card">
                <div class="rel-titulo"><i class="fas fa-users" style="margin-right:5px"></i>Crescimento de usuários</div>
                <div class="rel-canvas-wrap"><canvas id="rel-chart-usuarios"></canvas></div>
            </div>
            <div class="rel-card">
                <div class="rel-titulo"><i class="fas fa-layer-group" style="margin-right:5px"></i>Assinantes por plano</div>
                <div class="rel-canvas-wrap"><canvas id="rel-chart-planos"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Aba: Migrações -->
    <div id="aba-migracoes" class="aba" style="display:none">
        <style>
        .mig-header{display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap}
        .mig-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:.82rem;font-weight:600}
        .mig-badge.ok{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#22C55E}
        .mig-badge.warn{background:rgba(245,166,35,.12);border:1px solid rgba(245,166,35,.3);color:#F5A623}
        .mig-table{width:100%;border-collapse:collapse}
        .mig-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--cor-texto-secundario);padding:8px 12px;border-bottom:1px solid var(--cor-borda);text-align:left;font-weight:600}
        .mig-table td{padding:12px 12px;border-bottom:1px solid rgba(128,128,128,.07);font-size:.88rem;color:var(--cor-texto)}
        .mig-table tr:last-child td{border-bottom:none}
        .mig-table tr:hover td{background:rgba(128,128,128,.04)}
        .mig-versao{font-family:monospace;font-weight:700;color:var(--cor-destaque);font-size:.9rem}
        .mig-arquivo{font-family:monospace;font-size:.82rem;color:var(--cor-texto-secundario)}
        .mig-status-ok{display:inline-flex;align-items:center;gap:5px;color:#22C55E;font-weight:600;font-size:.82rem}
        .mig-status-pend{display:inline-flex;align-items:center;gap:5px;color:#F5A623;font-weight:600;font-size:.82rem}
        .mig-data{font-size:.8rem;color:var(--cor-texto-secundario)}
        .mig-log{background:var(--cor-fundo);border:1px solid var(--cor-borda);border-radius:10px;padding:12px 14px;font-family:monospace;font-size:.8rem;line-height:1.6;margin-top:14px;max-height:200px;overflow-y:auto;white-space:pre-wrap;display:none}
        .mig-log.show{display:block}
        .mig-acoes{display:flex;align-items:center;gap:10px;margin-top:16px}
        </style>

        <div class="card">
            <div class="mig-header">
                <h3 class="titulo-secao" style="margin:0">Migrações do Banco</h3>
                <span id="mig-badge-total" class="mig-badge ok"><i class="fas fa-check-circle"></i> <span id="mig-badge-txt">Carregando...</span></span>
                <span id="mig-badge-status" class="mig-badge ok" style="display:none"><i class="fas fa-database"></i> Banco atualizado</span>
            </div>

            <div style="overflow-x:auto">
                <table class="mig-table">
                    <thead>
                        <tr>
                            <th>VERSÃO</th>
                            <th>ARQUIVO</th>
                            <th>STATUS</th>
                            <th>APLICADA EM</th>
                        </tr>
                    </thead>
                    <tbody id="mig-tbody">
                        <tr><td colspan="4" style="text-align:center;color:var(--cor-texto-secundario);padding:24px">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="mig-acoes">
                <button id="btn-aplicar-migracoes" class="botao botao-primario" onclick="migAplicar()">
                    <i class="fas fa-play"></i> Aplicar pendentes
                </button>
                <span id="mig-status-txt" style="font-size:.84rem;color:var(--cor-texto-secundario)"></span>
            </div>
            <div id="mig-log" class="mig-log"></div>
        </div>
    </div>
</div>

<script>
window.abaSelecionada = window.abaSelecionada || 'usuarios';
window.periodoSelecionado = window.periodoSelecionado || {inicio: null, fim: null, preset: 'month'};

function mostrarAba(nome) {
    var abas = ['usuarios','planos','assinaturas','gateway','ia','migracoes','email','relatorios'];
    abas.forEach(function(n){
        var el = document.getElementById('aba-'+n);
        if (el) el.style.display = (n === nome) ? 'block' : 'none';
    });
    document.querySelectorAll('#tabs-admin .filtro-btn').forEach(function(el){
        el.classList.toggle('ativo', el.getAttribute('data-aba') === nome);
    });
    // Seletor de período só faz sentido em abas com dados temporais
    var abaComPeriodo = ['assinaturas','relatorios'];
    var seletorEl = document.getElementById('seletor-bonito-periodo');
    if (seletorEl) seletorEl.style.visibility = abaComPeriodo.indexOf(nome) >= 0 ? 'visible' : 'hidden';
    window.abaSelecionada = nome;
    try { localStorage.setItem('adminAba', nome); } catch(e){}
    carregarMetricasPeriodo();
    if (nome === 'usuarios') carregarUsuarios();
    if (nome === 'planos') carregarPlanos();
    if (nome === 'assinaturas') carregarAssinaturas();
    if (nome === 'migracoes') migCarregar();
    if (nome === 'ia') carregarConfigIA();
    if (nome === 'email') carregarConfigEmail();
    if (nome === 'relatorios') carregarRelatorios();
}

function migCarregar() {
    fetch('funcoes/admin_migracoes.php?api=admin_migracoes&acao=listar')
        .then(r => r.json()).then(function(d) {
            if (!d.sucesso) return;
            var lista = d.migracoes || [];
            var pendentes = lista.filter(function(m){ return m.status !== 'aplicada'; }).length;
            var total = lista.length;
            var aplicadas = total - pendentes;

            // Badge de contagem
            var badgeTotal = document.getElementById('mig-badge-total');
            var badgeTxt = document.getElementById('mig-badge-txt');
            var badgeStatus = document.getElementById('mig-badge-status');
            if (badgeTxt) badgeTxt.textContent = aplicadas + ' aplicada(s)';
            if (badgeTotal) badgeTotal.className = 'mig-badge ' + (pendentes === 0 ? 'ok' : 'warn');
            if (badgeStatus) {
                badgeStatus.style.display = pendentes === 0 ? 'inline-flex' : 'none';
            }

            // Tabela
            var tbody = document.getElementById('mig-tbody');
            if (!tbody) return;
            if (!lista.length) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:24px;color:var(--cor-texto-secundario)">Nenhuma migração encontrada</td></tr>';
                return;
            }
            tbody.innerHTML = lista.map(function(m) {
                var statusHtml = m.status === 'aplicada'
                    ? '<span class="mig-status-ok"><i class="fas fa-check-circle"></i> aplicada</span>'
                    : '<span class="mig-status-pend"><i class="fas fa-clock"></i> pendente</span>';
                var dataHtml = m.aplicada_em
                    ? '<span class="mig-data">' + m.aplicada_em + '</span>'
                    : '<span class="mig-data" style="opacity:.4">—</span>';
                return '<tr>'
                    + '<td><span class="mig-versao">' + m.versao + '</span></td>'
                    + '<td><span class="mig-arquivo">' + m.arquivo + '</span></td>'
                    + '<td>' + statusHtml + '</td>'
                    + '<td>' + dataHtml + '</td>'
                    + '</tr>';
            }).join('');

            // Botão
            var btn = document.getElementById('btn-aplicar-migracoes');
            if (btn) btn.disabled = pendentes === 0;
            var st = document.getElementById('mig-status-txt');
            if (st) st.textContent = pendentes === 0 ? 'Banco atualizado.' : pendentes + ' migração(ões) pendente(s).';
        });
}

function migAplicar() {
    var btn = document.getElementById('btn-aplicar-migracoes');
    var st = document.getElementById('mig-status-txt');
    var log = document.getElementById('mig-log');
    if (btn) btn.disabled = true;
    if (st) st.textContent = 'Aplicando...';
    fetch('funcoes/admin_migracoes.php?api=admin_migracoes&acao=aplicar')
        .then(r => r.json()).then(function(d) {
            var linhas = (d.log || []).concat(d.erros || []);
            if (log) {
                log.textContent = linhas.join('\n') || 'Nenhuma alteração necessária.';
                log.classList.add('show');
            }
            if (st) st.textContent = d.sucesso ? 'Concluído com sucesso.' : 'Concluído com erros.';
            migCarregar();
        }).catch(function(e) {
            if (st) st.textContent = 'Erro de conexão.';
            if (btn) btn.disabled = false;
        });
}

document.getElementById('form-criar-usuario').addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    fetch('funcoes/admin_usuarios.php?api=admin_usuarios&acao=criar', {method:'POST', body:fd})
        .then(r => r.json()).then(() => { this.reset(); carregarUsuarios(); });
});

function carregarUsuarios(){
    fetch('funcoes/admin_usuarios.php?api=admin_usuarios&acao=listar')
        .then(r => r.json()).then(d => {
            if (!d || !d.length) {
                document.getElementById('lista-usuarios').innerHTML = '<div class="estado-vazio"><p>Nenhum usuário encontrado.</p></div>';
                return;
            }
            var html = '<table class="tabela"><thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Perfil</th><th class="col-criado-em">Criado em</th><th>Ações</th></tr></thead><tbody>';
            d.forEach(function(u){
                var criado = (u.data_cadastro||'').substring(0,10).split('-').reverse().join('/');
                html += '<tr>' +
                    '<td>'+u.id+'</td>' +
                    '<td>'+u.nome+'</td>' +
                    '<td>'+u.email+'</td>' +
                    '<td><span class="badge '+(u.perfil==='admin'?'status-pending':'status-ok')+'">'+( u.perfil||'usuario')+'</span></td>' +
                    '<td class="col-criado-em">'+criado+'</td>' +
                    '<td><button class="icon-btn" title="Editar" onclick="abrirModalUsuario('+u.id+', \''+(u.nome||'').replace(/\'/g,"\\'")+'\', \''+(u.email||'').replace(/\'/g,"\\'")+'\', \''+(u.perfil||'usuario')+'\', \''+(u.status||'ativo')+'\')"><i class="fas fa-pencil-alt"></i></button></td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            document.getElementById('lista-usuarios').innerHTML = html;
        });
}

function carregarPlanos(){
    var r = rangeAtual();
    fetch('funcoes/admin_planos.php?api=admin_planos&acao=listar&inicio='+encodeURIComponent(r.inicio)+'&fim='+encodeURIComponent(r.fim))
        .then(r => r.json()).then(d => {
            if (!d || !d.length) {
                document.getElementById('lista-planos').innerHTML = '<div class="estado-vazio"><p>Nenhum plano encontrado.</p></div>';
                return;
            }
            var html = '<table class="tabela"><thead><tr><th>ID</th><th>Nome</th><th>Preço</th><th>Duração (meses)</th><th>Teste grátis</th><th>WhatsApp</th><th>Criado em</th><th>Price ID</th><th>Ações</th></tr></thead><tbody>';
            d.forEach(function(p){
                var preco = (p.preco||0);
                var dur = (p.duracao_meses||1);
                var trial = parseInt(p.dias_teste_gratis||0);
                var trialLabel = trial > 0 ? trial+' dias' : '—';
                var price = (p.stripe_price_id||'—');
                var criado = (p.criado_em||'').substring(0,10).split('-').reverse().join('/');
                var wpp = parseInt(p.tem_whatsapp||0);
                var wppHtml = wpp ? '<span style="color:#25D366;font-weight:600"><i class="fab fa-whatsapp"></i> Sim</span>' : '<span style="opacity:.45">Não</span>';
                html += '<tr>' +
                    '<td>'+p.id+'</td>' +
                    '<td>'+p.nome+'</td>' +
                    '<td>R$ '+Number(preco).toFixed(2)+'</td>' +
                    '<td>'+dur+'</td>' +
                    '<td>'+(trial > 0 ? '<span style="color:#22C55E;font-weight:600">'+trialLabel+'</span>' : '—')+'</td>' +
                    '<td>'+wppHtml+'</td>' +
                    '<td class="col-criado-em">'+(criado||'—')+'</td>' +
                    '<td><code>'+price+'</code></td>' +
                    '<td>' +
                    '<button class="icon-btn" title="Editar" onclick="abrirModalPlano('+p.id+',\''+preco+'\',\''+dur+'\',\''+p.stripe_price_id+'\','+wpp+')"><i class="fas fa-pencil-alt"></i></button> ' +
                    '<button class="icon-btn danger" title="Excluir" onclick="inativarPlano('+p.id+')"><i class="fas fa-trash"></i></button>' +
                    '</td></tr>';
            });
            html += '</tbody></table>';
            document.getElementById('lista-planos').innerHTML = html;
        }).catch(function(){
            document.getElementById('lista-planos').innerHTML = '<div class="estado-vazio"><p>Não foi possível carregar os planos.</p></div>';
        });
}

function abrirModalPlano(id, preco, duracao, priceId, temWhatsapp){
    var modal = document.getElementById('modal-editar-plano');
    modal.classList.add('ativo');
    document.getElementById('plano_id').value = id;
    document.getElementById('plano_preco').value = preco;
    document.getElementById('plano_duracao').value = duracao;
    document.getElementById('plano_price').value = priceId||'';
    document.getElementById('plano_whatsapp').checked = !!parseInt(temWhatsapp||0);
}
function fecharModalPlano(){
    document.getElementById('modal-editar-plano').classList.remove('ativo');
}
function salvarModalPlano(){
    var id = document.getElementById('plano_id').value;
    var preco = document.getElementById('plano_preco').value;
    var dur = document.getElementById('plano_duracao').value;
    var price = document.getElementById('plano_price').value;
    var wpp = document.getElementById('plano_whatsapp').checked ? 1 : 0;
    var fd1 = new FormData();
    fd1.append('id', id); fd1.append('preco', preco); fd1.append('duracao_meses', dur); fd1.append('tem_whatsapp', wpp);
    fetch('funcoes/admin_planos.php?api=admin_planos&acao=atualizar_basico', {method:'POST', body:fd1})
        .then(() => {
            var fd2 = new FormData();
            fd2.append('id', id); fd2.append('stripe_price_id', price);
            return fetch('funcoes/admin_planos.php?api=admin_planos&acao=atualizar_price', {method:'POST', body:fd2});
        })
        .then(() => { fecharModalPlano(); carregarPlanos(); });
}
function inativarPlano(id){
    if (!confirm('Deseja realmente excluir este plano?')) return;
    var fd = new FormData(); fd.append('id', id);
    fetch('funcoes/admin_planos.php?api=admin_planos&acao=inativar', {method:'POST', body:fd})
        .then(r => r.json()).then(() => carregarPlanos());
}
function abrirModalCriarPlano(){
    var m = document.getElementById('modal-criar-plano');
    if (m) m.classList.add('ativo');
}
function fecharModalCriarPlano(){
    var m = document.getElementById('modal-criar-plano');
    if (m) m.classList.remove('ativo');
}
function salvarCriacaoPlano(){
    var nome = document.getElementById('novo_plano_nome').value;
    var desc = document.getElementById('novo_plano_desc').value;
    var preco = document.getElementById('novo_plano_preco').value;
    var dur = document.getElementById('novo_plano_duracao').value;
    var trial = document.getElementById('novo_plano_trial').value;
    var wpp = document.getElementById('novo_plano_whatsapp').checked ? 1 : 0;
    var fd = new FormData();
    fd.append('nome', nome); fd.append('descricao', desc);
    fd.append('preco', preco); fd.append('duracao_meses', dur);
    fd.append('dias_teste_gratis', trial || 0);
    fd.append('tem_whatsapp', wpp);
    fetch('funcoes/admin_planos.php?api=admin_planos&acao=criar', {method:'POST', body:fd})
        .then(r => r.json())
        .then(function(d){
            if (!d || d.sucesso !== true) { alert('Não foi possível criar o plano. Verifique os dados e tente novamente.'); return; }
            fecharModalCriarPlano();
            carregarPlanos();
        }).catch(function(){ alert('Erro ao criar plano.'); });
}

function statusAssinaturaBadge(s) {
    if (!s) return '<span class="badge status-pending">—</span>';
    var s2 = s.toLowerCase();
    var cl = (s2 === 'ativa' || s2 === 'ativo' || s2 === 'active') ? 'status-ok'
           : (s2 === 'pendente' || s2 === 'pending')               ? 'status-error'
           : 'status-pending';
    return '<span class="badge '+cl+'">'+s+'</span>';
}

function carregarAssinaturas(){
    var r = rangeAtual();
    fetch('funcoes/admin_assinaturas.php?api=admin_assinaturas&acao=listar&inicio='+encodeURIComponent(r.inicio)+'&fim='+encodeURIComponent(r.fim))
        .then(r => r.json()).then(d => {
            if (!d || !d.length) {
                document.getElementById('lista-assinaturas').innerHTML = '<div class="estado-vazio"><p>Nenhuma assinatura encontrada.</p></div>';
                return;
            }
            var html = '<table class="tabela"><thead><tr><th>ID</th><th>Usuário</th><th>Plano</th><th>Status</th><th class="col-criado-em">Assinada em</th><th>Ações</th></tr></thead><tbody>';
            d.forEach(function(a){
                var criada = (a.criada_em||'').substring(0,10).split('-').reverse().join('/');
                html += '<tr>' +
                    '<td>'+a.id+'</td>' +
                    '<td>'+(a.usuario_nome||('#'+a.usuario_id))+'</td>' +
                    '<td>'+(a.plano_nome||('#'+a.plano_id))+'</td>' +
                    '<td>'+statusAssinaturaBadge(a.status)+'</td>' +
                    '<td class="col-criado-em">'+(criada||'—')+'</td>' +
                    '<td>' +
                    '<button class="icon-btn" title="Editar" onclick="abrirModalAssinatura('+a.id+',\''+a.plano_id+'\',\''+(a.status||'')+'\')"><i class="fas fa-pencil-alt"></i></button> ' +
                    '<button class="icon-btn danger" title="Revogar" onclick="revogar('+a.id+')"><i class="fas fa-ban"></i></button>' +
                    '</td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            document.getElementById('lista-assinaturas').innerHTML = html;
        });
}

function revogar(id){
    if (!confirm('Deseja revogar esta assinatura? O status será alterado para cancelada.')) return;
    var fd = new FormData(); fd.append('id', id);
    fetch('funcoes/admin_assinaturas.php?api=admin_assinaturas&acao=revogar', {method:'POST', body:fd})
        .then(r => r.json()).then(() => carregarAssinaturas());
}

function abrirModalAssinatura(id, plano_id, status) {
    var m = document.getElementById('modal-editar-assinatura');
    if (!m) return;
    document.getElementById('ass_id').value = id;
    document.getElementById('ass_plano_id').value = plano_id || '';
    var sel = document.getElementById('ass_status');
    sel.value = status || 'ativa';
    if (!sel.value) sel.value = 'ativa';
    m.classList.add('ativo');
}
function fecharModalAssinatura() {
    var m = document.getElementById('modal-editar-assinatura');
    if (m) m.classList.remove('ativo');
}
function salvarModalAssinatura() {
    var fd = new FormData();
    fd.append('id',       document.getElementById('ass_id').value);
    fd.append('plano_id', document.getElementById('ass_plano_id').value);
    fd.append('status',   document.getElementById('ass_status').value);
    fetch('funcoes/admin_assinaturas.php?api=admin_assinaturas&acao=atualizar', {method:'POST', body:fd})
        .then(r => r.json()).then(function(d){
            if (!d || d.sucesso !== true) { alert('Não foi possível salvar.'); return; }
            fecharModalAssinatura();
            carregarAssinaturas();
        }).catch(function(){ alert('Erro ao salvar.'); });
}

function abrirModalCriarAssinatura() {
    var m = document.getElementById('modal-criar-assinatura');
    if (!m) return;
    fetch('funcoes/admin_usuarios.php?api=admin_usuarios&acao=listar')
        .then(r => r.json()).then(function(users) {
            var sel = document.getElementById('nova_ass_usuario_id');
            sel.innerHTML = (users||[]).map(function(u){ return '<option value="'+u.id+'">'+(u.nome||u.email)+'</option>'; }).join('');
        });
    fetch('funcoes/admin_planos.php?api=admin_planos&acao=listar')
        .then(r => r.json()).then(function(plans) {
            var sel = document.getElementById('nova_ass_plano_id');
            sel.innerHTML = (plans||[]).map(function(p){ return '<option value="'+p.id+'">'+p.nome+'</option>'; }).join('');
        });
    document.getElementById('nova_ass_status').value = 'ativa';
    m.classList.add('ativo');
}
function fecharModalCriarAssinatura() {
    var m = document.getElementById('modal-criar-assinatura');
    if (m) m.classList.remove('ativo');
}
function salvarCriacaoAssinatura() {
    var fd = new FormData();
    fd.append('usuario_id', document.getElementById('nova_ass_usuario_id').value);
    fd.append('plano_id',   document.getElementById('nova_ass_plano_id').value);
    fd.append('status',     document.getElementById('nova_ass_status').value);
    fetch('funcoes/admin_assinaturas.php?api=admin_assinaturas&acao=criar', {method:'POST', body:fd})
        .then(r => r.json()).then(function(d){
            if (!d || d.sucesso !== true) { alert('Não foi possível criar a assinatura.'); return; }
            fecharModalCriarAssinatura();
            carregarAssinaturas();
        }).catch(function(){ alert('Erro ao criar assinatura.'); });
}

function abrirModalUsuario(id, nome, email, perfil, status){
    var m = document.getElementById('modal-editar-usuario');
    if (!m) return;
    document.getElementById('usuario_id').value = id;
    document.getElementById('usuario_nome').value = nome;
    document.getElementById('usuario_email').value = email;
    document.getElementById('usuario_perfil').value = perfil || 'usuario';
    document.getElementById('usuario_status').value = status || 'ativo';
    m.classList.add('ativo');
}
function fecharModalUsuario(){
    var m = document.getElementById('modal-editar-usuario');
    if (m) m.classList.remove('ativo');
}
function salvarModalUsuario(){
    var fd = new FormData();
    fd.append('id', document.getElementById('usuario_id').value);
    fd.append('nome', document.getElementById('usuario_nome').value);
    fd.append('email', document.getElementById('usuario_email').value);
    fd.append('perfil', document.getElementById('usuario_perfil').value);
    fd.append('status', document.getElementById('usuario_status').value);
    fetch('funcoes/admin_usuarios.php?api=admin_usuarios&acao=atualizar', {method:'POST', body:fd})
        .then(r => r.json()).then(function(d){
            if (!d || d.sucesso !== true) { alert('Não foi possível salvar. Verifique os dados.'); return; }
            fecharModalUsuario();
            carregarUsuarios();
        }).catch(function(){ alert('Erro ao salvar.'); });
}

document.getElementById('form-gateway').addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const k = fd.get('stripe_api_key') || '';
    if (k.startsWith('pk_')) { alert('Informe a chave secreta (sk_...), não a pública (pk_...)'); return; }
    const st = document.getElementById('status-gateway');
    st.textContent = 'Salvando...';
    fetch('funcoes/admin_gateway.php?api=admin_gateway&acao=salvar', {method:'POST', body:fd})
        .then(r => r.json()).then(d => {
            st.textContent = d.sucesso ? 'Salvo com sucesso ✓' : 'Erro ao salvar';
        });
});

document.getElementById('btn-testar-gateway').addEventListener('click', function(){
    var st = document.getElementById('status-gateway');
    st.textContent = 'Testando...';
    fetch('funcoes/admin_gateway.php?api=admin_gateway&acao=testar')
        .then(r => r.json()).then(d => {
            st.textContent = d.conectado ? ('Conectado ✓' + (d.conta ? ' ('+d.conta+')' : '')) : ('Falha: '+(d.motivo||'erro'));
        }).catch(function(){ st.textContent = 'Erro de rede ao testar'; });
});

document.getElementById('form-ia').addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const st = document.getElementById('status-ia');
    st.textContent = 'Salvando...';
    fetch('funcoes/admin_gateway.php?api=admin_gateway&acao=salvar', {method:'POST', body:fd})
        .then(r => r.json()).then(d => {
            st.textContent = d.sucesso ? 'Salvo ✓' : 'Erro ao salvar';
        });
});

function carregarConfigIA(){
    fetch('funcoes/admin_gateway.php?api=admin_gateway&acao=obter')
        .then(r => r.json()).then(cfg => {
            const f = document.getElementById('form-ia');
            if (!f) return;
            f.openai_api_key.value = cfg.openai_api_key || '';
            f.ai_auto_categorizar.checked = !!(cfg.ai_auto_categorizar && (cfg.ai_auto_categorizar==='1' || cfg.ai_auto_categorizar===1 || cfg.ai_auto_categorizar===true));
        });
}


function carregarConfigEmail(){
    fetch('funcoes/admin_email.php?api=admin_email&acao=obter')
        .then(r => r.json()).then(function(cfg) {
            var f = document.getElementById('form-email');
            if (!f) return;
            f.smtp_host.value       = cfg.smtp_host || '';
            f.smtp_port.value       = cfg.smtp_port || '587';
            f.smtp_encryption.value = cfg.smtp_encryption || 'tls';
            f.smtp_usuario.value    = cfg.smtp_usuario || '';
            f.smtp_from.value       = cfg.smtp_from || '';
            f.smtp_from_nome.value  = cfg.smtp_from_nome || '';
            // não preencher senha por segurança
        });
}

document.addEventListener('DOMContentLoaded', function(){
    var fEmail = document.getElementById('form-email');
    if (fEmail) {
        fEmail.addEventListener('submit', function(e){
            e.preventDefault();
            var fd = new FormData(this);
            var st = document.getElementById('status-email');
            st.textContent = 'Salvando...';
            fetch('funcoes/admin_email.php?api=admin_email&acao=salvar', {method:'POST', body:fd})
                .then(r => r.json()).then(function(d){
                    st.textContent = d.sucesso ? 'Salvo com sucesso ✓' : 'Erro ao salvar';
                }).catch(function(){ st.textContent = 'Erro de rede'; });
        });
    }
    var btnTestar = document.getElementById('btn-testar-email');
    if (btnTestar) {
        btnTestar.addEventListener('click', function(){
            var modal = document.getElementById('modal-testar-email');
            if (modal) { modal.style.display = 'flex'; }
        });
    }
});

function fecharModalTestarEmail(){
    var modal = document.getElementById('modal-testar-email');
    if (modal) modal.style.display = 'none';
}

function confirmarTesteEmail(){
    var dest = document.getElementById('email-teste-destino').value.trim();
    if (!dest) { alert('Informe o e-mail de destino.'); return; }
    var st = document.getElementById('status-email');
    st.textContent = 'Enviando...';
    fecharModalTestarEmail();
    var fd = new FormData();
    fd.append('destino', dest);
    fetch('funcoes/admin_email.php?api=admin_email&acao=testar', {method:'POST', body:fd})
        .then(r => r.json()).then(function(d){
            st.textContent = d.sucesso ? 'E-mail enviado com sucesso ✓' : ('Falha: ' + (d.erro || 'erro desconhecido'));
        }).catch(function(){ st.textContent = 'Erro de rede ao testar'; });
}

function rangeAtual(){
    if (!periodoSelecionado) { periodoSelecionado = {inicio:null, fim:null, preset:'month'}; }
    if (periodoSelecionado.preset === 'custom' && periodoSelecionado.inicio && periodoSelecionado.fim) {
        return {inicio: periodoSelecionado.inicio, fim: periodoSelecionado.fim};
    }
    var hoje = new Date();
    var inicio, fim;
    if (periodoSelecionado.preset === 'month') {
        var first = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        var last  = new Date(hoje.getFullYear(), hoje.getMonth()+1, 0);
        inicio = first.toISOString().slice(0,10);
        fim    = last.toISOString().slice(0,10);
    } else {
        fim = hoje.toISOString().slice(0,10);
        var dias = periodoSelecionado.preset === '7d' ? 7 : (periodoSelecionado.preset === '90d' ? 90 : 30);
        inicio = new Date(hoje.getTime() - dias*24*60*60*1000).toISOString().slice(0,10);
    }
    return {inicio: inicio, fim: fim};
}

function carregarMetricasPeriodo(){
    var r = rangeAtual();
    fetch('funcoes/admin_metrics.php?api=admin_metrics&acao=resumo&inicio='+r.inicio+'&fim='+r.fim)
        .then(r => r.json()).then(d => { renderMetrics(d); }).catch(function(){ renderMetrics({}); });
}

function renderMetrics(d){
    var fmt = new Intl.NumberFormat('pt-BR', {style:'currency', currency:'BRL'});
    var aba = window.abaSelecionada || 'usuarios';
    var cards = [];
    if (aba === 'usuarios') {
        cards.push(card('users','Novos usuários',''+(d.usuarios_novos||0)));
        cards.push(card('ban','Inativados',''+(d.usuarios_inativados||0)));
    } else if (aba === 'assinaturas') {
        cards.push(card('money-bill-wave','Receita', fmt.format(d.receita_total||0)));
        cards.push(card('ban','Cancelamentos',''+(d.cancelamentos||0)));
    } else if (aba === 'gateway') {
        if (d.gateway_conectado) cards.push(card('plug','Gateway', (d.gateway_nome||'') + ' ✓'));
    } else if (aba === 'planos') {
        cards.push(card('layer-group','Planos ativos',''+(d.planos_ativos||0)));
        cards.push(card('layer-group','Planos totais',''+(d.planos_total||0)));
    }
    document.getElementById('metrics-container').innerHTML = cards.join('');
}

function card(icon, label, value){
    return '<div class="resumo-card">' +
           '<div class="resumo-icon"><i class="fas fa-'+icon+'"></i></div>' +
           '<div class="resumo-info">' +
           '<span class="resumo-label">'+label+'</span>' +
           '<span class="resumo-valor">'+value+'</span>' +
           '</div></div>';
}

// ── Relatórios ──────────────────────────────────────────────
var _relCharts = {};
function _destroyChart(id) {
    if (_relCharts[id]) { try { _relCharts[id].destroy(); } catch(e){} delete _relCharts[id]; }
}

function carregarRelatorios() {
    var r = rangeAtual();
    fetch('funcoes/admin_metrics.php?api=admin_metrics&acao=graficos&inicio=' + encodeURIComponent(r.inicio) + '&fim=' + encodeURIComponent(r.fim))
        .then(function(res){ return res.json(); })
        .then(function(d){ if (d.sucesso) renderGraficosAdmin(d); })
        .catch(function(){ console.warn('Erro ao carregar dados de relatórios'); });
}

function relChartDefaults() {
    return {
        color: getComputedStyle(document.body).getPropertyValue('--cor-texto').trim() || '#ccc',
        gridColor: 'rgba(128,128,128,.1)',
        font: { family: 'inherit', size: 11 }
    };
}

function renderGraficosAdmin(d) {
    var def = relChartDefaults();
    var labels = d.labels || [];
    var corPrimaria  = '#F5A623';
    var corReceita   = '#4CAF50';
    var corCancelado = '#F44336';
    var corUsuarios  = '#64b5f6';

    // 1. Receita mensal (barras)
    _destroyChart('receita');
    var ctxR = document.getElementById('rel-chart-receita');
    if (ctxR) {
        _relCharts['receita'] = new Chart(ctxR, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Receita (R$)',
                    data: d.receita_mensal || [],
                    backgroundColor: corReceita + 'bb',
                    borderColor: corReceita,
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: def.gridColor }, ticks: { color: def.color, font: def.font } },
                    y: { grid: { color: def.gridColor }, ticks: { color: def.color, font: def.font,
                        callback: function(v){ return 'R$' + v.toLocaleString('pt-BR'); } } }
                }
            }
        });
    }

    // 2. Novas vs Canceladas (barras agrupadas)
    _destroyChart('assinaturas');
    var ctxA = document.getElementById('rel-chart-assinaturas');
    if (ctxA) {
        var novas = (d.assinaturas_por_mes || []).map(function(x){ return x.novas; });
        var canceladas = (d.assinaturas_por_mes || []).map(function(x){ return x.canceladas; });
        _relCharts['assinaturas'] = new Chart(ctxA, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Novas', data: novas, backgroundColor: corPrimaria + 'cc', borderColor: corPrimaria, borderWidth: 1, borderRadius: 6 },
                    { label: 'Canceladas', data: canceladas, backgroundColor: corCancelado + 'aa', borderColor: corCancelado, borderWidth: 1, borderRadius: 6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: def.color, font: def.font, boxWidth: 12 } } },
                scales: {
                    x: { grid: { color: def.gridColor }, ticks: { color: def.color, font: def.font } },
                    y: { grid: { color: def.gridColor }, ticks: { color: def.color, font: def.font } }
                }
            }
        });
    }

    // 3. Crescimento de usuários (linha)
    _destroyChart('usuarios');
    var ctxU = document.getElementById('rel-chart-usuarios');
    if (ctxU) {
        _relCharts['usuarios'] = new Chart(ctxU, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Novos usuários',
                    data: d.usuarios_por_mes || [],
                    borderColor: corUsuarios,
                    backgroundColor: corUsuarios + '22',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: corUsuarios
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: def.gridColor }, ticks: { color: def.color, font: def.font } },
                    y: { grid: { color: def.gridColor }, ticks: { color: def.color, font: def.font, stepSize: 1 }, beginAtZero: true }
                }
            }
        });
    }

    // 4. Assinantes por plano (donut)
    _destroyChart('planos');
    var ctxP = document.getElementById('rel-chart-planos');
    if (ctxP) {
        var porPlano = d.assinaturas_por_plano || [];
        var paleta = ['#F5A623','#4CAF50','#64b5f6','#a374ff','#F44336','#26C6DA'];
        if (porPlano.length === 0) {
            porPlano = [{ plano: 'Sem dados', total: 1 }];
        }
        _relCharts['planos'] = new Chart(ctxP, {
            type: 'doughnut',
            data: {
                labels: porPlano.map(function(x){ return x.plano; }),
                datasets: [{
                    data: porPlano.map(function(x){ return x.total; }),
                    backgroundColor: paleta.slice(0, porPlano.length),
                    borderWidth: 2,
                    borderColor: 'var(--cor-fundo-secundario)'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: def.color, font: def.font, boxWidth: 12, padding: 10 } }
                }
            }
        });
    }
}

// ── Inicialização ────────────────────────────────────────────
(function(){
    if (window.app && typeof window.app.configurarEventos === 'function') window.app.configurarEventos();
    var btnAplicar = document.getElementById('acao-aplicar-calendario');
    if (btnAplicar) {
        btnAplicar.addEventListener('click', function(){
            if (window.app && window.app.periodo) {
                var p = window.app.periodo;
                function fmt(d){ return d ? (d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')) : null; }
                if (p.inicio && p.fim) {
                    periodoSelecionado.preset = 'custom';
                    periodoSelecionado.inicio = fmt(p.inicio);
                    periodoSelecionado.fim = fmt(p.fim);
                } else if (p.mesSelecionado) {
                    var ini = new Date(p.mesSelecionado.ano, p.mesSelecionado.mes, 1);
                    var fi  = new Date(p.mesSelecionado.ano, p.mesSelecionado.mes+1, 0);
                    periodoSelecionado.preset = 'custom';
                    periodoSelecionado.inicio = fmt(ini);
                    periodoSelecionado.fim = fmt(fi);
                }
            }
            carregarMetricasPeriodo();
            if (window.abaSelecionada === 'relatorios') carregarRelatorios();
            if (window.abaSelecionada === 'assinaturas') carregarAssinaturas();
        });
    }
    // Restaurar aba salva ou abrir padrão
    var salva = '';
    try { salva = localStorage.getItem('adminAba') || ''; } catch(e){}
    mostrarAba(salva || 'usuarios');
    carregarPlanos();
    carregarAssinaturas();
    carregarPrevisaoMigracoes();
    carregarMetricas();
})();

function carregarMetricas(){
    fetch('funcoes/admin_usuarios.php?api=admin_usuarios&acao=listar')
        .then(r => r.json()).then(d => { window.totalUsuarios = d.length || 0; });
    carregarMetricasPeriodo();
}
</script>

<!-- Modal: Editar Plano -->
<div id="modal-editar-plano" class="modal">
  <div class="modal-conteudo">
    <div class="modal-cabecalho">
      <h2>Editar Plano</h2>
      <button class="fechar-modal" onclick="fecharModalPlano()">×</button>
    </div>
    <div class="modal-corpo">
      <input type="hidden" id="plano_id">
      <div class="form-grupo">
        <label class="form-label">Preço</label>
        <input class="form-input" type="number" step="0.01" min="0" id="plano_preco">
      </div>
      <div class="form-grupo">
        <label class="form-label">Duração (meses)</label>
        <input class="form-input" type="number" step="1" min="1" id="plano_duracao">
      </div>
      <div class="form-grupo">
        <label class="form-label">Price ID (Stripe)</label>
        <input class="form-input" type="text" id="plano_price">
      </div>
      <div class="admin-checkbox-grupo">
        <label class="admin-checkbox-label">
          <input type="checkbox" id="plano_whatsapp" class="admin-checkbox">
          <span><i class="fab fa-whatsapp" style="color:#25D366;margin-right:4px"></i> Inclui integração com WhatsApp</span>
        </label>
      </div>
      <div class="admin-form-acoes">
        <button class="botao botao-primario" onclick="salvarModalPlano()">Salvar</button>
        <button class="botao botao-secundario" onclick="fecharModalPlano()">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Editar Usuário -->
<div id="modal-editar-usuario" class="modal">
  <div class="modal-conteudo">
    <div class="modal-cabecalho">
      <h2>Editar Usuário</h2>
      <button class="fechar-modal" onclick="fecharModalUsuario()">×</button>
    </div>
    <div class="modal-corpo">
      <input type="hidden" id="usuario_id">
      <div class="form-grupo">
        <label class="form-label">Nome</label>
        <input class="form-input" type="text" id="usuario_nome">
      </div>
      <div class="form-grupo">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" id="usuario_email">
      </div>
      <div class="form-grupo">
        <label class="form-label">Perfil</label>
        <select class="form-select" id="usuario_perfil">
          <option value="usuario">Usuário</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="form-grupo">
        <label class="form-label">Status</label>
        <select class="form-select" id="usuario_status">
          <option value="ativo">Ativo</option>
          <option value="inativo">Inativo</option>
        </select>
      </div>
      <div class="admin-form-acoes">
        <button class="botao botao-primario" onclick="salvarModalUsuario()">Salvar</button>
        <button class="botao botao-secundario" onclick="fecharModalUsuario()">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Criar Assinatura -->
<div id="modal-criar-assinatura" class="modal">
  <div class="modal-conteudo">
    <div class="modal-cabecalho">
      <h2>Nova Assinatura</h2>
      <button class="fechar-modal" onclick="fecharModalCriarAssinatura()">×</button>
    </div>
    <div class="modal-corpo">
      <div class="form-grupo">
        <label class="form-label">Usuário</label>
        <select class="form-select" id="nova_ass_usuario_id"></select>
      </div>
      <div class="form-grupo">
        <label class="form-label">Plano</label>
        <select class="form-select" id="nova_ass_plano_id"></select>
      </div>
      <div class="form-grupo">
        <label class="form-label">Status</label>
        <select class="form-select" id="nova_ass_status">
          <option value="ativa">Ativa</option>
          <option value="pendente">Pendente</option>
          <option value="suspensa">Suspensa</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="admin-form-acoes">
        <button class="botao botao-primario" onclick="salvarCriacaoAssinatura()">Criar</button>
        <button class="botao botao-secundario" onclick="fecharModalCriarAssinatura()">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Editar Assinatura -->
<div id="modal-editar-assinatura" class="modal">
  <div class="modal-conteudo">
    <div class="modal-cabecalho">
      <h2>Editar Assinatura</h2>
      <button class="fechar-modal" onclick="fecharModalAssinatura()">×</button>
    </div>
    <div class="modal-corpo">
      <input type="hidden" id="ass_id">
      <div class="form-grupo">
        <label class="form-label">ID do Plano</label>
        <input class="form-input" type="number" min="1" id="ass_plano_id" placeholder="ID do plano">
      </div>
      <div class="form-grupo">
        <label class="form-label">Status</label>
        <select class="form-select" id="ass_status">
          <option value="ativa">Ativa</option>
          <option value="pendente">Pendente</option>
          <option value="suspensa">Suspensa</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="admin-form-acoes">
        <button class="botao botao-primario" onclick="salvarModalAssinatura()">Salvar</button>
        <button class="botao botao-secundario" onclick="fecharModalAssinatura()">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Criar Plano -->
<div id="modal-criar-plano" class="modal">
  <div class="modal-conteudo">
    <div class="modal-cabecalho">
      <h2>Novo Plano</h2>
      <button class="fechar-modal" onclick="fecharModalCriarPlano()">×</button>
    </div>
    <div class="modal-corpo">
      <div class="form-grupo">
        <label class="form-label">Nome</label>
        <input class="form-input" type="text" id="novo_plano_nome">
      </div>
      <div class="form-grupo">
        <label class="form-label">Descrição</label>
        <input class="form-input" type="text" id="novo_plano_desc">
      </div>
      <div class="form-grupo">
        <label class="form-label">Preço</label>
        <input class="form-input" type="number" step="0.01" min="0" id="novo_plano_preco" value="0">
      </div>
      <div class="form-grupo">
        <label class="form-label">Duração (meses)</label>
        <input class="form-input" type="number" step="1" min="1" id="novo_plano_duracao" value="1">
      </div>
      <div class="form-grupo">
        <label class="form-label">Dias de teste grátis <span style="font-size:.78rem;color:var(--cor-texto-secundario);font-weight:400">(0 = sem teste)</span></label>
        <input class="form-input" type="number" step="1" min="0" id="novo_plano_trial" value="0" placeholder="Ex: 7, 14, 30">
      </div>
      <div class="admin-checkbox-grupo">
        <label class="admin-checkbox-label">
          <input type="checkbox" id="novo_plano_whatsapp" class="admin-checkbox">
          <span><i class="fab fa-whatsapp" style="color:#25D366;margin-right:4px"></i> Inclui integração com WhatsApp</span>
        </label>
      </div>
      <div class="admin-form-acoes">
        <button class="botao botao-primario" onclick="salvarCriacaoPlano()">Criar</button>
        <button class="botao botao-secundario" onclick="fecharModalCriarPlano()">Cancelar</button>
      </div>
    </div>
  </div>
</div>
