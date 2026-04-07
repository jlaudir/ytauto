<?= $this->extend('layouts/client') ?>
<?= $this->section('head') ?>
<style>
.creator-layout{display:grid;grid-template-columns:420px 1fr;gap:24px;align-items:start}
@media(max-width:1100px){.creator-layout{grid-template-columns:1fr}}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="client-page">
  <div class="page-hero compact">
    <div>
      <h1>⚡ Criar Vídeo</h1>
      <p>Informe o nicho, gere o conteúdo e narre com IA em segundos.</p>
    </div>
    <?php if ($max_videos > 0): ?>
    <div class="usage-chip">
      <span><?= $usage ?>/<?= $max_videos ?> vídeos este mês</span>
    </div>
    <?php endif; ?>
  </div>

  <div class="creator-layout">
    <!-- ── LEFT: Input Panel ── -->
    <div class="creator-panel">
      <div class="cp-card">
        <h3 class="cp-title">1. Defina o Nicho</h3>
        <div class="input-wrap-big">
          <input type="text" id="nicheInput" class="niche-input-big"
            placeholder="ex: finanças pessoais, tecnologia, fitness..."
            maxlength="80"/>
          <div class="quick-tags-mini">
            <?php foreach (['IA & Tech','Finanças','Fitness','Games','Culinária','Negócios','Viagens','Ciência'] as $i => $t): ?>
            <?php $niches=['Tecnologia e IA','Finanças e Investimentos','Fitness e Academia','Games e Entretenimento','Culinária e Receitas','Empreendedorismo','Viagens e Turismo','Ciência e Curiosidades'][$i] ?>
            <button class="qtm-btn" onclick="setNiche('<?= $niches ?>')"><?= $t ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <button class="generate-main-btn" id="generateBtn" onclick="generateContent()">
          <span class="gmb-icon">✦</span>
          <span id="generateBtnText">GERAR CONTEÚDO COMPLETO</span>
        </button>
      </div>

      <!-- Voice Selection -->
      <div class="cp-card" id="voiceCard">
        <h3 class="cp-title">2. Escolha a Voz da Narração</h3>

        <?php
        $hasNarrate = false;
        if (session()->get('plan_id')) {
            $db = \Config\Database::connect();
            $hasNarrate = $db->from('plan_permissions pp')
                ->join('permissions p','p.id=pp.permission_id')
                ->where('pp.plan_id', session()->get('plan_id'))
                ->where('p.key','videos.narrate')
                ->countAllResults() > 0;
        }
        ?>

        <?php if (!$hasNarrate): ?>
        <div class="upgrade-banner">
          <span>🔒</span>
          <div>
            <strong>Narração com IA não inclusa no seu plano</strong>
            <span>Faça upgrade para o plano Pro e ative narrações em português com ElevenLabs.</span>
          </div>
        </div>
        <?php else: ?>

        <div class="voice-tabs">
          <button class="vtab active" onclick="selectGender('male',this)">♂ Masculino</button>
          <button class="vtab" onclick="selectGender('female',this)">♀ Feminino</button>
        </div>

        <div class="voice-list" id="voiceListMale">
          <?php foreach ($voices_male as $v): ?>
          <label class="voice-opt" for="vm_<?= $v['id'] ?>">
            <input type="radio" name="selected_voice" id="vm_<?= $v['id'] ?>"
              value="<?= esc($v['elevenlabs_id']) ?>" data-name="<?= esc($v['name']) ?>"/>
            <div class="vo-icon">♂</div>
            <div class="vo-info">
              <strong><?= esc($v['name']) ?></strong>
              <span>Masculino · Português</span>
            </div>
            <?php if ($v['preview_url']): ?>
            <button type="button" class="vo-preview" onclick="previewVoice('<?= esc($v['preview_url']) ?>',event)">▶</button>
            <?php endif; ?>
          </label>
          <?php endforeach; ?>
        </div>

        <div class="voice-list" id="voiceListFemale" style="display:none">
          <?php foreach ($voices_female as $v): ?>
          <label class="voice-opt" for="vf_<?= $v['id'] ?>">
            <input type="radio" name="selected_voice" id="vf_<?= $v['id'] ?>"
              value="<?= esc($v['elevenlabs_id']) ?>" data-name="<?= esc($v['name']) ?>"/>
            <div class="vo-icon" style="color:var(--blue)">♀</div>
            <div class="vo-info">
              <strong><?= esc($v['name']) ?></strong>
              <span>Feminino · Português</span>
            </div>
            <?php if ($v['preview_url']): ?>
            <button type="button" class="vo-preview" onclick="previewVoice('<?= esc($v['preview_url']) ?>',event)">▶</button>
            <?php endif; ?>
          </label>
          <?php endforeach; ?>
        </div>

        <button class="narrate-btn" id="narrateBtn" onclick="generateNarration()" disabled>
          <span>♪</span> Gerar Narração com IA
        </button>
        <div class="narrate-status" id="narrateStatus"></div>
        <div id="audioPlayerWrap" style="display:none">
          <audio id="audioPlayer" controls style="width:100%;margin-top:12px;border-radius:8px"></audio>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── RIGHT: Results ── -->
    <div id="resultsArea">
      <!-- Empty state -->
      <div class="creator-empty" id="creatorEmpty">
        <div class="ce-orb">
          <div class="ce-ring r1"></div>
          <div class="ce-ring r2"></div>
          <div class="ce-ring r3"></div>
          <span>▶</span>
        </div>
        <p>Informe o nicho e clique em <strong>Gerar Conteúdo</strong></p>
      </div>

      <!-- Loading -->
      <div class="creator-loading" id="creatorLoading" style="display:none">
        <div class="loading-steps-vert" id="loadingStepsVert">
          <?php foreach (['Analisando nicho...','Gerando título viral...','Criando thumbnail...','Montando vídeo...','Otimizando SEO...'] as $i => $step): ?>
          <div class="lsv-item" id="lsv<?= $i ?>">
            <div class="lsv-dot"></div>
            <span><?= $step ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="loading-bar-wrap"><div class="loading-bar" id="loadingBar"></div></div>
        <div class="loading-pct" id="loadingPct">0%</div>
      </div>

      <!-- Generated Content -->
      <div id="generatedContent" style="display:none">
        <!-- Title Banner -->
        <div class="result-title-card">
          <div class="rtc-score" id="viralScore">—</div>
          <div class="rtc-content">
            <div class="rtc-label">TÍTULO VIRAL GERADO</div>
            <h2 class="rtc-title" id="generatedTitle">—</h2>
          </div>
          <button class="btn-icon-sm" onclick="copyText('generatedTitle','Título copiado!')" title="Copiar">⎘</button>
        </div>

        <!-- Thumbnail + Video side by side -->
        <div class="media-grid">
          <div class="media-card">
            <div class="mc-header"><span>🖼</span> Thumbnail <span class="mc-badge">1280×720</span></div>
            <div class="thumb-wrap">
              <canvas id="thumbnailCanvas" width="1280" height="720" class="thumb-canvas"></canvas>
            </div>
            <div class="mc-actions">
              <button class="btn-sm-secondary" onclick="regenThumbnail()">↺ Regenerar</button>
              <button class="btn-sm-primary" onclick="downloadThumb()">⬇ Baixar PNG</button>
            </div>
          </div>

          <div class="media-card">
            <div class="mc-header"><span>▶</span> Pré-visualização <span class="mc-badge" id="vidDuration">—</span></div>
            <div class="video-wrap">
              <canvas id="videoCanvas" width="640" height="360" class="video-canvas"></canvas>
              <div class="video-ctl">
                <button id="playBtn" onclick="togglePlay()">▶</button>
                <div class="vc-prog"><div class="vc-fill" id="vcFill"></div></div>
                <span id="vcTime">0:00 / 0:00</span>
                <button id="muteBtn" onclick="toggleMute()">🔊</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="desc-card">
          <div class="dc-header-row">
            <h3>📝 Descrição SEO Otimizada</h3>
            <div style="display:flex;gap:8px">
              <button class="btn-sm-secondary" onclick="copyText('descTextarea','Descrição copiada!')">⎘ Copiar</button>
              <button class="btn-sm-secondary" onclick="regenDesc()">↺ Regenerar</button>
            </div>
          </div>
          <textarea id="descTextarea" class="desc-textarea" rows="14" oninput="updateDescCount()"></textarea>
          <div class="desc-footer">
            <span id="descCount">0 caracteres</span>
            <div class="tags-area" id="tagsArea"></div>
          </div>
        </div>

        <!-- Engagement Strategies -->
        <div class="strategy-card" id="strategyCard">
          <h3>📊 Estratégia de Engajamento Aplicada</h3>
          <div class="strategy-grid" id="strategyGrid"></div>
        </div>

        <!-- Save Actions -->
        <div class="save-bar">
          <button class="save-btn" id="saveBtn" onclick="saveVideo()">
            💾 Salvar Vídeo
          </button>
          <button class="post-btn" id="postBtn" onclick="openPostModal()" disabled>
            ▶ Postar no YouTube
          </button>
          <span class="save-note">* Salve primeiro para habilitar todas as funções</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Post Modal -->
<div class="modal-overlay" id="postModal" style="display:none">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('postModal')">✕</button>
    <div class="modal-icon">▶</div>
    <h3>Publicar no YouTube</h3>
    <p>Revisão final antes de publicar</p>
    <div class="modal-form">
      <div class="form-group"><label>Título</label><input type="text" id="modalTitle"/></div>
      <div class="form-group"><label>Visibilidade</label>
        <select id="modalVis"><option value="public">Público</option><option value="unlisted">Não listado</option><option value="private">Privado</option></select>
      </div>
      <label class="checkbox-label"><input type="checkbox" id="notifySubscribers" checked> Notificar inscritos</label>
    </div>
    <button class="modal-post-btn" onclick="confirmPost()">▶ PUBLICAR AGORA</button>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<!-- Inline generator scripts (same as standalone but tied to CI4 backend) -->
<script src="<?= base_url('js/thumbnail.js') ?>"></script>
<script src="<?= base_url('js/videoGen.js') ?>"></script>
<script src="<?= base_url('js/creator.js') ?>"></script>
<?= $this->endSection() ?>
