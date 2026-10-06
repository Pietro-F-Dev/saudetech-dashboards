<?php
use App\Core\Labels;

$pageTitle   = $v['razao_social'];
$pageSub     = 'Vistoria de ' . Labels::date($v['data_agendada']) . ' · ' . Labels::tipoAtividade($v['tipo_atividade']);
$pageIcon    = 'clipboard-check';
$pageActions = '<a class="btn" href="' . BASE_URL . '/?controller=vistoria&action=index"><i data-lucide="arrow-left"></i><span class="label">Voltar</span></a>';
if (!empty($canCancel)) {
    $pageActions .= '<form id="cancelar-vistoria" method="post" action="' . BASE_URL . '/?controller=vistoria&action=cancel" style="display:inline">'
                  . '<input type="hidden" name="_csrf" value="' . \App\Core\Auth::csrfToken() . '">'
                  . '<input type="hidden" name="id" value="' . Labels::e($v['id']) . '">'
                  . '<button type="button" class="btn btn-danger" onclick="confirmCancel()"><i data-lucide="calendar-x"></i><span class="label">Cancelar vistoria</span></button></form>';
}
include __DIR__ . '/../partials/header.php';

$contagem = ['conforme' => 0, 'nao_conforme' => 0, 'nao_aplicavel' => 0];
foreach ($itens as $i) {
    $contagem[$i['status']]++;
}
$gps = function ($lat, $lng) {
    if ($lat === null || $lng === null) return '—';
    $url = 'https://www.openstreetmap.org/?mlat=' . $lat . '&mlon=' . $lng . '#map=18/' . $lat . '/' . $lng;
    return '<a href="' . $url . '" target="_blank" rel="noopener" style="text-decoration:underline">' . Labels::e($lat . ', ' . $lng) . '</a>';
};
?>

<div class="grid grid-4">
  <div class="card">
    <div class="muted" style="font-size:12px">Status</div>
    <div style="margin-top:6px"><span class="<?= Labels::pill($v['status']) ?>"><?= Labels::get(Labels::STATUS_VISTORIA, $v['status']) ?></span></div>
  </div>
  <div class="card">
    <div class="muted" style="font-size:12px">Sincronização</div>
    <div style="margin-top:6px">
      <span class="<?= Labels::pill($v['sync_status']) ?>"><?= Labels::get(Labels::STATUS_SYNC, $v['sync_status']) ?></span>
      <?php if ($v['origem_offline']): ?><span class="pill"><i data-lucide="wifi-off" style="width:12px;height:12px"></i> Offline</span><?php endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="muted" style="font-size:12px">Itens avaliados</div>
    <div style="margin-top:6px;font-weight:700">
      <?= $contagem['conforme'] ?> conformes · <?= $contagem['nao_conforme'] ?> não conformes · <?= $contagem['nao_aplicavel'] ?> N/A
    </div>
  </div>
  <div class="card">
    <div class="muted" style="font-size:12px">Duração</div>
    <div style="margin-top:6px;font-weight:700"><?= $v['duracao_minutos'] !== null ? (int)$v['duracao_minutos'] . ' min' : '—' ?></div>
  </div>
</div>

<div class="grid grid-2 mt">
  <div class="card">
    <div class="card-title"><i data-lucide="store"></i> Estabelecimento</div>
    <table class="table">
      <tr><th>Razão social</th><td><?= Labels::e($v['razao_social']) ?></td></tr>
      <tr><th>CNPJ</th><td><?= Labels::e($v['cnpj']) ?></td></tr>
      <tr><th>Endereço</th><td><?= Labels::e($v['endereco']) ?><?= $v['bairro'] ? ' — ' . Labels::e($v['bairro']) : '' ?>, <?= Labels::e($v['cidade']) ?></td></tr>
      <tr><th>Responsável</th><td><?= Labels::e($v['estab_responsavel'] ?? '—') ?></td></tr>
    </table>
  </div>
  <div class="card">
    <div class="card-title"><i data-lucide="map-pin"></i> Execução</div>
    <table class="table">
      <tr><th>Agente</th><td><?= Labels::e($v['agente_nome']) ?> <?= $v['agente_matricula'] ? '(' . Labels::e($v['agente_matricula']) . ')' : '' ?></td></tr>
      <tr><th>Início</th><td><?= Labels::dateTime($v['data_hora_inicio']) ?> · GPS <?= $gps($v['latitude_inicio'], $v['longitude_inicio']) ?></td></tr>
      <tr><th>Fim</th><td><?= Labels::dateTime($v['data_hora_fim']) ?> · GPS <?= $gps($v['latitude_fim'], $v['longitude_fim']) ?></td></tr>
      <tr><th>Checklist</th><td>Versão <?= (int)$v['checklist_versao'] ?></td></tr>
      <tr><th>Sincronizada em</th><td><?= Labels::dateTime($v['sincronizada_em']) ?></td></tr>
    </table>
  </div>
</div>

