<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div>
    <h1>♪ Vozes — MultiVozes Engine</h1>
    <span class="page-sub">Vozes neurais PT-BR via Microsoft Edge TTS (gratuitas)</span>
  </div>
  <div style="display:flex;gap:10px">
    <button class="btn btn-secondary" onclick="testConnection()">⚡ Testar Conexão</button>
    <button class="btn btn-primary" onclick="syncVoices()">↻ Sincronizar Vozes</button>
  </div>
</div>

<!-- Engine Status -->
<div class="dash-card mb-20" style="margin-bottom:20px;border-color:<?= $connection['online'] ? 'rgba(0,232,122,0.3)' : 'rgba(255,60,60,0.3)' ?>;background:<?= $connection['online'] ? 'rgba(0,232,122,0.04)' : 'rgba(255,60,60,0.04)' ?>">
  <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
    <div style="font-size:32px"><?= $connection['online'] ? '✅' : '❌' ?></div>
    <div>
      <strong style="font-size:16px;color:<?= $connection['online'] ? 'var(--green)' : 'var(--red)' ?>">
        MultiVozes Engine <?= $connection['online'] ? 'Online' : 'Offline' ?>
      </strong>
      <div style="font-size:13px;color:var(--text2);margin-top:4px"><?= esc($connection['message']) ?></div>
    </div>
    <div style="margin-left:auto;text-align:right">
      <div style="font-size:12px;color:var(--text3)">URL configurada</div>
      <code style="font-size:13px;color:var(--blue)"><?= esc($engine_url ?: '(não configurada)') ?></code>
    </div>
  </div>
</div>

<?php if (!$connection['online']): ?>
<div class="dash-card mb-20" style="margin-bottom:20px;background:rgba(255,222,0,0.04);border-color:rgba(255,222,0,0.25)">
  <div style="display:flex;gap:14px;align-items:flex-start">
    <span style="font-size:24px">⚠</span>
    <div>
      <strong style="color:var(--yellow)">Como instalar o MultiVozes BR Engine</strong>
      <div style="font-size:13px;color:var(--text2);margin-top:8px;line-height:1.8">
        1. Acesse: <a href="https://github.com/samucamg/multivozes_br_engine" target="_blank" style="color:var(--blue)">github.com/samucamg/multivozes_br_engine</a><br>
        2. Clone o repositório e instale as dependências Python<br>
        3. Configure o <code>.env</code> com sua <code>API_KEY</code><br>
        4. Execute: <code>python app.py</code> (padrão: porta 5050)<br>
        5. Configure a URL em <a href="<?= base_url('admin/settings') ?>" style="color:var(--red)">Configurações → URL do MultiVozes Engine</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Voices Grid -->
<div class="voices-grid">
  <?php foreach ($voices as $voice): ?>
  <div class="voice-card" id="vc-<?= $voice['id'] ?>">
    <div class="vc-icon" style="color:<?= $voice['gender'] === 'female' ? 'var(--blue)' : 'var(--green)' ?>">
      <?= $voice['gender'] === 'female' ? '♀' : '♂' ?>
    </div>
    <div class="vc-body">
      <strong><?= esc($voice['name']) ?></strong>
      <span class="vc-gender badge badge-<?= $voice['gender'] === 'female' ? 'blue' : 'green' ?>"><?= $voice['gender'] ?></span>
      <code class="vc-id"><?= esc($voice['elevenlabs_id']) ?></code>
      <span style="font-size:11px;color:var(--text3)"><?= esc($voice['language']) ?> · Edge Neural</span>
    </div>
    <div class="vc-actions">
      <span class="badge badge-<?= $voice['is_active'] ? 'green' : 'red' ?>">
        <?= $voice['is_active'] ? 'Ativa' : 'Inativa' ?>
      </span>
      <button class="ab ab-<?= $voice['is_active'] ? 'orange' : 'green' ?>" onclick="toggleVoice(<?= $voice['id'] ?>)">
        <?= $voice['is_active'] ? '⏸' : '▶' ?>
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (empty($voices)): ?>
<div class="empty-state" style="padding:60px;text-align:center">
  <p style="font-size:16px;color:var(--text2)">Nenhuma voz cadastrada. Clique em <strong>↻ Sincronizar Vozes</strong> para carregar as vozes PT-BR.</p>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function syncVoices(){
  showToast('Sincronizando vozes...','default');
  fetch('/admin/voices/sync',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(d=>{
      if(d.error) showToast(d.error,'error');
      else { showToast(d.message,'success'); setTimeout(()=>location.reload(),1500); }
    })
    .catch(()=>showToast('Erro ao sincronizar','error'));
}
function toggleVoice(id){
  fetch(`/admin/voices/${id}/toggle`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(()=>location.reload());
}
function testConnection(){
  showToast('Testando conexão...','default');
  setTimeout(()=>location.reload(), 500);
}
</script>
<?= $this->endSection() ?>
