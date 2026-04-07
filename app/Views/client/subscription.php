<?= $this->extend('layouts/client') ?>
<?= $this->section('content') ?>
<div class="client-page client-narrow">
  <div class="page-hero compact">
    <h1>◇ Minha Assinatura</h1>
    <?php if ($is_free ?? false): ?>
    <a href="<?= base_url('app/upgrade') ?>" class="btn-create-big" style="font-size:14px;padding:10px 20px">🚀 Fazer Upgrade</a>
    <?php endif; ?>
  </div>

  <?php if ($subscription): ?>
  <div class="sub-detail-card">
    <div class="sdc-header">
      <div>
        <h2><?= esc($plan['name'] ?? '') ?>
          <?php if ($is_free ?? false): ?>
          <span style="font-size:13px;background:rgba(0,232,122,0.1);color:var(--green);border:1px solid rgba(0,232,122,0.3);border-radius:100px;padding:2px 10px;font-weight:600;font-family:var(--font-body);vertical-align:middle">GRATUITO</span>
          <?php endif; ?>
        </h2>
        <p><?= esc($plan['description'] ?? '') ?></p>
      </div>
      <span class="badge badge-<?= $subscription['status']==='active'?'green':($subscription['status']==='trial'?'yellow':'red') ?> badge-lg">
        <?= strtoupper($subscription['status']) ?>
      </span>
    </div>
    <div class="sdc-grid">
      <div class="sdc-item"><span>Iniciada em</span><strong><?= date('d/m/Y', strtotime($subscription['started_at'])) ?></strong></div>
      <div class="sdc-item"><span>Válida até</span>
        <strong><?= ($is_free ?? false) ? 'Sem vencimento' : date('d/m/Y', strtotime($subscription['expires_at'])) ?></strong>
      </div>
      <div class="sdc-item"><span>Valor</span>
        <strong><?= ($is_free ?? false) ? 'Gratuito' : 'R$ '.number_format($subscription['price_paid'],2,',','.').'/'.(($subscription['billing_cycle']==='annual')?'ano':'mês') ?></strong>
      </div>
      <div class="sdc-item"><span>Vídeos/mês</span><strong><?= $plan['max_videos_month'] ?: '∞ Ilimitado' ?></strong></div>
      <div class="sdc-item"><span>Vozes</span><strong><?= $plan['max_voices'] ?></strong></div>
      <div class="sdc-item"><span>Narração com IA</span>
        <?php
        $db = \Config\Database::connect();
        $hasNarrate = $db->from('plan_permissions pp')->join('permissions p','p.id=pp.permission_id')
            ->where('pp.plan_id', $plan['id'])->where('p.key','videos.narrate')->countAllResults() > 0;
        ?>
        <strong style="color:<?= $hasNarrate ? 'var(--green)' : 'var(--text3)' ?>"><?= $hasNarrate ? '✓ Incluída' : '✗ Não incluída' ?></strong>
      </div>
    </div>

    <?php if ($is_free ?? false): ?>
    <div style="margin-top:20px;background:linear-gradient(135deg,rgba(255,60,60,0.08),rgba(255,92,32,0.06));border:1px solid rgba(255,60,60,0.25);border-radius:var(--radius-sm);padding:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div>
        <strong style="font-family:var(--font-display);font-size:16px">Desbloqueie todo o potencial</strong>
        <p style="font-size:13px;color:var(--text2);margin-top:4px">Narração com IA · Mais vídeos · Downloads · Analytics</p>
      </div>
      <a href="<?= base_url('app/upgrade') ?>" class="btn-create-big" style="font-size:14px;padding:12px 24px;flex-shrink:0">🚀 Ver Planos</a>
    </div>
    <?php else: ?>
    <?php
    $daysLeft = (int)((strtotime($subscription['expires_at']) - time()) / 86400);
    if ($daysLeft <= 7 && $daysLeft >= 0): ?>
    <div class="expiry-warning">⚠ Sua assinatura vence em <strong><?= $daysLeft ?> dia(s)</strong>. Contate o suporte para renovar.</div>
    <?php elseif ($daysLeft < 0): ?>
    <div class="expiry-danger">❌ Assinatura vencida há <?= abs($daysLeft) ?> dia(s). <a href="<?= base_url('app/upgrade') ?>" style="color:var(--red)">Renove agora</a>.</div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="empty-card">
    <span class="empty-icon">◇</span>
    <h3>Sem assinatura ativa</h3>
    <a href="<?= base_url('app/upgrade') ?>" class="btn-primary-lg">🚀 Escolher um Plano</a>
  </div>
  <?php endif; ?>

  <?php if (!($is_free ?? false) && !empty($payments)): ?>
  <div class="section-header mt-20"><h2>Histórico de Pagamentos</h2></div>
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
