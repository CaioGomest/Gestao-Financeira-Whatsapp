class FinancasApp {
  constructor() {
    this.graficoPizza = null;
    this.graficoBarras = null;
    this.graficoMensal = null;
    this.graficoDonutReceitas = null;
    this.graficoDonutDespesas = null;
    this.periodo = {
      tipo: "mes_atual",
      inicio: null,
      fim: null,
      mesSelecionado: null,
    };
    this.mesNav = new Date();
    this.init();
  }

  init() {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => {
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
    const seletor = document.getElementById("seletor-periodo");
    const botaoSeletor = document.getElementById("btn-seletor-periodo");
    const painelSeletor = document.getElementById("painel-seletor-periodo");
    const textoSeletor = document.getElementById("texto-seletor-periodo");
    const painelPersonalizado = document.getElementById("painel-personalizado");
    const inicioModal = document.getElementById("data-inicio-modal");
    const fimModal = document.getElementById("data-fim-modal");
    const aplicarPersonalizado = document.getElementById(
      "acao-aplicar-personalizado",
    );
    const cancelarPersonalizado = document.getElementById(
      "acao-cancelar-personalizado",
    );
    const btnPrev = document.getElementById("painel-prev");
    const btnNext = document.getElementById("painel-next");
    const mesNavBotao = document.getElementById("mes-nav-botao");
    const mesNavTexto = document.getElementById("mes-nav-texto");
    const diasContainer = document.getElementById("calendario-dias");
    const aplicarCalendario = document.getElementById(
      "acao-aplicar-calendario",
    );
    const cancelarCalendario = document.getElementById(
      "acao-cancelar-calendario",
    );
    const btnEditar = document.getElementById("btn-editar-intervalo");
    this.selecaoCalendario = { inicio: null, fim: null };

    if (textoSeletor) {
      textoSeletor.textContent = this.obterTextoPeriodo(this.periodo.tipo);
    }

    if (botaoSeletor && painelSeletor) {
      botaoSeletor.addEventListener("click", () => {
        const abrir = painelSeletor.style.display === "none";
        painelSeletor.style.display = abrir ? "block" : "none";
        if (abrir) {
          if (this.periodo.inicio) {
            this.mesNav = new Date(
              this.periodo.inicio.getFullYear(),
              this.periodo.inicio.getMonth(),
              1,
            );
            this.selecaoCalendario = {
              inicio: this.periodo.inicio
                ? new Date(
                    this.periodo.inicio.getFullYear(),
                    this.periodo.inicio.getMonth(),
                    this.periodo.inicio.getDate(),
                  )
                : null,
              fim: this.periodo.fim
                ? new Date(
                    this.periodo.fim.getFullYear(),
                    this.periodo.fim.getMonth(),
                    this.periodo.fim.getDate(),
                  )
                : null,
            };
          }
          this.posicionarPainel(painelSeletor, botaoSeletor);
          this.atualizarMesNav(mesNavTexto);
          this.renderizarCalendario(diasContainer);
          let overlay = document.getElementById("overlay-seletor-periodo");
          if (!overlay) {
            overlay = document.createElement("div");
            overlay.id = "overlay-seletor-periodo";
            overlay.className = "overlay-seletor";
            document.body.appendChild(overlay);
          }
          overlay.style.display = "block";
        } else {
          const o = document.getElementById("overlay-seletor-periodo");
          if (o) o.remove();
        }
      });
      window.addEventListener("resize", () => {
        if (painelSeletor.style.display !== "none") {
          this.posicionarPainel(painelSeletor, botaoSeletor);
        }
      });
      if (btnPrev) {
        btnPrev.addEventListener("click", () => {
          this.mesNav = new Date(
            this.mesNav.getFullYear(),
            this.mesNav.getMonth() - 1,
            1,
          );
          this.atualizarMesNav(mesNavTexto);
          this.renderizarCalendario(diasContainer);
        });
      }
      if (btnNext) {
        btnNext.addEventListener("click", () => {
          this.mesNav = new Date(
            this.mesNav.getFullYear(),
            this.mesNav.getMonth() + 1,
            1,
          );
          this.atualizarMesNav(mesNavTexto);
          this.renderizarCalendario(diasContainer);
        });
      }
      if (mesNavBotao) {
        mesNavBotao.addEventListener("click", () => {
          this.definirMesSelecionado(
            this.mesNav.getFullYear(),
            this.mesNav.getMonth(),
          );
          if (textoSeletor)
            textoSeletor.textContent =
              this.obterTextoPeriodo("mes_selecionado");
          this.renderizarCalendario(diasContainer);
        });
      }
      if (aplicarCalendario) {
        aplicarCalendario.addEventListener("click", () => {
          const i = this.selecaoCalendario.inicio;
          const f = this.selecaoCalendario.fim || this.selecaoCalendario.inicio;
          if (!i) {
            this.definirMesSelecionado(
              this.mesNav.getFullYear(),
              this.mesNav.getMonth(),
            );
            if (textoSeletor)
              textoSeletor.textContent =
                this.obterTextoPeriodo("mes_selecionado");
          } else {
            const fi = `${i.getFullYear()}-${String(i.getMonth() + 1).padStart(2, "0")}-${String(i.getDate()).padStart(2, "0")}`;
            const ffDate = f || i;
            const ff = `${ffDate.getFullYear()}-${String(ffDate.getMonth() + 1).padStart(2, "0")}-${String(ffDate.getDate()).padStart(2, "0")}`;
            this.definirPeriodoPersonalizado(fi, ff);
            const mesmoMes =
              i.getFullYear() === ffDate.getFullYear() &&
              i.getMonth() === ffDate.getMonth();
            if (mesmoMes) {
              this.mesNav = new Date(i.getFullYear(), i.getMonth(), 1);
              if (textoSeletor)
                textoSeletor.textContent =
                  this.obterTextoPeriodo("mes_selecionado");
            } else {
              if (textoSeletor) textoSeletor.textContent = "Personalizado";
            }
          }
          painelSeletor.style.display = "none";
          // manter selecaoCalendario para reabrir com destaque
          const o = document.getElementById("overlay-seletor-periodo");
          if (o) o.remove();
        });
      }
      if (cancelarCalendario) {
        cancelarCalendario.addEventListener("click", () => {
          this.selecaoCalendario = { inicio: null, fim: null };
          this.renderizarCalendario(diasContainer);
          painelSeletor.style.display = "none";
          const o = document.getElementById("overlay-seletor-periodo");
          if (o) o.remove();
        });
      }
    }
  }

  obterTextoPeriodo(tipo) {
    if (tipo === "semana_atual") return "Semana atual";
    if (tipo === "mes_passado") return "Mês passado";
    if (tipo === "personalizado") return "Personalizado";
    if (tipo === "mes_selecionado") {
      const intl = new Intl.DateTimeFormat("pt-BR", {
        month: "long",
        year: "numeric",
      });
      const base = this.periodo.mesSelecionado
        ? new Date(
            this.periodo.mesSelecionado.ano,
            this.periodo.mesSelecionado.mes,
            1,
          )
        : new Date();
      return intl.format(base);
    }
    return "Mês atual";
  }

  posicionarPainel(painel, botao) {
    if (!painel || !botao) return;
    const vw = window.innerWidth || document.documentElement.clientWidth;
    const container = document.getElementById("seletor-bonito-periodo");
    const containerRect = container ? container.getBoundingClientRect() : botao.getBoundingClientRect();
    const panelWidth = 320;
    if (vw <= 600) {
      painel.style.position = "fixed";
      painel.style.left = "50%";
      painel.style.right = "auto";
      painel.style.transform = "translateX(-50%)";
      painel.style.width = `min(360px, calc(100vw - 20px))`;
      const ph = painel.offsetHeight || 0;
      const safeBottom = 160;
      const targetTop = containerRect.bottom + 8;
      const maxTop = Math.max(12, window.innerHeight - ph - safeBottom);
      painel.style.top = `${Math.max(12, Math.min(targetTop, maxTop))}px`;
      return;
    }
    painel.style.position = "absolute";
    painel.style.transform = "none";
    painel.style.width = `${panelWidth}px`;
    painel.style.right = "auto";
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
    const intl = new Intl.DateTimeFormat("pt-BR", {
      month: "long",
      year: "numeric",
    });
    el.textContent = intl.format(this.mesNav);
  }

  renderizarCalendario(container) {
    if (!container) return;
    container.innerHTML = "";
    const ano = this.mesNav.getFullYear();
    const mes = this.mesNav.getMonth();
    const primeiro = new Date(ano, mes, 1);
    const inicioSemana = primeiro.getDay();
    const diasMes = new Date(ano, mes + 1, 0).getDate();
    for (let i = 0; i < inicioSemana; i++) {
      const vazio = document.createElement("div");
      vazio.className = "calendario-dia vazio";
      container.appendChild(vazio);
    }
    for (let d = 1; d <= diasMes; d++) {
      const data = new Date(ano, mes, d);
      const el = document.createElement("button");
      el.className = "calendario-dia";
      el.textContent = String(d);
      const hoje = new Date();
      if (
        data.getFullYear() === hoje.getFullYear() &&
        data.getMonth() === hoje.getMonth() &&
        data.getDate() === hoje.getDate()
      ) {
        el.classList.add("hoje");
      }
      el.addEventListener("click", () => this.onSelecionarDia(data, container));
      container.appendChild(el);
    }
    this.atualizarRealceSelecao(container);
  }

  onSelecionarDia(data, container) {
    if (!this.selecaoCalendario.inicio) {
      this.selecaoCalendario.inicio = new Date(
        data.getFullYear(),
        data.getMonth(),
        data.getDate(),
      );
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
      this.selecaoCalendario.inicio = new Date(
        data.getFullYear(),
        data.getMonth(),
        data.getDate(),
      );
      this.selecaoCalendario.fim = null;
    }
    this.atualizarRealceSelecao(container);
  }

  atualizarRealceSelecao(container) {
    if (!container) return;
    const itens = container.querySelectorAll(".calendario-dia");
    itens.forEach((el) =>
      el.classList.remove("selecionado-inicio", "selecionado-fim", "intervalo"),
    );
    const ano = this.mesNav.getFullYear();
    const mes = this.mesNav.getMonth();
    const inicio = this.selecaoCalendario.inicio;
    const fim = this.selecaoCalendario.fim || inicio;
    if (!inicio) return;
    itens.forEach((el) => {
      if (el.classList.contains("vazio")) return;
      const dia = parseInt(el.textContent, 10);
      const data = new Date(ano, mes, dia);
      const mesmoDia = (a, b) =>
        a &&
        b &&
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate();
      if (mesmoDia(data, inicio)) el.classList.add("selecionado-inicio");
      if (mesmoDia(data, fim)) el.classList.add("selecionado-fim");
      if (fim && data > inicio && data < fim) el.classList.add("intervalo");
    });
  }

  definirMesSelecionado(ano, mes) {
    this.periodo.tipo = "mes_selecionado";
    this.periodo.mesSelecionado = { ano, mes };
    const inicio = new Date(ano, mes, 1);
    const fim = new Date(ano, mes + 1, 0);
    this.periodo.inicio = new Date(
      inicio.getFullYear(),
      inicio.getMonth(),
      inicio.getDate(),
    );
    this.periodo.fim = new Date(
      fim.getFullYear(),
      fim.getMonth(),
      fim.getDate(),
    );
    this.atualizarDescricaoPeriodo();
    this.atualizarGraficos();
  }

  async obterDadosFinanceiros() {
    try {
      const res = await fetch("funcoes/transacoes.php?api=transacoes&acao=listar");
      const json = await res.json();
      const todasTransacoes = json.data || json || [];

      const intervalo = this.obterIntervaloSelecionado();
      const transacoesFiltradas = todasTransacoes.filter((t) => {
        const dtRaw = t.data_transacao || t.data;
        if (!dtRaw) return false;
        const d = new Date(dtRaw);
        return d >= intervalo.inicio && d <= intervalo.fim;
      });

      let receitas = 0;
      let despesas = 0;
      const categoriasReceitasTotais = {};
      const categoriasDespesasTotais = {};

      const hoje = new Date();
      const labelsMensal = [];
      const mapaMesIdx = {};
      for (let i = 11; i >= 0; i--) {
        const d = new Date(hoje.getFullYear(), hoje.getMonth() - i, 1);
        mapaMesIdx[`${d.getFullYear()}-${d.getMonth()}`] = 11 - i;
        labelsMensal.push(d.toLocaleDateString("pt-BR", { month: "short", year: "2-digit" }));
      }
      const receitasMensal = new Array(12).fill(0);
      const despesasMensal = new Array(12).fill(0);

      transacoesFiltradas.forEach((transacao) => {
        const valor = parseFloat(transacao.valor) || 0;
        const ehTransferencia =
          transacao.eh_transferencia === 1 ||
          (typeof transacao.observacoes === "string" && transacao.observacoes.startsWith("TRANSFERENCIA:"));
        const categoriaNome = transacao.categoria_nome || "Sem Categoria";
        const categoriaCor = transacao.categoria_cor || "#888";

        if (!ehTransferencia && transacao.tipo === "receita") {
          receitas += valor;
          if (!categoriasReceitasTotais[categoriaNome]) {
            categoriasReceitasTotais[categoriaNome] = { nome: categoriaNome, valor: 0, cor: categoriaCor };
          }
          categoriasReceitasTotais[categoriaNome].valor += valor;
        } else if (!ehTransferencia && transacao.tipo === "despesa") {
          despesas += valor;
          if (!categoriasDespesasTotais[categoriaNome]) {
            categoriasDespesasTotais[categoriaNome] = { nome: categoriaNome, valor: 0, cor: categoriaCor };
          }
          categoriasDespesasTotais[categoriaNome].valor += valor;
        }

        // Agregar por mês nos últimos 12 meses
        const dtRaw = transacao.data_transacao || transacao.data;
        if (dtRaw && !ehTransferencia) {
          const d = new Date(dtRaw);
          const idx = mapaMesIdx[`${d.getFullYear()}-${d.getMonth()}`];
          if (idx !== undefined) {
            if (transacao.tipo === "receita") receitasMensal[idx] += valor;
            else if (transacao.tipo === "despesa") despesasMensal[idx] += valor;
          }
        }
      });

      const categoriasReceitasArray = Object.values(categoriasReceitasTotais)
        .filter((c) => c.valor > 0).sort((a, b) => b.valor - a.valor);
      const categoriasDespesasArray = Object.values(categoriasDespesasTotais)
        .filter((c) => c.valor > 0).sort((a, b) => b.valor - a.valor);

      return {
        receitas,
        despesas,
        categoriasReceitas: categoriasReceitasArray,
        categoriasDespesas: categoriasDespesasArray,
        categorias: categoriasDespesasArray,
        mensal: { labels: labelsMensal, receitas: receitasMensal, despesas: despesasMensal },
        transacoes: transacoesFiltradas,
      };
    } catch (error) {
      return { receitas: 0, despesas: 0, categorias: [], transacoes: [] };
    }
  }

  async inicializarGraficos() {
    return this.atualizarGraficos();
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
    const [iy, im, id] = inicioStr.split("-").map(Number);
    const [fy, fm, fd] = fimStr.split("-").map(Number);

    this.periodo.tipo = "personalizado";

    this.periodo.inicio = new Date(iy, im - 1, id, 0, 0, 0, 0);
    this.periodo.fim = new Date(fy, fm - 1, fd, 23, 59, 59, 999);

    this.atualizarDescricaoPeriodo();
    this.atualizarGraficos();
  }

  obterIntervaloSelecionado() {
    const hoje = new Date();
    const y = hoje.getFullYear();
    const m = hoje.getMonth();

    const normalizarInicio = (d) =>
      new Date(d.getFullYear(), d.getMonth(), d.getDate(), 0, 0, 0, 0);

    const normalizarFim = (d) =>
      new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23, 59, 59, 999);

    if (this.periodo.tipo === "mes_atual") {
      const inicio = normalizarInicio(new Date(y, m, 1));
      const fim = normalizarFim(new Date(y, m + 1, 0));
      return { inicio, fim };
    }

    if (this.periodo.tipo === "mes_passado") {
      const inicio = normalizarInicio(new Date(y, m - 1, 1));
      const fim = normalizarFim(new Date(y, m, 0));
      return { inicio, fim };
    }

    if (
      this.periodo.tipo === "mes_selecionado" &&
      this.periodo.mesSelecionado
    ) {
      const ano = this.periodo.mesSelecionado.ano;
      const mes = this.periodo.mesSelecionado.mes;
      const inicio = normalizarInicio(new Date(ano, mes, 1));
      const fim = normalizarFim(new Date(ano, mes + 1, 0));
      return { inicio, fim };
    }

    if (this.periodo.tipo === "semana_atual") {
      const diaSemana = hoje.getDay(); // 0 = domingo
      const ajuste = diaSemana === 0 ? 6 : diaSemana - 1;

      const inicioSemana = new Date(y, m, hoje.getDate() - ajuste);
      const fimSemana = new Date(
        inicioSemana.getFullYear(),
        inicioSemana.getMonth(),
        inicioSemana.getDate() + 6,
      );

      return {
        inicio: normalizarInicio(inicioSemana),
        fim: normalizarFim(fimSemana),
      };
    }

    return {
      inicio: normalizarInicio(this.periodo.inicio),
      fim: normalizarFim(this.periodo.fim),
    };
  }

  atualizarDescricaoPeriodo() {
    const el = document.getElementById("descricao-periodo");
    if (!el) return;
    const intlMes = new Intl.DateTimeFormat("pt-BR", {
      month: "long",
      year: "numeric",
    });
    if (this.periodo.tipo === "mes_atual") {
      el.textContent = `${intlMes.format(new Date())}`;
      return;
    }
    if (this.periodo.tipo === "mes_passado") {
      const hoje = new Date();
      const d = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
      el.textContent = `${intlMes.format(d)}`;
      return;
    }
    if (
      this.periodo.tipo === "mes_selecionado" &&
      this.periodo.mesSelecionado
    ) {
      const d = new Date(
        this.periodo.mesSelecionado.ano,
        this.periodo.mesSelecionado.mes,
        1,
      );
      el.textContent = `${intlMes.format(d)}`;
      return;
    }
    if (this.periodo.tipo === "semana_atual") {
      el.textContent = "Visão geral das suas finanças na semana atual";
      return;
    }
    const i = this.periodo.inicio;
    const f = this.periodo.fim;
    if (i && f) {
      const fmt = new Intl.DateTimeFormat("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      });
      el.textContent = `${fmt.format(i)} até ${fmt.format(f)}`;
    } else {
      el.textContent = "";
    }
  }

  async criarGraficoPizza(dados = null) {
    const canvas = document.getElementById("grafico-pizza");
    if (!canvas) {
      return;
    }

    const ctx = canvas.getContext("2d");
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
      type: "doughnut",
      data: {
        labels: ["Receitas", "Despesas"],
        datasets: [
          {
            data: [dados.receitas, dados.despesas],
            backgroundColor: [
              "#6C63FF", // Cor roxa para receitas
              "#FF6B6B", // Cor vermelha para despesas
            ],
            borderWidth: 0,
            cutout: "60%",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              color: "#ffffff",
              font: {
                size: 14,
                weight: "500",
              },
              padding: 20,
              usePointStyle: true,
              pointStyle: "circle",
            },
          },
          tooltip: {
            backgroundColor: "rgba(0, 0, 0, 0.8)",
            titleColor: "#ffffff",
            bodyColor: "#ffffff",
            borderColor: "#6C63FF",
            borderWidth: 1,
            callbacks: {
              label: function (context) {
                const valor = context.parsed;
                const total = dados.receitas + dados.despesas;
                const percentual = ((valor / total) * 100).toFixed(1);
                return `${context.label}: R$ ${valor.toLocaleString("pt-BR")} (${percentual}%)`;
              },
            },
          },
        },
        animation: {
          animateRotate: true,
          duration: 1000,
        },
      },
    });
  }

  async criarGraficoLinhas(dados = null) {
    const canvas = document.getElementById("grafico-linhas");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    if (!dados) dados = await this.obterDadosFinanceiros();

    const labels = dados.mensal.labels;
    const receitasData = dados.mensal.receitas;
    const despesasData = dados.mensal.despesas;

    if (this.graficoLinhas) {
      this.graficoLinhas.data.labels = labels;
      this.graficoLinhas.data.datasets[0].data = receitasData;
      this.graficoLinhas.data.datasets[1].data = despesasData;
      this.graficoLinhas.update("none");
      return;
    }

    const gradientReceitas = ctx.createLinearGradient(0, 0, 0, 400);
    gradientReceitas.addColorStop(0, "rgba(76, 175, 80, 0.35)");
    gradientReceitas.addColorStop(1, "rgba(76, 175, 80, 0)");

    const gradientDespesas = ctx.createLinearGradient(0, 0, 0, 400);
    gradientDespesas.addColorStop(0, "rgba(244, 67, 54, 0.35)");
    gradientDespesas.addColorStop(1, "rgba(244, 67, 54, 0)");

    this.graficoLinhas = new Chart(ctx, {
      type: "line",
      data: {
        labels,
        datasets: [
          {
            label: "Receitas",
            data: receitasData,
            borderColor: "#4CAF50",
            backgroundColor: gradientReceitas,
            borderWidth: 2,
            fill: true,
            tension: 0.45,
            pointRadius: 0,
            pointHoverRadius: 6,
            pointBackgroundColor: "#4CAF50",
          },
          {
            label: "Despesas",
            data: despesasData,
            borderColor: "#F44336",
            backgroundColor: gradientDespesas,
            borderWidth: 2,
            fill: true,
            tension: 0.45,
            pointRadius: 0,
            pointHoverRadius: 6,
            pointBackgroundColor: "#F44336",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: "top",
            labels: {
              usePointStyle: true,
              boxWidth: 8,
              padding: 16,
              font: { size: 13, weight: "500" },
            },
          },
          tooltip: {
            backgroundColor: "rgba(0, 0, 0, 0.75)",
            titleColor: "#fff",
            bodyColor: "#fff",
            padding: 12,
            cornerRadius: 6,
            callbacks: {
              label: (c) =>
                `${c.dataset.label}: R$ ${c.parsed.y.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`,
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: "#aaa", font: { size: 11 } },
          },
          y: {
            beginAtZero: true,
            grid: { color: "rgba(255, 255, 255, 0.05)" },
            ticks: {
              color: "#aaa",
              font: { size: 11 },
              callback: (v) => `R$ ${v.toLocaleString("pt-BR")}`,
            },
          },
        },
        animation: { duration: 1200, easing: "easeOutQuart" },
      },
    });
  }

  async criarGraficoMensal(dados = null) {
    const canvas = document.getElementById("grafico-mensal");
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!dados) dados = await this.obterDadosFinanceiros();
    if (this.graficoMensal) this.graficoMensal.destroy();

    this.graficoMensal = new Chart(ctx, {
      type: "bar",
      data: {
        labels: dados.mensal.labels,
        datasets: [
          {
            label: "Receitas",
            data: dados.mensal.receitas,
            backgroundColor: "rgba(76, 175, 80, 0.8)",
            borderRadius: 6,
          },
          {
            label: "Despesas",
            data: dados.mensal.despesas,
            backgroundColor: "rgba(244, 67, 54, 0.8)",
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "top" },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return `${ctx.dataset.label}: R$ ${ctx.parsed.y.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`;
              },
            },
          },
        },
        scales: {
          x: { grid: { display: false } },
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (v) {
                return "R$ " + v.toLocaleString("pt-BR");
              },
            },
          },
        },
      },
    });
  }

  async criarDonutReceitas(dados = null) {
    const canvas = document.getElementById("grafico-donut-receitas");
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!dados) dados = await this.obterDadosFinanceiros();
    if (this.graficoDonutReceitas) this.graficoDonutReceitas.destroy();

    const labels = dados.categoriasReceitas.map((c) => c.nome);
    const valores = dados.categoriasReceitas.map((c) => c.valor);
    const cores = dados.categoriasReceitas.map((c) => c.cor || "#4CAF50");

    this.graficoDonutReceitas = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels,
        datasets: [
          {
            data: valores,
            backgroundColor: cores,
            borderWidth: 0,
            cutout: "60%",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "bottom" } },
      },
    });
  }

  async criarDonutDespesas(dados = null) {
    const canvas = document.getElementById("grafico-donut-despesas");
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!dados) dados = await this.obterDadosFinanceiros();
      "EXIBINDO DADOS TRAZIDOS PARA PREENCHER O DONUT DE DESPESA ____________________",
    );
    if (this.graficoDonutDespesas) this.graficoDonutDespesas.destroy();

    const labels = dados.categoriasDespesas.map((c) => c.nome);
    const valores = dados.categoriasDespesas.map((c) => c.valor);
    const cores = dados.categoriasDespesas.map((c) => c.cor || "#F44336");

    this.graficoDonutDespesas = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels,
        datasets: [
          {
            data: valores,
            backgroundColor: cores,
            borderWidth: 0,
            cutout: "60%",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "bottom" } },
      },
    });
  }

  async criarGraficoBarras(dados = null) {
    const canvas = document.getElementById("grafico-barras");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    if (!dados) dados = await this.obterDadosFinanceiros();

    const categorias = dados.categoriasDespesas || dados.categorias || [];
    const labels = categorias.map((c) => c.nome);
    const valores = categorias.map((c) => c.valor);
    const cores = categorias.map((c) => c.cor || "#888");

    if (this.graficoBarras) {
      this.graficoBarras.data.labels = labels;
      this.graficoBarras.data.datasets[0].data = valores;
      this.graficoBarras.data.datasets[0].backgroundColor = cores;
      this.graficoBarras.update("none");
      return;
    }

    this.graficoBarras = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [
          {
            label: "Despesas por Categoria",
            data: valores,
            backgroundColor: cores,
            borderRadius: 6,
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (c) =>
                `R$ ${c.parsed.y.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`,
            },
          },
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: "#aaa" } },
          y: {
            beginAtZero: true,
            grid: { color: "rgba(255,255,255,0.05)" },
            ticks: {
              color: "#aaa",
              callback: (v) => `R$ ${v.toLocaleString("pt-BR")}`,
            },
          },
        },
      },
    });
  }

  async atualizarValoresDashboard(dados = null) {
    if (!dados) dados = await this.obterDadosFinanceiros();

    const fmt = (v) =>
      v.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const saldo = dados.receitas - dados.despesas;

    const elSaldo = document.getElementById("valor-saldo");
    const elReceitas = document.getElementById("valor-receitas");
    const elDespesas = document.getElementById("valor-despesas");

    if (elReceitas) elReceitas.textContent = `R$ ${fmt(dados.receitas)}`;
    if (elDespesas) elDespesas.textContent = `R$ ${fmt(dados.despesas)}`;
    if (elSaldo) elSaldo.textContent = `R$ ${fmt(saldo)}`;
  }

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
      categoriasArr.forEach((categoria) => {
        const percentual = total > 0 ? ((categoria.valor / total) * 100).toFixed(1) : 0;
        const el = document.createElement("div");
        el.className = "categoria-item";
        el.innerHTML = `
          <div class="categoria-info">
            <div class="categoria-cor" style="background-color: ${categoria.cor}"></div>
            <div class="categoria-detalhes">
              <span class="categoria-nome">${categoria.nome}</span>
              <span class="categoria-valor">R$ ${categoria.valor.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
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
      container.innerHTML = "";
      container.appendChild(frag);
    };

    renderizar("lista-categorias-receitas", dados.categoriasReceitas);
    renderizar("lista-categorias-despesas", dados.categoriasDespesas);
  }

  async atualizarListaTransacoes(dados = null) {
    if (!dados) dados = await this.obterDadosFinanceiros();

    const container = document.getElementById("lista-transacoes");
    const msgSemDados = document.getElementById("msg-sem-transacoes");
    if (!container) return;

    container.innerHTML = "";

    const transacoes = dados.transacoes || [];

    transacoes.sort((a, b) => {
      const strA = a.data_transacao || a.data || "";
      const strB = b.data_transacao || b.data || "";
      if (strB !== strA) return strB > strA ? 1 : -1;
      return (b.id || 0) - (a.id || 0);
    });

    if (transacoes.length === 0) {
      if (msgSemDados) msgSemDados.classList.remove("hidden");
      return;
    } else {
      if (msgSemDados) msgSemDados.classList.add("hidden");
    }

    const frag = document.createDocumentFragment();
    transacoes.forEach((t) => {
      // Adicionar offset de fuso para exibir a data corretamente no dia local
      const dataOriginal = new Date(t.data_transacao || t.data);
      const data = new Date(dataOriginal.valueOf() + dataOriginal.getTimezoneOffset() * 60000);
      const dataFormatada = data.toLocaleDateString("pt-BR");
      const valor = parseFloat(t.valor);
      const valorFormatado = valor.toLocaleString("pt-BR", { minimumFractionDigits: 2 });

      const tr = document.createElement("tr");
      tr.className = "transacao-linha transition-colors border-b last:border-0";

      const ehTransferencia =
        t.eh_transferencia === 1 ||
        (typeof t.observacoes === "string" && t.observacoes.indexOf("TRANSFERENCIA:") === 0);
      const ehSaida = t.tipo === "despesa";

      let icone, corIcone, bgIcone, corValor, sinal;

      if (ehTransferencia) {
        icone = "fas fa-exchange-alt";
        corIcone = "text-blue-500";
        bgIcone = "bg-blue-500/10";
        corValor = "text-blue-400";
        sinal = ehSaida ? "-" : "+";
      } else if (ehSaida) {
        icone = "fas fa-arrow-down";
        corIcone = "text-red-500";
        bgIcone = "bg-red-500/10";
        corValor = "text-red-400";
        sinal = "-";
      } else {
        icone = "fas fa-arrow-up";
        corIcone = "text-emerald-500";
        bgIcone = "bg-emerald-500/10";
        corValor = "text-emerald-400";
        sinal = "+";
      }

      const contaNome = t.conta_nome || "Conta Padrão";

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


  async atualizarGraficos() {
    const dados = await this.obterDadosFinanceiros();
    await this.atualizarValoresDashboard(dados);
    await this.criarGraficoLinhas(dados);
    await this.criarGraficoPizza(dados);
    await this.criarGraficoMensal(dados);
    await this.criarDonutReceitas(dados);
    await this.criarDonutDespesas(dados);
    await this.atualizarListaCategorias(dados);
    await this.atualizarListaTransacoes(dados);
  }
}

const app = new FinancasApp();

window.app = app;
