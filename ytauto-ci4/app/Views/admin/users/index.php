<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div><h1>Clientes</h1><span class="page-sub"><?= count($users) ?> registrado(s)</span></div>
  <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary">+ Novo Cliente</a>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar">
  <input type="text" name="search" placeholder="Buscar por nome ou email..." value="<?= esc($filters['search'] ?? '') ?>"/>
  <select name="plan_id">
    <option value="">Todos os planos</option>
    <?php foreach ($plans as $p): ?>
    <option value="<?= $p['id'] ?>" <?= ($filters['plan_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost">Limpar</a>
</form>

<div class="table-card">
  <table class="admin-table">
    <thead>
      <tr><th>Cliente</th><th>Plano</th><th>Status</th><th>Assinatura</th><th>Cadastro</th><th>Ações</th></tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
      <tr><td colspan="6" class="empty-state">Nenhum cliente encontrado.</td></tr>
      <?php endif; ?>
      <?php foreach ($users as $u): ?>
      <tr>
        <td>
          <div class="td-user">
            <div class="td-avatar"><?= strtoupper(substr($u['name'],0,1)) ?></div>
            <div>
              <strong><?= esc($u['name']) ?></strong>
              <span class="td-sub"><?= esc($u['email']) ?></span>
            </div>
          </div>
        </td>
        <td><?= esc($u['plan_name'] ?? '—') ?></td>
        <td>
          <span class="badge badge-<?= $u['is_active'] ? 'green' : 'red' ?>">
            <?= $u['is_active'] ? 'Ativo' : 'Inativo' ?>
          </span>
        </td>
        <td>
          <?php if ($u['sub_status']): ?>
          <span class="badge badge-<?= $u['sub_status'] === 'active' ? 'green' : ($u['sub_status'] === 'trial' ? 'yellow' : 'red') ?>">
            <?= $u['sub_status'] ?>
          </span>
          <?php if ($u['expires_at']): ?>
          <br><small><?= date('d/m/Y', strtotime($u['expires_at'])) ?></small>
          <?php endif; ?>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
        <td>
          <div class="action-btns">
            <a href="<?= base_url('admin/users/'.$u['id']) ?>" class="ab ab-blue" title="Ver">◎</a>
            <a href="<?= base_url('admin/users/'.$u['id'].'/edit') ?>" class="ab ab-yellow" title="Editar">✎</a>
            <button class="ab ab-<?= $u['is_active'] ? 'orange' : 'green' ?>" onclick="toggleUser(<?= $u['id'] ?>)" title="<?= $u['is_active'] ? 'Desativar' : 'Ativar' ?>">
              <?= $u['is_active'] ? '⏸' : '▶' ?>
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function toggleUser(id){
  if(!confirm('Alterar status deste cliente?')) return;
  fetch(`/admin/users/${id}/toggle`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(()=>location.reload()).catch(e=>showToast('Erro: '+e,'error'));
}
</script>
<?= $this->endSection() ?>
