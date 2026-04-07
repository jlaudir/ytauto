<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Login — YT.AUTO</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= base_url('css/auth.css') ?>"/>
</head>
<body>
<div class="auth-bg"><div class="auth-glow"></div></div>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="logo-icon">▶</span>
      <span class="logo-text">YT<span class="logo-dot">.</span>AUTO</span>
    </div>
    <h2 class="auth-title">Bem-vindo de volta</h2>
    <p class="auth-sub">Faça login para acessar o painel</p>

    <?php if (session()->getFlashdata('error')): ?>
    <div class="auth-alert auth-alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
    <div class="auth-alert auth-alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('login') ?>" class="auth-form">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>E-mail</label>
        <input type="email" name="email" required placeholder="seu@email.com" value="<?= old('email') ?>"/>
      </div>
      <div class="form-group">
        <label>Senha</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pwInput" required placeholder="••••••••"/>
          <button type="button" class="pw-toggle" onclick="togglePw()">👁</button>
        </div>
      </div>
      <button type="submit" class="auth-btn">Entrar <span>→</span></button>
    </form>

    <p class="auth-link">Não tem conta? <a href="<?= base_url('register') ?>">Criar agora</a></p>
  </div>
</div>
<script>function togglePw(){const i=document.getElementById('pwInput');i.type=i.type==='password'?'text':'password';}</script>
</body></html>
