<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div><h1>Vídeos Gerados</h1><span class="page-sub"><?= count($videos) ?> vídeos no sistema</span></div>
</div>

<div class="table-card">
  <table class="admin-table">
    <thead>
      <tr><th>#</th><th>Cliente</th><th>Nicho</th><th>Título</th><th>Status</th><th>Data</th><th>Ação</th></tr>
    </thead>
    <tbody>
      <?php if (empty($videos)): ?>
      <tr><td colspan="7" class="empty-state">Nenhum vídeo gerado ainda.</td></tr>
      <?php endif; ?>
      <?php foreach ($videos as $v): ?>
      <tr>
        <td><?= $v['id'] ?></td>
        <td>
          <strong><?= esc($v['user_name']) ?></strong><br>
          <small><?= esc($v['email']) ?></small>
        </td>
        <td><span class="badge badge-blue"><?= esc($v['niche']) ?></span></td>
        <td><?= esc(mb_substr($v['title'], 0, 60)) ?>...</td>
        <td>
          <span class="badge badge-<?= $v['status'] === 'ready' ? 'green' : ($v['status'] === 'failed' ? 'red' : ($v['status'] === 'posted' ? 'blue' : 'yellow')) ?>">
            <?= $v['status'] ?>
          </span>
        </td>
        <td><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
        <td>
          <a href="<?= base_url('admin/videos/' . $v['id']) ?>" class="ab ab-blue">◎ Ver</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
