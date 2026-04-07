<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <h1>Dashboard</h1>
  <span class="page-sub">Visão geral do sistema</span>
</div>

<!-- KPI Cards -->
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-icon" style="background:var(--blue-glow);color:var(--blue)">◎</div>
    <div class="kpi-body">
      <span class="kpi-label">Clientes Totais</span>
      <span class="kpi-value"><?= number_format($total_clients) ?></span>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon" style="background:var(--green-glow);color:var(--green)">◇</div>
    <div class="kpi-body">
      <span class="kpi-label">Assinaturas Ativas</span>
      <span class="kpi-value"><?= number_format($active_subs) ?></span>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon" style="background:var(--green-glow);color:var(--green)">R$</div>
    <div class="kpi-body">
      <span class="kpi-label">Faturado (mês)</span>
      <span class="kpi-value">R$ <?= number_format($summary['paid_this_month'],2,',','.') ?></span>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon" style="background:var(--red-glow);color:var(--red)">⚠</div>
    <div class="kpi-body">
      <span class="kpi-label">Inadimplente</span>
      <span class="kpi-value">R$ <?= number_format($summary['overdue_total'],2,',','.') ?></span>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon" style="background:var(--yellow-glow);color:var(--yellow)">◑</div>
    <div class="kpi-body">
      <span class="kpi-label">Pendente Total</span>
      <span class="kpi-value">R$ <?= number_format($summary['pending_total'],2,',','.') ?></span>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon" style="background:var(--red-glow);color:var(--red)">▶</div>
    <div class="kpi-body">
      <span class="kpi-label">Vídeos Gerados</span>
      <span class="kpi-value"><?= number_format($total_videos) ?></span>
    </div>
  </div>
</div>

<!-- Revenue Chart + Due Soon -->
<div class="dash-grid">
  <div class="dash-card dash-chart-card">
    <div class="dc-header"><h3>Faturamento Mensal</h3></div>
    <canvas id="revenueChart" height="220"></canvas>
  </div>

  <div class="dash-card">
    <div class="dc-header">
      <h3>Vencendo em 7 dias</h3>
      <a href="<?= base_url('admin/financial/subscriptions') ?>" class="dc-link">Ver todos →</a>
    </div>
    <?php if (empty($due_soon)): ?>
    <p class="empty-state">Nenhuma assinatura vencendo.</p>
    <?php else: ?>
    <div class="list-items">
      <?php foreach (array_slice($due_soon, 0, 8) as $s): ?>
      <div class="li-row">
        <div class="li-main">
          <strong><?= esc($s['user_name']) ?></strong>
          <span><?= esc($s['plan_name']) ?></span>
        </div>
        <div class="li-right">
          <span class="badge badge-<?= $s['status'] === 'trial' ? 'yellow' : 'blue' ?>"><?= $s['status'] ?></span>
          <span class="li-date"><?= date('d/m/Y', strtotime($s['expires_at'])) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Plans Stats + Overdue -->
<div class="dash-grid">
  <div class="dash-card">
    <div class="dc-header"><h3>Performance por Plano</h3></div>
    <table class="admin-table">
      <thead><tr><th>Plano</th><th>Clientes</th><th>Ativas</th><th>R$/mês</th></tr></thead>
      <tbody>
        <?php foreach ($plan_stats as $p): ?>
        <tr>
          <td><strong><?= esc($p['name']) ?></strong></td>
          <td><?= $p['user_count'] ?></td>
          <td><?= $p['active_subs'] ?></td>
          <td>R$ <?= number_format($p['price_monthly'],2,',','.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="dash-card">
    <div class="dc-header">
      <h3>Pagamentos Vencidos</h3>
      <a href="<?= base_url('admin/financial/overdue') ?>" class="dc-link">Ver todos →</a>
    </div>
    <?php if (empty($overdue_payments)): ?>
    <p class="empty-state">✓ Sem inadimplentes!</p>
    <?php else: ?>
    <div class="list-items">
      <?php foreach (array_slice($overdue_payments, 0, 6) as $p): ?>
      <div class="li-row">
        <div class="li-main">
          <strong><?= esc($p['user_name']) ?></strong>
          <span><?= esc($p['email']) ?></span>
        </div>
        <div class="li-right">
          <span class="badge badge-red">R$ <?= number_format($p['amount'],2,',','.') ?></span>
          <span class="li-date">Venc: <?= date('d/m/Y', strtotime($p['due_date'])) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Videos -->
<div class="dash-card">
  <div class="dc-header">
    <h3>Vídeos Recentes</h3>
    <a href="<?= base_url('admin/videos') ?>" class="dc-link">Ver todos →</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>#</th><th>Usuário</th><th>Nicho</th><th>Status</th><th>Data</th></tr></thead>
    <tbody>
      <?php foreach ($recent_videos as $v): ?>
      <tr>
        <td><?= $v['id'] ?></td>
        <td><?= esc($v['user_name']) ?></td>
        <td><?= esc($v['niche']) ?></td>
        <td><span class="badge badge-<?= $v['status'] === 'ready' ? 'green' : ($v['status'] === 'failed' ? 'red' : 'blue') ?>"><?= $v['status'] ?></span></td>
        <td><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
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

new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: {
    labels: months,
    datasets: [{
      label: 'Faturamento (R$)',
      data: totals,
      backgroundColor: 'rgba(255,60,60,0.7)',
      borderColor: '#ff3c3c',
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8a9bb8' } },
      y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8a9bb8', callback: v => 'R$'+v } }
    }
  }
});
</script>
<?= $this->endSection() ?>
