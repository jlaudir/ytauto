<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div><h1>Planos</h1><span class="page-sub">Gerencie planos e preços</span></div>
  <a href="<?= base_url('admin/plans/create') ?>" class="btn btn-primary">+ Novo Plano</a>
</div>

<div class="plans-grid">
  <?php foreach ($plans as $plan): ?>
  <div class="plan-admin-card">
    <div class="pac-header">
      <h3><?= esc($plan['name']) ?></h3>
      <span class="badge badge-<?= $plan['is_active'] ? 'green' : 'red' ?>"><?= $plan['is_active'] ? 'Ativo' : 'Inativo' ?></span>
    </div>
    <div class="pac-price">
      <span>R$ <?= number_format($plan['price_monthly'],2,',','.') ?><small>/mês</small></span>
      <span class="pac-annual">R$ <?= number_format($plan['price_annual'],2,',','.') ?>/ano</span>
    </div>
    <div class="pac-stats">
      <div class="ps-item"><span class="ps-val"><?= number_format($plan['user_count']) ?></span><span class="ps-lbl">Clientes</span></div>
      <div class="ps-item"><span class="ps-val"><?= number_format($plan['active_subs']) ?></span><span class="ps-lbl">Ativas</span></div>
      <div class="ps-item"><span class="ps-val"><?= $plan['max_videos_month'] ?: '∞' ?></span><span class="ps-lbl">Vídeos/mês</span></div>
    </div>
    <div class="pac-actions">
      <a href="<?= base_url('admin/plans/'.$plan['id'].'/edit') ?>" class="btn btn-secondary btn-sm">✎ Editar</a>
      <button class="btn btn-ghost btn-sm" onclick="deletePlan(<?= $plan['id'] ?>, '<?= esc($plan['name']) ?>')">🗑 Excluir</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="dash-card mt-20">
  <div class="dc-header">
    <h3>Permissões do Sistema</h3>
    <a href="<?= base_url('admin/permissions') ?>" class="btn btn-secondary btn-sm">Gerenciar →</a>
  </div>
  <p style="color:var(--text2);font-size:14px">As permissões controlam o acesso de cada plano às funcionalidades do sistema.</p>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function deletePlan(id, name){
  if(!confirm(`Excluir o plano "${name}"? Isso não pode ser desfeito.`)) return;
  fetch(`/admin/plans/${id}`,{method:'DELETE',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(d=>{ if(d.error) showToast(d.error,'error'); else { showToast('Plano excluído','success'); setTimeout(()=>location.reload(),1000); }});
}
</script>
<?= $this->endSection() ?>
