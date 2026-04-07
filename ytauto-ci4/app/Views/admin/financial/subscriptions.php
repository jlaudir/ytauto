<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <h1>Assinaturas</h1>
  <div style="display:flex;gap:10px">
    <a href="<?= base_url('admin/financial') ?>" class="btn btn-ghost">← Financeiro</a>
    <a href="<?= base_url('admin/financial/overdue') ?>" class="btn btn-secondary">⚠ Inadimplentes</a>
  </div>
</div>

<!-- Summary chips -->
<div class="chips-row">
  <div class="chip chip-green">✓ Ativas: <?= count(array_filter($subscriptions, fn($s) => $s['status']==='active')) ?></div>
  <div class="chip chip-yellow">⏳ Trial: <?= count(array_filter($subscriptions, fn($s) => $s['status']==='trial')) ?></div>
  <div class="chip chip-red">✗ Suspensas: <?= count(array_filter($subscriptions, fn($s) => $s['status']==='suspended')) ?></div>
  <div class="chip chip-blue">Vencendo em 7d: <?= count($due_soon) ?></div>
</div>

<div class="table-card mt-20">
  <table class="admin-table">
    <thead>
      <tr><th>Cliente</th><th>Plano</th><th>Status</th><th>Ciclo</th><th>Valor</th><th>Início</th><th>Vencimento</th><th>Ações</th></tr>
    </thead>
    <tbody>
      <?php foreach ($subscriptions as $s): ?>
      <?php
        $expires   = strtotime($s['expires_at']);
        $daysLeft  = (int)(($expires - time()) / 86400);
        $isWarning = $daysLeft >= 0 && $daysLeft <= 7;
        $isExpired = $daysLeft < 0;
      ?>
      <tr class="<?= $isExpired ? 'tr-danger' : ($isWarning ? 'tr-warning' : '') ?>">
        <td>
          <strong><?= esc($s['user_name']) ?></strong><br>
          <small><?= esc($s['email']) ?></small>
        </td>
        <td><?= esc($s['plan_name']) ?></td>
        <td>
          <span class="badge badge-<?= $s['status']==='active'?'green':($s['status']==='trial'?'yellow':($s['status']==='suspended'?'red':'blue')) ?>">
            <?= $s['status'] ?>
          </span>
        </td>
        <td><?= $s['billing_cycle'] === 'annual' ? '📅 Anual' : '🗓 Mensal' ?></td>
        <td>R$ <?= number_format($s['price_paid'],2,',','.') ?></td>
        <td><?= date('d/m/Y', strtotime($s['started_at'])) ?></td>
        <td>
          <?= date('d/m/Y', strtotime($s['expires_at'])) ?>
          <?php if ($isWarning): ?>
          <span class="badge badge-yellow"><?= $daysLeft ?>d</span>
          <?php elseif ($isExpired): ?>
          <span class="badge badge-red">Vencida</span>
          <?php endif; ?>
        </td>
        <td>
          <a href="<?= base_url('admin/users/'.$s['user_id']) ?>" class="ab ab-blue" title="Ver cliente">◎</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
