<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Criar Conta Grátis — YT.AUTO</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= base_url('css/auth.css') ?>"/>
<style>
.free-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(0,232,122,0.1);border:1px solid rgba(0,232,122,0.3);color:#00e87a;border-radius:100px;padding:5px 14px;font-size:13px;font-weight:700;margin-bottom:20px}
.upgrade-section{margin-top:36px;padding-top:28px;border-top:1px solid rgba(255,255,255,0.07)}
.upgrade-section h3{font-family:'Syne',sans-serif;font-weight:800;font-size:17px;text-align:center;margin-bottom:6px}
.upgrade-section p{font-size:13px;color:#8a9bb8;text-align:center;margin-bottom:20px}
.plan-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px}
.plan-card{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:14px;text-align:center;transition:.2s ease}
.plan-card:hover{border-color:rgba(255,60,60,0.35);background:rgba(255,60,60,0.04)}
.plan-card .pc-name{font-family:'Syne',sans-serif;font-weight:800;font-size:15px;margin-bottom:4px}
.plan-card .pc-price{font-size:20px;font-weight:700;color:#ff3c3c;margin-bottom:4px}
.plan-card .pc-price small{font-size:12px;font-weight:400;color:#8a9bb8}
.plan-card .pc-vids{font-size:12px;color:#8a9bb8;margin-bottom:4px}
.plan-card .pc-narrate{font-size:11px;color:#a855f7;margin-bottom:2px}
.plan-card .pc-trial{font-size:11px;color:#00e87a;margin-top:4px}
.pc-popular{border-color:rgba(255,60,60,0.5)!important;background:rgba(255,60,60,0.06)!important;position:relative}
.pc-popular::before{content:'MAIS POPULAR';position:absolute;top:-10px;left:50%;transform:translateX(-50%);background:#ff3c3c;color:#fff;font-size:9px;font-weight:700;padding:2px 10px;border-radius:100px;letter-spacing:1px;white-space:nowrap}
</style>
</head>
<body>
<div class="auth-bg"><div class="auth-glow"></div></div>
<div class="auth-wrap" style="max-width:540px">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="logo-icon">▶</span>
      <span class="logo-text">YT<span class="logo-dot">.</span>AUTO</span>
    </div>

    <div style="text-align:center">
      <div class="free-badge">✓ 100% Gratuito para começar</div>
    </div>
    <h2 class="auth-title">Crie sua conta grátis</h2>
    <p class="auth-sub">Sem cartão de crédito. Sem compromisso.</p>

    <?php if (session()->getFlashdata('errors')): ?>
    <div class="auth-alert auth-alert-error">
      <ul><?php foreach ((array)session()->getFlashdata('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('register') ?>" class="auth-form">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Nome completo</label>
        <input type="text" name="name" required placeholder="Seu nome completo" value="<?= old('name') ?>" autocomplete="name"/>
      </div>
      <div class="form-group">
        <label>E-mail</label>
        <input type="email" name="email" required placeholder="seu@email.com" value="<?= old('email') ?>" autocomplete="email"/>
      </div>
      <div class="form-group">
        <label>Senha (mín. 8 caracteres)</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pwInput" required placeholder="••••••••" minlength="8" autocomplete="new-password"/>
          <button type="button" class="pw-toggle" onclick="togglePw()">👁</button>
        </div>
      </div>

      <div style="background:rgba(0,232,122,0.05);border:1px solid rgba(0,232,122,0.15);border-radius:8px;padding:12px 14px;font-size:13px;color:#8a9bb8;line-height:1.6;margin-bottom:4px">
        <strong style="color:#00e87a">Plano Free inclui:</strong><br>
        ✓ 3 vídeos por mês &nbsp;·&nbsp; ✓ Thumbnails 1280×720 &nbsp;·&nbsp; ✓ Descrição SEO &nbsp;·&nbsp; ✓ Histórico de vídeos
      </div>

      <button type="submit" class="auth-btn">Criar conta grátis <span>→</span></button>
    </form>

    <p class="auth-link">Já tem conta? <a href="<?= base_url('login') ?>">Fazer login</a></p>

    <!-- Upgrade section -->
    <?php if (!empty($paid_plans)): ?>
    <div class="upgrade-section">
      <h3>Quer mais recursos?</h3>
      <p>Faça upgrade após o cadastro ou escolha um plano pago agora</p>
      <div class="plan-cards">
        <?php foreach ($paid_plans as $i => $p): ?>
        <div class="plan-card <?= $i === 1 ? 'pc-popular' : '' ?>">
          <div class="pc-name"><?= esc($p['name']) ?></div>
          <div class="pc-price">R$ <?= number_format($p['price_monthly'],0,',','.') ?><small>/mês</small></div>
          <div class="pc-vids"><?= $p['max_videos_month'] ?: '∞' ?> vídeos/mês</div>
          <div class="pc-narrate">♪ Narração com IA</div>
          <?php if ($p['trial_days'] > 0): ?>
          <div class="pc-trial">✓ <?= $p['trial_days'] ?>d grátis</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <p style="font-size:12px;color:#4a5a72;margin-top:14px;text-align:center">
        Após criar sua conta, vá em <strong>Perfil → Assinatura</strong> para fazer upgrade a qualquer momento.
      </p>
    </div>
    <?php endif; ?>

  </div>
</div>
<script>function togglePw(){const i=document.getElementById('pwInput');i.type=i.type==='password'?'text':'password';}</script>
</body></html>
