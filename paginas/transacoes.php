<?php
require_once __DIR__ . '/../funcoes/transacoes.php';
$dados_iniciais = lerTransacoes();
$resumo_inicio = $dados_iniciais['resumo'];
?>
<!-- Página de Transações Reestruturada -->
<div class="pagina-transacoes">
    <!-- Header da Página -->
    <div class="transacoes-header">
        <div class="transacoes-background"></div>
        <div class="header-content">
            <div class="header-info">
                <h1>
                    <i class="fas fa-exchange-alt"></i>
                    Transações
                </h1>
                <p>Gerencie suas receitas e despesas</p>
            </div>
            <div class="header-actions">
                <button class="btn-nova-transacao" onclick="abrirModalTransacao('despesa')">
                    <i class="fas fa-plus"></i>
                    <span>Nova Transação</span>
                </button>
                <button class="btn-nova-transacao" onclick="abrirModalImportacao()">
                    <i class="fas fa-file-upload"></i>
                    <span>Importar Extrato</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Resumo Rápido -->
    <div class="resumo-transacoes">
        <div class="resumo-card receitas">
            <div class="resumo-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="resumo-info">
                <span class="resumo-label">Receitas</span>
                <span class="resumo-valor" id="total-receitas"><?php echo 'R$ ' . number_format($resumo_inicio['total_receitas'], 2, ',', '.'); ?></span>
            </div>
        </div>
        <div class="resumo-card despesas">
            <div class="resumo-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="resumo-info">
                <span class="resumo-label">Despesas</span>
                <span class="resumo-valor" id="total-despesas"><?php echo 'R$ ' . number_format($resumo_inicio['total_despesas'], 2, ',', '.'); ?></span>
            </div>
        </div>
        <div class="resumo-card saldo">
            <div class="resumo-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="resumo-info">
                <span class="resumo-label">Saldo</span>
                <span class="resumo-valor" id="saldo-periodo"><?php echo 'R$ ' . number_format($resumo_inicio['saldo_atual'], 2, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="transacoes-filtros">
        <div class="filtros-container">
            <div class="filtro-grupo">
                <label for="filtro-mes">Período</label>
                <select id="filtro-mes" class="filtro-select">
                    <option value="">Todos os meses</option>
                    <option value="1">Janeiro</option>
                    <option value="2">Fevereiro</option>
                    <option value="3">Março</option>
                    <option value="4">Abril</option>
                    <option value="5">Maio</option>
                    <option value="6">Junho</option>
                    <option value="7">Julho</option>
                    <option value="8">Agosto</option>
                    <option value="9">Setembro</option>
                    <option value="10">Outubro</option>
                    <option value="11">Novembro</option>
                    <option value="12">Dezembro</option>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-categoria">Categoria</label>
                <select id="filtro-categoria" class="filtro-select">
                    <option value="">Todas</option>
                    <!-- Categorias serão carregadas via JavaScript -->
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-tipo">Tipo</label>
                <select id="filtro-tipo" class="filtro-select">
                    <option value="">Todos</option>
                    <option value="receita">Receitas</option>
                    <option value="despesa">Despesas</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Lista de Transações -->
    <div class="transacoes-content">
        <div class="barra-selecao" id="barra-selecao" style="display:none">
            <span id="contador-selecao">0 selecionadas</span>
            <div class="barra-selecao-acoes">
                <button class="botao secundario" id="cancelar-selecao">Cancelar Seleção</button>
                <button class="botao primario" id="excluir-selecionadas">Excluir Selecionadas</button>
            </div>
        </div>
        <div class="transacoes-lista" id="lista-transacoes-container" style="<?php echo (count($dados_iniciais['transacoes'])>0)?'display:block':'display:none'; ?>">
            <!-- As transações serão carregadas aqui via JavaScript -->
        </div>
        
        <!-- Estado Vazio -->
        <div id="mensagem-vazia" class="estado-vazio" style="<?php echo (count($dados_iniciais['transacoes'])>0)?'display:none':''; ?>">
            <div class="vazio-ilustracao">
                <div class="vazio-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="vazio-decoracao">
                    <div class="circulo circulo-1"></div>
                    <div class="circulo circulo-2"></div>
                    <div class="circulo circulo-3"></div>
                </div>
            </div>
            <div class="vazio-content">
                <h3>Nenhuma transação encontrada</h3>
                <p>Comece adicionando sua primeira transação para controlar suas finanças</p>
                <button class="btn-criar-primeira" onclick="abrirModalTransacao('despesa')">
                    <i class="fas fa-plus"></i>
                    Criar Primeira Transação
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Helper para compor URLs relativas ao diretório da aplicação
function obterUrl(caminho) {
    var caminhoNormalizado = (caminho || '').replace(/^\//, '');
    var path = window.location.pathname || '';
    var base = path;
    if (base.endsWith('/index.php')) {
        base = base.replace('/index.php', '');
    }
    if (base.includes('/paginas/')) {
        base = base.split('/paginas/')[0];
    }
    if (base.includes('/modais/')) {
        base = base.split('/modais/')[0];
    }
    if (!base.endsWith('/')) base += '/';
    return base + caminhoNormalizado;
}

// Variáveis globais
let todasTransacoes = <?php echo json_encode($dados_iniciais['transacoes'], JSON_UNESCAPED_UNICODE); ?>;
let filtroAtual = {
    mes: new Date().getMonth() + 1,
    categoria: '',
    tipo: ''
};
let modoSelecao = false;
let selecionados = new Set();
let temporizadorPress = null;

function getCorTipoTransacao(tipo) {
    if (tipo === 'transferencia') return '#6C63FF';
    return tipo === 'receita' ? '#4CAF50' : '#F44336';
}

function getIconeTipoTransacao(tipo) {
    if (tipo === 'transferencia') return 'fas fa-exchange-alt';
    return tipo === 'receita' ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
}

// Função para formatar valor monetário
function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

// Função para formatar data
function formatarData(data) {
    // Garantir parsing consistente: usar apenas a parte de data (YYYY-MM-DD)
    const parteData = (data || '').split(' ')[0];
    const [ano, mes, dia] = parteData.split('-');
    const dataObj = new Date(`${ano}-${mes}-${dia}T00:00:00`);
    return dataObj.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Função para calcular resumo das transações
function calcularResumo(transacoes) {
    const resumo = {
        receitas: 0,
        despesas: 0,
        saldo: 0
    };

    transacoes.forEach(transacao => {
        const ehTransferencia = (transacao.eh_transferencia === 1) || (typeof transacao.observacoes === 'string' && transacao.observacoes.indexOf('TRANSFERENCIA:') === 0);
        if (ehTransferencia) return;
        if (transacao.tipo === 'receita') {
            resumo.receitas += parseFloat(transacao.valor);
        } else {
            resumo.despesas += parseFloat(transacao.valor);
        }
    });

    resumo.saldo = resumo.receitas - resumo.despesas;
    return resumo;
}

// Função para atualizar resumo na interface
function atualizarResumo(transacoes) {
    const resumo = calcularResumo(transacoes);
    
    document.getElementById('total-receitas').textContent = formatarMoeda(resumo.receitas);
    document.getElementById('total-despesas').textContent = formatarMoeda(resumo.despesas);
    
    const saldoElement = document.getElementById('saldo-periodo');
    saldoElement.textContent = formatarMoeda(resumo.saldo);
    // Aplicar cor apenas quando positivo (>0) ou negativo (<0); zero fica neutro
    let classeSaldo = 'resumo-valor';
    if (resumo.saldo > 0) {
        classeSaldo += ' positivo';
    } else if (resumo.saldo < 0) {
        classeSaldo += ' negativo';
    }
    saldoElement.className = classeSaldo;
}

// Função para renderizar transações
function normalizarTransacoesComTransferencias(transacoes) {
    const grupos = {};
    const lista = [];

    transacoes.forEach(t => {
        const obs = (t.observacoes || '').toString();
        const ehTransf = obs.indexOf('TRANSFERENCIA:') === 0;
        if (!ehTransf) {
            lista.push(t);
            return;
        }
        const chave = `${t.descricao || ''}|${parseFloat(t.valor)}|${(t.data_transacao || '').substring(0,10)}`;
        if (!grupos[chave]) grupos[chave] = { saida: null, entrada: null };
        if (obs.indexOf('SAIDA') >= 0) grupos[chave].saida = t; else grupos[chave].entrada = t;
    });

    Object.keys(grupos).forEach(k => {
        const g = grupos[k];
        const base = g.saida || g.entrada;
        if (!base) return;
        lista.push({
            id: base.id,
            tipo: 'transferencia',
            descricao: base.descricao || 'Transferência',
            valor: parseFloat(base.valor),
            data_transacao: base.data_transacao,
            categoria_nome: 'Transferência',
            categoria_icone: 'fas fa-exchange-alt',
            conta_origem_nome: g.saida ? g.saida.conta_nome : '',
            conta_destino_nome: g.entrada ? g.entrada.conta_nome : '',
            eh_transferencia: 1
        });
    });

    return lista;
}

function renderizarTransacoes(transacoes) {
    const container = document.getElementById('lista-transacoes-container');
    const mensagemVazia = document.getElementById('mensagem-vazia');

    const lista = normalizarTransacoesComTransferencias(transacoes);

    if (lista.length === 0) {
        container.style.display = 'none';
        mensagemVazia.style.display = 'flex';
        return;
    }

    container.style.display = 'block';
    mensagemVazia.style.display = 'none';

    // Agrupar transações por data
    const transacoesPorData = {};
    lista.forEach(transacao => {
        const data = transacao.data_transacao.split(' ')[0];
        if (!transacoesPorData[data]) {
            transacoesPorData[data] = [];
        }
        transacoesPorData[data].push(transacao);
    });

    // Ordenar datas (mais recente primeiro)
    const datasOrdenadas = Object.keys(transacoesPorData).sort((a, b) => new Date(b) - new Date(a));

    let html = '';
    datasOrdenadas.forEach((data, dataIndex) => {
        const transacoesDoDia = transacoesPorData[data];
        const totalDia = transacoesDoDia.reduce((total, t) => {
            const ehTransferencia = (t.eh_transferencia === 1) || (typeof t.observacoes === 'string' && t.observacoes.indexOf('TRANSFERENCIA:') === 0);
            if (ehTransferencia) return total;
            return total + (t.tipo === 'receita' ? parseFloat(t.valor) : -parseFloat(t.valor));
        }, 0);

        html += `
            <div class="grupo-data" style="animation-delay: ${dataIndex * 0.1}s">
                <div class="cabecalho-data">
                    <span class="data-label">${formatarData(data)}</span>
                    <span class="total-dia ${totalDia >= 0 ? 'positivo' : 'negativo'}">
                        ${formatarMoeda(Math.abs(totalDia))}
                    </span>
                </div>
                <div class="transacoes-do-dia">
        `;

        transacoesDoDia.forEach((transacao, index) => {
            html += `
                <div class="transacao-card" data-id="${transacao.id}" style="animation-delay: ${(dataIndex * 0.1) + (index * 0.05)}s">
                    <div class="transacao-icon ${transacao.tipo}">
                        <i class="${getIconeTipoTransacao(transacao.tipo)}"></i>
                    </div>
                    <div class="selecao-marcador">✓</div>
                    <div class="transacao-info">
                        <div class="transacao-principal">
                            <span class="transacao-descricao">${transacao.descricao}</span>
                            <span class="transacao-valor ${transacao.tipo}">
                                ${transacao.tipo === 'transferencia' ? '' : (transacao.tipo === 'receita' ? '+' : '-')}
                                ${formatarMoeda(transacao.valor)}
                            </span>
                        </div>
                        <div class="transacao-detalhes">
                            <span class="transacao-categoria">
                                <i class="${transacao.tipo === 'transferencia' ? 'fas fa-exchange-alt' : (transacao.categoria_icone || 'fas fa-tag')}"></i>
                                ${transacao.tipo === 'transferencia' ? (transacao.conta_origem_nome + ' → ' + transacao.conta_destino_nome) : (transacao.categoria_nome || 'Sem categoria')}
                            </span>
                            <span class="transacao-hora">
                                ${new Date(transacao.data_transacao).toLocaleTimeString('pt-BR', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}
                            </span>
                        </div>
                    </div>
                    <div class="transacao-acoes">
                        <button class="btn-acao" onclick="editarTransacao(${transacao.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-acao excluir" onclick="excluirTransacao(${transacao.id})" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    configurarEventosSelecao();
}

// Função para filtrar transações
function filtrarTransacoes() {
    let transacoesFiltradas = [...todasTransacoes];

    // Filtro por mês
    if (filtroAtual.mes !== null && filtroAtual.mes !== '') {
        transacoesFiltradas = transacoesFiltradas.filter(transacao => {
            // Extrair mês com segurança do formato 'YYYY-MM-DD HH:MM:SS'
            const parteData = (transacao.data_transacao || '').substring(0, 10);
            const mesStr = parteData.split('-')[1];
            const mesTransacao = parseInt(mesStr, 10);
            return mesTransacao === parseInt(filtroAtual.mes, 10);
        });
    }

    // Filtro por categoria
    if (filtroAtual.categoria) {
        transacoesFiltradas = transacoesFiltradas.filter(transacao => 
            transacao.categoria_id === parseInt(filtroAtual.categoria)
        );
    }

    // Filtro por tipo
    if (filtroAtual.tipo) {
        transacoesFiltradas = transacoesFiltradas.filter(transacao => 
            transacao.tipo === filtroAtual.tipo
        );
    }

    renderizarTransacoes(transacoesFiltradas);
    atualizarResumo(transacoesFiltradas);
}

// Função para carregar transações da API
function carregarTransacoes() {
    const url = obterUrl('funcoes/transacoes.php?api=transacoes&acao=listar');
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao buscar transações: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (Array.isArray(data)) {
                todasTransacoes = data;
            } else if (data && Array.isArray(data.transacoes)) {
                todasTransacoes = data.transacoes;
            } else {
                todasTransacoes = [];
            }
            filtrarTransacoes();
            console.log('Transações carregadas da API:', todasTransacoes.length, 'transações');
        })
        .catch(error => {
            console.error('Erro ao carregar transações:', error);
            todasTransacoes = [];
            filtrarTransacoes();
        });
}

// Carregar categorias para o filtro
function carregarCategoriasFiltro() {
    const select = document.getElementById('filtro-categoria');
    if (!select) return;
    // Manter opção padrão "Todas" para evitar piscar de "Carregando..."
    // Não alterar o conteúdo enquanto busca as categorias

    fetch(obterUrl('funcoes/transacoes.php?api=categorias&acao=listar'))
        .then(response => {
            if (!response.ok) throw new Error('Erro ao buscar categorias');
            return response.json();
        })
        .then(data => {
            const categorias = Array.isArray(data) ? data : (data && Array.isArray(data.categorias) ? data.categorias : []);
            // Garantir que a primeira opção continue sendo "Todas"
            select.innerHTML = '<option value="">Todas</option>';
            categorias.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.nome;
                select.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Erro ao carregar categorias para filtro:', err);
            // Em erro, manter apenas "Todas"
            select.innerHTML = '<option value="">Todas</option>';
        });
}

// Função para editar transação
function editarTransacao(id) {
    const transacao = todasTransacoes.find(t => t.id === id);
    if (transacao) {
        abrirModalTransacao(transacao.tipo, transacao);
    }
}

// Função para excluir transação
function excluirTransacao(id) {
    if (confirm('Tem certeza que deseja excluir esta transação?')) {
        fetch(obterUrl('funcoes/transacoes.php?api=transacoes&acao=excluir'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                carregarTransacoes(); // Recarregar a lista
                mostrarNotificacao('Transação excluída com sucesso!', 'success');
            } else {
                const msg = data.erro || data.message || 'Falha ao excluir';
                mostrarNotificacao('Erro ao excluir transação: ' + msg, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarNotificacao('Erro ao excluir transação', 'error');
        });
    }
}

// Função para inicializar a página de transações
window.inicializarTransacoes = function() {
    // Configurar filtros
    const filtroMes = document.getElementById('filtro-mes');
    const filtroCategoria = document.getElementById('filtro-categoria');
    const filtroTipo = document.getElementById('filtro-tipo');
    
    if (filtroMes) {
        filtroMes.addEventListener('change', function() {
            filtroAtual.mes = this.value ? parseInt(this.value, 10) : null;
            filtrarTransacoes();
        });
        // Por padrão, filtrar pelo mês atual
        const mesAtual = new Date().getMonth() + 1;
        filtroAtual.mes = mesAtual;
        filtroMes.value = mesAtual;
    }

    if (filtroCategoria) {
        filtroCategoria.addEventListener('change', function() {
            filtroAtual.categoria = this.value;
            filtrarTransacoes();
        });
    }

    if (filtroTipo) {
        filtroTipo.addEventListener('change', function() {
            filtroAtual.tipo = this.value;
            filtrarTransacoes();
        });
    }

    carregarCategoriasFiltro();
    filtrarTransacoes();

    const btnCancelar = document.getElementById('cancelar-selecao');
    const btnExcluir = document.getElementById('excluir-selecionadas');
    if (btnCancelar) btnCancelar.addEventListener('click', sairModoSelecao);
    if (btnExcluir) btnExcluir.addEventListener('click', excluirSelecionadas);
    if (!window._escHandlerConfigurado) {
        window._escHandlerConfigurado = true;
        document.addEventListener('keydown', function(e){
            if (modoSelecao && (e.key === 'Escape' || e.key === 'Esc')) {
                sairModoSelecao();
            }
        });
    }
};

// Event listeners para filtros
document.addEventListener('DOMContentLoaded', window.inicializarTransacoes);

// Também executar quando a página for carregada via AJAX
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.inicializarTransacoes);
} else {
    // DOM já está carregado
    setTimeout(window.inicializarTransacoes, 100);
}

// Renderização imediata com dados do servidor para evitar estado vazio
(function primeiraRenderizacao(){
    try {
        filtrarTransacoes();
    } catch(e) {}
})();

function configurarEventosSelecao() {
    const container = document.getElementById('lista-transacoes-container');
    const cards = container.querySelectorAll('.transacao-card');
    cards.forEach(card => {
        const id = parseInt(card.getAttribute('data-id'), 10);
        const iniciar = () => {
            if (temporizadorPress) clearTimeout(temporizadorPress);
            temporizadorPress = setTimeout(() => { entrarModoSelecao(id); }, 500);
        };
        const cancelar = () => { if (temporizadorPress) { clearTimeout(temporizadorPress); temporizadorPress = null; } };
        card.addEventListener('mousedown', iniciar);
        card.addEventListener('mouseup', cancelar);
        card.addEventListener('mouseleave', cancelar);
        card.addEventListener('touchstart', iniciar, { passive: true });
        card.addEventListener('touchend', cancelar);
        card.addEventListener('click', function(e){
            if (!modoSelecao) return;
            e.preventDefault();
            alternarSelecao(id, card);
        });
    });
}

function entrarModoSelecao(idInicial) {
    modoSelecao = true;
    document.querySelector('.transacoes-content').classList.add('modo-selecao');
    document.getElementById('barra-selecao').style.display = 'flex';
    selecionados.clear();
    const card = document.querySelector('.transacao-card[data-id="'+idInicial+'"]');
    if (card) alternarSelecao(idInicial, card);
    atualizarBarraSelecao();
}

function sairModoSelecao() {
    modoSelecao = false;
    document.querySelector('.transacoes-content').classList.remove('modo-selecao');
    document.getElementById('barra-selecao').style.display = 'none';
    selecionados.clear();
    document.querySelectorAll('.transacao-card.selecionado').forEach(c=>c.classList.remove('selecionado'));
    atualizarBarraSelecao();
}

function alternarSelecao(id, cardEl) {
    if (selecionados.has(id)) {
        selecionados.delete(id);
        if (cardEl) cardEl.classList.remove('selecionado');
    } else {
        selecionados.add(id);
        if (cardEl) cardEl.classList.add('selecionado');
    }
    atualizarBarraSelecao();
}

function atualizarBarraSelecao() {
    const c = document.getElementById('contador-selecao');
    if (c) c.textContent = selecionados.size+' selecionadas';
}

async function excluirSelecionadas() {
    if (selecionados.size === 0) return;
    if (!confirm('Excluir transações selecionadas?')) return;
    const ids = Array.from(selecionados);
    let ok = 0, falha = 0;
    for (let i=0;i<ids.length;i++){
        try {
            const resp = await fetch(obterUrl('funcoes/transacoes.php?api=transacoes&acao=excluir'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: ids[i] })
            });
            const data = await resp.json();
            if (data && data.sucesso) ok++; else falha++;
        } catch(e){ falha++; }
    }
    carregarTransacoes();
    sairModoSelecao();
    if (typeof mostrarNotificacao === 'function') {
        mostrarNotificacao('Excluídas: '+ok+' | Falhas: '+falha, falha ? 'warning' : 'success');
    }
}
</script>
