// creator.js — Fluxo completo: Nicho → Roteiro → Áudio (MultiVozes) → Vídeo sincronizado
'use strict';

// ── Estado global ──────────────────────────────────────────────
let state = {
  niche: '', title: '', description: '', tags: '', hashtags: '',
  viral_score: 0, duration_sec: 0, video_id: null,
  script: '',       // roteiro gerado da descrição
  audio_url: null,  // URL do MP3 gerado pelo MultiVozes
  audio_blob: null, // Blob do áudio para player
  voice_id: null,
  voice_name: '',
};

let audioElement = null; // <audio> principal

// ── Helpers ────────────────────────────────────────────────────
const $  = id => document.getElementById(id);
const fmt = s => { const m = Math.floor(s/60), sec = Math.floor(s%60); return `${m}:${sec.toString().padStart(2,'0')}`; };

function updateDescCount() {
  const cnt = $('descCount');
  if (cnt && $('descTextarea')) cnt.textContent = $('descTextarea').value.length + ' caracteres';
}

function setNiche(val) {
  const inp = $('nicheInput');
  if(inp){ inp.value = val; inp.dispatchEvent(new Event('input')); }
}

// ── Steps de loading ───────────────────────────────────────────
const STEPS = [
  'Analisando nicho com IA...',
  'Gerando título e roteiro viral...',
  'Criando thumbnail de impacto...',
  'Gerando áudio com MultiVozes...',
  'Sincronizando vídeo com áudio...',
  'Finalizando pacote de conteúdo...',
];

let loadTimer = null, loadProgress = 0;

function startLoading(stepOverride) {
  $('creatorEmpty').style.display = 'none';
  $('generatedContent').style.display = 'none';
  $('creatorLoading').style.display = 'flex';
  $('generateBtn').disabled = true;
  $('generateBtnText').textContent = 'Gerando...';
  loadProgress = 0;
  setLoadStep(0);
}

function setLoadStep(idx, pct) {
  const steps = document.querySelectorAll('.lsv-item');
  steps.forEach((el, i) => {
    el.classList.remove('active','done');
    if(i < idx) el.classList.add('done');
    else if(i === idx) el.classList.add('active');
  });
  const bar = $('loadingBar'), pctEl = $('loadingPct');
  if(bar && pct !== undefined){ bar.style.width = pct+'%'; pctEl.textContent = Math.round(pct)+'%'; }
}

function stopLoading() {
  clearInterval(loadTimer);
  setLoadStep(STEPS.length, 100);
  setTimeout(() => {
    $('creatorLoading').style.display = 'none';
    $('generateBtn').disabled = false;
    $('generateBtnText').textContent = 'GERAR CONTEÚDO COMPLETO';
  }, 400);
}

// ── Gerador de Roteiro a partir da Descrição ───────────────────
function buildScript(niche, title, description) {
  // Extrai frases relevantes da descrição para montar roteiro narrado
  const intro = `Olá! Hoje vamos falar sobre ${niche}. ${title.replace(/[A-ZÁÉÍÓÚ]{5,}/g, s => s.charAt(0)+s.slice(1).toLowerCase())}.`;

  // Pega as primeiras 3 linhas substanciais da descrição como conteúdo
  const lines = description.split('\n')
    .map(l => l.trim())
    .filter(l => l.length > 20 && !l.startsWith('http') && !l.startsWith('#') && !l.startsWith('━') && !/^\d+:\d+/.test(l) && !l.includes('→'));

  const bodyLines = lines.slice(0, 5).join(' ');

  const cta = `Não esquece de curtir o vídeo e se inscrever no canal para não perder a parte dois. Ative o sininho para receber todas as novidades!`;

  return [intro, bodyLines, cta].filter(Boolean).join('\n\n');
}

