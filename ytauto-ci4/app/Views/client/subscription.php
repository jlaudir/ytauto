<?= $this->extend('layouts/client') ?>
<?= $this->section('content') ?>
<div class="client-page client-narrow">
  <div class="page-hero compact"><h1>◇ Minha Assinatura</h1></div>

  <?php if ($subscription): ?>
  <div class="sub-detail-card">
    <div class="sdc-header">
      <div>
        <h2><?= esc($plan['name'] ?? '') ?></h2>
        <p><?= esc($plan['description'] ?? '') ?></p>
      </div>
      <span class="badge badge-<?= $subscription['status']==='active'?'green':($subscription['status']==='trial'?'yellow':'red') ?> badge-lg">
        <?= strtoupper($subscription['status']) ?>
      </span>
    </div>
    <div class="sdc-grid">
      <div class="sdc-item"><span>Iniciada em</span><strong><?= date('d/m/Y', strtotime($subscription['started_at'])) ?></strong></div>
      <div class="sdc-item"><span>Válida até</span><strong><?= date('d/m/Y', strtotime($subscription['expires_at'])) ?></strong></div>
      <div class="sdc-item"><span>Valor</span><strong>R$ <?= number_format($subscription['price_paid'],2,',','.') ?>/mês</strong></div>
      <div class="sdc-item"><span>Ciclo</span><strong><?= $subscription['billing_cycle'] === 'annual' ? 'Anual' : 'Mensal' ?></strong></div>
      <div class="sdc-item"><span>Vídeos/mês</span><strong><?= $plan['max_videos_month'] ?: '∞ Ilimitado' ?></strong></div>
      <div class="sdc-item"><span>Vozes disponíveis</span><strong><?= $plan['max_voices'] ?></strong></div>
    </div>
    <?php
    $daysLeft = (int)((strtotime($subscription['expires_at']) - time()) / 86400);
    if ($daysLeft <= 7 && $daysLeft >= 0):
    ?>
    <div class="expiry-warning">
      ⚠ Sua assinatura vence em <strong><?= $daysLeft ?> dia(s)</strong>. Contate o suporte para renovar.
    </div>
    <?php elseif ($daysLeft < 0): ?>
    <div class="expiry-danger">
      ❌ Assinatura vencida há <?= abs($daysLeft) ?> dia(s). Contate o suporte.
    </div>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="empty-card"><span class="empty-icon">◇</span><h3>Sem assinatura ativa</h3><p>Contate o suporte para ativar seu plano.</p></div>
  <?php endif; ?>

  <!-- Payment History -->
  <div class="section-header mt-20"><h2>Histórico de Pagamentos</h2></div>
  <?php if (empty($payments)): ?>
  <div class="empty-card"><p>Nenhum pagamento registrado.</p></div>
  <?php else: ?>
  <div class="table-card">
    <table class="admin-table">
      <thead><tr><th>Vencimento</th><th>Valor</th><th>Método</th><th>Status</th><th>Pago em</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($p['due_date'])) ?></td>
          <td>R$ <?= number_format($p['amount'],2,',','.') ?></td>
          <td><?= esc($p['method']) ?></td>
          <td><span class="badge badge-<?= $p['status']==='paid'?'green':($p['status']==='pending'?'yellow':'red') ?>"><?= $p['status'] ?></span></td>
          <td><?= $p['paid_at'] ? date('d/m/Y', strtotime($p['paid_at'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
