<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div><h1>⚠ Inadimplentes</h1><span class="page-sub"><?= count($overdue) ?> pagamento(s) vencido(s)</span></div>
  <a href="<?= base_url('admin/financial') ?>" class="btn btn-ghost">← Voltar</a>
</div>

<?php if (empty($overdue)): ?>
<div class="empty-card"><span class="empty-icon" style="font-size:48px">✅</span><h3>Nenhum inadimplente!</h3><p>Todos os pagamentos estão em dia.</p></div>
<?php else: ?>
<div class="table-card">
  <table class="admin-table">
    <thead>
      <tr><th>Cliente</th><th>Plano</th><th>Valor</th><th>Vencimento</th><th>Dias em atraso</th><th>Método</th><th>Ações</th></tr>
    </thead>
    <tbody>
      <?php foreach ($overdue as $p):
        $daysOverdue = (int)((time() - strtotime($p['due_date'])) / 86400);
      ?>
      <tr>
        <td>
          <strong><?= esc($p['user_name']) ?></strong><br>
          <small><?= esc($p['email']) ?></small>
        </td>
        <td><?= esc($p['plan_name']) ?></td>
        <td><strong style="color:var(--red)">R$ <?= number_format($p['amount'],2,',','.') ?></strong></td>
        <td><?= date('d/m/Y', strtotime($p['due_date'])) ?></td>
        <td><span class="badge badge-red"><?= $daysOverdue ?> dias</span></td>
        <td><?= $p['method'] ?></td>
        <td>
          <div class="action-btns">
            <button class="ab ab-green" onclick="markPaid(<?= $p['id'] ?>)" title="Marcar como pago">✓ Pago</button>
            <button class="ab ab-red" onclick="markFailed(<?= $p['id'] ?>)" title="Marcar como falho">✗ Falho</button>
            <a href="<?= base_url('admin/users/'.$p['user_id']) ?>" class="ab ab-blue" title="Ver cliente">◎</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function markPaid(id){
  if(!confirm('Confirmar pagamento e renovar assinatura?')) return;
  fetch(`/admin/financial/payment/${id}/mark-paid`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{showToast(d.message||'Confirmado!','success');setTimeout(()=>location.reload(),1200);});
}
function markFailed(id){
  if(!confirm('Marcar como falho?')) return;
  fetch(`/admin/financial/payment/${id}/mark-failed`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(()=>{showToast('Marcado como falho','error');setTimeout(()=>location.reload(),1000);});
}
</script>
<?= $this->endSection() ?>
