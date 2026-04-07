<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div>
    <h1>Vídeo #<?= $video['id'] ?></h1>
    <span class="page-sub"><?= esc($video['niche']) ?></span>
  </div>
  <a href="<?= base_url('admin/videos') ?>" class="btn btn-ghost">← Voltar</a>
</div>

<div class="detail-grid">
  <!-- Info Card -->
  <div class="detail-card">
    <h3 class="dc-title">Informações</h3>
    <div class="detail-rows">
      <div class="dr-row"><span>ID</span><strong><?= $video['id'] ?></strong></div>
      <div class="dr-row"><span>Cliente</span><strong><a href="<?= base_url('admin/users/'.$video['user_id']) ?>" style="color:var(--blue)"><?= esc($video['user_name']) ?></a></strong></div>
      <div class="dr-row"><span>E-mail</span><strong><?= esc($video['email']) ?></strong></div>
      <div class="dr-row"><span>Nicho</span><strong><?= esc($video['niche']) ?></strong></div>
      <div class="dr-row"><span>Status</span>
        <span class="badge badge-<?= $video['status']==='ready'?'green':($video['status']==='failed'?'red':'blue') ?>">
          <?= $video['status'] ?>
        </span>
      </div>
      <div class="dr-row"><span>Viral Score</span><strong><?= $video['viral_score'] ?? '—' ?></strong></div>
      <div class="dr-row"><span>Duração</span><strong><?= $video['duration_sec'] ? gmdate('i:s', $video['duration_sec']) : '—' ?></strong></div>
      <div class="dr-row"><span>Voz</span><strong><?= $video['voice_name'] ? esc($video['voice_name']).' ('.esc($video['voice_gender']).')' : '—' ?></strong></div>
      <div class="dr-row"><span>YouTube</span><strong><?= $video['youtube_id'] ? '<a href="'.$video['youtube_url'].'" target="_blank">'.esc($video['youtube_id']).'</a>' : '—' ?></strong></div>
      <div class="dr-row"><span>Criado em</span><strong><?= date('d/m/Y H:i', strtotime($video['created_at'])) ?></strong></div>
    </div>
  </div>

  <!-- Thumbnail -->
  <div class="detail-card">
    <h3 class="dc-title">Thumbnail</h3>
    <?php if ($video['thumbnail_data']): ?>
    <img src="<?= $video['thumbnail_data'] ?>" alt="thumbnail"
      style="width:100%;border-radius:8px;border:1px solid var(--border)"/>
    <?php else: ?>
    <div class="empty-state" style="padding:40px">Sem thumbnail</div>
    <?php endif; ?>
  </div>
</div>

<!-- Title -->
<div class="detail-card mt-20">
  <h3 class="dc-title">Título</h3>
  <p style="font-size:18px;font-weight:700;font-family:var(--font-display)"><?= esc($video['title']) ?></p>
</div>

<!-- Description -->
<?php if ($video['description']): ?>
<div class="detail-card mt-20">
  <h3 class="dc-title">Descrição SEO</h3>
  <pre style="white-space:pre-wrap;font-family:var(--font-body);font-size:13px;color:var(--text2);line-height:1.7;max-height:400px;overflow-y:auto"><?= esc($video['description']) ?></pre>
</div>
<?php endif; ?>

<!-- Tags -->
<?php if ($video['tags']): ?>
<div class="detail-card mt-20">
  <h3 class="dc-title">Tags SEO</h3>
  <div style="display:flex;flex-wrap:wrap;gap:6px">
    <?php foreach (explode(',', $video['tags']) as $tag): ?>
    <span style="background:var(--bg3);border:1px solid var(--border);border-radius:100px;padding:3px 10px;font-size:12px;font-family:var(--font-mono);color:var(--text3)"><?= esc(trim($tag)) ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Audio -->
<?php if ($video['audio_path']): ?>
<div class="detail-card mt-20">
  <h3 class="dc-title">Narração Gerada</h3>
  <audio controls src="<?= base_url('writable/'.$video['audio_path']) ?>" style="width:100%;margin-top:8px"></audio>
  <div style="font-size:12px;color:var(--text3);margin-top:8px">
    Arquivo: <code><?= esc($video['audio_path']) ?></code>
  </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
