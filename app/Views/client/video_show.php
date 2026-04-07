<?= $this->extend('layouts/client') ?>
<?= $this->section('content') ?>
<div class="client-page">
  <div class="page-hero compact">
    <div>
      <h1><?= esc(mb_substr($video['title'], 0, 60)) ?>...</h1>
      <p>Nicho: <strong><?= esc($video['niche']) ?></strong></p>
    </div>
    <div style="display:flex;gap:10px">
      <a href="<?= base_url('app/history') ?>" class="btn btn-ghost">← Histórico</a>
      <a href="<?= base_url('app/create') ?>" class="btn btn-secondary">+ Criar Novo</a>
    </div>
  </div>

  <div class="detail-grid" style="grid-template-columns:1fr 1fr">
    <!-- Thumbnail -->
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
      <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-weight:600;font-size:14px">🖼 Thumbnail</div>
      <?php if ($video['thumbnail_data']): ?>
      <img src="<?= $video['thumbnail_data'] ?>" alt="thumbnail" style="width:100%;display:block"/>
      <?php else: ?>
      <div style="padding:60px;text-align:center;color:var(--text3)">Sem thumbnail</div>
      <?php endif; ?>
      <?php if ($video['thumbnail_data']): ?>
      <div style="padding:10px 14px;border-top:1px solid var(--border)">
        <a href="<?= $video['thumbnail_data'] ?>" download="thumbnail-<?= $video['id'] ?>.png" class="btn-sm-primary">⬇ Baixar PNG</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:16px">
        <h3 style="font-family:var(--font-display);font-weight:700;font-size:15px;margin-bottom:14px;color:var(--text2)">Detalhes</h3>
        <div class="detail-rows">
          <div class="dr-row"><span>Status</span>
            <span class="badge badge-<?= $video['status']==='ready'?'green':($video['status']==='posted'?'blue':'yellow') ?>"><?= $video['status'] ?></span>
          </div>
          <div class="dr-row"><span>Viral Score</span>
            <strong style="color:var(--red);font-size:18px;font-family:var(--font-display)"><?= $video['viral_score'] ?? '—' ?></strong>
          </div>
          <div class="dr-row"><span>Duração</span><strong><?= $video['duration_sec'] ? gmdate('i:s', $video['duration_sec']) : '—' ?></strong></div>
          <div class="dr-row"><span>Voz</span><strong><?= $video['voice_name'] ? esc($video['voice_name']) : 'Sem narração' ?></strong></div>
          <div class="dr-row"><span>Criado em</span><strong><?= date('d/m/Y H:i', strtotime($video['created_at'])) ?></strong></div>
        </div>
      </div>

      <?php if ($video['audio_path']): ?>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px">
        <h3 style="font-family:var(--font-display);font-weight:700;font-size:15px;margin-bottom:12px;color:var(--text2)">♪ Narração</h3>
        <audio controls src="<?= base_url('writable/'.$video['audio_path']) ?>" style="width:100%"></audio>
        <p style="font-size:12px;color:var(--text3);margin-top:8px">
          Gerado com ElevenLabs · <?= esc($video['voice_name'] ?? '') ?>
        </p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Title -->
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-top:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <h3 style="font-family:var(--font-display);font-weight:700;font-size:15px;color:var(--text2)">Título Viral</h3>
      <button class="btn-sm-secondary" onclick="copyText('vidTitle','Título copiado!')">⎘ Copiar</button>
    </div>
    <p id="vidTitle" style="font-family:var(--font-display);font-weight:800;font-size:18px;line-height:1.4"><?= esc($video['title']) ?></p>
  </div>

  <!-- Description -->
  <?php if ($video['description']): ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-top:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <h3 style="font-family:var(--font-display);font-weight:700;font-size:15px;color:var(--text2)">📝 Descrição SEO</h3>
      <button class="btn-sm-secondary" onclick="copyText('vidDesc','Descrição copiada!')">⎘ Copiar</button>
    </div>
    <textarea id="vidDesc" style="width:100%;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:14px;color:var(--text);font-size:13px;line-height:1.7;height:280px;resize:vertical;outline:none;font-family:var(--font-body)"><?= esc($video['description']) ?></textarea>
  </div>
  <?php endif; ?>

  <!-- Tags -->
  <?php if ($video['tags']): ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-top:16px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
      <h3 style="font-family:var(--font-display);font-weight:700;font-size:15px;flex:1;color:var(--text2)">🏷 Tags SEO</h3>
      <button class="btn-sm-secondary" onclick="copyText('vidTags','Tags copiadas!')">⎘ Copiar</button>
    </div>
    <div id="vidTags" style="display:flex;flex-wrap:wrap;gap:6px">
      <?php foreach (explode(',', $video['tags']) as $tag): ?>
      <span class="tag-pill"><?= esc(trim($tag)) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
