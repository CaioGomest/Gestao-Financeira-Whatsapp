<?php
require_once __DIR__ . '/../funcoes/categorias.php';
require_once __DIR__ . '/../funcoes/usuario.php';

$usuario_id = usuarioLogado() ? obterUsuarioId() : 1;
$tipo_parametro = isset($_GET['tipo']) ? $_GET['tipo'] : null;
$tipo_filtro = in_array($tipo_parametro, ['receita', 'despesa']) ? $tipo_parametro : null;
$todas_categorias_servidor = obterCategorias($tipo_filtro, $usuario_id);
$filtro_categorias_atual_servidor = $tipo_filtro ? $tipo_filtro : 'todos';

$script_atual = basename($_SERVER['SCRIPT_NAME']);
$esta_no_index = ($script_atual === 'index.php');

$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$uri_sem_query = strtok($uri, '?');
$base_app = $uri_sem_query;
if (substr($base_app, -10) === '/index.php') {
    $base_app = substr($base_app, 0, -10);
}
$pos_paginas = strpos($base_app, '/paginas/');
if ($pos_paginas !== false) {
    $base_app = substr($base_app, 0, $pos_paginas);
}
$pos_modais = strpos($base_app, '/modais/');
if ($pos_modais !== false) {
    $base_app = substr($base_app, 0, $pos_modais);
}
if (substr($base_app, -1) !== '/') {
    $base_app .= '/';
}

$base_link = $esta_no_index 
    ? ($base_app . 'index.php?pagina=categorias') 
    : ($base_app . 'paginas/categorias.php');

function adicionarParametroTipo($base, $tipo) {
    return $base . (strpos($base, '?') !== false ? '&' : '?') . 'tipo=' . $tipo;
}
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
    (function() {
        try {
            var escuro = localStorage.getItem('temaEscuro');
            if (escuro === 'true') {
                document.body.classList.add('tema-escuro');
            } else {
                document.body.classList.add('tema-claro');
            }
        } catch (e) {
            document.body.classList.add('tema-escuro');
        }
    })();
    </script>
    <?php if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') { ?>
        <nav class="menu-inferior">
            <a href="<?php echo $base_app; ?>index.php?pagina=dashboard" class="menu-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo $base_app; ?>index.php?pagina=transacoes" class="menu-item">
                <i class="fas fa-exchange-alt"></i>
                <span>Transações</span>
            </a>
            <div class="espaco-central"></div>
            <a href="<?php echo $base_app; ?>index.php?pagina=categorias" class="menu-item">
                <i class="fas fa-tags"></i>
                <span>Categorias</span>
            </a>
            <a href="<?php echo $base_app; ?>index.php?pagina=perfil" class="menu-item">
                <i class="fas fa-user"></i>
                <span>Perfil</span>
            </a>
        </nav>

        <div class="menu-overlay" id="menu-overlay"></div>
        <a href="#" class="botao-adicionar-central" onclick="toggleMenuCircular()">
            <i class="fas fa-plus"></i>
        </a>
        <div class="menu-circular" id="menu-circular">
            <a href="#" class="opcao-menu receita" onclick="abrirModalTransacao('receita')" title="Nova Receita">
                <i class="fas fa-arrow-up"></i>
                <span>Receita</span>
            </a>
            <a href="#" class="opcao-menu despesa" onclick="abrirModalTransacao('despesa')" title="Nova Despesa">
                <i class="fas fa-arrow-down"></i>
                <span>Despesa</span>
            </a>
            <a href="#" class="opcao-menu categoria" onclick="abrirModalCategoria()" title="Nova Categoria">
                <i class="fas fa-tags"></i>
                <span>Categoria</span>
            </a>
        </div>
    <?php } ?>
    <script>
        function toggleMenuCircular() {
            var menu = document.getElementById('menu-circular');
            var overlay = document.getElementById('menu-overlay');
            if (!menu || !overlay) return;
            menu.classList.toggle('ativo');
            overlay.classList.toggle('ativo');
        }
        (function(){
            var overlay = document.getElementById('menu-overlay');
            if (overlay) {
                overlay.addEventListener('click', function(){
                    document.getElementById('menu-circular').classList.remove('ativo');
                    overlay.classList.remove('ativo');
                });
            }
        })();
    </script>
<?php endif; ?>
<div class="pagina-categorias">
    <div class="categorias-header">
        <div class="categorias-background"></div>
        <div class="header-content">
            <div class="header-info">
                <h1>
                    <i class="fas fa-tags"></i>
                    Categorias
                </h1>
                <p>Organize suas transações por categorias</p>
            </div>
            <div class="header-actions">
                <button class="btn-nova-categoria" onclick="abrirModalCategoria()">
                    <i class="fas fa-plus"></i>
                    <span>Nova Categoria</span>
                </button>
            </div>
        </div>
    </div>

    <div class="categorias-filtros">
        <div class="filtros-container">
            <a class="filtro-btn <?php echo ($filtro_categorias_atual_servidor === 'todos') ? 'ativo' : ''; ?>" data-tipo="todos" href="<?php echo $base_link; ?>">
                <i class="fas fa-list"></i>
                Todas
            </a>
            <a class="filtro-btn <?php echo ($filtro_categorias_atual_servidor === 'receita') ? 'ativo' : ''; ?>" data-tipo="receita" href="<?php echo adicionarParametroTipo($base_link, 'receita'); ?>">
                <i class="fas fa-arrow-up"></i>
                Receitas
            </a>
            <a class="filtro-btn <?php echo ($filtro_categorias_atual_servidor === 'despesa') ? 'ativo' : ''; ?>" data-tipo="despesa" href="<?php echo adicionarParametroTipo($base_link, 'despesa'); ?>">
                <i class="fas fa-arrow-down"></i>
                Despesas
            </a>
        </div>
    </div>

    <div class="categorias-content">
        <div class="categorias-grid" id="lista-categorias-container">
        </div>
        <div id="mensagem-vazia" class="estado-vazio" style="display: none;">
            <div class="vazio-ilustracao">
                <div class="vazio-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="vazio-decoracao">
                    <div class="circulo circulo-1"></div>
                    <div class="circulo circulo-2"></div>
                    <div class="circulo circulo-3"></div>
                </div>
            </div>
            <div class="vazio-content">
                <h3>Nenhuma categoria encontrada</h3>
                <p>Crie sua primeira categoria para organizar melhor suas transações financeiras</p>
                <button class="btn-criar-primeira" onclick="abrirModalCategoria()">
                    <i class="fas fa-plus"></i>
                    Criar Primeira Categoria
                </button>
            </div>
        </div>
        
        
    </div>
