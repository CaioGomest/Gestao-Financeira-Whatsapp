<?php
require_once __DIR__ . '/../funcoes/usuario.php';
require_once __DIR__ . '/../funcoes/transacoes.php';
require_once __DIR__ . '/../funcoes/configuracoes.php';

$usuario_id = usuarioLogado() ? obterUsuarioId() : 1;
if (usuarioLogado()) {
    $usuario_atual = obterDadosUsuario();
} else {
    global $database;
    $sqlUsuario = "SELECT id, nome, email, foto_perfil, telefone, cpf, whatsapp_numero FROM usuarios WHERE id = 1";
    $usuarios = $database->select($sqlUsuario);
    $usuario_atual = !empty($usuarios) ? $usuarios[0] : ['id' => 1, 'nome' => 'Usuário', 'email' => 'usuario@email.com'];
}

$saldo_total = calcularSaldoTotal($usuario_id);
$configuracoes = lerConfiguracoes($usuario_id);
$simbolo_moeda = $configuracoes['preferencias']['simbolo_moeda'] ?? 'R$';

// Assinatura e plano real do usuário
$assinPerfil = $database->select(
    "SELECT a.status, a.plano_id, p.nome AS plano_nome, p.preco, p.descricao
     FROM assinaturas a
     LEFT JOIN planos p ON p.id = a.plano_id
     WHERE a.usuario_id = ? ORDER BY a.id DESC LIMIT 1",
    [$usuario_id]
);
$assinPerfil = !empty($assinPerfil) ? $assinPerfil[0] : null;
$isAtivaPerf = $assinPerfil && in_array(strtolower($assinPerfil['status'] ?? ''), ['ativa','ativo','trialing']);
$nomePlanoPerfil = ($isAtivaPerf && !empty($assinPerfil['plano_nome'])) ? $assinPerfil['plano_nome'] : 'Gratuito';
$descPlanoPerfil = ($isAtivaPerf && !empty($assinPerfil['descricao'])) ? $assinPerfil['descricao'] : 'Controle básico de finanças';

// Nível do plano para features
function nivelPlanoPerfil($nome) {
    $n = strtolower($nome ?? '');
    if (strpos($n, 'empre') !== false) return 2;
    if (strpos($n, 'pro') !== false || strpos($n, 'prem') !== false) return 1;
    return 0;
}
$nivelPerfil = nivelPlanoPerfil($nomePlanoPerfil);
$featuresPerfil = [
    ['label' => 'Controle de transações', 'min' => 0],
    ['label' => 'Categorias ilimitadas',   'min' => 0],
    ['label' => 'Dashboard financeiro',    'min' => 0],
    ['label' => 'Relatórios avançados',    'min' => 1],
    ['label' => 'Exportação em PDF',       'min' => 1],
];

if (!function_exists('formatar_moeda_php')) {
    function formatar_moeda_php($valor, $simbolo = 'R$') {
        return $simbolo . ' ' . number_format((float)$valor, 2, ',', '.');
    }
}
$nome_usuario = $usuario_atual['nome'] ?? 'Usuário';
?>
<style>
.pagina-perfil { display:flex; flex-direction:column; gap:16px; }
.perf-grid { display:grid; grid-template-columns:1fr; gap:16px; }
@media(min-width:720px){
  .perf-grid { grid-template-columns:1fr 1fr; align-items:start; }
  .perf-grid-full { grid-column:1/-1; }
}

