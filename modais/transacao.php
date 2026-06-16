<div class="modal" id="modalTransacao">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <h3 id="modal-titulo">Nova Transação</h3>
            <span class="fechar" onclick="fecharModalTransacao()">&times;</span>
        </div>
        <div class="modal-corpo">
            <form id="form-transacao" onsubmit="salvarTransacao(event)">
                <input type="hidden" id="transacao-id" value="">
                <input type="hidden" id="transacao-tipo" value="receita">
                <input type="hidden" id="transacao-recorrencia-grupo-id" value="">

                <div class="campo-formulario">
                    <label for="transacao-descricao">Descrição</label>
                    <input type="text" id="transacao-descricao" required>
                </div>

                <div class="campo-formulario">
                    <label for="transacao-valor">Valor</label>
                    <input type="number" id="transacao-valor" step="0.01" min="0.01" required>
                </div>

                <!-- Campo de data (dia exato para única/semanal/diária) -->
                <div class="campo-formulario" id="container-data">
                    <label for="transacao-data" id="label-transacao-data">Data</label>
                    <input type="date" id="transacao-data">
                </div>

                <!-- Campo mês de início (exclusivo para mensal) -->
                <div class="campo-formulario" id="container-mes-inicio" style="display: none;">
                    <label for="transacao-mes-inicio">Mês de início</label>
                    <input type="month" id="transacao-mes-inicio">
                </div>

                <div class="campo-formulario">
                    <label for="transacao-recorrencia">Recorrente?</label>
                    <select id="transacao-recorrencia" onchange="alternarCamposRecorrencia()">
                        <option value="">Não (Única)</option>
                        <option value="diario">Diariamente</option>
                        <option value="semanal">Semanalmente</option>
                        <option value="mensal">Mensalmente</option>
                    </select>
                </div>

                <div id="container-config-recorrencia" class="container-config-recorrencia" style="display: none;">
                    <!-- Configuração Mensal -->
                    <div id="config-recorrencia-mensal" style="display: none;">
                        <label style="display: block; margin-bottom: 5px; font-size: 0.9em; color: var(--cor-texto-secundario);">Dias do Vencimento</label>
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px;">
                            <!-- Dias gerados via JS -->
                        </div>
                        <input type="hidden" id="transacao-dias-vencimento">
                    </div>

                    <!-- Configuração Semanal -->
                    <div id="config-recorrencia-semanal" style="display: none;">
                        <label style="display: block; margin-bottom: 5px; font-size: 0.9em; color: var(--cor-texto-secundario);">Dias da Semana</label>
                        <div style="display: flex; justify-content: space-between; gap: 5px;">
                            <button type="button" class="btn-dia-semana" data-dia="0">D</button>
                            <button type="button" class="btn-dia-semana" data-dia="1">S</button>
                            <button type="button" class="btn-dia-semana" data-dia="2">T</button>
                            <button type="button" class="btn-dia-semana" data-dia="3">Q</button>
                            <button type="button" class="btn-dia-semana" data-dia="4">Q</button>
                            <button type="button" class="btn-dia-semana" data-dia="5">S</button>
                            <button type="button" class="btn-dia-semana" data-dia="6">S</button>
                        </div>
                        <input type="hidden" id="transacao-dias-semana">
                    </div>

                    <!-- Término da recorrência (opcional) -->
                    <div id="container-fim-recorrencia" style="margin-top: 10px;">
                        <label class="label-checkbox-fim">
                            <input type="checkbox" id="chk-fim-recorrencia" onchange="alternarCampoFim()">
                            <span>Definir término</span>
                        </label>
                        <div id="campo-fim-recorrencia" class="campo-formulario" style="display: none; margin-top: 8px; margin-bottom: 0;">
                            <label id="label-fim-recorrencia">Até (mês/ano)</label>
                            <input type="month" id="transacao-fim-recorrencia">
                        </div>
                    </div>
                </div>
                
                
                <div class="campo-formulario">
                    <label for="transacao-categoria">Categoria</label>
                    <select id="transacao-categoria" required>
                        <!-- Categorias serão carregadas via JavaScript -->
                    </select>
                </div>
                
                <div class="campo-formulario">
                    <label for="transacao-conta">Conta</label>
                    <select id="transacao-conta" onchange="atualizarContaDestino()" required>
                        <!-- Contas serão carregadas via JavaScript -->
                    </select>
                </div>
                
                <div class="campo-formulario" id="campo-conta-destino" style="display: none;">
                    <label for="transacao-conta-destino">Conta de Destino</label>
                    <select id="transacao-conta-destino" required>
                        <!-- Contas serão carregadas via JavaScript -->
                    </select>
                </div>
                
                <div class="acoes-formulario">
                    <button type="button" class="botao secundario" onclick="fecharModalTransacao()">Cancelar</button>
                    <button type="submit" class="botao primario">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Variáveis globais
