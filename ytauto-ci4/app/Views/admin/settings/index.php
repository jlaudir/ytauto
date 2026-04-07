<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <h1>Configurações do Sistema</h1>
</div>

<div class="form-card">
  <form method="POST" action="<?= base_url('admin/settings') ?>">
    <?= csrf_field() ?>

    <div class="form-section">
      <h3>Geral</h3>
      <div class="form-group">
        <label>Nome do Site</label>
        <input type="text" name="site_name" value="<?= esc($settings['site_name'] ?? 'YT.AUTO') ?>"/>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Dias de tolerância de pagamento</label>
          <input type="number" name="payment_due_days" min="0" value="<?= esc($settings['payment_due_days'] ?? 5) ?>"/>
          <span class="form-hint">Dias após vencimento antes de suspender a conta</span>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Integrações</h3>
      <div class="form-row">
        <div class="form-group">
          <label>ElevenLabs API Key</label>
          <div class="input-secret">
            <input type="password" name="elevenlabs_api_key" id="elKey"
              placeholder="<?= !empty($settings['elevenlabs_api_key']) ? '••••••••••••••• (configurada)' : 'Cole sua API Key aqui' ?>"/>
            <button type="button" onclick="toggleSecret('elKey')">👁</button>
          </div>
          <span class="form-hint">Obtenha em <a href="https://elevenlabs.io" target="_blank">elevenlabs.io</a></span>
        </div>
        <div class="form-group">
          <label>YouTube API Key</label>
          <div class="input-secret">
            <input type="password" name="youtube_api_key" id="ytKey"
              placeholder="<?= !empty($settings['youtube_api_key']) ? '••••••• (configurada)' : 'Cole sua API Key aqui' ?>"/>
            <button type="button" onclick="toggleSecret('ytKey')">👁</button>
          </div>
          <span class="form-hint">Obtenha no <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a></span>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Vozes Padrão</h3>
      <div class="form-row">
        <div class="form-group">
          <label>ID da Voz Masculina Padrão (ElevenLabs)</label>
          <input type="text" name="default_voice_male" value="<?= esc($settings['default_voice_male'] ?? '') ?>"/>
        </div>
        <div class="form-group">
          <label>ID da Voz Feminina Padrão (ElevenLabs)</label>
          <input type="text" name="default_voice_female" value="<?= esc($settings['default_voice_female'] ?? '') ?>"/>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Configurações de E-mail (SMTP)</h3>
      <div class="form-row">
        <div class="form-group">
          <label>SMTP Host</label>
          <input type="text" name="smtp_host" value="<?= esc($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com"/>
        </div>
        <div class="form-group">
          <label>SMTP Porta</label>
          <input type="number" name="smtp_port" value="<?= esc($settings['smtp_port'] ?? '587') ?>"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>SMTP Usuário</label>
          <input type="email" name="smtp_user" value="<?= esc($settings['smtp_user'] ?? '') ?>"/>
        </div>
        <div class="form-group">
          <label>SMTP Senha</label>
          <div class="input-secret">
            <input type="password" name="smtp_pass" id="smtpPass" placeholder="<?= !empty($settings['smtp_pass']) ? '••••• (configurada)' : 'Senha SMTP' ?>"/>
            <button type="button" onclick="toggleSecret('smtpPass')">👁</button>
          </div>
        </div>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">✓ Salvar Configurações</button>
    </div>
  </form>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>function toggleSecret(id){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';}</script>
<?= $this->endSection() ?>
