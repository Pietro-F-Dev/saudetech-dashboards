<?php
use App\Core\Auth;
use App\Core\Labels;

$qs = http_build_query(array_filter($filters + [], fn($v) => $v !== ''));

$pageTitle   = 'Vistorias';
$pageSub     = Auth::onlyOwnVistorias() ? 'Suas vistorias sanitárias' : 'Vistorias sanitárias registradas pelos agentes';
$pageIcon    = 'clipboard-check';
$newLink     = Auth::hasProfile('admin', 'supervisora', 'agente_administrativo') ? '/?controller=vistoria&action=create' : null;
$pageActions = '<a class="btn" href="' . BASE_URL . '/?controller=vistoria&action=exportCsv&' . Labels::e($qs) . '">'
             . '<i data-lucide="download"></i><span class="label">Exportar CSV</span></a>';
include __DIR__ . '/../partials/header.php';
?>

<form method="get" class="card filters" action="<?= BASE_URL ?>/">
  <input type="hidden" name="controller" value="vistoria">
  <input type="hidden" name="action" value="index">

  <label>Buscar
    <input type="text" name="q" maxlength="60" placeholder="Estabelecimento, CNPJ, bairro, agente" value="<?= Labels::e($filters['q']) ?>">
  </label>
  <label>De
    <input type="date" name="de" value="<?= Labels::e($filters['de']) ?>">
  </label>
  <label>Até
    <input type="date" name="ate" value="<?= Labels::e($filters['ate']) ?>">
  </label>
  <label>Status
    <select name="status">
      <option value="">Todos</option>
      <?php foreach (Labels::STATUS_VISTORIA as $k => $v): ?>
        <option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Sincronização
    <select name="sync">
      <option value="">Todas</option>
      <?php foreach (Labels::STATUS_SYNC as $k => $v): ?>
        <option value="<?= $k ?>" <?= $filters['sync'] === $k ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Tipo de estabelecimento
    <select name="tipo">
      <option value="">Todos</option>
      <?php foreach ($tipos as $t): ?>
        <option value="<?= Labels::e($t) ?>" <?= $filters['tipo'] === $t ? 'selected' : '' ?>><?= Labels::e(Labels::tipoAtividade($t)) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <?php if ($agentes): ?>
    <label>Agente
      <select name="agente_id">
        <option value="">Todos</option>
        <?php foreach ($agentes as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $filters['agente_id'] === $a['id'] ? 'selected' : '' ?>><?= Labels::e($a['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>
  <div class="actions">
    <button class="btn btn-primary" type="submit"><i data-lucide="filter"></i> Filtrar</button>
    <a class="btn" href="<?= BASE_URL ?>/?controller=vistoria&action=index">Limpar</a>
  </div>
</form>

<div class="card table-wrap" style="padding:0">
  <table class="table">
    <thead>
      <tr>
        <th>Data</th><th>Estabelecimento</th><th>Agente</th><th>Status</th><th>Sincronização</th>
        <th class="num">Não conf.</th><th class="num">Críticos</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8" class="muted">Nenhuma vistoria encontrada com esses filtros.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td style="white-space:nowrap"><?= Labels::date($r['data_agendada']) ?></td>
          <td>
            <div style="font-weight:600"><?= Labels::e($r['razao_social']) ?></div>
            <div class="muted" style="font-size:12px"><?= Labels::e(Labels::tipoAtividade($r['tipo_atividade'])) ?> · <?= Labels::e($r['bairro'] ?? '') ?></div>
          </td>
          <td><?= Labels::e($r['agente_nome']) ?></td>
          <td><span class="<?= Labels::pill($r['status']) ?>"><?= Labels::get(Labels::STATUS_VISTORIA, $r['status']) ?></span></td>
          <td>
            <span class="<?= Labels::pill($r['sync_status']) ?>"><?= Labels::get(Labels::STATUS_SYNC, $r['sync_status']) ?></span>
            <?php if ($r['origem_offline']): ?><span class="muted" title="Preenchida offline"><i data-lucide="wifi-off" style="width:14px;height:14px"></i></span><?php endif; ?>
          </td>
          <td class="num"><?= (int)$r['nao_conformes'] ?></td>
          <td class="num">
            <?php if ((int)$r['criticos'] > 0): ?>
              <span class="pill pill-bad"><i data-lucide="alert-triangle" style="width:12px;height:12px"></i><?= (int)$r['criticos'] ?></span>
            <?php else: ?>0<?php endif; ?>
          </td>
          <td><a class="btn btn-sm" href="<?= BASE_URL ?>/?controller=vistoria&action=view&id=<?= $r['id'] ?>"><i data-lucide="eye"></i> Ver</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="pagination">
  <span class="muted"><?= $total ?> vistoria(s) · página <?= $page ?> de <?= $pages ?></span>
  <?php if ($page > 1): ?>
    <a class="btn btn-sm" href="<?= BASE_URL ?>/?controller=vistoria&action=index&<?= Labels::e($qs) ?>&page=<?= $page - 1 ?>"><i data-lucide="chevron-left"></i> Anterior</a>
  <?php endif; ?>
  <?php if ($page < $pages): ?>
    <a class="btn btn-sm" href="<?= BASE_URL ?>/?controller=vistoria&action=index&<?= Labels::e($qs) ?>&page=<?= $page + 1 ?>">Próxima <i data-lucide="chevron-right"></i></a>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
