<?php
/**
 * Cartão com gráfico + tabela, montado a partir das linhas de uma consulta.
 * (Componente pronto — não precisa alterar.)
 *
 * Variáveis esperadas:
 *   $id        identificador único do gráfico (ex.: 'meu-indicador')
 *   $titulo    título do cartão
 *   $pergunta  pergunta que o gráfico responde
 *   $tipo      'bar', 'barh' (barras deitadas), 'line', 'doughnut' ou 'pie'
 *   $linhas    resultado da consulta (1ª coluna = rótulo, demais = números)
 *   $erro      (opcional) mensagem de erro do SQL
 */
use App\Core\Labels;

$colunas = $linhas ? array_keys($linhas[0]) : [];
$rotulos = array_map(fn($l) => (string)reset($l), $linhas);

// Colunas numéricas (a partir da 2ª) viram séries do gráfico
$series = [];
foreach (array_slice($colunas, 1) as $col) {
    $valores = array_column($linhas, $col);
    if ($valores && count(array_filter($valores, fn($v) => $v !== null && !is_numeric($v))) === 0) {
        $series[] = ['nome' => $col, 'valores' => array_map(fn($v) => $v === null ? null : (float)$v, $valores)];
    }
}
$nomeColuna = fn($c) => ucfirst(str_replace('_', ' ', $c));
?>
<div class="card">
  <div class="card-title"><i data-lucide="bar-chart-3"></i> <?= Labels::e($titulo) ?></div>
  <?php if (!empty($pergunta)): ?>
    <p class="muted" style="margin:-6px 0 12px;font-size:13px"><?= Labels::e($pergunta) ?></p>
  <?php endif; ?>

  <?php if (!empty($erro)): ?>
    <div class="alert-error"><strong>Erro na consulta SQL:</strong><br><?= Labels::e($erro) ?></div>
  <?php elseif (!$linhas): ?>
    <p class="muted">A consulta não devolveu nenhuma linha.</p>
  <?php elseif (!$series): ?>
    <div class="alert-error">Nenhuma coluna numérica encontrada. A 1ª coluna deve ser o rótulo e
      pelo menos uma das outras deve ser um número (ex.: COUNT(*) AS quantidade).</div>
  <?php else: ?>
    <div class="chart-box" style="height:<?= $tipo === 'barh' ? max(220, 34 * count($linhas) + 60) : 300 ?>px">
      <canvas id="<?= Labels::e($id) ?>" role="img" aria-label="<?= Labels::e($titulo) ?>"></canvas>
    </div>
    <script>
      window.graficosDoDashboard = window.graficosDoDashboard || [];
      window.graficosDoDashboard.push({
        id: <?= json_encode($id) ?>,
        tipo: <?= json_encode($tipo) ?>,
        rotulos: <?= json_encode($rotulos, JSON_UNESCAPED_UNICODE) ?>,
        series: <?= json_encode(array_map(fn($s) => ['nome' => $nomeColuna($s['nome'])] + $s, $series), JSON_UNESCAPED_UNICODE) ?>
      });
    </script>
  <?php endif; ?>

  <?php if ($linhas): ?>
    <details style="margin-top:10px">
      <summary class="muted" style="cursor:pointer;font-size:12px">Ver o resultado da consulta (<?= count($linhas) ?> linha(s))</summary>
      <div class="table-wrap"><table class="table">
        <thead><tr><?php foreach ($colunas as $c): ?><th><?= Labels::e($c) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($linhas as $l): ?>
            <tr><?php foreach ($l as $v): ?><td><?= Labels::e($v === null ? '—' : (string)$v) ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    </details>
  <?php endif; ?>
</div>
