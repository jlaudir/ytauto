<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= esc($title ?? 'Admin') ?> — YT.AUTO</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= base_url('css/admin.css') ?>"/>
<?= $this->renderSection('head') ?>
</head>
<body class="admin-body">

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <span class="logo-icon">▶</span>
    <span class="logo-text">YT<span class="logo-dot">.</span>AUTO</span>
    <span class="logo-badge">ADMIN</span>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-group">
      <span class="nav-group-label">Principal</span>
      <a href="<?= base_url('admin/dashboard') ?>" class="nav-item <?= (uri_string() === 'admin/dashboard' || uri_string() === 'admin') ? 'active' : '' ?>">
        <span class="nav-icon">◈</span> Dashboard
      </a>
    </div>

    <div class="nav-group">
      <span class="nav-group-label">Gestão</span>
      <a href="<?= base_url('admin/users') ?>" class="nav-item <?= str_starts_with(uri_string(),'admin/users') ? 'active' : '' ?>">
        <span class="nav-icon">◎</span> Clientes
      </a>
      <a href="<?= base_url('admin/plans') ?>" class="nav-item <?= str_starts_with(uri_string(),'admin/plans') ? 'active' : '' ?>">
        <span class="nav-icon">◇</span> Planos
      </a>
      <a href="<?= base_url('admin/permissions') ?>" class="nav-item <?= str_starts_with(uri_string(),'admin/permissions') ? 'active' : '' ?>">
        <span class="nav-icon">◉</span> Permissões
      </a>
    </div>

    <div class="nav-group">
      <span class="nav-group-label">Financeiro</span>
      <a href="<?= base_url('admin/financial') ?>" class="nav-item <?= uri_string() === 'admin/financial' ? 'active' : '' ?>">
        <span class="nav-icon">◑</span> Visão Geral
      </a>
      <a href="<?= base_url('admin/financial/payments') ?>" class="nav-item <?= uri_string() === 'admin/financial/payments' ? 'active' : '' ?>">
        <span class="nav-icon">◐</span> Pagamentos
      </a>
      <a href="<?= base_url('admin/financial/subscriptions') ?>" class="nav-item <?= uri_string() === 'admin/financial/subscriptions' ? 'active' : '' ?>">
        <span class="nav-icon">◒</span> Assinaturas
      </a>
      <a href="<?= base_url('admin/financial/overdue') ?>" class="nav-item <?= uri_string() === 'admin/financial/overdue' ? 'active' : '' ?>">
        <span class="nav-icon">⚠</span> Inadimplentes
      </a>
      <a href="<?= base_url('admin/financial/report') ?>" class="nav-item <?= uri_string() === 'admin/financial/report' ? 'active' : '' ?>">
        <span class="nav-icon">◳</span> Relatório
      </a>
    </div>

    <div class="nav-group">
      <span class="nav-group-label">Sistema</span>
      <a href="<?= base_url('admin/voices') ?>" class="nav-item <?= str_starts_with(uri_string(),'admin/voices') ? 'active' : '' ?>">
        <span class="nav-icon">♪</span> Vozes IA
      </a>
      <a href="<?= base_url('admin/videos') ?>" class="nav-item <?= str_starts_with(uri_string(),'admin/videos') ? 'active' : '' ?>">
        <span class="nav-icon">▶</span> Vídeos
      </a>
      <a href="<?= base_url('admin/settings') ?>" class="nav-item <?= str_starts_with(uri_string(),'admin/settings') ? 'active' : '' ?>">
        <span class="nav-icon">⚙</span> Configurações
      </a>
    </div>
  </nav>

  <div class="sidebar-footer">
    <div class="sf-user">
      <div class="sf-avatar"><?= strtoupper(substr(session()->get('user_name') ?? 'A', 0, 1)) ?></div>
      <div class="sf-info">
        <span class="sf-name"><?= esc(session()->get('user_name')) ?></span>
        <span class="sf-role">Administrador</span>
      </div>
    </div>
    <a href="<?= base_url('logout') ?>" class="sf-logout" title="Sair">⏻</a>
  </div>
</aside>

<!-- Main content -->
<div class="admin-content" id="adminContent">
  <header class="admin-topbar">
    <button class="topbar-toggle" id="sidebarToggle">☰</button>
    <div class="topbar-breadcrumb">
      <span><?= esc($title ?? 'Dashboard') ?></span>
    </div>
    <div class="topbar-actions">
      <a href="<?= base_url('app/dashboard') ?>" class="tb-btn" title="Ver área do cliente" target="_blank">
        ↗ Ver site
      </a>
      <div class="tb-time" id="tbTime"></div>
    </div>
  </header>

  <!-- Flash messages -->
  <?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?> <button onclick="this.parentElement.remove()">✕</button></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?> <button onclick="this.parentElement.remove()">✕</button></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-error">
    <ul><?php foreach ((array)session()->getFlashdata('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
    <button onclick="this.parentElement.remove()">✕</button>
  </div>
  <?php endif; ?>

  <main class="admin-main">
    <?= $this->renderSection('content') ?>
  </main>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="<?= base_url('js/admin.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
