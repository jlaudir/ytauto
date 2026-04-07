<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div>
    <h1><?= esc($user['name']) ?></h1>
    <span class="page-sub"><?= esc($user['email']) ?></span>
  </div>
  <div style="display:flex;gap:10px">
    <a href="<?= base_url('admin/users/'.$user['id'].'/edit') ?>" class="btn btn-secondary">✎ Editar</a>
    <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost">← Voltar</a>
  </div>
</div>

<!-- Profile + Subscription -->
<div class="detail-grid">
  <div class="detail-card">
    <h3 class="dc-title">Dados do Cliente</h3>
    <div class="detail-rows">
      <div class="dr-row"><span>Nome</span><strong><?= esc($user['name']) ?></strong></div>
      <div class="dr-row"><span>E-mail</span><strong><?= esc($user['email']) ?></strong></div>
      <div class="dr-row"><span>Telefone</span><strong><?= esc($user['phone'] ?? '—') ?></strong></div>
      <div class="dr-row"><span>CPF/CNPJ</span><strong><?= esc($user['document'] ?? '—') ?></strong></div>
      <div class="dr-row"><span>Plano</span><strong><?= esc($user['plan_name'] ?? '—') ?></strong></div>
      <div class="dr-row"><span>Status</span>
        <span class="badge badge-<?= $user['is_active'] ? 'green' : 'red' ?>"><?= $user['is_active'] ? 'Ativo' : 'Inativo' ?></span>
      </div>
      <div class="dr-row"><span>Cadastro</span><strong><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></strong></div>
      <div class="dr-row"><span>Último Login</span><strong><?= $user['last_login_at'] ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : '—' ?></strong></div>
    </div>
  </div>

  <div class="detail-card">
    <h3 class="dc-title">Assinaturas</h3>
    <?php if (empty($subscriptions)): ?>
    <p class="empty-state">Sem assinaturas.</p>
    <?php else: ?>
    <?php foreach ($subscriptions as $s): ?>
    <div class="sub-row">
      <div class="sr-plan"><?= esc($s['plan_name']) ?></div>
      <div class="sr-meta">
        <span class="badge badge-<?= $s['status'] === 'active' ? 'green' : ($s['status'] === 'trial' ? 'yellow' : 'red') ?>"><?= $s['status'] ?></span>
        <span><?= date('d/m/Y', strtotime($s['started_at'])) ?> → <?= date('d/m/Y', strtotime($s['expires_at'])) ?></span>
        <span>R$ <?= number_format($s['price_paid'],2,',','.') ?>/<?= $s['billing_cycle'] === 'annual' ? 'ano' : 'mês' ?></span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Gerar nova cobrança -->
    <div class="mt-20">
      <h4>Gerar Cobrança Manual</h4>
      <form method="POST" action="<?= base_url('admin/financial/payment/create') ?>" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>"/>
        <input type="hidden" name="subscription_id" value="<?= $subscriptions[0]['id'] ?? '' ?>"/>
        <div class="form-row">
          <div class="form-group">
            <label>Valor (R$)</label>
            <input type="number" name="amount" step="0.01" required placeholder="0.00"/>
          </div>
          <div class="form-group">
            <label>Vencimento</label>
            <input type="date" name="due_date" required value="<?= date('Y-m-d') ?>"/>
          </div>
          <div class="form-group">
            <label>Método</label>
            <select name="method">
              <option value="manual">Manual</option>
              <option value="pix">PIX</option>
              <option value="boleto">Boleto</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Gerar Cobrança</button>
      </form>
    </div>
  </div>
</div>

<!-- Payments -->
<div class="detail-card mt-20">
  <h3 class="dc-title">Histórico de Pagamentos</h3>
  <table class="admin-table">
    <thead><tr><th>Vencimento</th><th>Valor</th><th>Método</th><th>Status</th><th>Pago em</th><th>Ações</th></tr></thead>
    <tbody>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($p['due_date'])) ?></td>
        <td>R$ <?= number_format($p['amount'],2,',','.') ?></td>
        <td><?= $p['method'] ?></td>
        <td><span class="badge badge-<?= $p['status'] === 'paid' ? 'green' : ($p['status'] === 'pending' ? 'yellow' : 'red') ?>"><?= $p['status'] ?></span></td>
        <td><?= $p['paid_at'] ? date('d/m/Y', strtotime($p['paid_at'])) : '—' ?></td>
        <td>
          <?php if ($p['status'] === 'pending'): ?>
          <button class="ab ab-green" onclick="markPaid(<?= $p['id'] ?>)">✓ Pago</button>
          <button class="ab ab-red" onclick="markFailed(<?= $p['id'] ?>)">✗ Falhou</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Videos -->
<div class="detail-card mt-20">
  <h3 class="dc-title">Vídeos Gerados (<?= count($videos) ?>)</h3>
  <table class="admin-table">
    <thead><tr><th>#</th><th>Nicho</th><th>Título</th><th>Status</th><th>Data</th></tr></thead>
    <tbody>
      <?php foreach ($videos as $v): ?>
      <tr>
        <td><?= $v['id'] ?></td>
        <td><?= esc($v['niche']) ?></td>
        <td><?= esc(substr($v['title'],0,60)) ?>...</td>
        <td><span class="badge badge-<?= $v['status']==='ready'?'green':($v['status']==='failed'?'red':'blue') ?>"><?= $v['status'] ?></span></td>
        <td><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Activity Log -->
<div class="detail-card mt-20">
  <h3 class="dc-title">Log de Atividades</h3>
  <div class="activity-log">
    <?php foreach ($logs as $log): ?>
    <div class="log-item">
      <span class="log-time"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></span>
      <span class="log-action"><?= esc($log['action']) ?></span>
      <span class="log-detail"><?= esc($log['detail']) ?></span>
      <span class="log-ip"><?= esc($log['ip']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function markPaid(id){
  if(!confirm('Confirmar pagamento?')) return;
  fetch(`/admin/financial/payment/${id}/mark-paid`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{ showToast(d.message || 'Pago!','success'); setTimeout(()=>location.reload(),1200); })
    .catch(e=>showToast('Erro','error'));
}
function markFailed(id){
  if(!confirm('Marcar como falho e suspender assinatura?')) return;
  fetch(`/admin/financial/payment/${id}/mark-failed`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(()=>{ showToast('Marcado como falho','error'); setTimeout(()=>location.reload(),1200); });
}
</script>
<?= $this->endSection() ?>
