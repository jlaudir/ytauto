<?= $this->extend('layouts/client') ?>
<?= $this->section('content') ?>

<div class="client-page">
  <div class="page-hero">
    <div>
      <h1>Olá, <?= esc(explode(' ', session()->get('user_name'))[0]) ?>! 👋</h1>
      <p>Pronto para criar conteúdo viral hoje?</p>
    </div>
    <a href="<?= base_url('app/create') ?>" class="btn-create-big">
      <span>⚡</span> Criar Vídeo Agora
    </a>
  </div>

  <!-- Usage Card -->
  <div class="usage-strip">
    <div class="us-item">
      <span class="us-icon" style="color:var(--red)">▶</span>
      <div>
        <span class="us-val"><?= $month_usage ?><?= $max_videos > 0 ? ' / '.$max_videos : '' ?></span>
        <span class="us-lbl">Vídeos este mês</span>
      </div>
    </div>
    <?php if ($max_videos > 0): ?>
    <div class="us-progress-wrap">
      <div class="us-progress">
        <div class="us-fill" style="width:<?= min(100, round($month_usage/$max_videos*100)) ?>%"></div>
      </div>
      <span><?= round($month_usage/$max_videos*100) ?>% usado</span>
    </div>
    <?php else: ?>
    <span class="badge badge-green">∞ Ilimitado</span>
    <?php endif; ?>

    <div class="us-item">
      <span class="us-icon" style="color:var(--blue)">◇</span>
      <div>
        <span class="us-val"><?= esc($plan['name'] ?? '—') ?></span>
        <span class="us-lbl">Seu plano</span>
      </div>
    </div>

    <div class="us-item">
      <span class="us-icon" style="color:<?= ($subscription['status'] ?? '') === 'active' ? 'var(--green)' : 'var(--yellow)' ?>">◉</span>
      <div>
        <span class="us-val" style="color:<?= ($subscription['status'] ?? '') === 'active' ? 'var(--green)' : 'var(--yellow)' ?>">
          <?= ucfirst($subscription['status'] ?? 'inativa') ?>
        </span>
        <span class="us-lbl">Assinatura</span>
      </div>
    </div>

    <?php if ($next_payment): ?>
    <div class="us-item">
      <span class="us-icon" style="color:var(--yellow)">📅</span>
      <div>
        <span class="us-val"><?= date('d/m/Y', strtotime($next_payment['due_date'])) ?></span>
        <span class="us-lbl">Próx. vencimento (R$ <?= number_format($next_payment['amount'],2,',','.') ?>)</span>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent Videos -->
  <div class="section-header">
    <h2>Vídeos Recentes</h2>
    <a href="<?= base_url('app/history') ?>">Ver todos →</a>
  </div>

  <?php if (empty($recent_videos)): ?>
  <div class="empty-card">
    <span class="empty-icon">🎬</span>
    <h3>Nenhum vídeo criado ainda</h3>
    <p>Comece agora e crie seu primeiro vídeo viral em segundos!</p>
    <a href="<?= base_url('app/create') ?>" class="btn-primary-lg">⚡ Criar Primeiro Vídeo</a>
  </div>
  <?php else: ?>
  <div class="video-grid">
    <?php foreach ($recent_videos as $v): ?>
    <div class="video-card">
      <div class="vc-thumb">
        <?php if ($v['thumbnail_data']): ?>
        <img src="<?= $v['thumbnail_data'] ?>" alt="thumbnail"/>
        <?php else: ?>
        <div class="vc-thumb-placeholder">▶</div>
        <?php endif; ?>
        <span class="vc-status badge-<?= $v['status'] === 'ready' ? 'green' : 'blue' ?>"><?= $v['status'] ?></span>
      </div>
      <div class="vc-info">
        <div class="vc-niche"><?= esc($v['niche']) ?></div>
        <h4 class="vc-title"><?= esc(mb_substr($v['title'],0,70)) ?>...</h4>
        <div class="vc-meta">
          <?php if ($v['viral_score']): ?>
          <span class="vs-badge">🔥 <?= $v['viral_score'] ?></span>
          <?php endif; ?>
          <?php if ($v['voice_name']): ?>
          <span class="voice-badge">♪ <?= esc($v['voice_name']) ?></span>
          <?php endif; ?>
          <span class="vc-date"><?= date('d/m/Y', strtotime($v['created_at'])) ?></span>
        </div>
        <?php if ($v['audio_path']): ?>
        <audio controls class="vc-audio" src="<?= base_url('writable/'.$v['audio_path']) ?>"></audio>
        <?php endif; ?>
      </div>
      <div class="vc-actions">
        <a href="<?= base_url('app/video/'.$v['id']) ?>" class="btn-sm-secondary">Ver</a>
        <button class="btn-sm-danger" onclick="deleteVideo(<?= $v['id'] ?>)">🗑</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Subscription info -->
  <?php if ($subscription): ?>
  <div class="sub-info-card">
    <div class="sic-left">
      <span class="sic-icon">◇</span>
      <div>
        <strong>Assinatura <?= esc($plan['name'] ?? '') ?></strong>
        <span>Válida até <?= date('d/m/Y', strtotime($subscription['expires_at'])) ?></span>
      </div>
    </div>
    <a href="<?= base_url('app/subscription') ?>" class="btn-sm-secondary">Gerenciar</a>
  </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function deleteVideo(id){
  if(!confirm('Excluir este vídeo?')) return;
  fetch(`/app/video/${id}`,{method:'DELETE'}).then(r=>r.json()).then(d=>{
    if(d.success){ showToast('Vídeo excluído','success'); setTimeout(()=>location.reload(),800); }
  });
}
</script>
<?= $this->endSection() ?>
