<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= esc($title ?? 'YT.AUTO') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= base_url('css/app.css') ?>"/>
<?= $this->renderSection('head') ?>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-glow"></div>

<header class="app-header">
  <div class="app-header-inner">
    <a href="<?= base_url('app/dashboard') ?>" class="app-logo">
      <span class="logo-icon">▶</span>
      <span>YT<span class="logo-dot">.</span>AUTO</span>
    </a>
    <nav class="app-nav">
      <a href="<?= base_url('app/dashboard') ?>" class="<?= uri_string()==='app/dashboard'?'active':'' ?>">Dashboard</a>
      <a href="<?= base_url('app/create') ?>" class="<?= uri_string()==='app/create'?'active':'' ?>">Criar Vídeo</a>
      <a href="<?= base_url('app/history') ?>" class="<?= uri_string()==='app/history'?'active':'' ?>">Histórico</a>
      <?php
      $planModel = new \App\Models\PlanModel();
      $currentPlan = $planModel->find(session()->get('plan_id'));
      if ($currentPlan && $currentPlan['price_monthly'] == 0):
      ?>
      <a href="<?= base_url('app/upgrade') ?>" class="<?= uri_string()==='app/upgrade'?'active':'' ?>" style="color:var(--red);font-weight:700">🚀 Upgrade</a>
      <?php endif; ?>
    </nav>
    <div class="app-header-right">
      <div class="plan-badge"><?= esc(session()->get('plan_name') ?? 'Plano') ?></div>
      <div class="user-menu">
        <button class="user-avatar" id="userMenuBtn">
          <?= strtoupper(substr(session()->get('user_name') ?? 'U', 0, 1)) ?>
        </button>
        <div class="user-dropdown" id="userDropdown">
          <div class="ud-header">
            <strong><?= esc(session()->get('user_name')) ?></strong>
            <span><?= esc(session()->get('user_email')) ?></span>
          </div>
          <a href="<?= base_url('app/profile') ?>">◎ Meu Perfil</a>
          <a href="<?= base_url('app/subscription') ?>">◇ Assinatura</a>
          <hr/>
          <a href="<?= base_url('logout') ?>" class="ud-logout">⏻ Sair</a>
        </div>
      </div>
      <button class="theme-toggle" id="themeToggle">◐</button>
    </div>
  </div>
</header>

<?php if (session()->getFlashdata('success')): ?>
<div class="flash flash-success"><?= esc(session()->getFlashdata('success')) ?><button onclick="this.parentElement.remove()">✕</button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="flash flash-error"><?= esc(session()->getFlashdata('error')) ?><button onclick="this.parentElement.remove()">✕</button></div>
<?php endif; ?>

<main class="app-main">
  <?= $this->renderSection('content') ?>
</main>

<div id="toast"></div>
<script src="<?= base_url('js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