var categorias = [];
var contas = [];

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

window.abrirModalTransacao = function(tipo, transacao = null) {
    // Fechar menu circular se estiver aberto
    var menuCircular = document.getElementById('menu-circular');
    var menuOverlay = document.getElementById('menu-overlay');
    var botaoAdicionar = document.querySelector('.botao-adicionar-central');
    if (menuCircular) menuCircular.classList.remove('ativo');
    if (menuOverlay) menuOverlay.classList.remove('ativo');
    if (botaoAdicionar) botaoAdicionar.classList.remove('ativo');

    // Resetar formulário
    document.getElementById('form-transacao').reset();
    document.getElementById('transacao-id').value = '';
    
    // Resetar recorrência e campos relacionados
    document.getElementById('transacao-recorrencia').value = '';
    var chkFimReset = document.getElementById('chk-fim-recorrencia');
    if (chkFimReset) chkFimReset.checked = false;
    var campoFimReset = document.getElementById('campo-fim-recorrencia');
    if (campoFimReset) campoFimReset.style.display = 'none';
    var fimInputReset = document.getElementById('transacao-fim-recorrencia');
    if (fimInputReset) fimInputReset.value = '';
    var containerMesInicioReset = document.getElementById('container-mes-inicio');
    if (containerMesInicioReset) containerMesInicioReset.style.display = 'none';

    // Garantir que campo de data esteja visível no reset
    var containerData = document.getElementById('container-data');
    if(containerData) containerData.style.display = 'block';

    alternarCamposRecorrencia();
    
    // Definir tipo de transação
    document.getElementById('transacao-tipo').value = tipo || 'receita';
    
    // Mostrar/esconder campo de conta destino
    if (tipo === 'transferencia') {
        document.getElementById('campo-conta-destino').style.display = 'block';
    } else {
        document.getElementById('campo-conta-destino').style.display = 'none';
    }
    
    // Carregar categorias e contas
    carregarCategoriasParaTransacao();
    carregarContasParaTransacao();
    
    // Definir título do modal
    if (transacao) {
        var recorrenciaExistente = transacao.recorrencia || '';
        if (recorrenciaExistente) {
            document.getElementById('modal-titulo').textContent = 'Editar Ocorrência (recorrente)';
        } else {
            document.getElementById('modal-titulo').textContent = 'Editar Transação';
        }
        document.getElementById('transacao-id').value = transacao.id;
        document.getElementById('transacao-descricao').value = transacao.descricao;
        document.getElementById('transacao-valor').value = transacao.valor;
        document.getElementById('transacao-data').value = transacao.data || transacao.data_transacao || '';
        document.getElementById('transacao-recorrencia-grupo-id').value = transacao.recorrencia_grupo_id || '';

        // Restaurar tipo de recorrência, se houver
        if (recorrenciaExistente) {
            document.getElementById('transacao-recorrencia').value = recorrenciaExistente;
            alternarCamposRecorrencia();
        }

        // Selecionar categoria e conta após carregamento
        setTimeout(function() {
            document.getElementById('transacao-categoria').value = transacao.categoria_id;
            document.getElementById('transacao-conta').value = transacao.conta_id;
            if (tipo === 'transferencia') {
                document.getElementById('transacao-conta-destino').value = transacao.conta_destino_id;
            }
        }, 500);
    } else {
        document.getElementById('modal-titulo').textContent = 'Nova Transação';
        document.getElementById('transacao-recorrencia-grupo-id').value = '';
        // Definir data atual usando fuso horário local (sem UTC)
        function formatarDataInputLocal(data) {
            var ano = data.getFullYear();
            var mes = String(data.getMonth() + 1).padStart(2, '0');
            var dia = String(data.getDate()).padStart(2, '0');
            return ano + '-' + mes + '-' + dia;
        }
        var hoje = new Date();
        document.getElementById('transacao-data').value = formatarDataInputLocal(hoje);
        // Preencher mês início com mês atual
        document.getElementById('transacao-mes-inicio').value = hoje.getFullYear() + '-' + String(hoje.getMonth() + 1).padStart(2, '0');
    }
    
    // Exibir modal usando a classe 'ativo'
    document.getElementById('modalTransacao').classList.add('ativo');
}

