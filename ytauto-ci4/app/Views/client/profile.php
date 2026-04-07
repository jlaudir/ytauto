<?= $this->extend('layouts/client') ?>
<?= $this->section('content') ?>
<div class="client-page client-narrow">
  <div class="page-hero compact"><h1>◎ Meu Perfil</h1></div>

  <div class="form-card">
    <form method="POST" action="<?= base_url('app/profile') ?>">
      <?= csrf_field() ?>
      <div class="form-section">
        <h3>Dados Pessoais</h3>
        <div class="form-row">
          <div class="form-group">
            <label>Nome completo</label>
            <input type="text" name="name" required value="<?= esc($user['name']) ?>"/>
          </div>
          <div class="form-group">
            <label>E-mail (não editável)</label>
            <input type="email" value="<?= esc($user['email']) ?>" disabled/>
          </div>
        </div>
        <div class="form-group">
          <label>Telefone</label>
          <input type="text" name="phone" value="<?= esc($user['phone'] ?? '') ?>"/>
        </div>
      </div>
      <div class="form-section">
        <h3>Alterar Senha</h3>
        <div class="form-group">
          <label>Nova senha (deixe vazio para manter a atual)</label>
          <input type="password" name="password" minlength="8" placeholder="••••••••"/>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">✓ Salvar alterações</button>
      </div>
    </form>
  </div>

  <div class="info-card mt-20">
    <h3>Informações do Plano</h3>
    <div class="detail-rows">
      <div class="dr-row"><span>Plano</span><strong><?= esc($user['plan_name'] ?? '—') ?></strong></div>
      <div class="dr-row"><span>Membro desde</span><strong><?= date('d/m/Y', strtotime($user['created_at'])) ?></strong></div>
      <div class="dr-row"><span>Último login</span><strong><?= $user['last_login_at'] ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : '—' ?></strong></div>
    </div>
    <a href="<?= base_url('app/subscription') ?>" class="btn btn-secondary mt-12">Ver Assinatura →</a>
  </div>
</div>
<?= $this->endSection() ?>
