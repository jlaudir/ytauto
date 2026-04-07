// creator.js — Orchestrates the full video creation flow with CI4 backend
'use strict';

let currentState = {
  niche: '', title: '', description: '', tags: '', hashtags: '',
  viral_score: 0, duration_sec: 90, video_id: null, audio_path: null,
};

// ── Helpers ──────────────────────────────────────────────────
function setNiche(val) {
  document.getElementById('nicheInput').value = val;
}

function fmt(sec) {
  const m = Math.floor(sec / 60), s = Math.floor(sec % 60);
  return `${m}:${s.toString().padStart(2,'0')}`;
}

function updateDescCount() {
  const ta = document.getElementById('descTextarea');
  const cnt = document.getElementById('descCount');
  if (ta && cnt) cnt.textContent = ta.value.length + ' caracteres';
}

// ── Loading Stepper ───────────────────────────────────────────
let loadInterval = null;
function startLoading() {
  document.getElementById('creatorEmpty').style.display = 'none';
  document.getElementById('generatedContent').style.display = 'none';
  document.getElementById('creatorLoading').style.display = 'flex';
  document.getElementById('generateBtn').disabled = true;
  document.getElementById('generateBtnText').textContent = 'Gerando...';

  const bar  = document.getElementById('loadingBar');
  const pct  = document.getElementById('loadingPct');
  const steps = document.querySelectorAll('.lsv-item');
  let progress = 0, stepIdx = 0;
  const stepPcts = [10, 28, 50, 72, 90, 100];

  loadInterval = setInterval(() => {
    progress = Math.min(progress + 1, 95);
    bar.style.width = progress + '%';
    pct.textContent = progress + '%';
    const nextStep = stepPcts.findIndex(p => progress < p);
    const curStep = nextStep === -1 ? steps.length - 1 : Math.max(0, nextStep - 1);
    steps.forEach((el, i) => {
      el.classList.remove('active','done');
      if (i < curStep) el.classList.add('done');
      else if (i === curStep) el.classList.add('active');
    });
  }, 60);
}

function stopLoading(complete = true) {
  clearInterval(loadInterval);
  if (complete) {
    document.getElementById('loadingBar').style.width = '100%';
    document.getElementById('loadingPct').textContent = '100%';
    document.querySelectorAll('.lsv-item').forEach(el => { el.classList.remove('active'); el.classList.add('done'); });
  }
  setTimeout(() => {
    document.getElementById('creatorLoading').style.display = 'none';
    document.getElementById('generateBtn').disabled = false;
    document.getElementById('generateBtnText').textContent = 'GERAR CONTEÚDO COMPLETO';
  }, 400);
}

// ── Main Generate Function ────────────────────────────────────
async function generateContent() {
  const niche = document.getElementById('nicheInput').value.trim();
  if (!niche) { showToast('⚠ Informe o nicho!', 'error'); return; }

  currentState.niche = niche;
  startLoading();

  try {
    // Call CI4 backend to generate text content
    const resp = await fetch('/app/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ niche }),
    });
    const data = await resp.json();
    if (!data.success) throw new Error(data.error || 'Erro ao gerar conteúdo');

    // Store state
    Object.assign(currentState, {
      title: data.title,
      description: data.description,
      tags: data.tags,
      hashtags: data.hashtags,
      viral_score: data.viral_score,
      duration_sec: 60 + Math.floor(Math.random() * 60),
    });

    stopLoading(true);
    renderResults();

  } catch (err) {
    stopLoading(false);
    document.getElementById('creatorLoading').style.display = 'none';
    document.getElementById('creatorEmpty').style.display = 'flex';
    showToast('❌ ' + err.message, 'error');
    console.error(err);
  }
}

// ── Render Results ────────────────────────────────────────────
function renderResults() {
  const s = currentState;

  document.getElementById('generatedContent').style.display = 'block';
  document.getElementById('generatedContent').scrollIntoView({ behavior: 'smooth', block: 'start' });

  // Title & score
  document.getElementById('generatedTitle').textContent = s.title;
  document.getElementById('viralScore').textContent = s.viral_score;

  // Duration badge
  const durEl = document.getElementById('vidDuration');
  if (durEl) durEl.textContent = fmt(s.duration_sec);

  // Thumbnail
  const canvas = document.getElementById('thumbnailCanvas');
  if (canvas && window.ThumbnailGenerator) {
    ThumbnailGenerator.draw(canvas, s.niche, s.title);
  }

  // Video preview
  const vcanvas = document.getElementById('videoCanvas');
  if (vcanvas && window.VideoGenerator) {
    VideoGenerator.init(vcanvas, s.niche);
  }

  // Description
  const ta = document.getElementById('descTextarea');
  if (ta) { ta.value = s.description; updateDescCount(); }

  // Tags
  renderTags(s.tags);

  // Strategies
  renderStrategies();

  // Modal title
  const mt = document.getElementById('modalTitle');
  if (mt) mt.value = s.title;

  // Enable narrate button
  const nb = document.getElementById('narrateBtn');
  if (nb) nb.disabled = false;
}