function fecharModalTransacao() {
    var modal = document.getElementById('modalTransacao');
    if (modal) modal.classList.remove('ativo');
    var menu = document.getElementById('menu-circular');
    var overlay = document.getElementById('menu-overlay');
    var botao = document.querySelector('.botao-adicionar-central');
    if (menu) menu.classList.remove('ativo');
    if (overlay) overlay.classList.remove('ativo');
    if (botao) botao.classList.remove('ativo');
}

function alternarCamposRecorrencia() {
    var recorrencia = document.getElementById('transacao-recorrencia').value;
    var container = document.getElementById('container-config-recorrencia');
    var configMensal = document.getElementById('config-recorrencia-mensal');
    var configSemanal = document.getElementById('config-recorrencia-semanal');
    var containerData = document.getElementById('container-data');
    var containerMesInicio = document.getElementById('container-mes-inicio');
    var labelFim = document.getElementById('label-fim-recorrencia');
    var inputFim = document.getElementById('transacao-fim-recorrencia');

    // Resetar visibilidades
    containerData.style.display = 'block';
    containerMesInicio.style.display = 'none';

    if (recorrencia) {
        container.style.display = 'block';
        configMensal.style.display = 'none';
        configSemanal.style.display = 'none';

        if (recorrencia === 'mensal') {
            configMensal.style.display = 'block';
            // Trocar campo data por mês de início
            containerData.style.display = 'none';
            containerMesInicio.style.display = 'block';
            // Inicializar mês de início com valor atual se vazio
            var mesInicioInput = document.getElementById('transacao-mes-inicio');
            if (!mesInicioInput.value) {
                var hoje = new Date();
                mesInicioInput.value = hoje.getFullYear() + '-' + String(hoje.getMonth() + 1).padStart(2, '0');
            }
            // Gerar botões do mês com dia 1 como padrão
            gerarBotoesDiasMes(1);
            // Label e tipo do campo de término
            if (labelFim) labelFim.textContent = 'Até (mês/ano)';
            if (inputFim) inputFim.type = 'month';
        } else if (recorrencia === 'semanal') {
            configSemanal.style.display = 'block';
            containerData.style.display = 'block';
            var dataInput = document.getElementById('transacao-data').value;
            var dataObj = dataInput ? new Date(dataInput + 'T12:00:00') : new Date();
            selecionarDiaSemana(dataObj.getDay());
            if (labelFim) labelFim.textContent = 'Até (data)';
            if (inputFim) inputFim.type = 'date';
        } else {
            // Diário: só mostra data de início
            container.style.display = 'none';
            containerData.style.display = 'block';
        }
    } else {
        container.style.display = 'none';
    }
}

