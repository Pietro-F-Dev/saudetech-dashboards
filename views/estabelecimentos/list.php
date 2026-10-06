<?php
use App\Core\Auth;
use App\Core\Labels;

$pageTitle = 'Estabelecimentos';
$pageSub   = 'Cadastro dos estabelecimentos fiscalizados (dados correlacionados ao SGF)';
$pageIcon  = 'store';
$newLink   = $canEdit ? '/?controller=estabelecimento&action=create' : null;
include __DIR__ . '/../partials/header.php';
$csrf      = Auth::csrfToken();
$canDelete = Auth::hasProfile('admin', 'agente_administrativo');
?>

<div class="filters">
  <label>Buscar
    <input type="text" id="search" placeholder="Razão social, CNPJ, tipo ou bairro..." oninput="filterTable('search','estTable')">
  </label>
</div>

<div class="card table-wrap" style="padding:0">
  <table class="table" id="estTable">
    <thead>
      <tr>
        <th>Razão social</th><th>CNPJ</th><th>Tipo de atividade</th><th>Bairro</th>
        <th class="num">Vistorias</th><th>Última vistoria</th><th class="num">Interdições</th>
        <?php if ($canEdit): ?><th style="width:1%">Ações</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="8" class="muted">Nenhum estabelecimento cadastrado.</td></tr>
      <?php endif; ?>
      <?php foreach ($items as $e): ?>
        <tr>
          <td style="font-weight:600">
            <a href="<?= BASE_URL ?>/?controller=vistoria&action=index&q=<?= urlencode($e['cnpj']) ?>" title="Ver vistorias"><?= Labels::e($e['razao_social']) ?></a>
          </td>
          <td style="white-space:nowrap"><?= Labels::e($e['cnpj']) ?></td>
          <td><?= Labels::e(Labels::tipoAtividade($e['tipo_atividade'])) ?></td>
          <td><?= Labels::e($e['bairro'] ?? '—') ?></td>
          <td class="num"><?= (int)$e['total_vistorias'] ?></td>
          <td><?= Labels::date($e['ultima_vistoria']) ?></td>
          <td class="num"><?= (int)$e['total_interdicoes'] > 0
                ? '<span class="pill pill-bad">' . (int)$e['total_interdicoes'] . '</span>' : '0' ?></td>
          <?php if ($canEdit): ?>
            <td style="white-space:nowrap">
              <a class="btn btn-sm" href="<?= BASE_URL ?>/?controller=estabelecimento&action=edit&id=<?= $e['id'] ?>"><i data-lucide="pencil"></i> Editar</a>
              <?php if ($canDelete && (int)$e['total_vistorias'] === 0): ?>
                <form id="del-<?= $e['id'] ?>" method="post" action="<?= BASE_URL ?>/?controller=estabelecimento&action=delete" style="display:inline">
                  <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                  <input type="hidden" name="id" value="<?= $e['id'] ?>">
                  <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('del-<?= $e['id'] ?>')"><i data-lucide="trash-2"></i></button>
                </form>
              <?php endif; ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
