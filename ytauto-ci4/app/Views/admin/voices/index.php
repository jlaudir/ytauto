<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div>
    <h1>Vozes ElevenLabs</h1>
    <span class="page-sub">Gerencie as vozes disponíveis para narração</span>
  </div>
  <button class="btn btn-primary" onclick="syncVoices()">↻ Sincronizar com ElevenLabs</button>
</div>

<?php if (!empty($el_info)): ?>
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-icon" style="background:var(--purple-glow,rgba(168,85,247,0.1));color:var(--purple)">♪</div>
    <div class="kpi-body">
      <span class="kpi-label">Plano ElevenLabs</span>
      <span class="kpi-value"><?= esc($el_info['tier'] ?? 'N/A') ?></span>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon" style="background:var(--blue-glow);color:var(--blue)">⌨</div>
    <div class="kpi-body">
      <span class="kpi-label">Caracteres usados</span>
      <span class="kpi-value"><?= number_format($el_info['character_count'] ?? 0) ?></span>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon" style="background:var(--green-glow);color:var(--green)">✓</div>
    <div class="kpi-body">
      <span class="kpi-label">Limite de caracteres</span>
      <span class="kpi-value"><?= number_format($el_info['character_limit'] ?? 0) ?></span>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="voices-grid mt-20">
  <?php foreach ($voices as $voice): ?>
  <div class="voice-card" id="vc-<?= $voice['id'] ?>">
    <div class="vc-icon"><?= $voice['gender'] === 'female' ? '♀' : ($voice['gender'] === 'male' ? '♂' : '◎') ?></div>
    <div class="vc-body">
      <strong><?= esc($voice['name']) ?></strong>
      <span class="vc-gender badge badge-<?= $voice['gender'] === 'female' ? 'blue' : 'green' ?>"><?= $voice['gender'] ?></span>
      <code class="vc-id"><?= esc($voice['elevenlabs_id']) ?></code>
      <?php if ($voice['preview_url']): ?>
      <audio controls class="vc-audio" src="<?= esc($voice['preview_url']) ?>"></audio>
      <?php endif; ?>
    </div>
    <div class="vc-actions">
      <span class="badge badge-<?= $voice['is_active'] ? 'green' : 'red' ?>" id="vs-<?= $voice['id'] ?>">
        <?= $voice['is_active'] ? 'Ativa' : 'Inativa' ?>
      </span>
      <button class="ab ab-<?= $voice['is_active'] ? 'orange' : 'green' ?>" onclick="toggleVoice(<?= $voice['id'] ?>)">
        <?= $voice['is_active'] ? '⏸' : '▶' ?>
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function syncVoices(){
  showToast('Sincronizando...','default');
  fetch('/admin/voices/sync',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(d=>{ if(d.error) showToast(d.error,'error'); else { showToast(d.message,'success'); setTimeout(()=>location.reload(),1500); }});
}
function toggleVoice(id){
  fetch(`/admin/voices/${id}/toggle`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{ location.reload(); });
}
</script>
<?= $this->endSection() ?>
