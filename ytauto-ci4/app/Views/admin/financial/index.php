<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <h1>Controle Financeiro</h1>
  <div style="display:flex;gap:10px">
    <a href="<?= base_url('admin/financial/payments') ?>" class="btn btn-secondary">Pagamentos</a>
    <a href="<?= base_url('admin/financial/subscriptions') ?>" class="btn btn-secondary">Assinaturas</a>
    <a href="<?= base_url('admin/financial/report') ?>" class="btn btn-secondary">Relatório</a>
  </div>
</div>

<!-- Summary KPIs -->
<div class="kpi-grid">
  <div class="kpi-card kpi-lg">
    <div class="kpi-icon" style="background:var(--green-glow);color:var(--green)">R$</div>
    <div class="kpi-body">
      <span class="kpi-label">Faturado este mês</span>
      <span class="kpi-value">R$ <?= number_format($summary['paid_this_month'],2,',','.') ?></span>
    </div>
  </div>
  <div class="kpi-card kpi-lg">
    <div class="kpi-icon" style="background:var(--yellow-glow);color:var(--yellow)">⏳</div>
    <div class="kpi-body">
      <span class="kpi-label">Pendente (total)</span>
      <span class="kpi-value">R$ <?= number_format($summary['pending_total'],2,',','.') ?></span>
    </div>
  </div>
  <div class="kpi-card kpi-lg">
    <div class="kpi-icon" style="background:var(--red-glow);color:var(--red)">⚠</div>
    <div class="kpi-body">
      <span class="kpi-label">Inadimplente (vencido)</span>
      <span class="kpi-value" style="color:var(--red)">R$ <?= number_format($summary['overdue_total'],2,',','.') ?></span>
    </div>
  </div>
</div>

<!-- Revenue Chart -->
<div class="dash-card mt-20">
  <div class="dc-header"><h3>Faturamento — Últimos 12 meses</h3></div>
  <canvas id="revenueChart" height="200"></canvas>
</div>

<!-- Dual columns -->
<div class="dash-grid mt-20">
  <!-- Due soon -->
  <div class="dash-card">
    <div class="dc-header">
      <h3>Vencendo em 7 dias</h3>
      <a href="<?= base_url('admin/financial/subscriptions') ?>" class="dc-link">Ver todos →</a>
    </div>
    <?php if (empty($due_soon)): ?>
    <p class="empty-state">Nenhuma vencendo em breve.</p>
    <?php else: ?>
    <div class="list-items">
      <?php foreach ($due_soon as $s): ?>
      <div class="li-row">
        <div class="li-main">
          <strong><?= esc($s['user_name']) ?></strong>
          <span><?= esc($s['plan_name']) ?></span>
        </div>
        <div class="li-right">
          <span class="badge badge-yellow"><?= date('d/m/Y', strtotime($s['expires_at'])) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Overdue -->
  <div class="dash-card">
    <div class="dc-header">
      <h3>Pagamentos Vencidos</h3>
      <a href="<?= base_url('admin/financial/overdue') ?>" class="dc-link">Ver todos →</a>
    </div>
    <?php if (empty($overdue)): ?>
    <p class="empty-state">✓ Sem inadimplentes!</p>
    <?php else: ?>
    <div class="list-items">
      <?php foreach (array_slice($overdue, 0, 8) as $p): ?>
      <div class="li-row">
        <div class="li-main">
          <strong><?= esc($p['user_name']) ?></strong>
          <span><?= esc($p['email']) ?></span>
        </div>
        <div class="li-right">
          <span class="badge badge-red">R$ <?= number_format($p['amount'],2,',','.') ?></span>
          <button class="ab ab-green" onclick="markPaid(<?= $p['id'] ?>)" title="Marcar pago">✓</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Payments -->
<div class="dash-card mt-20">
  <div class="dc-header">
    <h3>Pagamentos Recentes</h3>
    <a href="<?= base_url('admin/financial/payments') ?>" class="dc-link">Ver todos →</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>Cliente</th><th>Plano</th><th>Valor</th><th>Vencimento</th><th>Pago em</th><th>Status</th><th>Ação</th></tr></thead>
    <tbody>
      <?php foreach (array_slice($recent_payments, 0, 10) as $p): ?>
      <tr>
        <td>
          <strong><?= esc($p['user_name']) ?></strong><br>
          <small><?= esc($p['email']) ?></small>
        </td>
        <td><?= esc($p['plan_name']) ?></td>
        <td>R$ <?= number_format($p['amount'],2,',','.') ?></td>
        <td><?= date('d/m/Y', strtotime($p['due_date'])) ?></td>
        <td><?= $p['paid_at'] ? date('d/m/Y', strtotime($p['paid_at'])) : '—' ?></td>
        <td><span class="badge badge-<?= $p['status']==='paid'?'green':($p['status']==='pending'?'yellow':'red') ?>"><?= $p['status'] ?></span></td>
        <td>
          <?php if ($p['status'] === 'pending'): ?>
          <button class="ab ab-green" onclick="markPaid(<?= $p['id'] ?>)">✓</button>
          <button class="ab ab-red" onclick="markFailed(<?= $p['id'] ?>)">✗</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const months = <?= json_encode(array_column($monthly_revenue, 'month')) ?>;
const totals  = <?= json_encode(array_map(fn($r) => (float)$r['total'], $monthly_revenue)) ?>;
new Chart(document.getElementById('revenueChart'),{
  type:'bar',
  data:{labels:months,datasets:[{label:'R$',data:totals,backgroundColor:'rgba(0,232,122,0.7)',borderColor:'#00e87a',borderWidth:2,borderRadius:6}]},
  options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#8a9bb8'}},y:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#8a9bb8',callback:v=>'R$'+v}}}}
});
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