<?php if ($interdicoes): ?>
  <div class="card mt" style="border-color:rgba(208,59,59,.5)">
    <div class="card-title" style="color:var(--status-critical)"><i data-lucide="octagon-alert"></i> Interdições</div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Tipo</th><th>Item</th><th>Motivo</th><th>Registrada em</th></tr></thead>
      <tbody>
        <?php foreach ($interdicoes as $i): ?>
          <tr>
            <td><span class="<?= Labels::pill($i['tipo']) ?>"><?= Labels::get(Labels::TIPO_INTERDICAO, $i['tipo']) ?></span></td>
            <td><?= Labels::e($i['item_descricao']) ?></td>
            <td><?= Labels::e($i['motivo']) ?></td>
            <td><?= Labels::dateTime($i['registrada_em']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
<?php endif; ?>

<div class="card mt">
  <div class="card-title"><i data-lucide="list-checks"></i> Checklist</div>
  <?php if (!$itens): ?>
    <p class="muted">Nenhum item registrado ainda (vistoria <?= Labels::e(mb_strtolower(Labels::get(Labels::STATUS_VISTORIA, $v['status']))) ?>).</p>
  <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>#</th><th>Categoria</th><th>Item</th><th>Resultado</th><th>Severidade</th><th>Observação</th><th class="num">Fotos</th></tr></thead>
      <tbody>
        <?php foreach ($itens as $i): ?>
          <tr>
            <td class="muted"><?= (int)$i['ordem'] ?></td>
            <td><?= Labels::e($i['categoria'] ?? '—') ?></td>
            <td><?= Labels::e($i['descricao']) ?></td>
            <td><span class="<?= Labels::pill($i['status']) ?>"><?= Labels::get(Labels::STATUS_ITEM, $i['status']) ?></span></td>
            <td><?= $i['severidade'] ? '<span class="' . Labels::pill($i['severidade']) . '">' . Labels::get(Labels::SEVERIDADE, $i['severidade']) . '</span>' : '—' ?></td>
            <td>
              <?= Labels::e($i['observacao'] ?? '') ?>
              <?php if ($i['justificativa_critica']): ?>
                <div style="color:var(--status-critical);font-size:13px;margin-top:4px"><strong>Justificativa:</strong> <?= Labels::e($i['justificativa_critica']) ?></div>
              <?php endif; ?>
            </td>
            <td class="num"><?= (int)$i['fotos'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<div class="grid grid-2 mt">
  <div class="card">
    <div class="card-title"><i data-lucide="file-signature"></i> Assinatura e autos</div>
    <?php if ($assinatura): ?>
      <p style="margin:0 0 8px">Assinado por <strong><?= Labels::e($assinatura['responsavel_nome']) ?></strong> em <?= Labels::dateTime($assinatura['assinada_em']) ?></p>
      <p class="muted" style="font-size:12px;word-break:break-all;margin:0 0 12px">SHA-256: <?= Labels::e($assinatura['hash_sha256']) ?></p>
    <?php else: ?>
      <p class="muted">Sem assinatura registrada.</p>
    <?php endif; ?>
    <?php foreach ($autos as $a): ?>
      <div style="display:flex;gap:8px;align-items:center;padding:8px 0;border-top:1px solid var(--border)">
        <i data-lucide="file-text"></i>
        <div style="flex:1;min-width:0">
          <div>Auto <?= Labels::get(Labels::TIPO_AUTO, $a['tipo']) ?> · <?= Labels::dateTime($a['gerado_em']) ?></div>
          <div class="muted" style="font-size:12px;word-break:break-all"><?= Labels::e($a['caminho_pdf']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-title"><i data-lucide="bell-ring"></i> Notificações e sincronização</div>
    <?php if (!$notificacoes && !$syncLog): ?><p class="muted">Sem registros.</p><?php endif; ?>
    <?php foreach ($notificacoes as $n): ?>
      <div style="padding:8px 0;border-top:1px solid var(--border)">
        <span class="<?= Labels::pill($n['status']) ?>"><?= Labels::get(Labels::STATUS_NOTIFICACAO, $n['status']) ?></span>
        Para <strong><?= Labels::e($n['destinatario_nome']) ?></strong> · <?= Labels::dateTime($n['created_at']) ?>
        <div class="muted" style="font-size:13px;margin-top:4px"><?= Labels::e($n['mensagem']) ?></div>
      </div>
    <?php endforeach; ?>
    <?php foreach ($syncLog as $s): ?>
      <div style="padding:8px 0;border-top:1px solid var(--border);font-size:13px">
        <i data-lucide="refresh-cw" style="width:14px;height:14px"></i>
        Tentativa <?= (int)$s['tentativa_numero'] ?> · <?= Labels::dateTime($s['executada_em']) ?> ·
        <span class="<?= Labels::pill($s['status']) ?>"><?= Labels::get(Labels::STATUS_SYNC, $s['status']) ?></span>
        <?php if ($s['mensagem_erro']): ?><div class="muted"><?= Labels::e($s['mensagem_erro']) ?></div><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
  function confirmCancel() {
    Swal.fire({ title: 'Cancelar esta vistoria?', text: 'Ela sai da agenda do agente no app.', icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Sim, cancelar', cancelButtonText: 'Voltar'
    }).then(r => { if (r.isConfirmed) document.getElementById('cancelar-vistoria').submit(); });
  }
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
