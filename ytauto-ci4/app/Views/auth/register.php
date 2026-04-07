<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Criar Conta — YT.AUTO</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= base_url('css/auth.css') ?>"/>
</head>
<body>
<div class="auth-bg"><div class="auth-glow"></div></div>
<div class="auth-wrap auth-wide">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="logo-icon">▶</span>
      <span class="logo-text">YT<span class="logo-dot">.</span>AUTO</span>
    </div>
    <h2 class="auth-title">Criar sua conta</h2>
    <p class="auth-sub">Escolha um plano e comece agora</p>

    <?php if (session()->getFlashdata('errors')): ?>
    <div class="auth-alert auth-alert-error">
      <ul><?php foreach ((array)session()->getFlashdata('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <!-- Planos -->
    <div class="plan-selector">
      <?php foreach ($plans as $plan): ?>
      <label class="plan-option" for="plan_<?= $plan['id'] ?>">
        <input type="radio" name="plan_id_sel" id="plan_<?= $plan['id'] ?>" value="<?= $plan['id'] ?>"
          onchange="document.getElementById('plan_id_hidden').value=this.value;document.querySelectorAll('.plan-option').forEach(e=>e.classList.remove('selected'));this.parentElement.classList.add('selected')"
          <?= (old('plan_id') == $plan['id'] || ($plan['slug'] === 'starter' && !old('plan_id'))) ? 'checked' : '' ?>>
        <div class="po-content">
          <div class="po-name"><?= esc($plan['name']) ?></div>
          <div class="po-price">R$ <?= number_format($plan['price_monthly'],2,',','.') ?><small>/mês</small></div>
          <div class="po-desc"><?= esc($plan['description']) ?></div>
          <?php if ($plan['trial_days'] > 0): ?>
          <div class="po-trial">✓ <?= $plan['trial_days'] ?> dias grátis</div>
          <?php endif; ?>
          <div class="po-vids"><?= $plan['max_videos_month'] === 0 ? '∞ vídeos/mês' : $plan['max_videos_month'].' vídeos/mês' ?></div>
        </div>
      </label>
      <?php endforeach; ?>
    </div>

    <form method="POST" action="<?= base_url('register') ?>" class="auth-form">
      <?= csrf_field() ?>
      <input type="hidden" name="plan_id" id="plan_id_hidden" value="<?= old('plan_id', $plans[0]['id'] ?? '') ?>"/>
      <div class="form-row">
        <div class="form-group">
          <label>Nome completo</label>
          <input type="text" name="name" required placeholder="Seu nome" value="<?= old('name') ?>"/>
        </div>
        <div class="form-group">
          <label>E-mail</label>
          <input type="email" name="email" required placeholder="seu@email.com" value="<?= old('email') ?>"/>
        </div>
      </div>
      <div class="form-group">
        <label>Senha (mín. 8 caracteres)</label>
        <input type="password" name="password" required placeholder="••••••••" minlength="8"/>
      </div>
      <button type="submit" class="auth-btn">Criar conta <span>→</span></button>
    </form>

    <p class="auth-link">Já tem conta? <a href="<?= base_url('login') ?>">Fazer login</a></p>
  </div>
</div>
<script>
// Auto-select first plan
document.addEventListener('DOMContentLoaded',()=>{
  const first=document.querySelector('.plan-option input:checked');
  if(first) first.parentElement.classList.add('selected');
  else {
    const f=document.querySelector('.plan-option input');
    if(f){f.checked=true;f.parentElement.classList.add('selected');document.getElementById('plan_id_hidden').value=f.value;}
  }
});
</script>
</body></html>