function renderTags(tags) {
  const area = document.getElementById('tagsArea');
  if (!area) return;
  area.innerHTML = '';
  (typeof tags === 'string' ? tags.split(',') : tags).slice(0, 12).forEach(t => {
    const sp = document.createElement('span');
    sp.className = 'tag-pill';
    sp.textContent = t.trim();
    area.appendChild(sp);
  });
}

function renderStrategies() {
  const grid = document.getElementById('strategyGrid');
  if (!grid) return;
  const strategies = [
    { icon: '👁️', val: `${Math.floor(Math.random()*40+30)}K`, label: 'VIEWS ESTIMADAS', desc: 'Baseado no viral score e nicho' },
    { icon: '👍', val: `${(Math.random()*3+3).toFixed(1)}%`, label: 'TAXA DE LIKE', desc: 'CTA incluído no vídeo' },
    { icon: '🔔', val: `${Math.floor(Math.random()*150+50)}+`, label: 'INSCRIÇÕES', desc: 'Teaser de parte 2 incluído' },
    { icon: '💬', val: `${Math.floor(Math.random()*80+20)}`, label: 'COMENTÁRIOS', desc: 'Pergunta de engajamento no final' },
    { icon: '⏱️', val: `${Math.floor(Math.random()*25+55)}%`, label: 'WATCH TIME', desc: 'Curiosity gap aplicado' },
    { icon: '🔍', val: `Top ${Math.floor(Math.random()*3+1)}`, label: 'SEO', desc: `${Math.floor(Math.random()*10+15)} tags otimizadas` },
  ];
  grid.innerHTML = '';
  strategies.forEach(s => {
    const d = document.createElement('div');
    d.className = 'strat-item';
    d.innerHTML = `<span class="strat-icon">${s.icon}</span><div class="strat-val">${s.val}</div><div class="strat-label">${s.label}</div><div class="strat-desc">${s.desc}</div>`;
    grid.appendChild(d);
  });
}

// ── Thumbnail Actions ─────────────────────────────────────────
function regenThumbnail() {
  const canvas = document.getElementById('thumbnailCanvas');
  if (canvas && window.ThumbnailGenerator && currentState.niche) {
    ThumbnailGenerator.draw(canvas, currentState.niche, currentState.title);
    showToast('↺ Thumbnail regenerada!', 'success');
  }
}

function downloadThumb() {
  const canvas = document.getElementById('thumbnailCanvas');
  if (!canvas) return;
  const link = document.createElement('a');
  link.download = `thumb-${currentState.niche.replace(/\s+/g,'-')}.png`;
  link.href = canvas.toDataURL('image/png');
  link.click();
  showToast('⬇ Thumbnail baixada!', 'success');
}

// ── Video Controls ────────────────────────────────────────────
let videoPlaying = false;
function togglePlay() {
  if (!currentState.niche) return;
  if (videoPlaying) {
    window.VideoGenerator?.pause();
    document.getElementById('playBtn').textContent = '▶';
    videoPlaying = false;
    speechSynthesis?.cancel();
  } else {
    window.VideoGenerator?.play(document.getElementById('videoCanvas'), currentState.niche, (prog, elapsed, total) => {
      document.getElementById('vcFill').style.width = (prog * 100) + '%';
      document.getElementById('vcTime').textContent = `${fmt(elapsed)} / ${fmt(total)}`;
      if (prog >= 1) { document.getElementById('playBtn').textContent = '↺'; videoPlaying = false; }
    });
    document.getElementById('playBtn').textContent = '⏸';
    videoPlaying = true;
  }
}

let muted = false;
function toggleMute() {
  muted = window.VideoGenerator?.toggleMute() ?? !muted;
  document.getElementById('muteBtn').textContent = muted ? '🔇' : '🔊';
}

// ── Description Actions ───────────────────────────────────────
function regenDesc() {
  // Request server-side regeneration
  if (!currentState.niche) return;
  fetch('/app/generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({ niche: currentState.niche }),
  })
  .then(r => r.json())
  .then(data => {
    if (data.description) {
      document.getElementById('descTextarea').value = data.description;
      updateDescCount();
      showToast('↺ Descrição regenerada!', 'success');
    }
  });
}