</div>

<script>
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

    window.editarCategoria = function(categoria) {
        window.abrirModalCategoria(categoria);
    };

    var todasCategorias = <?php echo json_encode($todas_categorias_servidor, JSON_UNESCAPED_UNICODE); ?>;
    var filtroCategoriasAtual = '<?php echo $filtro_categorias_atual_servidor; ?>';
    function carregarCategorias() {
        fetch(obterUrl('funcoes/transacoes.php?api=categorias&acao=listar'))
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro ao buscar categorias: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                try {
                    todasCategorias = JSON.parse(text);
                    renderizarCategorias();
                } catch (e) {
                    todasCategorias = [];
                    renderizarCategorias();
                }
            })
            .catch(error => {
                todasCategorias = [];
                renderizarCategorias();
            });
    }
    
    function renderizarCategorias() {
        var container = document.getElementById('lista-categorias-container');
        var mensagemVazia = document.getElementById('mensagem-vazia');
        
        var categoriasFiltradas = todasCategorias.filter(function(categoria) {
            return filtroCategoriasAtual === 'todos' || categoria.tipo === filtroCategoriasAtual;
        });
        
        container.innerHTML = '';
        
        if (categoriasFiltradas.length > 0) {
            mensagemVazia.style.display = 'none';
            container.style.display = 'grid';
            
            categoriasFiltradas.forEach(function(categoria, index) {
                var card = document.createElement('div');
                card.className = 'categoria-card';
                card.style.animationDelay = (index * 0.1) + 's';
                card.onclick = function() { editarCategoria(categoria); };
                
                var cardHeader = document.createElement('div');
                cardHeader.className = 'categoria-header';
                
                var corIndicador = document.createElement('div');
                corIndicador.className = 'categoria-cor-indicador';
                corIndicador.style.backgroundColor = categoria.cor;
                // Ícone dentro da bolinha de cor
                var iconeClasse = (categoria.icone || '').includes('fa-') || (categoria.icone || '').includes('fas')
                    ? categoria.icone
                    : ('fas fa-' + (categoria.icone || 'tag'));
                corIndicador.innerHTML = '<i class="' + iconeClasse + '"></i>';
                
                var tipoIcon = document.createElement('div');
                tipoIcon.className = 'categoria-tipo-icon';
                tipoIcon.innerHTML = categoria.tipo === 'receita' 
                    ? '<i class="fas fa-arrow-up"></i>' 
                    : '<i class="fas fa-arrow-down"></i>';
                tipoIcon.style.color = categoria.tipo === 'receita' ? '#27ae60' : '#e74c3c';
                
                cardHeader.appendChild(corIndicador);
                cardHeader.appendChild(tipoIcon);
                
                var cardBody = document.createElement('div');
                cardBody.className = 'categoria-body';
                
                var nome = document.createElement('h3');
                nome.className = 'categoria-nome';
                nome.textContent = categoria.nome;
                
                var tipo = document.createElement('p');
                tipo.className = 'categoria-tipo';
                tipo.textContent = categoria.tipo === 'receita' ? 'Receita' : 'Despesa';
                
                cardBody.appendChild(nome);
                cardBody.appendChild(tipo);
                
                var cardFooter = document.createElement('div');
                cardFooter.className = 'categoria-footer';
                cardFooter.innerHTML = '<i class="fas fa-edit"></i> Editar';
                
                card.appendChild(cardHeader);
                card.appendChild(cardBody);
                card.appendChild(cardFooter);
                
                container.appendChild(card);
            });
        } else {
            mensagemVazia.style.display = 'flex';
            container.style.display = 'none';
        }
    }
    
    window.inicializarCategorias = function() {
        renderizarCategorias();
    };
    
    // Carregar categorias ao iniciar a página
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.inicializarCategorias);
    } else {
        // DOM já está carregado
        setTimeout(window.inicializarCategorias, 100);
    }

    // Removido carregamento via fetch: dados já estão disponíveis do servidor
</script>
<?php 
// Incluir modal de categoria para garantir que as funções estejam disponíveis
// tanto ao acessar via index.php quanto diretamente esta página.
include __DIR__ . '/../modais/categoria.php';
// Incluir modal de transação no fallback para habilitar o botão central
if (!$esta_no_index) {
    include __DIR__ . '/../modais/transacao.php';
}
?>
<?php if (!$esta_no_index): ?>
</body>
<?php endif; ?>
