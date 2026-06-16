// Aplicação de Finanças Pessoais
class FinancasApp {
    constructor() {
        this.valoresOcultos = false;
        this.graficoPizza = null;
        this.graficoBarras = null;
        this.graficoMensal = null;
        this.graficoDonutReceitas = null;
        this.graficoDonutDespesas = null;
        this.periodo = { tipo: 'mes_atual', inicio: null, fim: null, mesSelecionado: null };
        this.mesNav = new Date();
        this.init();
    }

    init() {
        // Aguardar o DOM carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
            this.inicializarGraficos();
            this.configurarEventos();
            this.atualizarDescricaoPeriodo();
        });
        } else {
            this.inicializarGraficos();
            this.configurarEventos();
            this.atualizarDescricaoPeriodo();
        }
    }

    configurarEventos() {
        const seletor = document.getElementById('seletor-periodo');
        const botaoSeletor = document.getElementById('btn-seletor-periodo');
        const painelSeletor = document.getElementById('painel-seletor-periodo');
        const textoSeletor = document.getElementById('texto-seletor-periodo');
        const painelPersonalizado = document.getElementById('painel-personalizado');
        const inicioModal = document.getElementById('data-inicio-modal');
        const fimModal = document.getElementById('data-fim-modal');
        const aplicarPersonalizado = document.getElementById('acao-aplicar-personalizado');
        const cancelarPersonalizado = document.getElementById('acao-cancelar-personalizado');
        const btnPrev = document.getElementById('painel-prev');
        const btnNext = document.getElementById('painel-next');
        const mesNavBotao = document.getElementById('mes-nav-botao');
        const mesNavTexto = document.getElementById('mes-nav-texto');
        const diasContainer = document.getElementById('calendario-dias');
        const aplicarCalendario = document.getElementById('acao-aplicar-calendario');
        const cancelarCalendario = document.getElementById('acao-cancelar-calendario');
        const btnEditar = document.getElementById('btn-editar-intervalo');
        this.selecaoCalendario = { inicio: null, fim: null };

        if (textoSeletor) {
            textoSeletor.textContent = this.obterTextoPeriodo(this.periodo.tipo);
        }

        if (botaoSeletor && painelSeletor) {
            botaoSeletor.addEventListener('click', () => {
                const abrir = painelSeletor.style.display === 'none';
                painelSeletor.style.display = abrir ? 'block' : 'none';
                if ( abrir ) {
                    if (this.periodo.inicio) {
                        this.mesNav = new Date(this.periodo.inicio.getFullYear(), this.periodo.inicio.getMonth(), 1);
                        this.selecaoCalendario = {
                            inicio: this.periodo.inicio ? new Date(this.periodo.inicio.getFullYear(), this.periodo.inicio.getMonth(), this.periodo.inicio.getDate()) : null,
                            fim: this.periodo.fim ? new Date(this.periodo.fim.getFullYear(), this.periodo.fim.getMonth(), this.periodo.fim.getDate()) : null
                        };
                    }
                    this.posicionarPainel(painelSeletor, botaoSeletor);
                    this.atualizarMesNav(mesNavTexto);
                    this.renderizarCalendario(diasContainer);
                    let overlay = document.getElementById('overlay-seletor-periodo');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.id = 'overlay-seletor-periodo';
                        overlay.className = 'overlay-seletor';
                        document.body.appendChild(overlay);
                    }
                    overlay.style.display = 'block';
                } else {
                    const o = document.getElementById('overlay-seletor-periodo');
                    if (o) o.remove();
                }
            });
            document.addEventListener('click', (e) => {
                const container = document.getElementById('seletor-bonito-periodo');
                const clicandoNoPainel = painelSeletor && painelSeletor.style.display !== 'none' && painelSeletor.contains(e.target);
                const clicandoNoBotao = botaoSeletor && botaoSeletor.contains(e.target);
                const clicandoNoContainer = container && container.contains(e.target);
                if (painelSeletor && painelSeletor.style.display !== 'none') {
                    if (clicandoNoPainel || clicandoNoBotao || clicandoNoContainer) return;
                    painelSeletor.style.display = 'none';
                    if (painelPersonalizado) painelPersonalizado.style.display = 'none';
                    const o = document.getElementById('overlay-seletor-periodo');
                    if (o) o.remove();
                }
            });
            let _resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(_resizeTimer);
                _resizeTimer = setTimeout(() => {
                    if (painelSeletor.style.display !== 'none') {
                        this.posicionarPainel(painelSeletor, botaoSeletor);
                    }
                }, 150);
            });
            if (btnPrev) {
                btnPrev.addEventListener('click', () => {
                    this.mesNav = new Date(this.mesNav.getFullYear(), this.mesNav.getMonth() - 1, 1);
                    this.atualizarMesNav(mesNavTexto);
                    this.renderizarCalendario(diasContainer);
                });
            }
            if (btnNext) {
                btnNext.addEventListener('click', () => {
                    this.mesNav = new Date(this.mesNav.getFullYear(), this.mesNav.getMonth() + 1, 1);
                    this.atualizarMesNav(mesNavTexto);
                    this.renderizarCalendario(diasContainer);
                });
            }
            if (mesNavBotao) {
                mesNavBotao.addEventListener('click', () => {
                    // Aplicar mês atualmente exibido
                    this.definirMesSelecionado(this.mesNav.getFullYear(), this.mesNav.getMonth());
                    if (textoSeletor) textoSeletor.textContent = this.obterTextoPeriodo('mes_selecionado');
                    this.renderizarCalendario(diasContainer);
                });
            }
            if (aplicarCalendario) {
                aplicarCalendario.addEventListener('click', () => {
                    const i = this.selecaoCalendario.inicio;
                    const f = this.selecaoCalendario.fim || this.selecaoCalendario.inicio;
                    if (!i) {
                        // Sem seleção de dias: aplica o mês exibido
                        this.definirMesSelecionado(this.mesNav.getFullYear(), this.mesNav.getMonth());
                        if (textoSeletor) textoSeletor.textContent = this.obterTextoPeriodo('mes_selecionado');
                    } else {
                        // Com seleção: aplica intervalo personalizado
                        const fi = `${i.getFullYear()}-${String(i.getMonth()+1).padStart(2,'0')}-${String(i.getDate()).padStart(2,'0')}`;
                        const ffDate = f || i;
                        const ff = `${ffDate.getFullYear()}-${String(ffDate.getMonth()+1).padStart(2,'0')}-${String(ffDate.getDate()).padStart(2,'0')}`;
                        this.definirPeriodoPersonalizado(fi, ff);
                        const mesmoMes = i.getFullYear() === ffDate.getFullYear() && i.getMonth() === ffDate.getMonth();
                        if (mesmoMes) {
                            this.mesNav = new Date(i.getFullYear(), i.getMonth(), 1);
                            if (textoSeletor) textoSeletor.textContent = this.obterTextoPeriodo('mes_selecionado');
                        } else {
                            if (textoSeletor) textoSeletor.textContent = 'Personalizado';
                        }
                    }
                    painelSeletor.style.display = 'none';
                    // manter selecaoCalendario para reabrir com destaque
                    const o = document.getElementById('overlay-seletor-periodo');
                    if (o) o.remove();
                });
            }
            if (cancelarCalendario) {
                cancelarCalendario.addEventListener('click', () => {
                    this.selecaoCalendario = { inicio: null, fim: null };
                    this.renderizarCalendario(diasContainer);
                    painelSeletor.style.display = 'none';
                    const o = document.getElementById('overlay-seletor-periodo');
                    if (o) o.remove();
                });
            }
        }
    }

    obterTextoPeriodo(tipo) {
        if (tipo === 'semana_atual') return 'Semana atual';
        if (tipo === 'mes_passado') return 'Mês passado';
        if (tipo === 'personalizado') return 'Personalizado';
        if (tipo === 'mes_selecionado') {
            const intl = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' });
            const base = this.periodo.mesSelecionado
                ? new Date(this.periodo.mesSelecionado.ano, this.periodo.mesSelecionado.mes, 1)
                : new Date();
            return intl.format(base);
        }
        return 'Mês Atual';
    }

    posicionarPainel(painel, botao) {
        if (!painel || !botao) return;
        const vw = window.innerWidth || document.documentElement.clientWidth;
        const container = document.getElementById('seletor-bonito-periodo');
        const containerRect = container ? container.getBoundingClientRect() : botao.getBoundingClientRect();
        const panelWidth = 320;
        if (vw <= 600) {
            painel.style.position = 'fixed';
            painel.style.left = '50%';
            painel.style.right = 'auto';
            painel.style.transform = 'translateX(-50%)';
            painel.style.width = `min(360px, calc(100vw - 20px))`;
            const ph = painel.offsetHeight || 0;
            const margin = 8;
            const safeBottom = 160;
            const targetTop = containerRect.bottom + margin;
            const maxTop = Math.max(12, (window.innerHeight - ph - safeBottom));
            const top = Math.min(targetTop, maxTop);
            painel.style.top = `${Math.max(12, top)}px`;
            return;
        }
        painel.style.position = 'absolute';
        painel.style.transform = 'none';
        painel.style.width = `${panelWidth}px`;
        painel.style.right = 'auto';
        // Calcular overflow para direita e ajustar deslocamento para a esquerda
        const overflowRight = containerRect.left + panelWidth - vw;
        const overflowLeft = Math.min(0, containerRect.left);
        let offset = 0;
        if (overflowRight > 0) offset = -overflowRight - 8;
        if (overflowLeft < 0) offset = -overflowLeft + 8;
        painel.style.left = `${offset}px`;
    }

    atualizarMesNav(el) {
        if (!el) return;
        const intl = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' });
        el.textContent = intl.format(this.mesNav);
    }

    renderizarCalendario(container) {
        if (!container) return;
        const ano = this.mesNav.getFullYear();
        const mes = this.mesNav.getMonth();
        const primeiro = new Date(ano, mes, 1);
        const inicioSemana = primeiro.getDay();
        const diasMes = new Date(ano, mes + 1, 0).getDate();
        const hoje = new Date();
        const hojeAno = hoje.getFullYear(), hojeMes = hoje.getMonth(), hojeDia = hoje.getDate();
        const frag = document.createDocumentFragment();
        for (let i = 0; i < inicioSemana; i++) {
            const vazio = document.createElement('div');
            vazio.className = 'calendario-dia vazio';
            frag.appendChild(vazio);
        }
        for (let d = 1; d <= diasMes; d++) {
            const data = new Date(ano, mes, d);
            const el = document.createElement('button');
            el.className = 'calendario-dia';
            el.textContent = String(d);
            if (data.getFullYear() === hojeAno && data.getMonth() === hojeMes && data.getDate() === hojeDia) {
                el.classList.add('hoje');
            }
            el.addEventListener('click', () => this.onSelecionarDia(data, container));
            frag.appendChild(el);
        }
        container.innerHTML = '';
        container.appendChild(frag);
        this.atualizarRealceSelecao(container);
    }

    onSelecionarDia(data, container) {
        if (!this.selecaoCalendario.inicio) {
            this.selecaoCalendario.inicio = new Date(data.getFullYear(), data.getMonth(), data.getDate());
            this.selecaoCalendario.fim = null;
        } else if (!this.selecaoCalendario.fim) {
            const s = this.selecaoCalendario.inicio;
            const d = new Date(data.getFullYear(), data.getMonth(), data.getDate());
            if (d >= s) {
                this.selecaoCalendario.fim = d;
            } else {
                this.selecaoCalendario.inicio = d;
            }
        } else {
            this.selecaoCalendario.inicio = new Date(data.getFullYear(), data.getMonth(), data.getDate());
            this.selecaoCalendario.fim = null;
        }
        this.atualizarRealceSelecao(container);
    }

    atualizarRealceSelecao(container) {
        if (!container) return;
        const itens = container.querySelectorAll('.calendario-dia');
        itens.forEach(el => el.classList.remove('selecionado-inicio','selecionado-fim','intervalo'));
        const ano = this.mesNav.getFullYear();
        const mes = this.mesNav.getMonth();
        const inicio = this.selecaoCalendario.inicio;
        const fim = this.selecaoCalendario.fim || inicio;
        if (!inicio) return;
        itens.forEach(el => {
            if (el.classList.contains('vazio')) return;
            const dia = parseInt(el.textContent, 10);
            const data = new Date(ano, mes, dia);
            const mesmoDia = (a,b) => a && b && a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate();
            if (mesmoDia(data, inicio)) el.classList.add('selecionado-inicio');
            if (mesmoDia(data, fim)) el.classList.add('selecionado-fim');
            if (fim && data > inicio && data < fim) el.classList.add('intervalo');
        });
    }

    definirMesSelecionado(ano, mes) {
        this.periodo.tipo = 'mes_selecionado';
        this.periodo.mesSelecionado = { ano, mes };
        const inicio = new Date(ano, mes, 1);
        const fim = new Date(ano, mes + 1, 0);
        this.periodo.inicio = new Date(inicio.getFullYear(), inicio.getMonth(), inicio.getDate());
        this.periodo.fim = new Date(fim.getFullYear(), fim.getMonth(), fim.getDate());
        this.atualizarDescricaoPeriodo();
        this.atualizarGraficos();
    }

    // Função para alternar visibilidade dos valores
    toggleOcultarValores() {
        this.valoresOcultos = !this.valoresOcultos;
        
        const elementosValor = document.querySelectorAll('[id*="valor-"]');
        const elementosOculto = document.querySelectorAll('[id*="-oculto"]');
        const iconeOcultar = document.getElementById('icone-ocultar');
        const textoOcultar = document.querySelector('.botao-ocultar span');

        if (this.valoresOcultos) {
            // Ocultar valores
            elementosValor.forEach(el => el.style.display = 'none');
            elementosOculto.forEach(el => el.style.display = 'block');
            iconeOcultar.className = 'fas fa-eye';
            if (textoOcultar) textoOcultar.textContent = 'Mostrar';
        } else {
            // Mostrar valores
            elementosValor.forEach(el => el.style.display = 'block');
            elementosOculto.forEach(el => el.style.display = 'none');
            iconeOcultar.className = 'fas fa-eye-slash';
            if (textoOcultar) textoOcultar.textContent = 'Ocultar';
        }
    }

    // Obter dados financeiros da API
    async obterDadosFinanceiros() {
        try {
            console.log('Iniciando obtenção de dados financeiros...');
            
            // Buscar transações
            console.log('Buscando transações...');
            const responseTransacoes = await fetch('funcoes/transacoes.php?api=transacoes&acao=listar');
            console.log('Response transações:', responseTransacoes.status, responseTransacoes.statusText);
            
            if (!responseTransacoes.ok) {
                throw new Error(`Erro ao buscar transações: ${responseTransacoes.status}`);
            }
            
            const textTransacoes = await responseTransacoes.text();
            console.log('Texto da resposta transações:', textTransacoes.substring(0, 200));
            
            let transacoes;
            try {
                transacoes = JSON.parse(textTransacoes);
            } catch (e) {
                console.error('Erro ao fazer parse do JSON de transações:', e);
                console.error('Texto recebido:', textTransacoes);
                throw new Error('Resposta de transações não é um JSON válido');
            }
            
            // Buscar categorias
            console.log('Buscando categorias...');
            const responseCategorias = await fetch('funcoes/transacoes.php?api=categorias&acao=listar');
            console.log('Response categorias:', responseCategorias.status, responseCategorias.statusText);
            
            if (!responseCategorias.ok) {
                throw new Error(`Erro ao buscar categorias: ${responseCategorias.status}`);
            }
            
            const textCategorias = await responseCategorias.text();
            console.log('Texto da resposta categorias:', textCategorias.substring(0, 200));
            
            let categorias;
            try {
                categorias = JSON.parse(textCategorias);
            } catch (e) {
                console.error('Erro ao fazer parse do JSON de categorias:', e);
                console.error('Texto recebido:', textCategorias);
                throw new Error('Resposta de categorias não é um JSON válido');
            }
            
            console.log('Transações obtidas:', transacoes.length);
            console.log('Categorias obtidas:', categorias.length);
            
            const intervalo = this.obterIntervaloSelecionado();
            const transacoesFiltradas = Array.isArray(transacoes) ? transacoes.filter(t => {
                const dtRaw = t.data_transacao || t.data;
                if (!dtRaw) return false;
                const dt = new Date(dtRaw);
                const d = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
                return (!intervalo.inicio || d >= intervalo.inicio) && (!intervalo.fim || d <= intervalo.fim);
            }) : [];
            let receitas = 0;
            let despesas = 0;
            const categoriasReceitasTotais = {};
            const categoriasDespesasTotais = {};

            // Últimos 12 meses
            const nomesMeses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
            const agora = new Date();
            const mapaMesIdx = {};
            const labelsMensal = [];
            const receitasMensal = new Array(12).fill(0);
            const despesasMensal = new Array(12).fill(0);
            for (let i = 11; i >= 0; i--) {
                const d = new Date(agora.getFullYear(), agora.getMonth() - i, 1);
                const chave = `${d.getFullYear()}-${d.getMonth()}`;
                const idx = 11 - i;
                mapaMesIdx[chave] = idx;
                labelsMensal.push(`${nomesMeses[d.getMonth()]}/${String(d.getFullYear()).slice(-2)}`);
            }
            
            transacoesFiltradas.forEach(transacao => {
                const ehTransferencia = (transacao.eh_transferencia === 1) || (typeof transacao.observacoes === 'string' && transacao.observacoes.indexOf('TRANSFERENCIA:') === 0);
                const valor = parseFloat(transacao.valor);
                if (!ehTransferencia) {
                    if (transacao.tipo === 'receita') {
                        receitas += valor;
                    } else {
                        despesas += valor;
                    }
                }
                
                // Agrupar por categoria separando receitas e despesas
                const categoriaNome = transacao.categoria_nome || 'Sem categoria';
                const categoriaCor = transacao.categoria_cor || '#999999';
                if (!ehTransferencia && transacao.tipo === 'receita') {
                    if (!categoriasReceitasTotais[categoriaNome]) {
                        categoriasReceitasTotais[categoriaNome] = { nome: categoriaNome, valor: 0, cor: categoriaCor };
                    }
                    categoriasReceitasTotais[categoriaNome].valor += valor;
                } else if (!ehTransferencia && transacao.tipo === 'despesa') {
                    if (!categoriasDespesasTotais[categoriaNome]) {
                        categoriasDespesasTotais[categoriaNome] = { nome: categoriaNome, valor: 0, cor: categoriaCor };
                    }
                    categoriasDespesasTotais[categoriaNome].valor += valor;
                }

                // Agregar por mês nos últimos 12 meses (slice evita criar Date desnecessário)
                const dtRaw = transacao.data_transacao || transacao.data;
                if (dtRaw && !ehTransferencia) {
                    // Formato: YYYY-MM-DD, pegar ano e mês diretamente da string
                    const anoStr = dtRaw.slice(0, 4);
                    const mesStr = dtRaw.slice(5, 7);
                    const chave = `${anoStr}-${parseInt(mesStr, 10) - 1}`;
                    const idx = mapaMesIdx[chave];
                    if (idx !== undefined) {
                        if (transacao.tipo === 'receita') receitasMensal[idx] += valor;
                        else if (transacao.tipo === 'despesa') despesasMensal[idx] += valor;
                    }
                }
            });
            
            // Converter categorias para arrays
            const categoriasReceitasArray = Object.values(categoriasReceitasTotais).filter(cat => cat.valor > 0).sort((a,b)=>b.valor-a.valor);
            const categoriasDespesasArray = Object.values(categoriasDespesasTotais).filter(cat => cat.valor > 0).sort((a,b)=>b.valor-a.valor);
            
            const resultado = {
                receitas: receitas,
                despesas: despesas,
                categoriasReceitas: categoriasReceitasArray,
                categoriasDespesas: categoriasDespesasArray,
                categorias: categoriasDespesasArray,
                mensal: { labels: labelsMensal, receitas: receitasMensal, despesas: despesasMensal },
                transacoes: transacoesFiltradas
            };
            
            console.log('Dados financeiros processados:', resultado);
            return resultado;
            
        } catch (error) {
            console.error('Erro ao obter dados financeiros:', error);
            // Retornar dados vazios em caso de erro
            return {
                receitas: 0,
                despesas: 0,
                categorias: [],
                transacoes: []
            };
        }
    }

    // Inicializar gráficos
    async inicializarGraficos() {
        const dados = await this.obterDadosFinanceiros();
        await this.atualizarValoresDashboard(dados);

        const canvasLinhas = document.getElementById('grafico-linhas');
        const canvasPizza = document.getElementById('grafico-pizza');
        const canvasMensal = document.getElementById('grafico-mensal');
        const canvasDonutRec = document.getElementById('grafico-donut-receitas');
        const canvasDonutDes = document.getElementById('grafico-donut-despesas');

        if (canvasLinhas) {
            await this.criarGraficoLinhas(dados);
        }

        if (canvasPizza) {
            await this.criarGraficoPizza(dados);
        }

        if (canvasMensal) {
            await this.criarGraficoMensal(dados);
        }

        if (canvasDonutRec) {
            await this.criarDonutReceitas(dados);
        }
        if (canvasDonutDes) {
            await this.criarDonutDespesas(dados);
        }
        await this.atualizarListaCategorias(dados);
        await this.atualizarListaTransacoes(dados);
    }

    // Atualizar valores do dashboard
    async atualizarValoresDashboard(dados = null) {
        try {
            // Se os dados não foram fornecidos, buscar da API
            if (!dados) {
                dados = await this.obterDadosFinanceiros();
            }
            
            // Atualizar saldo (receitas - despesas)
            const saldo = dados.receitas - dados.despesas;
            const elementoSaldo = document.getElementById('valor-saldo');
            if (elementoSaldo) {
                elementoSaldo.textContent = `R$ ${saldo.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
            }

            // Atualizar receitas
            const elementoReceitas = document.getElementById('valor-receitas');
            if (elementoReceitas) {
                elementoReceitas.textContent = `R$ ${dados.receitas.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
            }

            // Atualizar despesas
            const elementoDespesas = document.getElementById('valor-despesas');
            if (elementoDespesas) {
                elementoDespesas.textContent = `R$ ${dados.despesas.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
            }
        } catch (error) {
            console.error('Erro ao atualizar valores do dashboard:', error);
        }
    }

    definirPeriodo(tipo) {
        this.periodo.tipo = tipo;
        const intervalo = this.obterIntervaloSelecionado();
        this.periodo.inicio = intervalo.inicio;
        this.periodo.fim = intervalo.fim;
        this.atualizarDescricaoPeriodo();
        this.atualizarGraficos();
    }

    definirPeriodoPersonalizado(inicioStr, fimStr) {
        this.periodo.tipo = 'personalizado';
        const i = inicioStr ? new Date(inicioStr) : null;
        const f = fimStr ? new Date(fimStr) : null;
        this.periodo.inicio = i ? new Date(i.getFullYear(), i.getMonth(), i.getDate()) : null;
        this.periodo.fim = f ? new Date(f.getFullYear(), f.getMonth(), f.getDate()) : null;
        this.atualizarDescricaoPeriodo();
        this.atualizarGraficos();
    }

    obterIntervaloSelecionado() {
        const hoje = new Date();
        const y = hoje.getFullYear();
        const m = hoje.getMonth();
        if (this.periodo.tipo === 'mes_atual') {
            const inicio = new Date(y, m, 1);
            const fim = new Date(y, m + 1, 0);
            return { inicio, fim };
        }
        if (this.periodo.tipo === 'mes_passado') {
            const inicio = new Date(y, m - 1, 1);
            const fim = new Date(y, m, 0);
            return { inicio, fim };
        }
        if (this.periodo.tipo === 'mes_selecionado' && this.periodo.mesSelecionado) {
            const ano = this.periodo.mesSelecionado.ano;
            const mes = this.periodo.mesSelecionado.mes;
            const inicio = new Date(ano, mes, 1);
            const fim = new Date(ano, mes + 1, 0);
            return { inicio, fim };
        }
        if (this.periodo.tipo === 'semana_atual') {
            const diaSemana = hoje.getDay();
            const ajuste = diaSemana === 0 ? 6 : diaSemana - 1;
            const inicio = new Date(y, m, hoje.getDate() - ajuste);
            const fim = new Date(y, m, inicio.getDate() + 6);
            return { inicio: new Date(inicio.getFullYear(), inicio.getMonth(), inicio.getDate()), fim: new Date(fim.getFullYear(), fim.getMonth(), fim.getDate()) };
        }
        return { inicio: this.periodo.inicio, fim: this.periodo.fim };
    }

    atualizarDescricaoPeriodo() {
        const el = document.getElementById('descricao-periodo');
        if (!el) return;
        const intlMes = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' });
        if (this.periodo.tipo === 'mes_atual') {
            el.textContent = `Visão geral das suas finanças em ${intlMes.format(new Date())}`;
            return;
        }
        if (this.periodo.tipo === 'mes_passado') {
            const hoje = new Date();
            const d = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
            el.textContent = `Visão geral das suas finanças em ${intlMes.format(d)}`;
            return;
        }
        if (this.periodo.tipo === 'mes_selecionado' && this.periodo.mesSelecionado) {
            const d = new Date(this.periodo.mesSelecionado.ano, this.periodo.mesSelecionado.mes, 1);
            el.textContent = `Visão geral das suas finanças em ${intlMes.format(d)}`;
            return;
        }
        if (this.periodo.tipo === 'semana_atual') {
            el.textContent = 'Visão geral das suas finanças na semana atual';
            return;
        }
        const i = this.periodo.inicio;
        const f = this.periodo.fim;
        if (i && f) {
            const fmt = new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            el.textContent = `Visão geral das suas finanças de ${fmt.format(i)} até ${fmt.format(f)}`;
        } else {
            el.textContent = 'Visão geral das suas finanças';
        }
    }

    // Criar gráfico de pizza (receitas vs despesas)
    async criarGraficoPizza(dados = null) {
        const canvas = document.getElementById('grafico-pizza');
        if (!canvas) {
            console.log('Canvas do gráfico de pizza não encontrado');
            return;
        }

        const ctx = canvas.getContext('2d');
        // Se os dados não foram fornecidos, buscar da API
        if (!dados) {
            dados = await this.obterDadosFinanceiros();
        }

        // Se o gráfico já existe, apenas atualizar os dados
        if (this.graficoPizza) {
            this.graficoPizza.data.datasets[0].data = [dados.receitas, dados.despesas];
            this.graficoPizza.update('none');
            return;
        }

        this.graficoPizza = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Receitas', 'Despesas'],
                datasets: [{
                    data: [dados.receitas, dados.despesas],
                    backgroundColor: [
                        '#6C63FF', // Cor roxa para receitas
                        '#FF6B6B'  // Cor vermelha para despesas
                    ],
                    borderWidth: 0,
                    cutout: '60%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#ffffff',
                            font: {
                                size: 14,
                                weight: '500'
                            },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#6C63FF',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const valor = context.parsed;
                                const total = dados.receitas + dados.despesas;
                                const percentual = ((valor / total) * 100).toFixed(1);
                                return `${context.label}: R$ ${valor.toLocaleString('pt-BR')} (${percentual}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 1000
                }
            }
        });
    }

    // Criar gráfico de linhas (entradas e saídas ao longo do tempo)
    async criarGraficoLinhas(dados = null) {
        const canvas = document.getElementById('grafico-linhas');
        if (!canvas) {
            console.log('Canvas do gráfico de linhas não encontrado');
            return;
        }

        const ctx = canvas.getContext('2d');
        
        // Se os dados não foram fornecidos, buscar da API
        if (!dados) {
            dados = await this.obterDadosFinanceiros();
        }

        // Construir séries reais dos últimos 7 dias a partir das transações
        const nomesDias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        const hoje = new Date();
        // Normalizar hora para meia-noite para evitar diferenças de fuso
        const hojeMidnight = new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate());
        const dias = [];
        for (let i = 6; i >= 0; i--) {
            const d = new Date(hojeMidnight);
            d.setDate(hojeMidnight.getDate() - i);
            dias.push(d);
        }
        const labels = dias.map(d => nomesDias[d.getDay()]);
        const receitasData = new Array(7).fill(0);
        const despesasData = new Array(7).fill(0);

        if (Array.isArray(dados.transacoes)) {
            dados.transacoes.forEach(t => {
                const ehTransferencia = (t.eh_transferencia === 1) || (typeof t.observacoes === 'string' && t.observacoes.indexOf('TRANSFERENCIA:') === 0);
                const valor = parseFloat(t.valor) || 0;
                const dtRaw = t.data_transacao || t.data;
                if (!dtRaw) return;
                const dt = new Date(dtRaw);
                const dtMidnight = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
                const diffMs = hojeMidnight.getTime() - dtMidnight.getTime();
                const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                // Mapear para índice dos últimos 7 dias (0..6)
                if (diffDias >= 0 && diffDias <= 6) {
                    const idx = 6 - diffDias;
                    if (!ehTransferencia && t.tipo === 'receita') {
                        receitasData[idx] += valor;
                    } else if (!ehTransferencia && t.tipo === 'despesa') {
                        despesasData[idx] += valor;
                    }
                }
            });
        }

        // Se o gráfico já existe, apenas atualizar os dados (evita destroy/create caro)
        if (this.graficoLinhas) {
            this.graficoLinhas.data.labels = labels;
            this.graficoLinhas.data.datasets[0].data = receitasData;
            this.graficoLinhas.data.datasets[1].data = despesasData;
            this.graficoLinhas.update('none');
            return;
        }

        this.graficoLinhas = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Receitas',
                        data: receitasData,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4CAF50',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    },
                    {
                        label: 'Despesas',
                        data: despesasData,
                        borderColor: '#F44336',
                        backgroundColor: 'rgba(244, 67, 54, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#F44336',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 14,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.parsed.y.toLocaleString('pt-BR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    // Gráfico de barras mensal (últimos 12 meses)
    async criarGraficoMensal(dados = null) {
        const canvas = document.getElementById('grafico-mensal');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!dados) dados = await this.obterDadosFinanceiros();

        if (this.graficoMensal) {
            this.graficoMensal.data.labels = dados.mensal.labels;
            this.graficoMensal.data.datasets[0].data = dados.mensal.receitas;
            this.graficoMensal.data.datasets[1].data = dados.mensal.despesas;
            this.graficoMensal.update('none');
            return;
        }

        this.graficoMensal = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dados.mensal.labels,
                datasets: [
                    {
                        label: 'Receitas',
                        data: dados.mensal.receitas,
                        backgroundColor: 'rgba(76, 175, 80, 0.8)',
                        borderRadius: 6,
                    },
                    {
                        label: 'Despesas',
                        data: dados.mensal.despesas,
                        backgroundColor: 'rgba(244, 67, 54, 0.8)',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx){
                                return `${ctx.dataset.label}: R$ ${ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits:2})}`;
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(v){ return 'R$ ' + v.toLocaleString('pt-BR'); }
                        }
                    }
                }
            }
        });
    }

    // Donut de receitas por categoria
    async criarDonutReceitas(dados = null) {
        const canvas = document.getElementById('grafico-donut-receitas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!dados) dados = await this.obterDadosFinanceiros();

        const labels = dados.categoriasReceitas.map(c=>c.nome);
        const valores = dados.categoriasReceitas.map(c=>c.valor);
        const cores = dados.categoriasReceitas.map(c=>c.cor || '#4CAF50');

        if (this.graficoDonutReceitas) {
            this.graficoDonutReceitas.data.labels = labels;
            this.graficoDonutReceitas.data.datasets[0].data = valores;
            this.graficoDonutReceitas.data.datasets[0].backgroundColor = cores;
            this.graficoDonutReceitas.update('none');
            return;
        }

        this.graficoDonutReceitas = new Chart(ctx, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: valores, backgroundColor: cores, borderWidth: 0, cutout: '60%' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Donut de despesas por categoria
    async criarDonutDespesas(dados = null) {
        const canvas = document.getElementById('grafico-donut-despesas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!dados) dados = await this.obterDadosFinanceiros();

        const labels = dados.categoriasDespesas.map(c=>c.nome);
        const valores = dados.categoriasDespesas.map(c=>c.valor);
        const cores = dados.categoriasDespesas.map(c=>c.cor || '#F44336');

        if (this.graficoDonutDespesas) {
            this.graficoDonutDespesas.data.labels = labels;
            this.graficoDonutDespesas.data.datasets[0].data = valores;
            this.graficoDonutDespesas.data.datasets[0].backgroundColor = cores;
            this.graficoDonutDespesas.update('none');
            return;
        }

        this.graficoDonutDespesas = new Chart(ctx, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: valores, backgroundColor: cores, borderWidth: 0, cutout: '60%' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Criar gráfico de barras (despesas por categoria)
    async criarGraficoBarras(dados = null) {
        const canvas = document.getElementById('grafico-barras');
        if (!canvas) {
            console.log('Canvas do gráfico de barras não encontrado');
            return;
        }

        const ctx = canvas.getContext('2d');
        // Se os dados não foram fornecidos, buscar da API
        if (!dados) {
            dados = await this.obterDadosFinanceiros();
        }

        // Se o gráfico já existe, apenas atualizar os dados
        if (this.graficoBarras) {
            this.graficoBarras.data.labels = dados.categorias.map(cat => cat.nome);
            this.graficoBarras.data.datasets[0].data = dados.categorias.map(cat => cat.valor);
            this.graficoBarras.data.datasets[0].backgroundColor = dados.categorias.map(cat => cat.cor);
            this.graficoBarras.update('none');
            return;
        }

        this.graficoBarras = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dados.categorias.map(cat => cat.nome),
                datasets: [{
                    label: 'Despesas',
                    data: dados.categorias.map(cat => cat.valor),
                    backgroundColor: dados.categorias.map(cat => cat.cor),
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#6C63FF',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: R$ ${context.parsed.y.toLocaleString('pt-BR')}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#ffffff',
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            borderColor: 'rgba(255, 255, 255, 0.2)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#ffffff',
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    // Atualizar listas de categorias (receitas e despesas)
    async atualizarListaCategorias(dados = null) {
        if (!dados) dados = await this.obterDadosFinanceiros();

        const renderizar = (containerId, categoriasArr) => {
            const container = document.getElementById(containerId);
            if (!container) return;
            if (!categoriasArr || categoriasArr.length === 0) {
                container.innerHTML = '<p class="sem-dados">Nenhuma categoria encontrada</p>';
                return;
            }
            const total = categoriasArr.reduce((sum, c) => sum + c.valor, 0);
            const frag = document.createDocumentFragment();
            categoriasArr.forEach(categoria => {
                const percentual = total > 0 ? ((categoria.valor / total) * 100).toFixed(1) : 0;
                const el = document.createElement('div');
                el.className = 'categoria-item';
                el.innerHTML = `
                    <div class="categoria-info">
                        <div class="categoria-cor" style="background-color: ${categoria.cor}"></div>
                        <div class="categoria-detalhes">
                            <span class="categoria-nome">${categoria.nome}</span>
                            <span class="categoria-valor">R$ ${categoria.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                        </div>
                    </div>
                    <div class="categoria-percentual">
                        <span>${percentual}%</span>
                        <div class="categoria-barra">
                            <div class="categoria-progresso" style="transform: scaleX(${percentual / 100}); background-color: ${categoria.cor}"></div>
                        </div>
                    </div>`;
                frag.appendChild(el);
            });
            container.innerHTML = '';
            container.appendChild(frag);
        };

        renderizar('lista-categorias-receitas', dados.categoriasReceitas);
        renderizar('lista-categorias-despesas', dados.categoriasDespesas);
    }

    // Atualizar lista de transações
    async atualizarListaTransacoes(dados = null) {
        if (!dados) dados = await this.obterDadosFinanceiros();
        
        const container = document.getElementById('lista-transacoes');
        const msgSemDados = document.getElementById('msg-sem-transacoes');
        
        if (!container) return;
        
        container.innerHTML = '';
        
        // Usar todas as transações filtradas pelo período
        const transacoes = dados.transacoes || [];

        // Ordenar por data (mais recente primeiro) e depois por ID (decrescente) para desempate
        transacoes.sort((a, b) => {
            const strA = a.data_transacao || a.data || '';
            const strB = b.data_transacao || b.data || '';
            if (strB !== strA) return strB > strA ? 1 : -1;
            return (b.id || 0) - (a.id || 0);
        });

        if (transacoes.length === 0) {
            if (msgSemDados) msgSemDados.classList.remove('hidden');
            return;
        } else {
            if (msgSemDados) msgSemDados.classList.add('hidden');
        }

        const frag = document.createDocumentFragment();
        transacoes.forEach(t => {
            // Ajustar fuso horário para exibição correta da data
            const dataOriginal = new Date(t.data_transacao || t.data);
            // Adicionar offset do fuso horário para garantir que a data seja exibida corretamente no dia local
            const data = new Date(dataOriginal.valueOf() + dataOriginal.getTimezoneOffset() * 60000);

            const dataFormatada = data.toLocaleDateString('pt-BR');
            const valor = parseFloat(t.valor);
            const valorFormatado = valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 });

            const tr = document.createElement('tr');
            tr.className = 'transacao-linha transition-colors border-b last:border-0';

            // Determinar tipo e ícone
            const ehTransferencia = (t.eh_transferencia === 1) || (typeof t.observacoes === 'string' && t.observacoes.indexOf('TRANSFERENCIA:') === 0);
            const ehSaida = t.tipo === 'despesa';

            let icone, corIcone, bgIcone, corValor, sinal;

            if (ehTransferencia) {
                icone = 'fas fa-exchange-alt';
                corIcone = 'text-blue-500';
                bgIcone = 'bg-blue-500/10';
                corValor = 'text-blue-400';
                sinal = ehSaida ? '-' : '+';
            } else if (ehSaida) {
                icone = 'fas fa-arrow-down';
                corIcone = 'text-red-500';
                bgIcone = 'bg-red-500/10';
                corValor = 'text-red-400';
                sinal = '-';
            } else {
                icone = 'fas fa-arrow-up';
                corIcone = 'text-emerald-500';
                bgIcone = 'bg-emerald-500/10';
                corValor = 'text-emerald-400';
                sinal = '+';
            }

            // Conta e Categoria
            const contaNome = t.conta_nome || 'Conta Padrão';

            tr.innerHTML = `
                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs transacao-data">${dataFormatada}</td>
                <td class="px-4 py-3 transacao-descricao">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full ${bgIcone} flex items-center justify-center shrink-0">
                            <i class="${icone} ${corIcone}"></i>
                        </div>
                        <span class="font-medium">${t.descricao}</span>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded text-xs font-medium transacao-conta-badge">
                        ${contaNome}
                    </span>
                </td>
                <td class="px-4 py-3 text-right font-medium ${corValor}">
                    ${sinal} R$ ${valorFormatado}
                </td>
            `;
            frag.appendChild(tr);
        });
        container.appendChild(frag);
    }

    // Atualizar gráficos com novos dados
    async atualizarGraficos() {
        // Fazer apenas uma chamada da API e reutilizar os dados
        const dados = await this.obterDadosFinanceiros();
        await this.atualizarValoresDashboard(dados);
        await this.criarGraficoLinhas(dados);
        await this.criarGraficoPizza(dados);
        await this.criarDonutReceitas(dados);
        await this.criarDonutDespesas(dados);
        await this.atualizarListaCategorias(dados);
        await this.atualizarListaTransacoes(dados);
    }
}

// Instanciar a aplicação
const app = new FinancasApp();

// Expor funções globalmente para uso nos elementos HTML
window.app = app;