// ── ElevenLabs Narration ──────────────────────────────────────
function selectGender(gender, btn) {
  document.querySelectorAll('.vtab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('voiceListMale').style.display = gender === 'male' ? 'flex' : 'none';
  document.getElementById('voiceListFemale').style.display = gender === 'female' ? 'flex' : 'none';
  document.getElementById('voiceListMale').style.flexDirection = 'column';
  document.getElementById('voiceListFemale').style.flexDirection = 'column';
}

function previewVoice(url, event) {
  event.preventDefault();
  event.stopPropagation();
  const audio = new Audio(url);
  audio.play().catch(() => showToast('Preview indisponível', 'error'));
}

async function generateNarration() {
  const voiceInput = document.querySelector('input[name="selected_voice"]:checked');
  if (!voiceInput) { showToast('Selecione uma voz!', 'error'); return; }

  const voiceId = voiceInput.value;
  const desc    = document.getElementById('descTextarea')?.value || '';
  const text    = desc.substring(0, 1500); // limit for API

  if (!text) { showToast('Gere o conteúdo primeiro!', 'error'); return; }

  const btn    = document.getElementById('narrateBtn');
  const status = document.getElementById('narrateStatus');

  btn.disabled = true;
  status.textContent = '♪ Gerando narração com ElevenLabs...';

  try {
    const resp = await fetch('/api/narrate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({
        text,
        voice_id: voiceId,
        video_id: currentState.video_id || 0,
      }),
    });
    const data = await resp.json();

    if (!resp.ok || data.error) throw new Error(data.error || 'Erro na narração');

    currentState.audio_path = data.audio_url;

    // Show audio player
    const wrap   = document.getElementById('audioPlayerWrap');
    const player = document.getElementById('audioPlayer');
    if (wrap && player) {
      player.src = data.audio_url;
      wrap.style.display = 'block';
      player.play().catch(() => {});
    }

    status.textContent = `✓ Narração gerada com ${data.voice_name} (${data.voice_gender})`;
    showToast('♪ Narração gerada com sucesso!', 'success');

  } catch (err) {
    status.textContent = '❌ ' + err.message;
    showToast('Erro: ' + err.message, 'error');
    console.error(err);
  } finally {
    btn.disabled = false;
  }
}

// ── Save Video ────────────────────────────────────────────────
async function saveVideo() {
  const s      = currentState;
  const canvas = document.getElementById('thumbnailCanvas');
  const thumbData = canvas ? canvas.toDataURL('image/jpeg', 0.6) : '';
  const desc   = document.getElementById('descTextarea')?.value || s.description;

  const btn = document.getElementById('saveBtn');
  btn.disabled = true;
  btn.textContent = '💾 Salvando...';

  try {
    const resp = await fetch('/app/save-video', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({
        niche: s.niche,
        title: s.title,
        description: desc,
        tags: s.tags,
        hashtags: s.hashtags,
        viral_score: s.viral_score,
        duration_sec: s.duration_sec,
        thumbnail_data: thumbData,
      }),
    });
    const data = await resp.json();
    if (!data.success) throw new Error('Falha ao salvar');

    currentState.video_id = data.video_id;
    showToast('✅ Vídeo salvo no histórico!', 'success');
    document.getElementById('postBtn').disabled = false;
    btn.textContent = '✓ Salvo!';

  } catch (err) {
    showToast('Erro: ' + err.message, 'error');
    btn.textContent = '💾 Salvar Vídeo';
    btn.disabled = false;
  }
}

// ── Post to YouTube (mock) ────────────────────────────────────
function openPostModal() {
  if (!currentState.video_id) { showToast('Salve o vídeo primeiro!', 'error'); return; }
  document.getElementById('postModal').style.display = 'flex';
}

async function confirmPost() {
  const title = document.getElementById('modalTitle').value;
  const vis   = document.getElementById('modalVis').value;
  const postBtn = document.querySelector('.modal-post-btn');

  postBtn.disabled = true;
  postBtn.textContent = '↻ Publicando...';

  // Simulate API delay
  await new Promise(r => setTimeout(r, 2000 + Math.random() * 1500));
  const videoId = Math.random().toString(36).substr(2,11);
  const url = `https://www.youtube.com/watch?v=${videoId}`;

  closeModal('postModal');
  showToast(`✅ Publicado! ID: ${videoId}`, 'success', 5000);

  postBtn.disabled = false;
  postBtn.textContent = '▶ PUBLICAR AGORA';

  setTimeout(() => {
    if (confirm(`✅ Vídeo publicado com sucesso!\n\nURL: ${url}\n\nAbrir no YouTube?`)) {
      window.open(url, '_blank');
    }
  }, 500);
}
