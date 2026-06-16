<?php
require_once __DIR__ . '/config/config.php';
require_once 'funcoes/usuario.php';

if (!usuarioLogado()) {
    header('Location: login.php');
    exit;
}

require_once 'funcoes/transacoes.php';
require_once 'funcoes/categorias.php';

if (usuarioLogado()) {
    $usuario_atual = obterDadosUsuario();
} else {
    $usuario_atual = [
        'id' => 1,
        'nome' => 'Usuário Teste',
        'email' => 'teste@teste.com'
    ];
}

$pagina_inicial = isset($_GET['pagina']) ? preg_replace('/[^a-z_]/', '', $_GET['pagina']) : '';

if ($pagina_inicial === '' && isset($_COOKIE['paginaAtual'])) {
    $cookiePagina = preg_replace('/[^a-z_]/', '', $_COOKIE['paginaAtual']);
    $paginasPermitidas = ['dashboard','transacoes','categorias','perfil','admin'];
    if (in_array($cookiePagina, $paginasPermitidas, true)) {
        if ($cookiePagina === 'admin' && (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin')) {
            $pagina_inicial = 'dashboard';
        } else {
            $pagina_inicial = $cookiePagina;
        }
    }
}

if ($pagina_inicial === '') {
    $pagina_inicial = (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin') ? 'admin' : 'dashboard';
}

$arquivo_pagina_inicial = 'paginas/' . $pagina_inicial . '.php';
if (!file_exists($arquivo_pagina_inicial)) {
    $pagina_inicial = 'dashboard';
    $arquivo_pagina_inicial = 'paginas/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanças Pessoais</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        (function(){
            function loadScript(src, worker) {
                return new Promise(function(resolve, reject){
                    var s = document.createElement('script');
                    s.src = src;
                    s.onload = function(){
                        try {
                            if (window.pdfjsLib && window.pdfjsLib.GlobalWorkerOptions) {
                                window.pdfjsLib.GlobalWorkerOptions.workerSrc = worker;
                                resolve(window.pdfjsLib);
                            } else {
                                reject(new Error('pdfjsLib não disponível após carregar ' + src));
                            }
                        } catch(e) { reject(e); }
                    };
                    s.onerror = function(){ reject(new Error('Falha ao carregar ' + src)); };
                    document.head.appendChild(s);
                });
            }
            window.__loadPdfJs = {
                ensure: async function() {
                    if (window.pdfjsLib && window.pdfjsLib.getDocument) return window.pdfjsLib;
                    var fontes = [
                        { lib: 'assets/libs/pdfjs/pdf.min.js', worker: 'assets/libs/pdfjs/pdf.worker.min.js' },
                        // cdnjs versão estável 2.x
                        { lib: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js', worker: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js' },
                        // jsDelivr versões 4.x e 3.x
                        { lib: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.0.379/build/pdf.min.js', worker: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.0.379/build/pdf.worker.min.js' },
                        { lib: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.9.179/build/pdf.min.js', worker: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.9.179/build/pdf.worker.min.js' },
                        // unpkg fallback
                        { lib: 'https://unpkg.com/pdfjs-dist@4.0.379/build/pdf.min.js', worker: 'https://unpkg.com/pdfjs-dist@4.0.379/build/pdf.worker.min.js' },
                        { lib: 'https://unpkg.com/pdfjs-dist@3.9.179/build/pdf.min.js', worker: 'https://unpkg.com/pdfjs-dist@3.9.179/build/pdf.worker.min.js' }
                    ];
                    for (var i=0;i<fontes.length;i++) {
                        var c = fontes[i];
                        try { 
                            console.log('Tentando carregar PDF.js de', c.lib);
                            var lib = await loadScript(c.lib, c.worker);
                            if (lib && lib.getDocument) {
                                console.log('PDF.js carregado de', c.lib);
                                return lib;
                            }
                        } catch(e) {
                            console.warn('Falha ao carregar PDF.js de', c.lib, e && e.message ? e.message : e);
                        }
                    }
                    console.error('PDF.js não pôde ser carregado localmente nem de CDNs');
                    return null;
                }
            };
        })();
    </script>
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
    <div id="app">
        <header class="app-header">
            <?php
            $dados_usuario = isset($usuario_atual) ? $usuario_atual : obterDadosUsuario();
            $nome_header = isset($dados_usuario['nome']) ? $dados_usuario['nome'] : (isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Usuário');
            $foto_header = isset($dados_usuario['foto_perfil']) ? $dados_usuario['foto_perfil'] : '';
            $url_avatar_header = $foto_header ? $foto_header : ('https://ui-avatars.com/api/?name=' . urlencode($nome_header) . '&background=fbbf24&color=000');
            ?>
            <div class="app-header-left">
                <div class="app-logo">
                    <i class="fas fa-tree"></i>
                </div>
                <div class="app-titles">
                    <span class="app-title">PEAK</span>
                    <span class="app-subtitle">Gestão Financeira</span>
                </div>
            </div>
            <div class="app-header-right">
                <button class="botao-ocultar" onclick="app.toggleOcultarValores()">
                    <i class="fas" id="icone-ocultar"></i>
                </button>
                <img src="<?php echo htmlspecialchars($url_avatar_header); ?>" class="app-avatar" alt="Avatar">
            </div>
        </header>
        <!-- Conteúdo principal -->
        <div class="conteudo" id="conteudo-principal">
            <!-- Conteúdo carregado dinamicamente ou renderizado inicialmente -->
            <?php include $arquivo_pagina_inicial; ?>
        </div>
        
        <script>
        // Variável para controlar a página atual
        var paginaAtual = '<?php echo $pagina_inicial; ?>';
        
        // Função para definir item ativo no menu
        function definirItemAtivo(pagina) {
            // Remover classe ativa de todos os itens
            var itensMenu = document.querySelectorAll('.menu-inferior .menu-item');
            itensMenu.forEach(function(item) {
                item.classList.remove('ativo');
            });
            
            // Adicionar classe ativa ao item correspondente
            var itemAtivo = document.querySelector('.menu-inferior .menu-item[onclick*="' + pagina + '"]');
            if (itemAtivo) {
                itemAtivo.classList.add('ativo');
            }
            
            // Atualizar variável da página atual
            paginaAtual = pagina;
        }
        
        // Função para carregar páginas via AJAX
        function carregarPagina(pagina) {
            console.log('Carregando página:', pagina);
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    console.log('Página carregada com sucesso:', pagina);
                    // Inserir HTML e executar scripts embutidos
                    var conteudoPrincipal = document.getElementById('conteudo-principal');
                    var containerTemporario = document.createElement('div');
                    containerTemporario.innerHTML = this.responseText;

                    // Extrair scripts do conteúdo carregado
                    var scriptsEncontrados = containerTemporario.querySelectorAll('script');

                    // Renderizar apenas elementos não-script dentro do conteúdo principal
                    conteudoPrincipal.innerHTML = '';
                    containerTemporario.childNodes.forEach(function(no) {
                        var ehScript = no.tagName && no.tagName.toLowerCase() === 'script';
                        if (!ehScript) {
                            conteudoPrincipal.appendChild(no.cloneNode(true));
                        }
                    });

                    // Remover scripts de página anteriores para evitar acúmulo
                    document.querySelectorAll('script[data-pagina-script]').forEach(function(s) { s.remove(); });

                    // Executar scripts embutidos após inserir o conteúdo
                    scriptsEncontrados.forEach(function(script) {
                        var novoScript = document.createElement('script');
                        novoScript.setAttribute('data-pagina-script', pagina);
                        // Manter src quando existir; caso contrário, injetar o conteúdo
                        if (script.src) {
                            // Garantir caminho absoluto quando necessário
                            var src = script.src;
                            try {
                                // Se for relativo, prefixar com '/'
                                var urlObj = new URL(src, window.location.origin);
                                novoScript.src = urlObj.href;
                            } catch (e) {
                                novoScript.src = src.charAt(0) === '/' ? src : ('/' + src);
                            }
                        } else {
                            novoScript.textContent = script.innerHTML;
                        }
                        document.body.appendChild(novoScript);
                    });
                    
                    // Definir item ativo no menu
                    definirItemAtivo(pagina);
                        try { localStorage.setItem('paginaAtual', pagina); } catch(e) {}
                        try { document.cookie = 'paginaAtual=' + encodeURIComponent(pagina) + '; path=/; max-age=31536000'; } catch(e) {}
                    
                    // Inicializar funcionalidades específicas de cada página
                    if (pagina === 'dashboard' && typeof window.app !== 'undefined') {
                        console.log('Inicializando dashboard...');
                        setTimeout(function() {
                            window.app.configurarEventos();
                            window.app.atualizarDescricaoPeriodo();
                            window.app.inicializarGraficos();
                        }, 100);
                    } else if (pagina === 'admin' && typeof window.app !== 'undefined') {
                        console.log('Inicializando admin (seletor de período)...');
                        setTimeout(function() {
                            window.app.configurarEventos();
                            window.app.atualizarDescricaoPeriodo && window.app.atualizarDescricaoPeriodo();
                        }, 100);
                    } else if (pagina === 'transacoes' && typeof window.inicializarTransacoes === 'function') {
                        console.log('Inicializando transações...');
                        setTimeout(function() {
                            window.inicializarTransacoes();
                        }, 100);
                    } else if (pagina === 'categorias' && typeof window.inicializarCategorias === 'function') {
                        console.log('Inicializando categorias...');
                        setTimeout(function() {
                            window.inicializarCategorias();
                        }, 100);
                    } else if (pagina === 'perfil' && typeof window.inicializarPerfil === 'function') {
                        console.log('Inicializando perfil...');
                        setTimeout(function() {
                            window.inicializarPerfil();
                        }, 100);
                    } else {
                        console.log('Nenhuma função de inicialização encontrada para:', pagina);
                        console.log('Funções disponíveis:', {
                            inicializarTransacoes: typeof window.inicializarTransacoes,
                            inicializarCategorias: typeof window.inicializarCategorias,
                            inicializarPerfil: typeof window.inicializarPerfil
                        });
                    }
                } else if (this.readyState == 4) {
                    console.error('Erro ao carregar página:', pagina, 'Status:', this.status);
                }
            };
            xhttp.open("GET", "paginas/" + pagina + ".php", true);
            xhttp.send();
        }
        
        // Inicializar aplicação
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar ícone de ocultar
            var iconeOcultar = document.getElementById('icone-ocultar');
            if (iconeOcultar) {
                iconeOcultar.classList.add('fa-eye-slash');
            }
            
            // Definir item ativo com base na página inicial
            try {
                var salva = localStorage.getItem('paginaAtual');
                if (salva && salva !== paginaAtual) {
                    carregarPagina(salva);
                } else {
                    definirItemAtivo(paginaAtual);
                }
            } catch(e) { definirItemAtivo(paginaAtual); }
        });
        
        // A funcionalidade do app agora está em assets/js/app.js
        </script>
        
        <?php if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') { ?>
            <!-- Menu de navegação inferior -->
            <nav class="menu-inferior">
                <a href="#" class="menu-item" onclick="carregarPagina('dashboard')">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="menu-item" onclick="carregarPagina('transacoes')">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transações</span>
                </a>
                <div class="espaco-central"></div>
                <a href="#" class="menu-item" onclick="carregarPagina('categorias')">
                    <i class="fas fa-tags"></i>
                    <span>Categorias</span>
                </a>
                <a href="#" class="menu-item" onclick="carregarPagina('perfil')">
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
            </nav>

            <!-- Overlay escuro para o menu -->
            <div class="menu-overlay" id="menu-overlay"></div>

            <!-- Botão central flutuante -->
            <a href="#" class="botao-adicionar-central" onclick="toggleMenuCircular()">
                <i class="fas fa-plus"></i>
            </a>

            <!-- Menu circular -->
            <div class="menu-circular" id="menu-circular">
                <a href="#" class="opcao-menu receita" data-tipo="receita" onclick="abrirModalTransacao('receita')">
                    <i class="fas fa-arrow-up"></i>
                    <span>Receita</span>
                </a>
                <a href="#" class="opcao-menu despesa" data-tipo="despesa" onclick="abrirModalTransacao('despesa')">
                    <i class="fas fa-arrow-down"></i>
                    <span>Despesa</span>
                </a>
                <a href="#" class="opcao-menu categoria" onclick="abrirModalCategoria()">
                    <i class="fas fa-tags"></i>
                    <span>Categoria</span>
                </a>
            </div>
        <?php } ?>
        
        <script>
        // Função para alternar o menu circular
        function toggleMenuCircular() {
            var menu = document.getElementById('menu-circular');
            var overlay = document.getElementById('menu-overlay');
            
            menu.classList.toggle('ativo');
            overlay.classList.toggle('ativo');
        }
        
        function abrirModalTransacao(tipo) {
            var mc = document.getElementById('menu-circular');
            var ov = document.getElementById('menu-overlay');
            if (mc) mc.classList.remove('ativo');
            if (ov) ov.classList.remove('ativo');

            var modal = document.getElementById('modalTransacao');
            var campoTipo = document.getElementById('transacao-tipo');
            if (modal && campoTipo) {
                campoTipo.value = tipo;
                var campoDestino = document.getElementById('campo-conta-destino');
                if (campoDestino) {
                    campoDestino.style.display = (tipo === 'transferencia') ? 'block' : 'none';
                }
                if (typeof carregarCategoriasParaTransacao === 'function') carregarCategoriasParaTransacao();
                if (typeof carregarContasParaTransacao === 'function') carregarContasParaTransacao();
                modal.classList.add('ativo');
                return;
            }

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var modalContainer = document.getElementById('modal-container');
                    if (!modalContainer) {
                        modalContainer = document.createElement('div');
                        modalContainer.id = 'modal-container';
                        document.body.appendChild(modalContainer);
                    }
                    modalContainer.innerHTML = this.responseText;
                    var campoTipo2 = document.getElementById('transacao-tipo');
                    if (campoTipo2) campoTipo2.value = tipo;
                    var modal2 = document.getElementById('modalTransacao');
                    if (modal2) modal2.classList.add('ativo');
                }
            };
            xhttp.open("GET", "modais/transacao.php", true);
            xhttp.send();
        }
        </script>
        
        <!-- Modais -->
        <div id="container-modais">
            <?php 
            include 'modais/transacao.php';
            include 'modais/categoria.php';
            include 'modais/importar_extrato.php';
            ?>
        </div>
    </div>
    
    <!-- Importação de scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