/* Hero */
.perf-hero { display:flex; flex-direction:row; align-items:center; gap:20px; padding:20px 24px; background:var(--cor-fundo-secundario); border:1px solid var(--cor-borda); border-radius:20px; }
.perf-hero-info { display:flex; flex-direction:column; gap:4px; flex:1; }
.perf-hero-name { font-size:1.15rem; }
.perf-hero-email { margin-bottom:0; }
.perf-hero-actions { display:flex; align-items:center; gap:8px; margin-top:4px; flex-wrap:wrap; }
@media(max-width:480px){
  .perf-hero { flex-direction:column; text-align:center; }
  .perf-hero-info { align-items:center; }
}
.perf-avatar-wrap { position:relative; cursor:pointer; margin-bottom:6px; }
.perf-avatar { width:88px; height:88px; border-radius:50%; background:linear-gradient(135deg,#F5A623,#f77c0a); display:flex; align-items:center; justify-content:center; font-size:1.9rem; font-weight:700; color:#111; border:3px solid rgba(245,166,35,.4); box-shadow:0 0 0 5px rgba(245,166,35,.1),0 8px 20px rgba(245,166,35,.2); overflow:hidden; transition:transform .2s; }
.perf-avatar img { width:100%; height:100%; object-fit:cover; }
.perf-avatar-overlay { position:absolute; inset:0; border-radius:50%; background:rgba(0,0,0,.5); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem; opacity:0; transition:opacity .2s; }
.perf-avatar-wrap:hover .perf-avatar-overlay { opacity:1; }
.perf-avatar-wrap:hover .perf-avatar { transform:scale(1.04); }
.perf-hero-name { font-size:1.25rem; font-weight:700; color:var(--cor-texto); }
.perf-hero-email { font-size:.86rem; color:var(--cor-texto-secundario); margin-bottom:4px; }
.perf-plan-badge { display:inline-flex; align-items:center; gap:4px; padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:600; background:rgba(245,166,35,.1); border:1px solid rgba(245,166,35,.28); color:#F5A623; margin-bottom:4px; }
.perf-hero-editar { margin-top:6px; padding:7px 18px; background:rgba(var(--cor-destaque-rgb),.1); border:1px solid rgba(var(--cor-destaque-rgb),.28); border-radius:20px; color:var(--cor-destaque); font-size:.84rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:background .2s,transform .15s; }
.perf-hero-editar:hover { background:rgba(var(--cor-destaque-rgb),.2); transform:translateY(-1px); }

/* Settings groups */
.perf-grp { display:flex; flex-direction:column; gap:0; }
.perf-grp-lbl { font-size:.71rem; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--cor-texto-secundario); padding:0 4px 6px; opacity:.65; }
.perf-grp-card { background:var(--cor-fundo-secundario); border:1px solid var(--cor-borda); border-radius:16px; overflow:hidden; }
.perf-row { display:flex; align-items:center; gap:12px; padding:12px 14px; border-bottom:1px solid rgba(128,128,128,.09); cursor:pointer; transition:background .12s; text-align:left; width:100%; background:transparent; border-left:none; border-right:none; border-top:none; }
.perf-row:last-child { border-bottom:none; }
.perf-row:not(.no-click):hover { background:rgba(128,128,128,.05); }
.perf-row.no-click { cursor:default; }
.perf-row-ico { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.82rem; flex-shrink:0; }
.perf-row-body { flex:1; min-width:0; }
.perf-row-ttl { font-size:.9rem; color:var(--cor-texto); font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.perf-row-sub { font-size:.75rem; color:var(--cor-texto-secundario); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.perf-row-val { font-size:.83rem; color:var(--cor-texto-secundario); flex-shrink:0; max-width:130px; text-align:right; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.perf-row-val.empty { font-style:italic; font-size:.8rem; }
.perf-row-val.ok { color:var(--cor-texto); }
.perf-row-arrow { color:rgba(128,128,128,.35); font-size:.72rem; flex-shrink:0; }
.perf-dot-ok { width:7px; height:7px; border-radius:50%; background:#22C55E; flex-shrink:0; }
.perf-dot-warn { width:7px; height:7px; border-radius:50%; background:#F5A623; flex-shrink:0; }

/* Plan features */
.perf-feat { display:flex; align-items:center; gap:8px; padding:3px 0; font-size:.85rem; color:var(--cor-texto-secundario); }
.perf-feat.on { color:var(--cor-texto); }
.perf-feat .ck { color:#22C55E; }
.perf-feat .nx { color:var(--cor-borda); }
.perf-upgrade { width:100%; padding:12px; border-radius:12px; background:linear-gradient(135deg,#F5A623,#f77c0a); border:none; color:#111; font-size:.92rem; font-weight:700; cursor:pointer; transition:transform .15s,box-shadow .2s; box-shadow:0 4px 14px rgba(245,166,35,.3); display:flex; align-items:center; justify-content:center; gap:8px; }
.perf-upgrade:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(245,166,35,.4); }

/* Inline edit rows */
.perf-row-edit { display:flex; }
#conta-edit-actions { display:flex; }
.perf-grp-card.editing .perf-grp-lbl { color:var(--cor-destaque); }

/* Toggle */
.perf-toggle { width:46px; height:25px; background:var(--cor-borda); border-radius:13px; position:relative; cursor:pointer; transition:background .25s; flex-shrink:0; border:none; }
.perf-toggle::after { content:''; position:absolute; top:3px; left:3px; width:19px; height:19px; background:#fff; border-radius:50%; transition:transform .25s cubic-bezier(.4,0,.2,1); box-shadow:0 1px 4px rgba(0,0,0,.2); }
.perf-toggle.on { background:var(--cor-destaque); }
.perf-toggle.on::after { transform:translateX(21px); }
.perf-sel { padding:6px 10px; border-radius:8px; border:1px solid var(--cor-borda); background:var(--cor-fundo); color:var(--cor-texto); font-size:.84rem; cursor:pointer; }

/* Pass form */
.perf-pass-form { display:flex; flex-direction:column; gap:10px; padding:4px 0 6px; }

/* Inputs */
.perf-inp-grp { display:flex; flex-direction:column; gap:4px; }
.perf-inp-grp label { font-size:.78rem; color:var(--cor-texto-secundario); font-weight:500; }
.perf-inp { padding:10px 12px; border:1px solid var(--cor-borda); border-radius:10px; background:var(--cor-fundo); color:var(--cor-texto); font-size:.9rem; transition:border-color .2s,box-shadow .2s; width:100%; }
.perf-inp:focus { outline:none; border-color:rgba(245,166,35,.6); box-shadow:0 0 0 3px rgba(245,166,35,.1); }
.perf-btn-pry { padding:10px 16px; border-radius:10px; background:var(--cor-destaque); border:none; color:#111; font-size:.9rem; font-weight:700; cursor:pointer; transition:transform .15s; }
.perf-btn-pry:hover { transform:translateY(-1px); }
.perf-btn-pry:disabled { opacity:.6; cursor:not-allowed; transform:none; }
.perf-btn-gst { padding:10px 16px; border-radius:10px; background:transparent; border:1px solid var(--cor-borda); color:var(--cor-texto-secundario); font-size:.9rem; cursor:pointer; transition:border-color .2s,color .2s; }
.perf-btn-gst:hover { border-color:var(--cor-destaque); color:var(--cor-destaque); }
.perf-pass-form { display:flex; flex-direction:column; gap:10px; padding:8px 0 4px; }

/* Logout */
.perf-logout { width:100%; padding:14px; border-radius:14px; background:rgba(239,68,68,.07); border:1px solid rgba(239,68,68,.18); color:#EF4444; font-size:.92rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all .2s; }
.perf-logout:hover { background:rgba(239,68,68,.14); border-color:rgba(239,68,68,.35); transform:translateY(-1px); }
.perf-footer-ver { text-align:center; font-size:.76rem; color:var(--cor-texto-secundario); opacity:.45; padding:2px 0 4px; }

/* Confirm */
.perf-confirm-bg { position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:1100; display:flex; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(4px); animation:pfade .2s ease; }
.perf-confirm-box { background:var(--cor-fundo-secundario); border-radius:20px; padding:24px 20px 20px; max-width:300px; width:100%; text-align:center; animation:pscale .25s cubic-bezier(.4,0,.2,1); }
.perf-confirm-ico { font-size:2.2rem; margin-bottom:10px; }
.perf-confirm-ttl { font-size:1.05rem; font-weight:700; color:var(--cor-texto); margin-bottom:4px; }
.perf-confirm-msg { font-size:.86rem; color:var(--cor-texto-secundario); margin-bottom:18px; }
.perf-confirm-acts { display:flex; gap:8px; }

/* Toast */
#perf-toasts { position:fixed; top:68px; right:12px; z-index:9999; display:flex; flex-direction:column; gap:8px; pointer-events:none; }
.perf-toast { padding:10px 14px; border-radius:12px; color:#fff; font-size:.83rem; font-weight:500; display:flex; align-items:center; gap:8px; transform:translateX(130%); opacity:0; transition:transform .3s cubic-bezier(.4,0,.2,1),opacity .3s; max-width:260px; box-shadow:0 4px 14px rgba(0,0,0,.2); pointer-events:auto; }
.perf-toast.show { transform:translateX(0); opacity:1; }
.perf-toast.success { background:#22C55E; }
.perf-toast.error { background:#EF4444; }
.perf-toast.info { background:#3B82F6; }

@keyframes pfade { from{opacity:0} to{opacity:1} }
@keyframes pslide { from{transform:translateY(40px);opacity:0} to{transform:translateY(0);opacity:1} }
@keyframes pscale { from{transform:scale(.9);opacity:0} to{transform:scale(1);opacity:1} }

/* Modal de edição por campo */
.perf-modal-overlay { position:absolute; inset:0; background:rgba(0,0,0,.55); backdrop-filter:blur(4px); animation:pfade .2s ease; }
.perf-modal-box { position:absolute; bottom:0; left:0; right:0; max-width:480px; margin:0 auto; background:var(--cor-fundo-secundario); border:1px solid var(--cor-borda); border-radius:20px 20px 0 0; padding:20px 20px 36px; animation:pslide .25s cubic-bezier(.4,0,.2,1); }
@media(min-width:520px){ .perf-modal-box { top:50%; bottom:auto; transform:translateY(-50%); border-radius:20px; padding:24px 24px 28px; } }
.perf-modal-hdr { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
.perf-modal-footer { display:flex; gap:8px; margin-top:18px; }
</style>

<div id="perf-toasts"></div>

<div class="pagina-perfil">

    <!-- Hero -->
    <div class="perf-hero">
        <div class="perf-avatar-wrap" onclick="perfil.abrirUpload()">
            <div class="perf-avatar" id="perf-avatar"></div>
            <div class="perf-avatar-overlay"><i class="fas fa-camera"></i></div>
        </div>
        <input type="file" id="perf-file-inp" accept="image/*" style="display:none" onchange="perfil.handleUpload(this)">
        <div class="perf-hero-info">
            <div class="perf-hero-name" id="perf-nome-hero"></div>
            <div class="perf-hero-email" id="perf-email-hero"></div>
            <div class="perf-hero-actions">
                <div class="perf-plan-badge"><i class="fas fa-bolt" style="font-size:.65rem"></i> Plano Gratuito</div>
            </div>
        </div>
    </div>

    <div class="perf-grid">

    <!-- COL ESQUERDA -->
    <div style="display:flex;flex-direction:column;gap:16px">

    <!-- Minha Conta -->
    <div class="perf-grp">
        <div class="perf-grp-lbl">Minha Conta</div>
        <div class="perf-grp-card">
            <!-- nome -->
            <button class="perf-row" onclick="perfil.abrirModal('nome')">
                <div class="perf-row-ico" style="background:rgba(245,166,35,.12);color:#F5A623"><i class="fas fa-user"></i></div>
                <div class="perf-row-body"><div class="perf-row-ttl">Nome</div></div>
                <div class="perf-row-val ok" id="pf-row-nome">—</div>
                <i class="fas fa-chevron-right perf-row-arrow"></i>
            </button>

            <!-- email -->
            <button class="perf-row" onclick="perfil.abrirModal('email')">
                <div class="perf-row-ico" style="background:rgba(59,130,246,.1);color:#3B82F6"><i class="fas fa-envelope"></i></div>
                <div class="perf-row-body"><div class="perf-row-ttl">E-mail</div></div>
                <div class="perf-row-val ok" id="pf-row-email">—</div>
                <i class="fas fa-chevron-right perf-row-arrow"></i>
            </button>

            <!-- telefone / whatsapp (campo unificado) -->
            <button class="perf-row" onclick="perfil.abrirModal('telefone')">
                <div class="perf-row-ico" style="background:rgba(34,197,94,.1);color:#22C55E"><i class="fab fa-whatsapp"></i></div>
                <div class="perf-row-body"><div class="perf-row-ttl">Telefone / WhatsApp</div><div class="perf-row-sub">Para alertas e notificações</div></div>
                <div class="perf-row-val empty" id="pf-tel">Não informado</div>
                <div id="st-tel" class="perf-dot-warn"></div>
                <i class="fas fa-chevron-right perf-row-arrow"></i>
            </button>

            <!-- CPF -->
            <button class="perf-row" onclick="perfil.abrirModal('cpf')">
                <div class="perf-row-ico" style="background:rgba(139,92,246,.1);color:#8B5CF6"><i class="fas fa-id-card"></i></div>
                <div class="perf-row-body"><div class="perf-row-ttl">CPF <span style="font-size:.7rem;opacity:.45" title="Usado para relatórios fiscais"><i class="fas fa-info-circle"></i></span></div></div>
                <div class="perf-row-val empty" id="pf-cpf">Não informado</div>
                <div id="st-cpf" class="perf-dot-warn"></div>
                <i class="fas fa-chevron-right perf-row-arrow"></i>
            </button>
        </div>
    </div>

    <!-- Segurança -->
    <div class="perf-grp">
        <div class="perf-grp-lbl">Segurança</div>
        <div class="perf-grp-card">
            <button class="perf-row" id="seg-btn-row" onclick="perfil.abrirSenha()">
                <div class="perf-row-ico" style="background:rgba(34,197,94,.1);color:#22C55E"><i class="fas fa-key"></i></div>
                <div class="perf-row-body"><div class="perf-row-ttl">Trocar senha</div></div>
                <i class="fas fa-chevron-right perf-row-arrow" id="seg-chevron"></i>
            </button>
            <div id="seg-form" style="display:none;padding:4px 14px 14px;border-bottom:1px solid rgba(128,128,128,.09)">
                <div class="perf-pass-form">
                    <div style="font-size:.82rem;color:var(--cor-texto-secundario)"><i class="fas fa-info-circle"></i> Um código será enviado para o seu email.</div>
                    <div id="seg-code-area" style="display:none">
                        <div class="perf-inp-grp">
                            <label>Código recebido no email</label>
                            <input type="text" class="perf-inp" id="seg-codigo" placeholder="000000" maxlength="6">
                        </div>
                    </div>
                    <div class="perf-inp-grp">
                        <label>Nova senha</label>
                        <input type="password" class="perf-inp" id="seg-nova" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="perf-inp-grp">
                        <label>Confirmar nova senha</label>
                        <input type="password" class="perf-inp" id="seg-conf" placeholder="Repita a nova senha">
                    </div>
                    <div style="display:flex;gap:8px">
                        <button class="perf-btn-gst" style="flex:1" onclick="perfil.fecharSenha()">Cancelar</button>
                        <button class="perf-btn-pry" style="flex:1" id="seg-save" onclick="perfil.salvarSenha()">Enviar código</button>
                    </div>
                </div>
            </div>
            <div class="perf-row no-click">
                <div class="perf-row-ico" style="background:rgba(239,68,68,.1);color:#EF4444"><i class="fas fa-shield-alt"></i></div>
                <div class="perf-row-body">
                    <div class="perf-row-ttl">Autenticação 2FA</div>
                    <div class="perf-row-sub">Camada extra de proteção</div>
                </div>
                <span style="font-size:.72rem;color:var(--cor-texto-secundario);background:var(--cor-borda);padding:3px 8px;border-radius:6px;flex-shrink:0">Em breve</span>
            </div>
        </div>
    </div>

    </div><!-- /col esquerda -->

    <!-- COL DIREITA -->
    <div style="display:flex;flex-direction:column;gap:16px">

    <!-- Preferências -->
    <div class="perf-grp">
        <div class="perf-grp-lbl">Preferências</div>
        <div class="perf-grp-card">
            <div class="perf-row no-click">
                <div class="perf-row-ico" style="background:rgba(99,102,241,.1);color:#6366F1"><i class="fas fa-moon"></i></div>
                <div class="perf-row-body"><div class="perf-row-ttl">Tema escuro</div></div>
                <button class="perf-toggle" id="tog-tema" onclick="perfil.toggleTema()"></button>
            </div>
            <div class="perf-row no-click">
                <div class="perf-row-ico" style="background:rgba(245,166,35,.1);color:#F5A623"><i class="fas fa-eye-slash"></i></div>
                <div class="perf-row-body">
                    <div class="perf-row-ttl">Ocultar valores</div>
                    <div class="perf-row-sub">Esconde saldos financeiros</div>
                </div>
                <button class="perf-toggle" id="tog-ocultar" onclick="perfil.toggleOcultar()"></button>
            </div>
            <div class="perf-row no-click">
                <div class="perf-row-ico" style="background:rgba(34,197,94,.1);color:#22C55E"><i class="fas fa-coins"></i></div>
                <div class="perf-row-body"><div class="perf-row-ttl">Moeda padrão</div></div>
                <select class="perf-sel" id="sel-moeda" onchange="perfil.setMoeda(this.value)">
                    <option value="BRL">R$ Real</option>
                    <option value="USD">$ Dólar</option>
                    <option value="EUR">€ Euro</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Plano -->
    <div class="perf-grp">
        <div class="perf-grp-lbl">Plano &amp; Assinatura</div>
        <div class="perf-grp-card">
            <div class="perf-row no-click">
                <div class="perf-row-ico" style="background:rgba(108,99,255,.12);color:#6C63FF"><i class="fas fa-<?php echo $nivelPerfil >= 2 ? 'building' : ($nivelPerfil >= 1 ? 'crown' : 'seedling'); ?>"></i></div>
                <div class="perf-row-body">
                    <div class="perf-row-ttl"><?php echo htmlspecialchars($nomePlanoPerfil); ?></div>
                    <div class="perf-row-sub"><?php echo htmlspecialchars($descPlanoPerfil); ?></div>
                </div>
                <?php if ($isAtivaPerf): ?>
                    <span style="font-size:.72rem;padding:3px 8px;border-radius:6px;background:rgba(76,175,80,.12);color:#4CAF50;font-weight:600;flex-shrink:0;border:1px solid rgba(76,175,80,.3);">ATIVA</span>
                <?php elseif ($assinPerfil && $assinPerfil['status'] === 'pendente'): ?>
                    <span style="font-size:.72rem;padding:3px 8px;border-radius:6px;background:rgba(255,193,7,.12);color:#d4a017;font-weight:600;flex-shrink:0;">PENDENTE</span>
                <?php else: ?>
                    <span style="font-size:.72rem;padding:3px 8px;border-radius:6px;background:rgba(245,166,35,.14);color:#F5A623;font-weight:600;flex-shrink:0">GRATUITO</span>
                <?php endif; ?>
            </div>
            <div style="padding:4px 14px 8px;display:flex;flex-direction:column;gap:2px;border-bottom:1px solid rgba(128,128,128,.09)">
                <?php foreach ($featuresPerfil as $feat):
                    $tem = ($nivelPerfil >= $feat['min']);
                ?>
                <div class="perf-feat <?php echo $tem ? 'on' : ''; ?>">
                    <i class="fas fa-<?php echo $tem ? 'check ck' : 'times nx'; ?>"></i>
                    <?php echo htmlspecialchars($feat['label']); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="padding:12px 14px">
                <?php if ($nivelPerfil < 1): ?>
                <button class="perf-upgrade" onclick="typeof carregarPagina==='function'?carregarPagina('assinatura'):(window.location.href='index.php?pagina=assinatura')">
                    <i class="fas fa-rocket"></i> Fazer upgrade para PRO
                </button>
                <?php else: ?>
                <button class="perf-upgrade" onclick="typeof carregarPagina==='function'?carregarPagina('assinatura'):(window.location.href='index.php?pagina=assinatura')" style="background:linear-gradient(135deg,#4CAF50,#2e7d32);">
                    <i class="fas fa-receipt"></i> Gerenciar assinatura
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dados & Privacidade -->
    <div class="perf-grp">
        <div class="perf-grp-lbl">Dados &amp; Privacidade</div>
        <div class="perf-grp-card">
            <button class="perf-row" onclick="perfil.exportar()">
                <div class="perf-row-ico" style="background:rgba(59,130,246,.1);color:#3B82F6"><i class="fas fa-download"></i></div>
                <div class="perf-row-body">
                    <div class="perf-row-ttl">Exportar dados</div>
                    <div class="perf-row-sub">Backup em formato JSON</div>
                </div>
                <i class="fas fa-chevron-right perf-row-arrow"></i>
            </button>
            <button class="perf-row" onclick="perfil.toast('Acesse: peak.app/privacidade','info')">
                <div class="perf-row-ico" style="background:rgba(99,102,241,.1);color:#6366F1"><i class="fas fa-file-alt"></i></div>
                <div class="perf-row-body"><div class="perf-row-ttl">Política de Privacidade</div></div>
                <i class="fas fa-external-link-alt perf-row-arrow"></i>
            </button>
            <button class="perf-row" onclick="perfil.toast('Contato: suporte@peak.app','info')">
                <div class="perf-row-ico" style="background:rgba(239,68,68,.1);color:#EF4444"><i class="fas fa-trash-alt"></i></div>
                <div class="perf-row-body"><div class="perf-row-ttl" style="color:#EF4444">Excluir conta</div></div>
                <i class="fas fa-chevron-right perf-row-arrow"></i>
            </button>
        </div>
    </div>

    </div><!-- /col direita -->
    </div><!-- /perf-grid -->

    <!-- Sair -->
    <button class="perf-logout" onclick="perfil.confirmarSair()">
        <i class="fas fa-sign-out-alt"></i> Sair da conta
    </button>

    <div class="perf-footer-ver">Peak Gestão Financeira · v1.0.0</div>

</div><!-- /pagina-perfil -->

<!-- Modal de edição por campo -->
<div id="perf-edit-modal" style="display:none;position:fixed;inset:0;z-index:1200">
    <div class="perf-modal-overlay" onclick="perfil.fecharModal()"></div>
    <div class="perf-modal-box">
        <div class="perf-modal-hdr">
            <span id="perf-modal-ttl" style="font-size:1rem;font-weight:700;color:var(--cor-texto)">Editar</span>
            <button onclick="perfil.fecharModal()" style="background:none;border:none;color:var(--cor-texto-secundario);font-size:1.1rem;cursor:pointer;padding:4px 8px;border-radius:8px;line-height:1"><i class="fas fa-times"></i></button>
        </div>
        <div id="perf-modal-body" style="display:flex;flex-direction:column;gap:10px"></div>
        <div id="perf-modal-step2" style="display:none;margin-top:14px;padding-top:14px;border-top:1px solid var(--cor-borda)">
            <div style="font-size:.83rem;color:var(--cor-texto-secundario);margin-bottom:10px"><i class="fas fa-envelope"></i> Código enviado para o novo e-mail. Válido por 15 min.</div>
            <div class="perf-inp-grp">
                <label style="font-size:.78rem;color:var(--cor-texto-secundario);font-weight:500">Código de verificação</label>
                <input type="text" class="perf-inp" id="perf-modal-codigo" maxlength="6" placeholder="000000" style="letter-spacing:6px;font-size:1.1rem;text-align:center">
            </div>
        </div>
        <div class="perf-modal-footer">
            <button class="perf-btn-gst" style="flex:1" onclick="perfil.fecharModal()">Cancelar</button>
            <button class="perf-btn-pry" style="flex:1" id="perf-modal-save" onclick="perfil.salvarModal()">Salvar</button>
        </div>
    </div>
</div>

<!-- Confirm Dialog -->
<div class="perf-confirm-bg" id="perf-cfm" style="display:none">
    <div class="perf-confirm-box">
        <div class="perf-confirm-ico" id="cfm-ico">⚠️</div>
        <div class="perf-confirm-ttl" id="cfm-ttl">Confirmar</div>
        <div class="perf-confirm-msg" id="cfm-msg">Tem certeza?</div>
        <div class="perf-confirm-acts">
            <button class="perf-btn-gst" style="flex:1" id="cfm-cancel">Cancelar</button>
            <button style="flex:1;padding:10px;border-radius:10px;background:#EF4444;border:none;color:#fff;font-weight:700;cursor:pointer" id="cfm-ok">Confirmar</button>
        </div>
    </div>
</div>

<script>
var dadosUsuario = <?php echo json_encode($usuario_atual, JSON_UNESCAPED_UNICODE); ?>;

var perfil = (function(){
'use strict';

var S = {
    u: Object.assign({}, dadosUsuario || {}),
    tema: localStorage.getItem('temaEscuro') === 'true',
    ocultar: localStorage.getItem('valoresOcultos') === 'true',
    moeda: localStorage.getItem('moedaPadrao') || 'BRL',
    segStep: 0, // 0=fechado, 1=form-send, 2=form-code
};

function url(p){ var b=window.location.pathname||''; ['index.php','paginas/','modais/'].forEach(function(x){ if(b.includes(x)) b=b.split(x)[0]; }); if(!b.endsWith('/')) b+='/'; return b+(p||'').replace(/^\//,''); }
function initials(n){ if(!n) return '?'; var p=n.trim().split(' '); return p.length>=2?(p[0][0]+p[p.length-1][0]).toUpperCase():p[0].slice(0,2).toUpperCase(); }
function el(id){ return document.getElementById(id); }

function toast(msg, type){
    type=type||'success';
    var c=el('perf-toasts'); if(!c) return;
    var d=document.createElement('div');
    var icons={success:'fa-check-circle',error:'fa-exclamation-circle',info:'fa-info-circle'};
    d.className='perf-toast '+type;
    d.innerHTML='<i class="fas '+(icons[type]||'fa-info-circle')+'"></i><span>'+msg+'</span>';
    c.appendChild(d);
    setTimeout(function(){ d.classList.add('show'); },20);
    setTimeout(function(){ d.classList.remove('show'); setTimeout(function(){ d.remove(); },350); },3500);
}

function confirm_(opts){
    var bg=el('perf-cfm'); if(!bg){ opts.onOk&&opts.onOk(); return; }
    el('cfm-ico').textContent=opts.icon||'⚠️';
    el('cfm-ttl').textContent=opts.title||'Confirmar';
    el('cfm-msg').textContent=opts.msg||'Tem certeza?';
    el('cfm-ok').textContent=opts.okTxt||'Confirmar';
    el('cfm-ok').style.background=opts.danger?'#EF4444':'#F5A623';
    bg.style.display='flex';
    function close(){ bg.style.display='none'; }
    el('cfm-ok').onclick=function(){ close(); opts.onOk&&opts.onOk(); };
    el('cfm-cancel').onclick=close;
}

function status(valId, stId, val){
    var v=el(valId), s=el(stId); if(!v||!s) return;
    if(val){ v.textContent=val; v.className='perf-row-val ok'; s.className='perf-dot-ok'; }
    else { v.textContent='Não informado'; v.className='perf-row-val empty'; s.className='perf-dot-warn'; }
}

function renderHero(){
    var n=S.u.nome||'Usuário', e=S.u.email||'', foto=S.u.foto_perfil||'';
    var av=el('perf-avatar');
    if(av){ if(foto){ av.innerHTML='<img src="'+foto+'" alt="avatar" style="width:100%;height:100%;object-fit:cover">'; } else { av.textContent=initials(n); av.style.fontSize='1.9rem'; } }
    if(el('perf-nome-hero')) el('perf-nome-hero').textContent=n;
    if(el('perf-email-hero')) el('perf-email-hero').textContent=e;
}


function renderFields(){
    var u=S.u;
    var nomEl=el('pf-row-nome'); if(nomEl){ nomEl.textContent=u.nome||'—'; nomEl.className=u.nome?'perf-row-val ok':'perf-row-val empty'; }
    var emlEl=el('pf-row-email'); if(emlEl){ emlEl.textContent=u.email||'—'; emlEl.className=u.email?'perf-row-val ok':'perf-row-val empty'; }
    var tel=u.whatsapp_numero||u.telefone||'';
    status('pf-tel','st-tel',tel?fmtTelDisplay(tel):'');
    status('pf-cpf','st-cpf',u.cpf);
}

function renderToggles(){
    var tt=el('tog-tema'), to=el('tog-ocultar'), sm=el('sel-moeda');
    if(tt) tt.className='perf-toggle'+(S.tema?' on':'');
    if(to) to.className='perf-toggle'+(S.ocultar?' on':'');
    if(sm) sm.value=S.moeda;
}

function toggle(id){
    var w=el('wrap-'+id); if(!w) return;
    w.classList.toggle('open');
}


function abrirUpload(){ var f=el('perf-file-inp'); if(f) f.click(); }

function handleUpload(input){
    if(!input.files||!input.files[0]) return;
    var r=new FileReader();
    r.onload=function(e){
        var av=el('perf-avatar'); if(av) av.innerHTML='<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover">';
        S.u.foto_perfil=e.target.result;
        toast('Foto atualizada! Salve o perfil.','info');
    };
    r.readAsDataURL(input.files[0]);
}

function mskCPF(inp){ var v=inp.value.replace(/\D/g,'').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2'); inp.value=v; }
function mskTel(inp){ var v=inp.value.replace(/\D/g,'').replace(/^(\d{2})(\d)/,'($1) $2').replace(/(\d{5})(\d{1,4})$/,'$1-$2'); inp.value=v; }

// ── Modal de edição por campo ────────────────────────────────────────────────
var _modal = { campo: null, step: 0 };

function fmtTelDisplay(v){ if(!v) return ''; var d=String(v).replace(/\D/g,''); return d.replace(/^(\d{2})(\d)/,'($1) $2').replace(/(\d{5})(\d{1,4})$/,'$1-$2'); }

function abrirModal(campo){
    var u=S.u;
    _modal.campo=campo; _modal.step=0;
    var ttl='', html='';
    if(campo==='nome'){
        ttl='Editar Nome';
        html='<div class="perf-inp-grp"><label style="font-size:.78rem;color:var(--cor-texto-secundario);font-weight:500">Nome</label>'
            +'<input type="text" class="perf-inp" id="mod-nome" value="'+((u.nome||'').replace(/"/g,'&quot;'))+'" placeholder="Nome completo"></div>';
    } else if(campo==='email'){
        ttl='Editar E-mail';
        html='<div class="perf-inp-grp"><label style="font-size:.78rem;color:var(--cor-texto-secundario);font-weight:500">Novo e-mail</label>'
            +'<input type="email" class="perf-inp" id="mod-email" value="'+((u.email||'').replace(/"/g,'&quot;'))+'" placeholder="seu@email.com">'
            +'<span style="font-size:.74rem;color:var(--cor-texto-secundario);margin-top:3px"><i class="fas fa-info-circle"></i> Um código de verificação será enviado para o novo e-mail.</span></div>';
    } else if(campo==='telefone'){
        ttl='Telefone / WhatsApp';
        var telVal=fmtTelDisplay(u.whatsapp_numero||u.telefone||'');
        html='<div class="perf-inp-grp"><label style="font-size:.78rem;color:var(--cor-texto-secundario);font-weight:500">Número (WhatsApp)</label>'
            +'<input type="tel" class="perf-inp" id="mod-tel" value="'+telVal+'" placeholder="(00) 00000-0000" oninput="perfil.mskTel(this)"></div>';
    } else if(campo==='cpf'){
        ttl='CPF';
        html='<div class="perf-inp-grp"><label style="font-size:.78rem;color:var(--cor-texto-secundario);font-weight:500">CPF</label>'
            +'<input type="text" class="perf-inp" id="mod-cpf" value="'+((u.cpf||'').replace(/"/g,'&quot;'))+'" placeholder="000.000.000-00" oninput="perfil.mskCPF(this)" maxlength="14"></div>';
    }
    var ttlEl=el('perf-modal-ttl'), body=el('perf-modal-body'), step2=el('perf-modal-step2'), modal=el('perf-edit-modal');
    if(!modal) return;
    if(ttlEl) ttlEl.textContent=ttl;
    if(body) body.innerHTML=html;
    if(step2) step2.style.display='none';
    var saveBtn=el('perf-modal-save'); if(saveBtn){ saveBtn.disabled=false; saveBtn.innerHTML='Salvar'; }
    modal.style.display='block';
    setTimeout(function(){ var inp=body?body.querySelector('input'):null; if(inp) inp.focus(); }, 80);
}

function fecharModal(){
    var modal=el('perf-edit-modal'); if(modal) modal.style.display='none';
    _modal.campo=null; _modal.step=0;
}

function salvarModal(){
    var campo=_modal.campo;
    var btn=el('perf-modal-save');
    if(btn){ btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; }

    function reativarBtn(){ if(btn){ btn.disabled=false; btn.innerHTML='Salvar'; } }

    if(campo==='telefone'){
        var telInp=el('mod-tel'); if(!telInp){ reativarBtn(); return; }
        var num=(telInp.value||'').replace(/\D/g,'');
        _postAtualizar({whatsapp_numero: num, telefone: num ? fmtTelDisplay(num) : ''}, function(ok){
            if(ok){ S.u.whatsapp_numero=num; S.u.telefone=num; renderFields(); fecharModal(); toast('Telefone atualizado!','success'); }
            reativarBtn();
        });
    } else if(campo==='cpf'){
        var cpfInp=el('mod-cpf'); if(!cpfInp){ reativarBtn(); return; }
        var cpfVal=(cpfInp.value||'').trim();
        _postAtualizar({cpf: cpfVal}, function(ok){
            if(ok){ S.u.cpf=cpfVal; renderFields(); fecharModal(); toast('CPF atualizado!','success'); }
            reativarBtn();
        });
    } else if(campo==='nome'){
        var nomeInp=el('mod-nome');
        if(!nomeInp){ reativarBtn(); return; }
        var nome=(nomeInp.value||'').trim();
        if(!nome){ toast('Nome obrigatório.','error'); reativarBtn(); return; }
        _postAtualizar({nome:nome}, function(ok){
            if(ok){ S.u.nome=nome; renderHero(); renderFields(); fecharModal(); toast('Nome atualizado!','success'); }
            reativarBtn();
        });
    } else if(campo==='email'){
        var emailInp=el('mod-email');
        if(!emailInp){ reativarBtn(); return; }
        var email=(emailInp.value||'').trim();
        if(!email||!/\S+@\S+\.\S+/.test(email)){ toast('E-mail inválido.','error'); reativarBtn(); return; }

        if(_modal.step===1){
            // confirmar código
            var codigo=(el('perf-modal-codigo')||{value:''}).value.trim();
            if(!codigo){ toast('Informe o código.','error'); reativarBtn(); return; }
            _postAtualizar({email:email, codigo_email:codigo}, function(ok, erro){
                if(ok){ S.u.email=email; renderHero(); renderFields(); fecharModal(); toast('E-mail atualizado!','success'); }
                else { toast(erro==='codigo_invalido'?'Código inválido ou expirado.':(erro||'Erro ao salvar.'),'error'); }
                reativarBtn();
            });
        } else if(email !== (S.u.email||'')){
            // enviar código para novo email
            fetch(url('funcoes/usuario.php?api=usuario&acao=enviar_codigo_email'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({novo_email:email})})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if(d&&d.sucesso){
                    _modal.step=1;
                    var s2=el('perf-modal-step2'); if(s2) s2.style.display='block';
                    toast('Código enviado para o novo e-mail!','info');
                    if(btn){ btn.innerHTML='Confirmar'; btn.disabled=false; }
                    var cod=el('perf-modal-codigo'); if(cod){ cod.value=''; cod.focus(); }
                } else { toast(d&&d.erro?d.erro:'Erro ao enviar código.','error'); reativarBtn(); }
            }).catch(function(){ toast('Erro de conexão.','error'); reativarBtn(); });
        } else {
            toast('Nenhuma alteração.','info'); reativarBtn();
        }
    }
}

function _postAtualizar(dados, cb){
    fetch(url('funcoes/usuario.php?api=usuario&acao=atualizar'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(dados)})
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d&&d.sucesso){ if(window.dadosUsuario) Object.assign(window.dadosUsuario,S.u); cb(true); }
        else { toast(d&&d.erro?d.erro:'Erro ao salvar.','error'); cb(false, d&&d.erro); }
    }).catch(function(){ toast('Erro de conexão.','error'); cb(false); });
}

function toggleTema(){
    S.tema=!S.tema;
    localStorage.setItem('temaEscuro',S.tema);
    renderToggles();
    if(typeof window.alternarTema==='function') window.alternarTema();
    else { document.body.classList.toggle('tema-escuro',S.tema); document.body.classList.toggle('tema-claro',!S.tema); }
}

function toggleOcultar(){
    S.ocultar=!S.ocultar;
    localStorage.setItem('valoresOcultos',S.ocultar);
    renderToggles();
    toast(S.ocultar?'Valores ocultados.':'Valores visíveis.','info');
}

function setMoeda(v){ S.moeda=v; localStorage.setItem('moedaPadrao',v); toast('Moeda alterada.','success'); }

function abrirSenha(){
    if(S.segStep>0){ fecharSenha(); return; }
    S.segStep=1;
    el('seg-form').style.display='block';
    el('seg-code-area').style.display='none';
    if(el('seg-save')) el('seg-save').textContent='Enviar código';
    if(el('seg-chevron')) el('seg-chevron').style.transform='rotate(90deg)';
}

function fecharSenha(){
    S.segStep=0;
    el('seg-form').style.display='none';
    if(el('seg-chevron')) el('seg-chevron').style.transform='';
    ['seg-codigo','seg-nova','seg-conf'].forEach(function(id){ var e=el(id); if(e) e.value=''; });
}

function salvarSenha(){
    var btn=el('seg-save');
    if(S.segStep===1){
        // Passo 1: enviar código
        if(btn){ btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; }
        fetch(url('funcoes/usuario.php?api=usuario&acao=enviar_codigo_senha'),{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d&&d.sucesso){
                S.segStep=2;
                el('seg-code-area').style.display='block';
                el('seg-save').textContent='Confirmar nova senha';
                toast('Código enviado para seu email!','success');
            } else { toast('Erro ao enviar código.','error'); }
        }).catch(function(){ toast('Erro de conexão.','error'); })
        .then(function(){ if(btn){ btn.disabled=false; } });
    } else if(S.segStep===2){
        // Passo 2: trocar senha
        var codigo=(el('seg-codigo').value||'').trim();
        var nova=(el('seg-nova').value||'');
        var conf=(el('seg-conf').value||'');
        if(!codigo){ toast('Informe o código.','error'); return; }
        if(nova.length<6){ toast('Senha mínima de 6 caracteres.','error'); return; }
        if(nova!==conf){ toast('Senhas não coincidem.','error'); return; }
        if(btn){ btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; }
        fetch(url('funcoes/usuario.php?api=usuario&acao=trocar_senha'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({codigo:codigo,nova_senha:nova})})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d&&d.sucesso){ toast('Senha alterada com sucesso!','success'); fecharSenha(); }
            else { toast(d&&d.erro?d.erro:'Código inválido ou expirado.','error'); }
        }).catch(function(){ toast('Erro de conexão.','error'); })
        .then(function(){ if(btn){ btn.disabled=false; btn.innerHTML='Confirmar nova senha'; } });
    }
}

function exportar(){
    if(typeof window.exportarDados==='function'){ window.exportarDados(); return; }
    var blob=new Blob([JSON.stringify({usuario:S.u,exportadoEm:new Date().toISOString()},null,2)],{type:'application/json'});
    var a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='peak_dados.json'; a.click();
    toast('Dados exportados!','success');
}

function confirmarSair(){
    confirm_({icon:'👋',title:'Sair da conta',msg:'Encerrar sessão atual?',okTxt:'Sair',danger:true,onOk:sair});
}

function sair(){
    try{ localStorage.clear(); }catch(e){}
    fetch(url('funcoes/usuario.php'),{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'acao=logout'})
    .finally(function(){ window.location.href=url('login.php'); });
}


function init(){
    S.u=Object.assign({},dadosUsuario||{});
    S.tema=localStorage.getItem('temaEscuro')==='true';
    S.ocultar=localStorage.getItem('valoresOcultos')==='true';
    S.moeda=localStorage.getItem('moedaPadrao')||'BRL';
    renderHero(); renderFields(); renderToggles();
}

return { abrirUpload:abrirUpload, handleUpload:handleUpload, mskCPF:mskCPF, mskTel:mskTel, abrirModal:abrirModal, fecharModal:fecharModal, salvarModal:salvarModal, toggleTema:toggleTema, toggleOcultar:toggleOcultar, setMoeda:setMoeda, abrirSenha:abrirSenha, fecharSenha:fecharSenha, salvarSenha:salvarSenha, exportar:exportar, confirmarSair:confirmarSair, toast:toast, init:init };
})();

// Compatibilidade com AJAX loader
window.inicializarPerfil = function(){ setTimeout(perfil.init,50); };
window.carregarDadosPerfil = window.inicializarPerfil;

// Inicializar
setTimeout(perfil.init, document.readyState==='loading' ? 200 : 50);
</script>