function gerarBotoesDiasMes(diaSelecionado) {
    var container = document.querySelector('#config-recorrencia-mensal div');
    container.innerHTML = '';
    
    // Armazenar múltiplos dias - usar variável local ao escopo para não perder referência
    var diasSelecionados = [diaSelecionado];
    document.getElementById('transacao-dias-vencimento').value = diasSelecionados.join(',');
    
    for (var i = 1; i <= 31; i++) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-dia-mes' + (i === diaSelecionado ? ' ativo' : '');
        btn.textContent = i;
        btn.onclick = function() {
            var dia = parseInt(this.textContent);
            this.classList.toggle('ativo');
            
            // Atualizar array baseado na classe visual
            if (this.classList.contains('ativo')) {
                if (!diasSelecionados.includes(dia)) {
                    diasSelecionados.push(dia);
                }
            } else {
                var index = diasSelecionados.indexOf(dia);
                if (index > -1) {
                    diasSelecionados.splice(index, 1);
                }
            }
            
            // Ordenar e salvar
            diasSelecionados.sort((a, b) => a - b);
            document.getElementById('transacao-dias-vencimento').value = diasSelecionados.join(',');
        };
        container.appendChild(btn);
    }
}

function selecionarDiaSemana(diaIdx) {
    // Limpar seleção anterior
    document.querySelectorAll('.btn-dia-semana').forEach(b => b.classList.remove('ativo'));
    var btn = document.querySelector('.btn-dia-semana[data-dia="' + diaIdx + '"]');
    if (btn) btn.classList.add('ativo');
    
    // Inicializar array com o dia atual
    var diasSelecionados = [diaIdx];
    document.getElementById('transacao-dias-semana').value = diasSelecionados.join(',');
    
    // Adicionar listener a todos os botões (evitando clonagem complexa)
    var botoes = document.querySelectorAll('.btn-dia-semana');
    botoes.forEach(btn => {
        // Remover listener antigo para evitar duplicação (hack simples)
        var novoBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(novoBtn, btn);
        
        novoBtn.onclick = function() {
            var dia = parseInt(this.dataset.dia);
            this.classList.toggle('ativo');
            
            // Recalcular lista baseado nos botões ativos
            diasSelecionados = [];
            document.querySelectorAll('.btn-dia-semana.ativo').forEach(b => {
                diasSelecionados.push(parseInt(b.dataset.dia));
            });
            
            diasSelecionados.sort((a, b) => a - b);
            document.getElementById('transacao-dias-semana').value = diasSelecionados.join(',');
        };
    });
}

function carregarCategoriasParaTransacao() {
    var tipo = document.getElementById('transacao-tipo').value;
    var select = document.getElementById('transacao-categoria');
    select.innerHTML = '<option value="">Carregando...</option>';
    
    // Carregar categorias via AJAX
    var xhr = new XMLHttpRequest();
    // Para transferências, listar todas as categorias (sem filtro por tipo)
    var urlCategorias = 'funcoes/transacoes.php?api=categorias&acao=listar' + (tipo === 'transferencia' ? '' : ('&tipo=' + tipo));
    xhr.open('GET', obterUrl(urlCategorias), true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                categorias = JSON.parse(xhr.responseText);
                select.innerHTML = '';
                
                if (categorias.length === 0) {
                    select.innerHTML = '<option value="">Nenhuma categoria encontrada</option>';
                } else {
                    categorias.forEach(function(categoria) {
                        var option = document.createElement('option');
                        option.value = categoria.id;
                        option.textContent = categoria.nome;
                        select.appendChild(option);
                    });
                }
            } catch (e) {
                select.innerHTML = '<option value="">Erro ao carregar categorias</option>';
            }
        } else {
            select.innerHTML = '<option value="">Erro ao carregar categorias</option>';
        }
    };
    xhr.send();
}

