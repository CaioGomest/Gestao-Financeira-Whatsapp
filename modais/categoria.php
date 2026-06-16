<div class="modal" id="modalCategoria">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <h3 id="modal-titulo">Nova Categoria</h3>
            <span class="fechar" onclick="fecharModalCategoria()">&times;</span>
        </div>
        <div class="modal-corpo">
            <form id="form-categoria" onsubmit="salvarCategoria(event)">
                <input type="hidden" id="categoria-id" value="">
                
                <div class="campo-formulario">
                    <label for="categoria-nome">Nome</label>
                    <input type="text" id="categoria-nome" required>
                </div>
                
                <div class="campo-formulario">
                    <label for="categoria-tipo">Tipo</label>
                    <select id="categoria-tipo" required>
                        <option value="receita">Receita</option>
                        <option value="despesa">Despesa</option>
                    </select>
                </div>
                
                <div class="campo-formulario">
                    <label for="categoria-cor">Cor</label>
                    <input type="color" id="categoria-cor" required oninput="atualizarIconePreview()" onchange="atualizarIconePreview()">
                </div>
                
                <div class="campo-formulario">
                    <label for="categoria-icone">Ícone</label>
                    <select id="categoria-icone" onchange="atualizarIconePreview()" required>
                        <option value="home">Casa</option>
                        <option value="shopping-cart">Compras</option>
                        <option value="utensils">Alimentação</option>
                        <option value="car">Transporte</option>
                        <option value="medkit">Saúde</option>
                        <option value="graduation-cap">Educação</option>
                        <option value="gamepad">Lazer</option>
                        <option value="tshirt">Vestuário</option>
                        <option value="money-bill-wave">Salário</option>
                        <option value="gift">Presente</option>
                        <option value="piggy-bank">Investimento</option>
                        <option value="chart-line">Rendimento</option>
                    </select>
                    <div class="icone-preview">
                        <i id="icone-preview" class="fas fa-home"></i>
                    </div>
                </div>
                
                <div class="acoes-formulario">
                    <button type="button" class="botao secundario" onclick="fecharModalCategoria()">Cancelar</button>
                    <button type="submit" class="botao primario">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.abrirModalCategoria = function(categoria = null) {
    // Fechar menu circular se estiver aberto
    var menuCircular = document.getElementById('menu-circular');
    var menuOverlay = document.getElementById('menu-overlay');
    var botaoAdicionar = document.querySelector('.botao-adicionar-central');
    if (menuCircular) menuCircular.classList.remove('ativo');
    if (menuOverlay) menuOverlay.classList.remove('ativo');
    if (botaoAdicionar) botaoAdicionar.classList.remove('ativo');

    // Resetar formulário
    document.getElementById('form-categoria').reset();
    document.getElementById('categoria-id').value = '';
    
    // Definir título do modal
    if (categoria) {
        document.getElementById('modal-titulo').textContent = 'Editar Categoria';
        document.getElementById('categoria-id').value = categoria.id;
        document.getElementById('categoria-nome').value = categoria.nome;
        document.getElementById('categoria-tipo').value = categoria.tipo;
        document.getElementById('categoria-cor').value = categoria.cor;
        document.getElementById('categoria-icone').value = categoria.icone;
        atualizarIconePreview();
    } else {
        document.getElementById('modal-titulo').textContent = 'Nova Categoria';
    }
    
    // Exibir modal usando a classe 'ativo'
    document.getElementById('modalCategoria').classList.add('ativo');
}

function fecharModalCategoria() {
    document.getElementById('modalCategoria').classList.remove('ativo');
    var menuCircular = document.getElementById('menu-circular');
    var menuOverlay = document.getElementById('menu-overlay');
    var botaoAdicionar = document.querySelector('.botao-adicionar-central');
    if (menuCircular) menuCircular.classList.remove('ativo');
    if (menuOverlay) menuOverlay.classList.remove('ativo');
    if (botaoAdicionar) botaoAdicionar.classList.remove('ativo');
}

function atualizarIconePreview() {
    var iconeSelecionado = document.getElementById('categoria-icone').value;
    var corSelecionada = document.getElementById('categoria-cor').value;
    var iconePreview = document.getElementById('icone-preview');
    iconePreview.className = 'fas fa-' + iconeSelecionado;
    iconePreview.style.color = corSelecionada || '#666666';
}

function salvarCategoria(event) {
    event.preventDefault();
    
    // Obter dados do formulário
    var id = document.getElementById('categoria-id').value;
    var nome = document.getElementById('categoria-nome').value;
    var tipo = document.getElementById('categoria-tipo').value;
    var cor = document.getElementById('categoria-cor').value;
    var icone = document.getElementById('categoria-icone').value;
    
    // Preparar dados para envio
    var dados = {
        nome: nome,
        tipo: tipo,
        cor: cor,
        icone: icone
    };
    
    if (id) {
        dados.id = parseInt(id);
    }
    
    // Helper para montar URL correta relativa à raiz da aplicação
    function caminhoAplicacao(relativo) {
        if (typeof obterUrl === 'function') {
            return obterUrl(relativo);
        }
        var caminhoNormalizado = (relativo || '').replace(/^\//, '');
        var base = window.location.pathname || '';
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

    // Enviar requisição AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', caminhoAplicacao('funcoes/transacoes.php?api=categorias&acao=salvar'), true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var resposta = JSON.parse(xhr.responseText);
                if (resposta.sucesso) {
                    // Fechar modal
                    fecharModalCategoria();
                    
                    // Recarregar categorias
                    if (typeof carregarCategorias === 'function') {
                        carregarCategorias();
                    } else if (typeof app !== 'undefined' && app.atualizarGraficos) {
                        app.atualizarGraficos();
                    } else {
                        // Recarregar página se não houver função de carregar categorias
                        location.reload();
                    }
                } else {
                    alert('Erro ao salvar categoria: ' + (resposta.erro || 'Erro desconhecido'));
                }
            } catch (e) {
                alert('Erro ao processar resposta do servidor');
            }
        } else {
            alert('Erro ao salvar categoria');
        }
    };
    xhr.send(JSON.stringify(dados));
}
</script>