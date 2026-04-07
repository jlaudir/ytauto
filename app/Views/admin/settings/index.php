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
        <div class="form-group" style="grid-column:1/-1">
          <label>URL do MultiVozes Engine *</label>
          <input type="url" name="multivozes_base_url"
            value="<?= esc($settings['multivozes_base_url'] ?? 'http://localhost:5050') ?>"
            placeholder="http://localhost:5050"/>
          <span class="form-hint">
            URL onde o <a href="https://github.com/samucamg/multivozes_br_engine" target="_blank">MultiVozes BR Engine</a>
            está rodando. Ex: <code>http://localhost:5050</code> (local) ou <code>http://seu-servidor:5050</code> (remoto)
          </span>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>API Key do MultiVozes Engine</label>
          <div class="input-secret">
            <input type="password" name="multivozes_api_key" id="mvKey"
              placeholder="<?= !empty($settings['multivozes_api_key']) ? '••••••• (configurada)' : 'Cole a API_KEY do .env do engine' ?>"/>
            <button type="button" onclick="toggleSecret('mvKey')">👁</button>
          </div>
          <span class="form-hint">Definida no arquivo <code>.env</code> do MultiVozes BR Engine (variável <code>API_KEY</code>)</span>
        </div>
        <div class="form-group">
          <label>Modelo TTS</label>
          <select name="multivozes_model">
            <option value="tts-1" <?= ($settings['multivozes_model'] ?? 'tts-1') === 'tts-1' ? 'selected' : '' ?>>tts-1 (padrão — mais rápido)</option>
            <option value="tts-1-hd" <?= ($settings['multivozes_model'] ?? '') === 'tts-1-hd' ? 'selected' : '' ?>>tts-1-hd (maior qualidade)</option>
          </select>
          <span class="form-hint">O engine é 100% compatível com o padrão OpenAI TTS</span>
        </div>
      </div>
      <div class="form-row">
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
      <h3>💳 Asaas — Pagamentos PIX</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Asaas API Key</label>
          <div class="input-secret">
            <input type="password" name="asaas_api_key" id="asaasKey"
              placeholder="<?= !empty($settings['asaas_api_key']) ? '••••••• (configurada)' : '$aact_...' ?>"/>
            <button type="button" onclick="toggleSecret('asaasKey')">👁</button>
          </div>
          <span class="form-hint">
            Obtenha em <a href="https://www.asaas.com" target="_blank">asaas.com</a>
            → Configurações → Integrações → API Key
          </span>
        </div>
        <div class="form-group">
          <label>Modo de Operação</label>
          <select name="asaas_sandbox">
            <option value="1" <?= ($settings['asaas_sandbox'] ?? '1') === '1' ? 'selected' : '' ?>>
              🧪 Sandbox (testes)
            </option>
            <option value="0" <?= ($settings['asaas_sandbox'] ?? '1') === '0' ? 'selected' : '' ?>>
              🚀 Produção (real)
            </option>
          </select>
          <span class="form-hint">Use Sandbox para testar antes de cobrar de verdade</span>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group" style="grid-column:1/-1">
          <label>URL do Webhook Asaas</label>
          <input type="text" value="<?= base_url('payment/asaas/webhook') ?>" readonly style="background:var(--bg3);cursor:copy" onclick="navigator.clipboard.writeText(this.value);showToast('URL copiada!','success')" title="Clique para copiar"/>
          <span class="form-hint">Configure esta URL no painel Asaas → Configurações → Webhooks → Cobranças</span>
        </div>
      </div>
      <div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="testAsaas()">⚡ Testar Conexão Asaas</button>
        <span id="asaasTestResult" style="font-size:13px;margin-left:12px;color:var(--text3)"></span>
      </div>
    </div>

    <div class="form-section">
      <h3>Vozes Padrão (Edge TTS)</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Voz Masculina Padrão</label>
          <select name="default_voice_male">
            <?php
            $maleVoices = ['pt-BR-AntonioNeural'=>'Antônio','pt-BR-FabioNeural'=>'Fábio','pt-BR-HumbertoNeural'=>'Humberto','pt-BR-JulioNeural'=>'Júlio','pt-BR-NicolauNeural'=>'Nicolau','pt-BR-ValerioNeural'=>'Valério'];
            foreach ($maleVoices as $id => $label):
            ?>
            <option value="<?= $id ?>" <?= ($settings['default_voice_male'] ?? 'pt-BR-AntonioNeural') === $id ? 'selected' : '' ?>>
              <?= $label ?> (<?= $id ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Voz Feminina Padrão</label>
          <select name="default_voice_female">
            <?php
            $femaleVoices = ['pt-BR-FranciscaNeural'=>'Francisca','pt-BR-BrendaNeural'=>'Brenda','pt-BR-ElzaNeural'=>'Elza','pt-BR-GiovannaNeural'=>'Giovanna','pt-BR-LeticiaNeural'=>'Letícia','pt-BR-ManuelaNeural'=>'Manuela','pt-BR-ThalitaNeural'=>'Thalita','pt-BR-YaraNeural'=>'Yara'];
            foreach ($femaleVoices as $id => $label):
            ?>
            <option value="<?= $id ?>" <?= ($settings['default_voice_female'] ?? 'pt-BR-FranciscaNeural') === $id ? 'selected' : '' ?>>
              <?= $label ?> (<?= $id ?>)
            </option>
            <?php endforeach; ?>
          </select>
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
<script>
function toggleSecret(id){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';}
function testAsaas(){
  const el=document.getElementById('asaasTestResult');
  el.textContent='Testando...'; el.style.color='var(--text3)';
  fetch('/admin/settings/test-asaas',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(d=>{
      if(d.valid){el.textContent='✅ '+d.message+(d.sandbox?' [SANDBOX]':'');el.style.color='var(--green)';}
      else{el.textContent='❌ '+d.message;el.style.color='var(--red)';}
    }).catch(()=>{el.textContent='❌ Erro de rede';el.style.color='var(--red)';});
}
</script>
<?= $this->endSection() ?>
