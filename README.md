# Peak — Finanças Pessoais

> Controle suas finanças com clareza e precisão.

Peak é uma aplicação web de finanças pessoais com interface mobile-first, painel analítico, categorização de transações, suporte a planos por assinatura e integração com WhatsApp via IA.

A integração com WhatsApp roda em produção em uma **VPS própria**, com **Evolution API** e **n8n** auto-hospedados para orquestração do fluxo de mensagens.

---

## Screenshots

| Dashboard | Perfil |
|-----------|--------|
| ![Dashboard](https://github.com/user-attachments/assets/ade7b3ae-11f7-443b-ac54-ad0d736a47ca) | ![Perfil](https://github.com/user-attachments/assets/09433182-78b3-452d-8932-7a12df9bf0e6) |

---

## Funcionalidades

- **Dashboard** com saldo, receitas, despesas e gráficos por período
- **Transações** com paginação, filtros por categoria/tipo e edição inline
- **Categorias** personalizadas (receita / despesa) com ícone e cor
- **Transações recorrentes** (diária, semanal, mensal, anual)
- **Importação de extrato** bancário
- **Múltiplos períodos** — mês atual, últimos 3/6 meses, ano, ou intervalo livre
- **Modo escuro** nativo
- **Perfil completo** — nome, e-mail, telefone, foto, senha
- **Planos por assinatura** via Stripe (Gratuito / Premium)
- **Integração WhatsApp** — registre transações por mensagem via n8n + Evolution API + IA (Groq)
- **Painel admin** — usuários, métricas, planos, assinaturas, SMTP, gateway de pagamento

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8+ (sem framework) |
| Banco de dados | MySQL 8 |
| Frontend | HTML, CSS, JavaScript (SPA via fetch) |
| Gráficos | Chart.js |
| Pagamentos | Stripe |
| Automação | n8n + Evolution API (WhatsApp) |
| IA | Groq (Llama) |
| Servidor local | XAMPP |

---

## Estrutura de pastas

```
├── api/                  # Endpoints REST (transações, contas, stripe webhook)
├── assets/
│   ├── css/style.css     # Estilos principais (dark mode incluso)
│   └── js/app.js         # SPA client-side (toda a lógica de UI)
├── config/               # Banco de dados e fuso horário
├── database/             # SQL de setup completo
├── funcoes/              # Lógica de negócio PHP (auth, transações, categorias…)
├── migrations/           # Scripts de migração incremental
├── modais/               # Fragmentos HTML de modais
├── paginas/              # Views PHP (dashboard, transações, categorias, perfil, admin)
├── scripts/              # Scripts utilitários (migração de dados)
├── index.php             # Entry point / roteador
├── login.php             # Tela de login e cadastro
└── Peak.json             # Workflow n8n (integração WhatsApp)
```

---

## Integração WhatsApp (opcional)

Peak suporta registro de transações via WhatsApp usando:

- **Evolution API** — gateway WhatsApp
- **n8n** — orquestração do fluxo (importe `Peak.json` no n8n)
- **Groq** — LLM para interpretar a mensagem e extrair valor, categoria e tipo

**Fluxo:** usuário envia mensagem no WhatsApp → Evolution API dispara webhook → n8n interpreta com Groq → transação registrada automaticamente no Peak.

---

## Planos e Assinaturas

Peak conta com um sistema de planos (Gratuito / Premium) gerenciado via **Stripe**, com suporte a período de teste gratuito. Recursos avançados como a integração WhatsApp ficam disponíveis nos planos pagos.

---

## Status do Projeto

O projeto está em desenvolvimento ativo. Dashboard, transações, categorias e a integração com WhatsApp já estão funcionando a parte do WhatsApp opera normalmente, mas os prompts de IA ainda estão sendo refinados para respostas mais precisas.

A parte de planos, assinaturas e gateway de pagamento (Stripe) ainda está em fase de homologação e testes.

