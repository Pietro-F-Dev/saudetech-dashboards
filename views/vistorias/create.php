<?php
use App\Core\Labels;

$pageTitle = 'Agendar vistoria';
$pageSub   = 'A vistoria aparece na agenda do app do agente sanitário';
$pageIcon  = 'calendar-plus';
include __DIR__ . '/../partials/header.php';
?>

<form method="post" action="<?= BASE_URL ?>/?controller=vistoria&action=create" class="card form">
  <?php if (!empty($error)): ?><div class="alert-error"><?= Labels::e($error) ?></div><?php endif; ?>

  <label>Estabelecimento
    <select name="estabelecimento_id" required>
      <option value="">Selecione…</option>
      <?php foreach ($estabelecimentos as $e): ?>
        <option value="<?= $e['id'] ?>" <?= $form['estabelecimento_id'] === $e['id'] ? 'selected' : '' ?>>
          <?= Labels::e($e['razao_social']) ?> — <?= Labels::e(Labels::tipoAtividade($e['tipo_atividade'])) ?><?= $e['bairro'] ? ' · ' . Labels::e($e['bairro']) : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <div class="row">
    <label>Agente sanitário
      <select name="agente_id" required>
        <option value="">Selecione…</option>
        <?php foreach ($agentes as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $form['agente_id'] === $a['id'] ? 'selected' : '' ?>><?= Labels::e($a['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Data da vistoria
      <input type="date" name="data_agendada" required min="<?= date('Y-m-d') ?>" value="<?= Labels::e($form['data_agendada']) ?>">
    </label>
  </div>

  <p class="muted" style="font-size:13px;margin:0 0 14px">
    O checklist é escolhido automaticamente: a versão ativa mais recente para o tipo do estabelecimento.
  </p>

  <div style="display:flex;gap:8px">
    <button type="submit" class="btn btn-primary"><i data-lucide="calendar-check"></i> Agendar</button>
    <a href="<?= BASE_URL ?>/?controller=vistoria&action=index" class="btn"><i data-lucide="arrow-left"></i> Voltar</a>
  </div>
</form>

<?php include __DIR__ . '/../partials/footer.php'; ?>
