<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div><h1>Pagamentos</h1></div>
  <a href="<?= base_url('admin/financial') ?>" class="btn btn-ghost">← Financeiro</a>
</div>

<!-- Summary chips -->
<div class="chips-row">
  <div class="chip chip-green">✓ Pago: R$ <?= number_format($summary['paid_this_month'],2,',','.') ?> (mês)</div>
  <div class="chip chip-yellow">⏳ Pendente: R$ <?= number_format($summary['pending_total'],2,',','.') ?></div>
  <div class="chip chip-red">⚠ Vencido: R$ <?= number_format($summary['overdue_total'],2,',','.') ?></div>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar">
  <select name="status">
    <option value="">Todos os status</option>
    <?php foreach (['paid','pending','failed','refunded'] as $s): ?>
    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="month" name="month" value="<?= $filters['month'] ?? '' ?>"/>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  <a href="<?= base_url('admin/financial/payments') ?>" class="btn btn-ghost">Limpar</a>
</form>

<div class="table-card">
  <table class="admin-table">
    <thead>
      <tr><th>Cliente</th><th>Plano</th><th>Valor</th><th>Método</th><th>Vencimento</th><th>Status</th><th>Pago em</th><th>Ações</th></tr>
    </thead>
    <tbody>
      <?php if (empty($payments)): ?>
      <tr><td colspan="8" class="empty-state">Nenhum pagamento encontrado.</td></tr>
      <?php endif; ?>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td>
          <strong><?= esc($p['user_name']) ?></strong><br>
          <small><?= esc($p['email']) ?></small>
        </td>
        <td><?= esc($p['plan_name']) ?></td>
        <td><strong>R$ <?= number_format($p['amount'],2,',','.') ?></strong></td>
        <td><?= $p['method'] ?></td>
        <td>
          <?= date('d/m/Y', strtotime($p['due_date'])) ?>
          <?php if ($p['status'] === 'pending' && strtotime($p['due_date']) < time()): ?>
          <span class="badge badge-red" style="font-size:9px">vencido</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge badge-<?= $p['status']==='paid'?'green':($p['status']==='pending'?'yellow':($p['status']==='failed'?'red':'blue')) ?>">
            <?= $p['status'] ?>
          </span>
        </td>
        <td><?= $p['paid_at'] ? date('d/m/Y', strtotime($p['paid_at'])) : '—' ?></td>
        <td>
          <?php if ($p['status'] === 'pending'): ?>
          <div class="action-btns">
            <button class="ab ab-green" onclick="markPaid(<?= $p['id'] ?>)">✓ Pago</button>
            <button class="ab ab-red" onclick="markFailed(<?= $p['id'] ?>)">✗ Falhou</button>
          </div>
          <?php else: ?>
          <span style="color:var(--text3);font-size:12px">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function markPaid(id){
  if(!confirm('Confirmar pagamento?')) return;
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
