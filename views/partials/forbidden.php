<?php
$pageTitle = 'Acesso negado';
$pageSub   = 'Seu perfil não tem permissão para acessar esta área.';
$pageIcon  = 'shield-alert';
include __DIR__ . '/header.php';
?>
<div class="card">
  <p>Se precisar deste acesso, fale com o administrador do sistema.</p>
  <a class="btn" href="<?= BASE_URL ?>/?controller=dashboard&action=index"><i data-lucide="arrow-left"></i> Voltar ao painel</a>
</div>
<?php include __DIR__ . '/footer.php'; ?>
