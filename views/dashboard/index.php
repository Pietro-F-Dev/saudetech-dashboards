<?php
// =============================================================================
// ALUNO — CONFIGURE AQUI O SEU INDICADOR
// Troque o título, a pergunta e o tipo do gráfico do indicador que você escolheu.
// Tipos: 'bar' (colunas), 'barh' (barras deitadas), 'line' (linha),
//        'doughnut' (rosca) ou 'pie' (pizza)
// =============================================================================
$configMeuIndicador = [
    'titulo'   => 'Indicador I10',
    'pergunta' => 'Quais itens do checklist são mais reprovados?',
    'tipo'     => 'barh',
];
// =============================================================================

$pageTitle = 'Dashboard';
$pageSub   = 'Indicadores das vistorias sanitárias — laboratório de Gestão de Configuração';
$pageIcon  = 'layout-dashboard';
include __DIR__ . '/../partials/header.php';

$n = fn($v) => number_format((float)$v, 0, ',', '.');
?>

<style>
  .kpi .kpi-head { display:flex; align-items:center; gap:8px; color:var(--muted); font-size:13px; font-weight:600; }
  .kpi .kpi-value { font-size:30px; font-weight:700; margin-top:4px; }
  .chart-box { position:relative; height:300px; }
  .secao { font-size:13px; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin:22px 0 10px; font-weight:700; }
</style>

<div class="secao">Exemplos prontos</div>
<div class="grid grid-2">
  <div class="card kpi">
    <div class="kpi-head"><i data-lucide="clipboard-list"></i> Vistorias cadastradas</div>
    <div class="kpi-value"><?= $n($totalVistorias) ?></div>
  </div>
  <div class="card kpi">
    <div class="kpi-head"><i data-lucide="store"></i> Estabelecimentos cadastrados</div>
    <div class="kpi-value"><?= $n($totalEstabelecimentos) ?></div>
  </div>
</div>

<div class="grid grid-2 mt">
  <?php
    $id = 'exemplo-usuarios'; $titulo = 'Usuários por perfil (exemplo)';
    $pergunta = 'Quantos usuários existem em cada perfil de acesso?';
    $tipo = 'doughnut'; $linhas = $usuariosPorPerfil; $erro = null;
    include __DIR__ . '/_grafico.php';
  ?>

  <?php
    // ---- O SEU INDICADOR (dados vêm de DashboardRepository::meuIndicador) ----
    $id = 'meu-indicador';
    $titulo = $configMeuIndicador['titulo'];
    $pergunta = $configMeuIndicador['pergunta'];
    $tipo = $configMeuIndicador['tipo'];
    $linhas = $meuIndicador;
    $erro = $erroMeuIndicador;
    include __DIR__ . '/_grafico.php';
  ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script>
// Desenha todos os gráficos registrados pelos cartões (_grafico.php).
// Redesenha quando o tema claro/escuro muda.
(function () {
  const graficos = [];
  const css = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
  // Paleta categórica (ordem fixa), validada para daltonismo
  const paleta = () => document.documentElement.classList.contains('theme-dark')
    ? ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767']
    : ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];

  function desenhar() {
    graficos.splice(0).forEach(g => g.destroy());
    if (!window.Chart) return;
    const cores = paleta(), grade = css('--grid'), texto = css('--muted'), fundo = css('--surface');
    Chart.defaults.color = texto;
    Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';

    (window.graficosDoDashboard || []).forEach(cfg => {
      const el = document.getElementById(cfg.id);
      if (!el) return;
      const pizza = cfg.tipo === 'doughnut' || cfg.tipo === 'pie';
      const deitado = cfg.tipo === 'barh';
      const datasets = cfg.series.map((s, i) => ({
        label: s.nome,
        data: s.valores,
        backgroundColor: pizza ? cfg.rotulos.map((_, j) => cores[j % cores.length]) : cores[i % cores.length],
        borderColor: pizza ? fundo : cores[i % cores.length],
        borderWidth: pizza ? 2 : (cfg.tipo === 'line' ? 2 : 0),
        borderRadius: pizza || cfg.tipo === 'line' ? 0 : 4,
        maxBarThickness: 40,
        tension: 0.25,
      }));
      const eixos = pizza ? {} : {
        x: { grid: { display: deitado, color: grade }, border: { display: false } },
        y: { grid: { display: !deitado, color: grade }, border: { display: false }, beginAtZero: true, ticks: { precision: 0 } },
      };
      graficos.push(new Chart(el, {
        type: deitado ? 'bar' : cfg.tipo,
        data: { labels: cfg.rotulos, datasets },
        options: {
          maintainAspectRatio: false,
          indexAxis: deitado ? 'y' : 'x',
          plugins: { legend: { display: pizza || datasets.length > 1, position: pizza ? 'right' : 'top' } },
          scales: eixos,
        },
      }));
    });
  }

  document.addEventListener('DOMContentLoaded', desenhar);
  document.addEventListener('themechange', desenhar);
})();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
