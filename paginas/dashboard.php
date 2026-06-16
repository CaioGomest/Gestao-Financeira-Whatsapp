<?php
require_once __DIR__ . '/../funcoes/transacoes.php';
require_once __DIR__ . '/../funcoes/categorias.php';
require_once __DIR__ . '/../funcoes/configuracoes.php';
require_once __DIR__ . '/../funcoes/usuario.php';

$usuario_id = usuarioLogado() ? obterUsuarioId() : 0;
$mes_atual = date('Y-m');
$totais_mes = calcularTotaisMes($usuario_id, $mes_atual);
$resumo = [
    'total_receitas' => $totais_mes['receitas'],
    'total_despesas' => $totais_mes['despesas'],
    'saldo_atual' => $totais_mes['saldo']
];
$configuracoes = lerConfiguracoes();
$simbolo_moeda = $configuracoes['preferencias']['simbolo_moeda'];
$dados_usuario = obterDadosUsuario();
$nome_usuario = $dados_usuario['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Usuário');
$foto_usuario = $dados_usuario['foto_perfil'] ?? '';
$url_avatar = $foto_usuario ? $foto_usuario : 'https://ui-avatars.com/api/?name=' . urlencode($nome_usuario) . '&background=fbbf24&color=000';
?>

<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wealth OS - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }

        .glass-panel {
            background: rgba(24, 24, 27, 0.85);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-menu {
            background: rgba(9, 9, 11, 0.92);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.5);
        }

        .gold-gradient-text {
            background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gold-gradient-bg {
            background: linear-gradient(135deg, #fbbf24 0%, #b45309 100%);
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-zinc-950 text-zinc-400 antialiased min-h-screen relative overflow-x-hidden">

    <div class="fixed top-0 left-0 w-full h-96 pointer-events-none z-0" style="background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(251,191,36,0.06), transparent);"></div>

    <main class="min-h-screen w-full z-10 pb-0 relative">

        <div id="view-dashboard" class="space-y-6 block animate-fade-in">

            <!-- Header global presente em index.php -->

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-medium tracking-tight text-white">Dashboard</h1>
                    <p class="text-sm text-zinc-500 mt-1" id="descricao-periodo">Visão geral das suas finanças</p>
                </div>

                <div class="relative" id="seletor-bonito-periodo" style="overflow: visible;">
                    <button id="btn-seletor-periodo"
                        class="px-4 py-2 text-xs font-medium rounded-full flex items-center gap-2 transition-colors">
                        <span id="texto-seletor-periodo">Mês Atual</span>
                        <iconify-icon icon="solar:alt-arrow-down-linear"></iconify-icon>
                    </button>
                    <div class="seletor-painel" id="painel-seletor-periodo" style="display: none;">
                        <div class="painel-topo">
                            <button class="painel-nav" id="painel-prev"><i class="fas fa-chevron-left"></i></button>
                            <button class="mes-nav-botao" id="mes-nav-botao">
                                <span id="mes-nav-texto"></span>
                            </button>
                            <button class="painel-nav" id="painel-next"><i class="fas fa-chevron-right"></i></button>
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="glass-panel p-6 rounded-2xl relative overflow-hidden group">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all duration-500">
                    </div>
                    <div class="relative z-10">
                        <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 mb-1">Saldo Total</p>
                        <h2 class="text-3xl font-medium tracking-tight gold-gradient-text" id="valor-saldo">
                            <?php echo $simbolo_moeda . ' ' . number_format($resumo['saldo_atual'], 2, ',', '.'); ?>
                        </h2>
                        <h2 class="text-3xl font-medium tracking-tight text-white" id="valor-oculto"
                            style="display: none;">••••••</h2>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl border-l-2 border-l-emerald-500/50">
                    <p class="text-xs text-zinc-500 mb-1">Receitas</p>
                    <p class="text-xl font-medium text-white tracking-tight" id="valor-receitas">
                        <?php echo $simbolo_moeda . ' ' . number_format($resumo['total_receitas'], 2, ',', '.'); ?>
                    </p>
                    <p class="text-xl font-medium text-white tracking-tight" id="receitas-oculto"
                        style="display: none;">••••••</p>
                </div>

                <div class="glass-panel p-6 rounded-2xl border-l-2 border-l-red-500/50">
                    <p class="text-xs text-zinc-500 mb-1">Despesas</p>
                    <p class="text-xl font-medium text-white tracking-tight" id="valor-despesas">
                        <?php echo $simbolo_moeda . ' ' . number_format($resumo['total_despesas'], 2, ',', '.'); ?>
                    </p>
                    <p class="text-xl font-medium text-white tracking-tight" id="despesas-oculto"
                        style="display: none;">••••••</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="glass-panel p-6 rounded-2xl lg:col-span-2">
                    <h3 class="text-sm font-medium text-white mb-6">Entradas e Saídas</h3>
                    <div class="h-64"><canvas id="grafico-linhas"></canvas></div>
                </div>
                <div class="glass-panel p-6 rounded-2xl">
                    <h3 class="text-sm font-medium text-white mb-6 flex items-center gap-2">
                        <iconify-icon icon="solar:pie-chart-2-linear" class="text-emerald-500"></iconify-icon>
                        Categorias de Receitas
                    </h3>
                    <div class="h-64 relative w-full flex justify-center items-center">
                        <canvas id="grafico-donut-receitas"></canvas>
                    </div>
                    <div id="lista-categorias-receitas" class="mt-4 space-y-1"></div>
                </div>
                <div class="glass-panel p-6 rounded-2xl">
                    <h3 class="text-sm font-medium text-white mb-6 flex items-center gap-2">
                        <iconify-icon icon="solar:pie-chart-2-linear" class="text-amber-500"></iconify-icon>
                        Categorias de Despesas
                    </h3>
                    <div class="h-64 relative w-full flex justify-center items-center">
                        <canvas id="grafico-donut-despesas"></canvas>
                    </div>
                    <div id="lista-categorias-despesas" class="mt-4 space-y-1"></div>
                </div>
            </div>

            <!-- Lista de Transações -->
            <div class="glass-panel p-6 rounded-2xl animate-fade-in mt-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-medium text-white flex items-center gap-2">
                        <iconify-icon icon="solar:list-check-linear" class="text-blue-500"></iconify-icon>
                        Transações
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-zinc-400">
                        <thead class="text-xs uppercase font-medium text-zinc-500 border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-3">Data</th>
                                <th class="px-4 py-3">Descrição</th>
                                <th class="px-4 py-3">Conta</th>
                                <th class="px-4 py-3 text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody id="lista-transacoes" class="divide-y divide-zinc-800">
                            <!-- Preenchido via JS -->
                        </tbody>
                    </table>
                    <div id="msg-sem-transacoes" class="hidden text-center py-8 text-zinc-500">
                        Nenhuma transação encontrada neste período.
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>

</html>
