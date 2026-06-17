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

// Lógica de Roteamento inicial
$pagina_inicial = isset($_GET['pagina']) ? preg_replace('/[^a-z_]/', '', $_GET['pagina']) : '';

if ($pagina_inicial === '' && isset($_COOKIE['paginaAtual'])) {
    $cookiePagina = preg_replace('/[^a-z_]/', '', $_COOKIE['paginaAtual']);
    $paginasPermitidas = ['dashboard', 'transacoes', 'categorias', 'perfil', 'admin'];
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
    <?php
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $uri_sem_query = strtok($uri, '?');
    $caminho_base = $uri_sem_query;
    if (substr($caminho_base, -10) === '/index.php') {
        $caminho_base = substr($caminho_base, 0, -10);
    }
    $pos_paginas = strpos($caminho_base, '/paginas/');
    if ($pos_paginas !== false) {
        $caminho_base = substr($caminho_base, 0, $pos_paginas);
    }
    $pos_modais = strpos($caminho_base, '/modais/');
    if ($pos_modais !== false) {
        $caminho_base = substr($caminho_base, 0, $pos_modais);
    }
    if (substr($caminho_base, -1) !== '/') {
        $caminho_base .= '/';
    }
    ?>
    <base href="<?= htmlspecialchars($caminho_base) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanças Pessoais</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // PDF.js loader (mantido original)
        (function () {
            function loadScript(src, worker) {
                return new Promise(function (resolve, reject) {
                    var s = document.createElement('script');
                    s.src = src;
                    s.onload = function () {
                        try {
                            if (window.pdfjsLib && window.pdfjsLib.GlobalWorkerOptions) {
                                window.pdfjsLib.GlobalWorkerOptions.workerSrc = worker;
                                resolve(window.pdfjsLib);
                            } else {
                                reject(new Error('pdfjsLib não disponível'));
                            }
                        } catch (e) { reject(e); }
                    };
                    s.onerror = function () { reject(new Error('Falha ao carregar ' + src)); };
                    document.head.appendChild(s);
                });
            }
            window.__loadPdfJs = {
                ensure: async function () {
                    if (window.pdfjsLib && window.pdfjsLib.getDocument) return window.pdfjsLib;
                    var fontes = [
                        { lib: 'assets/libs/pdfjs/pdf.min.js', worker: 'assets/libs/pdfjs/pdf.worker.min.js' },
                        { lib: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js', worker: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js' }
                    ];
                    for (var i = 0; i < fontes.length; i++) {
                        try {
                            return await loadScript(fontes[i].lib, fontes[i].worker);
                        } catch (e) { console.warn(e); }
                    }
                    return null;
                }
            };
        })();
    </script>
</head>

<body>
    <script>
        // Aplica tema antes de renderizar (evita flash)
        (function () {
            try {
                var escuro = localStorage.getItem('temaEscuro');
                var isDark = escuro !== 'false';
                document.body.classList.add(isDark ? 'tema-escuro' : 'tema-claro');
            } catch (e) { document.body.classList.add('tema-escuro'); }
        })();
    </script>

    <div id="app">
        <header class="app-header">
            <?php
            $dados_usuario = isset($usuario_atual) ? $usuario_atual : obterDadosUsuario();
            $nome_header = $dados_usuario['nome'] ?? 'Usuário';
            $foto_header = $dados_usuario['foto_perfil'] ?? '';
            $url_avatar_header = $foto_header ?: ('https://ui-avatars.com/api/?name=' . urlencode($nome_header) . '&background=fbbf24&color=000');
            ?>
            <div class="app-header-left">
                <div class="app-logo"><i class="fas fa-tree"></i></div>
                <div class="app-titles">
                    <span class="app-title">PEAK</span>
                    <span class="app-subtitle">Otimização Financeira</span>
                </div>
            </div>
            <div class="app-header-right">
                <div class="flex items-center mr-4">
                    <button id="btn-tema" onclick="alternarTema()" title="Alternar tema" class="btn-tema-toggle">
                        <i id="icone-tema" class="fas fa-moon"></i>
                    </button>
                </div>
                <img src="<?= htmlspecialchars($url_avatar_header) ?>" class="app-avatar" alt="Avatar">
            </div>
        </header>

        <div class="conteudo" id="conteudo-principal">
            <?php include $arquivo_pagina_inicial; ?>
        </div>

        <div class="fixed bottom-8 left-1/2 -translate-x-1/2 w-auto min-w-[20rem] max-w-[95vw] 
            bg-[#121214]/80 backdrop-blur-xl border border-white/10 p-2 rounded-full 
            flex items-center justify-center gap-1 shadow-2xl shadow-black/50 z-50 menu-inferior">

            <div class="flex items-center gap-1">
                <a href="javascript:void(0)" onclick="carregarPagina('dashboard')" data-pagina="dashboard"
                    class="nav-pill-item w-12 h-12 rounded-full flex items-center justify-center transition-all hover:bg-white/10">
                    <i class="fas fa-home text-lg"></i>
                </a>
                <a href="javascript:void(0)" onclick="carregarPagina('transacoes')" data-pagina="transacoes"
                    class="nav-pill-item w-12 h-12 rounded-full flex items-center justify-center transition-all hover:bg-white/10">
                    <i class="fas fa-exchange-alt text-lg"></i>
                </a>
            </div>

            <div class="px-2">
                <a href="javascript:void(0)" onclick="toggleMenuCircular()"
                    class="w-14 h-14 bg-amber-500 hover:bg-amber-600 text-black rounded-full flex items-center justify-center shadow-lg transition-all transform hover:scale-110 active:scale-95">
                    <i class="fas fa-plus text-xl"></i>
                </a>
            </div>

            <div class="flex items-center gap-1">
                <a href="javascript:void(0)" onclick="carregarPagina('categorias')" data-pagina="categorias"
                    class="nav-pill-item w-12 h-12 rounded-full flex items-center justify-center transition-all hover:bg-white/10">
                    <i class="fas fa-tags text-lg"></i>
                </a>
                <a href="javascript:void(0)" onclick="carregarPagina('perfil')" data-pagina="perfil"
                    class="nav-pill-item w-12 h-12 rounded-full flex items-center justify-center transition-all hover:bg-white/10">
                    <i class="fas fa-user text-lg"></i>
                </a>
                <?php
                $perfil_menu_admin = (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin')
                    || (isset($usuario_atual['perfil']) && $usuario_atual['perfil'] === 'admin');
                if ($perfil_menu_admin): ?>
                    <a href="javascript:void(0)" onclick="carregarPagina('admin')" data-pagina="admin"
                        class="nav-pill-item w-12 h-12 rounded-full flex items-center justify-center transition-all hover:bg-white/10">
                        <i class="fas fa-tools text-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="menu-overlay" id="menu-overlay" onclick="toggleMenuCircular()"></div>
        <div class="menu-circular" id="menu-circular">
            <a href="javascript:void(0)" class="opcao-menu receita" onclick="abrirModalTransacao('receita')">
                <i class="fas fa-arrow-up"></i><span>Receita</span>
            </a>
            <a href="javascript:void(0)" class="opcao-menu despesa" onclick="abrirModalTransacao('despesa')">
                <i class="fas fa-arrow-down"></i><span>Despesa</span>
            </a>
            <a href="javascript:void(0)" class="opcao-menu categoria"
                onclick="toggleMenuCircular(); setTimeout(function(){ if(typeof abrirModalCategoria==='function') abrirModalCategoria(); }, 150)">
                <i class="fas fa-tag"></i><span>Categoria</span>
            </a>
            <a href="javascript:void(0)" class="opcao-menu fechar-menu" onclick="toggleMenuCircular()">
                <i class="fas fa-times"></i><span>Fechar</span>
            </a>
        </div>

        <div id="container-modais">
            <?php
            include 'modais/transacao.php';
            include 'modais/categoria.php';
            include 'modais/importar_extrato.php';
            ?>
        </div>
    </div>

    <script src="assets/js/app.js"></script>

    <script>
        var paginaAtual = '<?= $pagina_inicial ?>';

        function definirItemAtivo(pagina) {
            document.querySelectorAll('.nav-pill-item').forEach(function(a) {
                a.classList.remove('text-amber-500', 'bg-white/10');
                a.classList.add('text-neutral-500');
            });
            var link = document.querySelector(`.nav-pill-item[data-pagina="${pagina}"]`);
            if (link) {
                link.classList.remove('text-neutral-500');
                link.classList.add('text-amber-500');
            }
        }

        function carregarPagina(pagina, push = true) {
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    var conteudo = document.getElementById('conteudo-principal');
                    var temp = document.createElement('div');
                    temp.innerHTML = this.responseText;

                    var scripts = temp.querySelectorAll('script');
                    conteudo.innerHTML = '';
                    temp.childNodes.forEach(no => {
                        if (no.tagName !== 'SCRIPT') conteudo.appendChild(no.cloneNode(true));
                    });

                    // Remover scripts da página anterior antes de adicionar os novos
                    document.querySelectorAll('script[data-pagina-script]').forEach(function(s) { s.remove(); });

                    // Adicionar scripts da nova página com atributo para limpeza futura
                    scripts.forEach(function(script) {
                        var novoScript = document.createElement('script');
                        novoScript.setAttribute('data-pagina-script', pagina);
                        if (script.src) {
                            try {
                                var urlObj = new URL(script.src, window.location.origin);
                                novoScript.src = urlObj.href;
                            } catch (e) {
                                novoScript.src = script.src.charAt(0) === '/' ? script.src : ('/' + script.src);
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

        // Evento para quando o usuário usa as setas do navegador
        window.onpopstate = function (e) {
            if (e.state && e.state.pagina) carregarPagina(e.state.pagina, false);
        };

        // Inicialização
        document.addEventListener('DOMContentLoaded', function () {
            definirItemAtivo(paginaAtual);
            // Salva o estado inicial na History API para o F5 funcionar
            if (!history.state) history.replaceState({ pagina: paginaAtual }, "", window.location.search);
            var conteudo = document.getElementById('conteudo-principal');
            if (conteudo) conteudo.classList.remove('sem-padding');
        });
        
        // A funcionalidade do app agora está em assets/js/app.js
        </script>
        
        
        <script>
        // Função para alternar o menu circular
        function toggleMenuCircular() {
            document.getElementById('menu-circular').classList.toggle('ativo');
            document.getElementById('menu-overlay').classList.toggle('ativo');
        }

        function aplicarTema(isDark) {
            document.body.classList.toggle('tema-escuro', isDark);
            document.body.classList.toggle('tema-claro', !isDark);
            var icone = document.getElementById('icone-tema');
            var label = document.getElementById('label-tema');
            if (icone) icone.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
            if (label) label.textContent = isDark ? 'Escuro' : 'Claro';
        }

        function alternarTema() {
            var isDark = !document.body.classList.contains('tema-escuro');
            localStorage.setItem('temaEscuro', isDark);
            aplicarTema(isDark);
        }

        // Sincroniza ícone com tema atual ao carregar
        (function() {
            var isDark = localStorage.getItem('temaEscuro') !== 'false';
            aplicarTema(isDark);
        })();
    </script>
</body>

</html>