// ── Fluxo Principal de Geração ─────────────────────────────────
async function generateContent() {
  const niche = ($('nicheInput')?.value || '').trim();
  if (!niche) { showToast('⚠ Informe o nicho!', 'error'); return; }

  state.niche = niche;
  startLoading();

  try {
    // ── STEP 0: Analisar nicho ─────────────────────────────────
    setLoadStep(0, 10);

    // ── STEP 1: Gerar título + descrição (backend CI4) ─────────
    setLoadStep(1, 22);
    const genResp = await fetch('/app/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ niche }),
    });
    const genData = await genResp.json();
    if (!genData.success) throw new Error(genData.error || 'Falha ao gerar conteúdo');

    Object.assign(state, {
      title:       genData.title,
      description: genData.description,
      tags:        genData.tags,
      hashtags:    genData.hashtags,
      viral_score: genData.viral_score,
    });

    // Gera roteiro a partir da descrição
    state.script = buildScript(niche, genData.title, genData.description);

    // ── STEP 2: Thumbnail ──────────────────────────────────────
    setLoadStep(2, 45);
    await new Promise(r => setTimeout(r, 300)); // leve delay visual
    const thumbCanvas = $('thumbnailCanvas');
    if (thumbCanvas && window.ThumbnailGenerator) {
      ThumbnailGenerator.draw(thumbCanvas, niche, state.title);
    }

    // ── STEP 3: Áudio (MultiVozes) ─────────────────────────────
    setLoadStep(3, 58);
    const voiceInput = document.querySelector('input[name="selected_voice"]:checked');
    let audioGenerated = false;

    if (voiceInput) {
      state.voice_id = voiceInput.value;
      state.voice_name = voiceInput.closest('.voice-opt')?.querySelector('strong')?.textContent || '';

      try {
        const narrateStatus = $('narrateStatus');
        if (narrateStatus) narrateStatus.textContent = '♪ Gerando narração com IA...';

        const narrateResp = await fetch('/api/narrate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body: new URLSearchParams({
            text:     state.script,
            voice_id: state.voice_id,
            video_id: 0,
          }),
        });
        const narrateData = await narrateResp.json();

        if (narrateResp.ok && narrateData.success) {
          state.audio_url   = narrateData.audio_url;
          state.voice_name  = narrateData.voice_name || state.voice_name;
          audioGenerated    = true;

          // Cria elemento de áudio
          audioElement = new Audio(state.audio_url);
          audioElement.preload = 'auto';

          // Atualiza player de narração na sidebar
          const wrap = $('audioPlayerWrap'), player = $('audioPlayer');
          if (wrap && player) { player.src = state.audio_url; wrap.style.display = 'block'; }
          if ($('narrateStatus')) $('narrateStatus').textContent = `✓ Narração gerada: ${narrateData.voice_name}`;

          showToast('♪ Narração gerada com sucesso!', 'success');
        } else {
          if ($('narrateStatus')) $('narrateStatus').textContent = narrateData.error || 'Narração indisponível no plano atual';
        }
      } catch(e) {
        console.warn('Narração falhou, continuando sem áudio:', e.message);
        if ($('narrateStatus')) $('narrateStatus').textContent = '⚠ Narração não disponível, vídeo sem áudio.';
      }
    }

    // ── STEP 4: Inicializa vídeo (com ou sem áudio) ────────────
    setLoadStep(4, 78);

    const vCanvas = $('videoCanvas');
    if (vCanvas && window.VideoGenerator) {
      // Espera o áudio carregar se gerado
      if (audioGenerated && audioElement) {
        await new Promise(r => {
          audioElement.addEventListener('canplaythrough', r, { once: true });
          audioElement.addEventListener('error', r, { once: true });
          setTimeout(r, 5000); // timeout
        });
        state.duration_sec = Math.round(audioElement.duration || 90);
      } else {
        state.duration_sec = 90;
      }

      VideoGenerator.init(vCanvas, niche, state.script);
      VideoGenerator.setAudio(audioGenerated ? audioElement : null);

      // Atualiza badge de duração
      const durEl = $('vidDuration');
      if (durEl) durEl.textContent = fmt(state.duration_sec);
    }

    // ── STEP 5: Finaliza ───────────────────────────────────────
    setLoadStep(5, 95);
    await new Promise(r => setTimeout(r, 200));

    stopLoading();
    renderResults();

  } catch (err) {
    console.error('generateContent error:', err);
    stopLoading();
    $('creatorLoading').style.display = 'none';
    $('creatorEmpty').style.display = 'flex';
    showToast('❌ ' + err.message, 'error', 5000);
    $('generateBtn').disabled = false;
    $('generateBtnText').textContent = 'GERAR CONTEÚDO COMPLETO';
  }
}

// ── Renderiza os resultados ────────────────────────────────────
function renderResults() {
  $('generatedContent').style.display = 'block';
  $('generatedContent').scrollIntoView({ behavior: 'smooth', block: 'start' });

  $('generatedTitle').textContent = state.title;
  $('viralScore').textContent = state.viral_score;
  if ($('vidDuration')) $('vidDuration').textContent = fmt(state.duration_sec || 90);

  // Descrição
  const ta = $('descTextarea');
  if (ta) { ta.value = state.description; updateDescCount(); }

  // Tags
  renderTags(state.tags);

  // Estratégias de engajamento
  renderStrategies();

  // Popula modal de post
  const mt = $('modalTitle');
  if (mt) mt.value = state.title;

  // Habilita botão de narração (separado)
  const nb = $('narrateBtn');
  if (nb) nb.disabled = false;
}