function carregarContasParaTransacao() {
    var selectConta = document.getElementById('transacao-conta');
    var selectContaDestino = document.getElementById('transacao-conta-destino');
    selectConta.innerHTML = '<option value="">Carregando...</option>';
    selectContaDestino.innerHTML = '<option value="">Carregando...</option>';
    
    // Carregar contas via AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('GET', obterUrl('api/contas.php'), true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                contas = JSON.parse(xhr.responseText);
                selectConta.innerHTML = '';
                selectContaDestino.innerHTML = '';
                
                if (contas.length === 0) {
                    selectConta.innerHTML = '<option value="">Nenhuma conta encontrada</option>';
                    selectContaDestino.innerHTML = '<option value="">Nenhuma conta encontrada</option>';
                } else {
                    contas.forEach(function(conta) {
                        // Adicionar ao select de conta origem
                        var option1 = document.createElement('option');
                        option1.value = conta.id;
                        option1.textContent = conta.nome;
                        selectConta.appendChild(option1);
                        
                        // Adicionar ao select de conta destino
                        var option2 = document.createElement('option');
                        option2.value = conta.id;
                        option2.textContent = conta.nome;
                        selectContaDestino.appendChild(option2);
                    });
                    
                    // Atualizar conta destino para desabilitar a conta selecionada
                    atualizarContaDestino();
                }
            } catch (e) {
                selectConta.innerHTML = '<option value="">Erro ao carregar contas</option>';
                selectContaDestino.innerHTML = '<option value="">Erro ao carregar contas</option>';
            }
        } else {
            selectConta.innerHTML = '<option value="">Erro ao carregar contas</option>';
            selectContaDestino.innerHTML = '<option value="">Erro ao carregar contas</option>';
        }
    };
    xhr.send();
}

function atualizarContaDestino() {
    var contaOrigem = document.getElementById('transacao-conta').value;
    var selectContaDestino = document.getElementById('transacao-conta-destino');
    
    // Habilitar todas as opções
    for (var i = 0; i < selectContaDestino.options.length; i++) {
        selectContaDestino.options[i].disabled = false;
    }
    
    // Desabilitar a conta de origem
    for (var i = 0; i < selectContaDestino.options.length; i++) {
        if (selectContaDestino.options[i].value === contaOrigem) {
            selectContaDestino.options[i].disabled = true;
            
            // Se a conta destino for igual à origem, selecionar outra
            if (selectContaDestino.value === contaOrigem) {
                for (var j = 0; j < selectContaDestino.options.length; j++) {
                    if (!selectContaDestino.options[j].disabled) {
                        selectContaDestino.value = selectContaDestino.options[j].value;
                        break;
                    }
                }
            }
            
            break;
        }
    }
}

function alternarCampoFim() {
    var chk = document.getElementById('chk-fim-recorrencia');
    var campo = document.getElementById('campo-fim-recorrencia');
    if (campo) campo.style.display = chk.checked ? 'block' : 'none';
}

