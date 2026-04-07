<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <h1><?= esc($title) ?></h1>
  <a href="<?= base_url('admin/plans') ?>" class="btn btn-ghost">← Voltar</a>
</div>

<div class="form-card">
  <form method="POST" action="<?= esc($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
      <h3>Informações do Plano</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Nome do plano *</label>
          <input type="text" name="name" required value="<?= esc($plan['name'] ?? old('name')) ?>"/>
        </div>
        <div class="form-group">
          <label>Descrição</label>
          <input type="text" name="description" value="<?= esc($plan['description'] ?? '') ?>"/>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Preços</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Preço Mensal (R$) *</label>
          <input type="number" name="price_monthly" step="0.01" min="0" required value="<?= $plan['price_monthly'] ?? '0.00' ?>"/>
        </div>
        <div class="form-group">
          <label>Preço Anual (R$)</label>
          <input type="number" name="price_annual" step="0.01" min="0" value="<?= $plan['price_annual'] ?? '0.00' ?>"/>
        </div>
        <div class="form-group">
          <label>Dias de Trial (0 = sem trial)</label>
          <input type="number" name="trial_days" min="0" value="<?= $plan['trial_days'] ?? 0 ?>"/>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Limites e Recursos</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Vídeos por mês (0 = ilimitado)</label>
          <input type="number" name="max_videos_month" min="0" value="<?= $plan['max_videos_month'] ?? 10 ?>"/>
        </div>
        <div class="form-group">
          <label>Vozes disponíveis</label>
          <input type="number" name="max_voices" min="1" value="<?= $plan['max_voices'] ?? 2 ?>"/>
        </div>
        <div class="form-group">
          <label>Ordem de exibição</label>
          <input type="number" name="sort_order" min="0" value="<?= $plan['sort_order'] ?? 0 ?>"/>
        </div>
      </div>
      <div class="form-row checks-row">
        <label class="checkbox-label">
          <input type="checkbox" name="has_analytics" value="1" <?= ($plan['has_analytics'] ?? 0) ? 'checked' : '' ?>>
          Analytics incluído
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="has_api_access" value="1" <?= ($plan['has_api_access'] ?? 0) ? 'checked' : '' ?>>
          Acesso à API
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="has_admin_panel" value="1" <?= ($plan['has_admin_panel'] ?? 0) ? 'checked' : '' ?>>
          Painel Admin
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="is_active" value="1" <?= ($plan['is_active'] ?? 1) ? 'checked' : '' ?>>
          Plano ativo (visível)
        </label>
      </div>
    </div>

    <div class="form-section">
      <h3>Permissões de Acesso</h3>
      <p class="form-hint">Selecione quais funcionalidades este plano pode acessar:</p>
      <div class="perms-grid">
        <?php
        $groups = [];
        foreach ($permissions as $p) $groups[$p['group']][] = $p;
        foreach ($groups as $gname => $gperms):
        ?>
        <div class="perm-group">
          <div class="perm-group-title"><?= esc($gname) ?></div>
          <?php foreach ($gperms as $p): ?>
          <label class="checkbox-label perm-item">
            <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>"
              <?= in_array($p['key'], $planPerms) ? 'checked' : '' ?>>
            <span><?= esc($p['label']) ?></span>
            <code><?= esc($p['key']) ?></code>
          </label>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <?= $plan ? '✓ Salvar Plano' : '+ Criar Plano' ?>
      </button>
      <a href="<?= base_url('admin/plans') ?>" class="btn btn-ghost">Cancelar</a>
    </div>
  </form>
</div>
<?= $this->endSection() ?>