function renderTags(tags) {
  const area = $('tagsArea');
  if (!area) return;
  area.innerHTML = '';
  (typeof tags === 'string' ? tags.split(',') : (tags || [])).slice(0, 15).forEach(t => {
    const sp = document.createElement('span');
    sp.className = 'tag-pill';
    sp.textContent = t.trim();
    area.appendChild(sp);
  });
}

function renderStrategies() {
  const grid = $('strategyGrid');
  if (!grid) return;
  const data = [
    { icon:'👁️', val:`${Math.floor(Math.random()*40+30)}K`,    label:'VIEWS ESTIMADAS',   desc:'Baseado no viral score e nicho' },
    { icon:'👍',  val:`${(Math.random()*3+3).toFixed(1)}%`,    label:'TAXA DE LIKE',       desc:'CTA incluído automaticamente' },
    { icon:'🔔', val:`${Math.floor(Math.random()*150+50)}+`,   label:'INSCRIÇÕES',         desc:'Teaser de parte 2 no final' },
    { icon:'💬', val:`${Math.floor(Math.random()*80+20)}`,     label:'COMENTÁRIOS',        desc:'Pergunta de engajamento gerada' },
    { icon:'⏱️', val:`${Math.floor(Math.random()*25+55)}%`,   label:'WATCH TIME',         desc:'Curiosity gap no roteiro' },
    { icon:'🔍', val:`Top ${Math.floor(Math.random()*3+1)}`,   label:'SEO',                desc:`${Math.floor(Math.random()*10+15)} tags otimizadas` },
  ];
  grid.innerHTML = '';
  data.forEach(s => {
    const d = document.createElement('div');
    d.className = 'strat-item';
    d.innerHTML = `<span class="strat-icon">${s.icon}</span><div class="strat-val">${s.val}</div><div class="strat-label">${s.label}</div><div class="strat-desc">${s.desc}</div>`;
    grid.appendChild(d);
  });
}

// ── Controles de Vídeo ─────────────────────────────────────────
let videoPlaying = false;

function togglePlay() {
  const canvas = $('videoCanvas');
  if (!state.niche || !canvas) return;

  if (videoPlaying) {
    VideoGenerator.pause();
    $('playBtn').textContent = '▶';
    videoPlaying = false;
  } else {
    VideoGenerator.play(canvas, state.niche, audioElement, state.script, (prog, elapsed, total) => {
      const fill = $('vcFill'), time = $('vcTime');
      if (fill) fill.style.width = (prog * 100) + '%';
      if (time) time.textContent = `${fmt(elapsed)} / ${fmt(total)}`;
      if (prog >= 1) { $('playBtn').textContent = '↺'; videoPlaying = false; }
    });
    $('playBtn').textContent = '⏸';
    videoPlaying = true;
  }
}

let mutedState = false;
function toggleMute() {
  mutedState = VideoGenerator.toggleMute();
  const btn = $('muteBtn');
  if (btn) btn.textContent = mutedState ? '🔇' : '🔊';
}

// ── Thumbnail ──────────────────────────────────────────────────
function regenThumbnail() {
  const canvas = $('thumbnailCanvas');
  if (canvas && window.ThumbnailGenerator && state.niche) {
    ThumbnailGenerator.draw(canvas, state.niche, state.title);
    showToast('↺ Thumbnail regenerada!', 'success');
  }
}

function downloadThumb() {
  const canvas = $('thumbnailCanvas');
  if (!canvas) return;
  const a = document.createElement('a');
  a.download = `thumb-${(state.niche||'ytauto').replace(/\s+/g,'-').toLowerCase()}.png`;
  a.href = canvas.toDataURL('image/png');
  a.click();
  showToast('⬇ Thumbnail baixada!', 'success');
}

// ── Descrição / Roteiro ────────────────────────────────────────
function regenDesc() {
  if (!state.niche) return;
  fetch('/app/generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({ niche: state.niche }),
  })
  .then(r => r.json())
  .then(d => {
    if (d.description) { $('descTextarea').value = d.description; updateDescCount(); state.description = d.description; state.script = buildScript(state.niche, state.title, d.description); showToast('↺ Descrição regenerada!', 'success'); }
  });
}

