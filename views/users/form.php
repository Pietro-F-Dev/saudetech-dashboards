<?php
use App\Core\Labels;

$pageTitle = $isNew ? 'Novo usuário' : 'Editar usuário';
$pageSub   = 'Acesso ao SaúdeTech Dashboards';
$pageIcon  = 'user-cog';
include __DIR__ . '/../partials/header.php';

$action = $isNew ? 'create' : 'edit&id=' . urlencode($user['id']);
?>

<form method="post" action="<?= BASE_URL ?>/?controller=user&action=<?= $action ?>" class="card form">
  <?php if (!empty($error)): ?><div class="alert-error"><?= Labels::e($error) ?></div><?php endif; ?>

  <label>Nome completo
    <input type="text" name="nome" required maxlength="150" value="<?= Labels::e($user['nome'] ?? '') ?>">
  </label>

  <div class="row">
    <label>E-mail
      <input type="email" name="email" required maxlength="150" value="<?= Labels::e($user['email'] ?? '') ?>">
    </label>
    <label>Matrícula
      <input type="text" name="matricula" maxlength="30" value="<?= Labels::e($user['matricula'] ?? '') ?>" placeholder="Ex.: AGT-1001">
    </label>
  </div>

  <div class="row">
    <label>Perfil
      <select name="perfil" required>
        <?php foreach (Labels::PERFIS as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($user['perfil'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if ($isNew): ?>
      <label>Senha inicial (mín. 8 caracteres)
        <input type="password" name="senha" required minlength="8" autocomplete="new-password">
      </label>
    <?php endif; ?>
  </div>

  <label class="check">
    <input type="checkbox" name="ativo" value="1" <?= !empty($user['ativo']) ? 'checked' : '' ?>> Usuário ativo
  </label>

  <div style="display:flex;gap:8px;margin-top:6px">
    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Salvar</button>
    <a href="<?= BASE_URL ?>/?controller=user&action=index" class="btn"><i data-lucide="arrow-left"></i> Voltar</a>
  </div>
</form>

<?php include __DIR__ . '/../partials/footer.php'; ?>
