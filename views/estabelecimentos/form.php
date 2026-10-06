<?php
use App\Core\Labels;

$pageTitle = $isNew ? 'Novo estabelecimento' : 'Editar estabelecimento';
$pageSub   = 'Dados cadastrais do estabelecimento fiscalizado';
$pageIcon  = 'store';
include __DIR__ . '/../partials/header.php';

$action = $isNew ? 'create' : 'edit&id=' . urlencode($item['id']);
$v = fn($k) => Labels::e((string)($item[$k] ?? ''));
?>

<form method="post" action="<?= BASE_URL ?>/?controller=estabelecimento&action=<?= $action ?>" class="card form">
  <?php if (!empty($error)): ?><div class="alert-error"><?= Labels::e($error) ?></div><?php endif; ?>

  <label>Razão social
    <input type="text" name="razao_social" required maxlength="200" value="<?= $v('razao_social') ?>">
  </label>

  <div class="row">
    <label>CNPJ
      <input type="text" name="cnpj" id="cnpj" required maxlength="18" placeholder="00.000.000/0000-00" value="<?= $v('cnpj') ?>">
    </label>
    <label>Tipo de atividade
      <input type="text" name="tipo_atividade" required maxlength="100" list="tipos" value="<?= $v('tipo_atividade') ?>">
      <datalist id="tipos">
        <?php foreach (Labels::TIPOS_ATIVIDADE as $k => $t): ?><option value="<?= $k ?>"><?= $t ?></option><?php endforeach; ?>
      </datalist>
    </label>
  </div>

  <label>Endereço
    <input type="text" name="endereco" required maxlength="250" value="<?= $v('endereco') ?>">
  </label>

  <div class="row">
    <label>Bairro
      <input type="text" name="bairro" maxlength="100" value="<?= $v('bairro') ?>">
    </label>
    <label>Cidade
      <input type="text" name="cidade" required maxlength="100" value="<?= $v('cidade') ?>">
    </label>
  </div>

  <div class="row">
    <label>Latitude
      <input type="text" name="latitude" inputmode="decimal" placeholder="-23.310000" value="<?= $v('latitude') ?>">
    </label>
    <label>Longitude
      <input type="text" name="longitude" inputmode="decimal" placeholder="-51.160000" value="<?= $v('longitude') ?>">
    </label>
  </div>

  <div class="row">
    <label>Responsável
      <input type="text" name="responsavel_nome" maxlength="150" value="<?= $v('responsavel_nome') ?>">
    </label>
    <label>CPF do responsável
      <input type="text" name="responsavel_cpf" id="cpf" maxlength="14" placeholder="000.000.000-00" value="<?= $v('responsavel_cpf') ?>">
    </label>
  </div>

  <label>ID no SGF (sistema externo)
    <input type="text" name="sgf_id_externo" maxlength="50" value="<?= $v('sgf_id_externo') ?>">
  </label>

  <div style="display:flex;gap:8px;margin-top:6px">
    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Salvar</button>
    <a href="<?= BASE_URL ?>/?controller=estabelecimento&action=index" class="btn"><i data-lucide="arrow-left"></i> Voltar</a>
  </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script>
  if (window.IMask) {
    IMask(document.getElementById('cnpj'), { mask: '00.000.000/0000-00' });
    IMask(document.getElementById('cpf'),  { mask: '000.000.000-00' });
  }
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
