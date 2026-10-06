<?php
use App\Core\Auth;
use App\Core\Labels;

$pageTitle = 'Usuários';
$pageSub   = 'Agentes sanitários, supervisoras, agentes administrativos e administradores';
$pageIcon  = 'users';
$newLink   = '/?controller=user&action=create';
include __DIR__ . '/../partials/header.php';
$csrf = Auth::csrfToken();
?>

<div class="filters">
  <label>Buscar
    <input type="text" id="search" placeholder="Nome, e-mail, matrícula ou perfil..." oninput="filterTable('search','usersTable')">
  </label>
</div>

<div class="card table-wrap" style="padding:0">
  <table class="table" id="usersTable">
    <thead>
      <tr><th>Nome</th><th>E-mail</th><th>Matrícula</th><th>Perfil</th><th>Situação</th><th style="width:1%">Ações</th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td style="font-weight:600"><?= Labels::e($u['nome']) ?></td>
          <td><?= Labels::e($u['email']) ?></td>
          <td><?= Labels::e($u['matricula'] ?? '—') ?></td>
          <td><?= Labels::e(Labels::get(Labels::PERFIS, $u['perfil'])) ?></td>
          <td><span class="pill <?= $u['ativo'] ? 'pill-ok' : '' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td style="white-space:nowrap">
            <a class="btn btn-sm" href="<?= BASE_URL ?>/?controller=user&action=edit&id=<?= $u['id'] ?>"><i data-lucide="pencil"></i> Editar</a>
            <a class="btn btn-sm" href="<?= BASE_URL ?>/?controller=user&action=changePassword&id=<?= $u['id'] ?>"><i data-lucide="key-round"></i> Senha</a>
            <?php if ($u['id'] !== Auth::id()): ?>
              <form id="del-<?= $u['id'] ?>" method="post" action="<?= BASE_URL ?>/?controller=user&action=delete" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('del-<?= $u['id'] ?>')"><i data-lucide="trash-2"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
