<?php
use App\Core\Labels;

$pageTitle = 'Alterar senha';
$pageSub   = $isSelf ? 'Sua senha de acesso' : 'Redefinir a senha de ' . $userName;
$pageIcon  = 'key-round';
include __DIR__ . '/../partials/header.php';
?>

<form method="post" action="<?= BASE_URL ?>/?controller=user&action=changePassword&id=<?= urlencode($userId) ?>" class="card form" style="max-width:480px">
  <?php if (!empty($error)): ?><div class="alert-error"><?= Labels::e($error) ?></div><?php endif; ?>

  <?php if ($isSelf): ?>
    <label>Senha atual
      <input type="password" name="current_password" required autocomplete="current-password">
    </label>
  <?php endif; ?>

  <label>Nova senha (mín. 8 caracteres)
    <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
  </label>
  <label>Confirmar nova senha
    <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
  </label>

  <div style="display:flex;gap:8px">
    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Salvar</button>
    <a href="<?= BASE_URL ?>/?controller=<?= $isAdmin && !$isSelf ? 'user' : 'dashboard' ?>&action=index" class="btn"><i data-lucide="arrow-left"></i> Voltar</a>
  </div>
</form>

<?php include __DIR__ . '/../partials/footer.php'; ?>