function salvarTransacao(event) {
    event.preventDefault();

    // Obter dados do formulário
    var id = document.getElementById('transacao-id').value;
    var tipo = document.getElementById('transacao-tipo').value;
    var descricao = document.getElementById('transacao-descricao').value;
    var valor = document.getElementById('transacao-valor').value;
    var recorrencia = document.getElementById('transacao-recorrencia').value;
    var categoria_id = document.getElementById('transacao-categoria').value;
    var conta_id = document.getElementById('transacao-conta').value;
    var conta_destino_id = tipo === 'transferencia' ? document.getElementById('transacao-conta-destino').value : '';
    var dias_vencimento = document.getElementById('transacao-dias-vencimento').value;
    var dias_semana = document.getElementById('transacao-dias-semana').value;

    // Definir data base: mensal usa mês de início, demais usam campo data
    var data;
    if (recorrencia === 'mensal') {
        var mesInicio = document.getElementById('transacao-mes-inicio').value; // 'YYYY-MM'
        data = mesInicio ? (mesInicio + '-01') : (new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-01');
    } else {
        data = document.getElementById('transacao-data').value;
    }

    // Calcular repetições
    var repeticoes = 1;
    var chkFim = document.getElementById('chk-fim-recorrencia');
    var fimValor = document.getElementById('transacao-fim-recorrencia').value;
    if (recorrencia && chkFim && chkFim.checked && fimValor) {
        if (recorrencia === 'mensal') {
            // Calcular meses entre início e fim (inclusive)
            var partsInicio = data.split('-');
            var partsFim = fimValor.split('-');
            var anoInicio = parseInt(partsInicio[0]), mesInicio2 = parseInt(partsInicio[1]);
            var anoFim = parseInt(partsFim[0]), mesFim = parseInt(partsFim[1]);
            repeticoes = (anoFim - anoInicio) * 12 + (mesFim - mesInicio2) + 1;
            if (repeticoes < 1) repeticoes = 1;
            if (repeticoes > 120) repeticoes = 120;
        } else if (recorrencia === 'semanal') {
            var dtInicio = new Date(data + 'T12:00:00');
            var dtFim = new Date(fimValor + 'T12:00:00');
            var diffMs = dtFim - dtInicio;
            repeticoes = Math.max(1, Math.floor(diffMs / (7 * 24 * 60 * 60 * 1000)) + 1);
            if (repeticoes > 520) repeticoes = 520;
        } else if (recorrencia === 'diario') {
            var dtInicioD = new Date(data + 'T12:00:00');
            var dtFimD = new Date(fimValor + 'T12:00:00');
            repeticoes = Math.max(1, Math.floor((dtFimD - dtInicioD) / (24 * 60 * 60 * 1000)) + 1);
            if (repeticoes > 730) repeticoes = 730;
        }
    } else if (recorrencia) {
        // Padrão se não tiver término
        if (recorrencia === 'mensal') repeticoes = 12;
        else if (recorrencia === 'semanal') repeticoes = 12;
        else if (recorrencia === 'diario') repeticoes = 30;
    }

    // Validar formulário
    var dataValida = recorrencia === 'mensal'
        ? !!(document.getElementById('transacao-mes-inicio').value)
        : !!data;
    if (!descricao || !valor || !dataValida || !categoria_id || !conta_id || (tipo === 'transferencia' && !conta_destino_id)) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }

    // Preparar dados para envio
    var dados = {
        tipo: tipo,
        descricao: descricao,
        valor: parseFloat(valor),
        data_transacao: data,
        categoria_id: parseInt(categoria_id),
        conta_id: parseInt(conta_id),
        recorrencia: recorrencia,
        repeticoes: recorrencia ? repeticoes : 1,
        dias_vencimento: (recorrencia === 'mensal' && dias_vencimento) ? dias_vencimento.split(',').map(Number) : null,
        dias_semana: (recorrencia === 'semanal' && dias_semana) ? dias_semana.split(',').map(Number) : null
    };
    
    if (id) {
        dados.id = parseInt(id);
    }
    
    if (tipo === 'transferencia') {
        dados.conta_destino_id = parseInt(conta_destino_id);
    }
    
    // Enviar requisição AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', obterUrl('funcoes/transacoes.php?api=transacoes&acao=salvar'), true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var resposta = JSON.parse(xhr.responseText);
                if (resposta.sucesso) {
                    // Fechar modal
                    fecharModalTransacao();
                    
                    // Recarregar transações
                    if (typeof carregarTransacoes === 'function') {
                        carregarTransacoes();
                    } else if (typeof app !== 'undefined' && app.atualizarGraficos) {
                        app.atualizarGraficos();
                    } else {
                        // Recarregar página se não houver função de carregar transações
                        location.reload();
                    }
                } else {
                    alert('Erro ao salvar transação: ' + (resposta.erro || 'Erro desconhecido'));
                }
            } catch (e) {
                alert('Erro ao processar resposta do servidor');
            }
        } else {
            alert('Erro ao salvar transação');
        }
    };
    xhr.send(JSON.stringify(dados));
}
</script>
