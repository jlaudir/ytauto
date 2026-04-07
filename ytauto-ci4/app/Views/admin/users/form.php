<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <h1><?= esc($title) ?></h1>
  <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost">← Voltar</a>
</div>

<div class="form-card">
  <form method="POST" action="<?= esc($action) ?>">
    <?= csrf_field() ?>
    <div class="form-section">
      <h3>Dados Pessoais</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Nome completo *</label>
          <input type="text" name="name" required value="<?= esc($user['name'] ?? old('name')) ?>"/>
        </div>
        <div class="form-group">
          <label>E-mail *</label>
          <input type="email" name="email" required value="<?= esc($user['email'] ?? old('email')) ?>"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Telefone</label>
          <input type="text" name="phone" value="<?= esc($user['phone'] ?? '') ?>"/>
        </div>
        <div class="form-group">
          <label>CPF / CNPJ</label>
          <input type="text" name="document" value="<?= esc($user['document'] ?? '') ?>"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= $user ? 'Nova Senha (deixe vazio para manter)' : 'Senha *' ?></label>
          <input type="password" name="password" <?= !$user ? 'required' : '' ?> minlength="8" placeholder="••••••••"/>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="is_active">
            <option value="1" <?= ($user['is_active'] ?? 1) ? 'selected' : '' ?>>Ativo</option>
            <option value="0" <?= !($user['is_active'] ?? 1) ? 'selected' : '' ?>>Inativo</option>
          </select>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Plano</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Plano contratado *</label>
          <select name="plan_id" required>
            <?php foreach ($plans as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($user['plan_id'] ?? old('plan_id')) == $p['id'] ? 'selected' : '' ?>>
              <?= esc($p['name']) ?> — R$ <?= number_format($p['price_monthly'],2,',','.') ?>/mês
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if (!$user): ?>
        <div class="form-group">
          <label>Ciclo de cobrança</label>
          <select name="billing_cycle">
            <option value="monthly">Mensal</option>
            <option value="annual">Anual</option>
          </select>
        </div>
        <div class="form-group">
          <label>Vencimento da assinatura</label>
          <input type="date" name="expires_at" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"/>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <?= $user ? '✓ Salvar alterações' : '+ Criar cliente' ?>
      </button>
      <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost">Cancelar</a>
    </div>
  </form>
</div>
<?= $this->endSection() ?>
