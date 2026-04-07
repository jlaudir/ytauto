<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div><h1>Relatório Financeiro <?= $year ?></h1></div>
  <form method="GET" style="display:flex;gap:10px;align-items:center">
    <select name="year" onchange="this.form.submit()" class="form-control-sm">
      <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
      <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <a href="<?= base_url('admin/financial') ?>" class="btn btn-ghost">← Financeiro</a>
  </form>
</div>

<!-- KPIs -->
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-icon" style="color:var(--green)">R$</div>
    <div class="kpi-body">
      <span class="kpi-label">Total Recebido <?= $year ?></span>
      <span class="kpi-value">R$ <?= number_format(array_sum(array_column($by_month,'total')),2,',','.') ?></span>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon" style="color:var(--blue)">◈</div>
    <div class="kpi-body">
      <span class="kpi-label">Transações</span>
      <span class="kpi-value"><?= array_sum(array_column($by_month,'count')) ?></span>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon" style="color:var(--yellow)">R$</div>
    <div class="kpi-body">
      <span class="kpi-label">Média Mensal</span>
      <span class="kpi-value">R$ <?= count($by_month) > 0 ? number_format(array_sum(array_column($by_month,'total'))/count($by_month),2,',','.') : '0,00' ?></span>
    </div>
  </div>
</div>

<!-- Charts -->
<div class="dash-grid mt-20">
  <div class="dash-card">
    <div class="dc-header"><h3>Faturamento Mensal <?= $year ?></h3></div>
    <canvas id="monthChart" height="260"></canvas>
  </div>
  <div class="dash-card">
    <div class="dc-header"><h3>Faturamento por Plano</h3></div>
    <canvas id="planChart" height="260"></canvas>
  </div>
</div>

<!-- Tables -->
<div class="dash-grid mt-20">
  <div class="dash-card">
    <div class="dc-header"><h3>Detalhamento Mensal</h3></div>
    <table class="admin-table">
      <thead><tr><th>Mês</th><th>Transações</th><th>Total</th></tr></thead>
      <tbody>
        <?php $months_pt=['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']; ?>
        <?php foreach ($by_month as $m): ?>
        <tr>
          <td><?= $months_pt[(int)$m['month']] ?? $m['month'] ?></td>
          <td><?= $m['count'] ?></td>
          <td><strong>R$ <?= number_format($m['total'],2,',','.') ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="dash-card">
    <div class="dc-header"><h3>Detalhamento por Plano</h3></div>
    <table class="admin-table">
      <thead><tr><th>Plano</th><th>Pagamentos</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($by_plan as $p): ?>
        <tr>
          <td><?= esc($p['plan_name']) ?></td>
          <td><?= $p['count'] ?></td>
          <td><strong>R$ <?= number_format($p['total'],2,',','.') ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const months_pt = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const mData = <?= json_encode($by_month) ?>;
const pData = <?= json_encode($by_plan) ?>;

new Chart(document.getElementById('monthChart'),{
  type:'line',
  data:{
    labels: mData.map(d=>months_pt[parseInt(d.month)-1]),
    datasets:[{label:'R$',data:mData.map(d=>parseFloat(d.total)),fill:true,
      backgroundColor:'rgba(0,232,122,0.1)',borderColor:'#00e87a',tension:0.4,pointBackgroundColor:'#00e87a'}]
  },
  options:{responsive:true,plugins:{legend:{display:false}},
    scales:{x:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#8a9bb8'}},
            y:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#8a9bb8',callback:v=>'R$'+v}}}}
});

new Chart(document.getElementById('planChart'),{
  type:'doughnut',
  data:{
    labels:pData.map(d=>d.plan_name),
    datasets:[{data:pData.map(d=>parseFloat(d.total)),
      backgroundColor:['#ff3c3c','#00e87a','#3d9cff','#ffde00','#a855f7'],borderWidth:0}]
  },
  options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'#8a9bb8'}}}}
});
</script>
<?= $this->endSection() ?>