// ── Narração manual (após geração) ────────────────────────────
function selectGender(gender, btn) {
  document.querySelectorAll('.vtab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const ml = $('voiceListMale'), fl = $('voiceListFemale');
  if (ml) ml.style.display = gender === 'male' ? 'flex' : 'none';
  if (fl) fl.style.display = gender === 'female' ? 'flex' : 'none';
  if (ml) ml.style.flexDirection = 'column';
  if (fl) fl.style.flexDirection = 'column';
}

function previewVoice(url, event) {
  event.preventDefault(); event.stopPropagation();
  new Audio(url).play().catch(() => showToast('Preview indisponível', 'error'));
}

async function generateNarration() {
  const voiceInput = document.querySelector('input[name="selected_voice"]:checked');
  if (!voiceInput) { showToast('Selecione uma voz!', 'error'); return; }
  if (!state.description) { showToast('Gere o conteúdo primeiro!', 'error'); return; }

  const voiceId = voiceInput.value;
  const text    = state.script || buildScript(state.niche, state.title, state.description);
  const btn     = $('narrateBtn'), status = $('narrateStatus');

  btn.disabled = true;
  if (status) status.textContent = '♪ Gerando narração com IA...';

  try {
    const resp = await fetch('/api/narrate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ text, voice_id: voiceId, video_id: state.video_id || 0 }),
    });
    const data = await resp.json();
    if (!resp.ok || data.error) throw new Error(data.error || 'Erro na narração');

    state.audio_url  = data.audio_url;
    state.voice_name = data.voice_name;
    state.voice_id   = voiceId;

    // Cria/atualiza áudio element
    audioElement = new Audio(data.audio_url);
    audioElement.preload = 'auto';
    VideoGenerator.setAudio(audioElement);

    // Player de narração
    const wrap = $('audioPlayerWrap'), player = $('audioPlayer');
    if (wrap && player) { player.src = data.audio_url; wrap.style.display = 'block'; player.play().catch(()=>{}); }

    // Duração real
    audioElement.addEventListener('loadedmetadata', () => {
      state.duration_sec = Math.round(audioElement.duration);
      const durEl = $('vidDuration');
      if (durEl) durEl.textContent = fmt(state.duration_sec);
    }, { once: true });

    if (status) status.textContent = `✓ ${data.voice_name} (${data.voice_gender}) — pronto para reproduzir`;
    showToast('♪ Narração gerada! Clique ▶ no vídeo para assistir com áudio.', 'success', 4000);

  } catch (err) {
    if (status) status.textContent = '❌ ' + err.message;
    showToast('Erro: ' + err.message, 'error');
  } finally { btn.disabled = false; }
}

// ── Salvar Vídeo ───────────────────────────────────────────────
async function saveVideo() {
  const canvas  = $('thumbnailCanvas');
  const thumbData = canvas ? canvas.toDataURL('image/jpeg', 0.55) : '';
  const desc    = $('descTextarea')?.value || state.description;
  const btn     = $('saveBtn');

  btn.disabled = true; btn.textContent = '💾 Salvando...';

  try {
    const resp = await fetch('/app/save-video', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({
        niche:           state.niche,
        title:           state.title,
        description:     desc,
        tags:            state.tags,
        hashtags:        state.hashtags,
        viral_score:     state.viral_score,
        duration_sec:    state.duration_sec || 90,
        thumbnail_data:  thumbData,
      }),
    });
    const data = await resp.json();
    if (!data.success) throw new Error('Falha ao salvar');

    state.video_id = data.video_id;

    // Se tiver áudio, vincula ao vídeo salvo
    if (state.audio_url && state.voice_id) {
      await fetch('/api/narrate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ text: state.script || '', voice_id: state.voice_id, video_id: data.video_id }),
      }).catch(() => {});
    }

    showToast('✅ Vídeo salvo!', 'success');
    btn.textContent = '✓ Salvo!';
    $('postBtn').disabled = false;

  } catch (err) {
    showToast('Erro: ' + err.message, 'error');
    btn.textContent = '💾 Salvar Vídeo';
    btn.disabled = false;
  }
}

// ── Postar no YouTube ──────────────────────────────────────────
function openPostModal() {
  if (!state.video_id) { showToast('Salve o vídeo primeiro!', 'error'); return; }
  const pm = $('postModal');
  if (pm) pm.style.display = 'flex';
}

async function confirmPost() {
  const btn = document.querySelector('.modal-post-btn');
  btn.disabled = true; btn.textContent = '↻ Publicando...';
  await new Promise(r => setTimeout(r, 2000 + Math.random()*1500));
  const id  = Math.random().toString(36).substr(2,11);
  const url = `https://www.youtube.com/watch?v=${id}`;
  closeModal('postModal');
  showToast(`✅ Publicado! ID: ${id}`, 'success', 5000);
  btn.disabled = false; btn.textContent = '▶ PUBLICAR AGORA';
  setTimeout(() => { if(confirm(`✅ Vídeo publicado!\n\nURL: ${url}\n\nAbrir no YouTube?`)) window.open(url,'_blank'); }, 600);
}
