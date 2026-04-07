<?= $this->extend('layouts/client') ?>
<?= $this->section('content') ?>
<div class="client-page">
  <div class="page-hero compact">
    <h1>📚 Histórico de Vídeos</h1>
    <a href="<?= base_url('app/create') ?>" class="btn-sm-primary">+ Criar Novo</a>
  </div>

  <?php if (empty($videos)): ?>
  <div class="empty-card">
    <span class="empty-icon">📭</span>
    <h3>Nenhum vídeo criado ainda</h3>
    <a href="<?= base_url('app/create') ?>" class="btn-primary-lg">⚡ Criar Primeiro Vídeo</a>
  </div>
  <?php else: ?>
  <div class="history-list">
    <?php foreach ($videos as $v): ?>
    <div class="history-item">
      <div class="hi-thumb">
        <?php if ($v['thumbnail_data']): ?>
        <img src="<?= $v['thumbnail_data'] ?>" alt=""/>
        <?php else: ?>
        <div class="hi-thumb-ph">▶</div>
        <?php endif; ?>
      </div>
      <div class="hi-body">
        <div class="hi-niche"><?= esc($v['niche']) ?></div>
        <h3 class="hi-title"><?= esc($v['title']) ?></h3>
        <div class="hi-meta">
          <?php if ($v['viral_score']): ?><span class="vs-badge">🔥 <?= $v['viral_score'] ?></span><?php endif; ?>
          <?php if ($v['duration_sec']): ?><span>⏱ <?= gmdate('i:s',$v['duration_sec']) ?></span><?php endif; ?>
          <?php if ($v['voice_name']): ?><span>♪ <?= esc($v['voice_name']) ?> (<?= $v['voice_gender'] ?>)</span><?php endif; ?>
          <span class="badge badge-<?= $v['status']==='ready'?'green':($v['status']==='posted'?'blue':'yellow') ?>"><?= $v['status'] ?></span>
          <span class="hi-date"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></span>
        </div>
        <?php if ($v['audio_path']): ?>
        <audio controls src="<?= base_url('writable/'.$v['audio_path']) ?>" style="margin-top:8px;height:32px;width:100%;max-width:400px"></audio>
        <?php endif; ?>
      </div>
      <div class="hi-actions">
        <a href="<?= base_url('app/video/'.$v['id']) ?>" class="btn-sm-secondary">Ver detalhes</a>
        <button class="btn-sm-danger" onclick="deleteVideo(<?= $v['id'] ?>)">🗑</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function deleteVideo(id){
  if(!confirm('Excluir este vídeo?')) return;
  fetch(`/app/video/${id}`,{method:'DELETE'}).then(r=>r.json()).then(d=>{
    if(d.success){showToast('Excluído!','success');setTimeout(()=>location.reload(),700);}
  });
}
</script>
<?= $this->endSection() ?>
