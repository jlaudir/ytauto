// videoGen.js — Vídeo Canvas sincronizado com áudio real do MultiVozes
'use strict';

const VideoGenerator = (() => {
  let raf = null, playing = false, muted = false;
  let audioEl = null;
  let totalDur = 90;
  let niche = '', currentScenes = [];

  const palettes = {
    tecnologia: { bg: ['#0a0a2e','#1a1a5e'], accent: '#00d4ff', hi: '#00ff88', icon: '🤖' },
    financas:   { bg: ['#0d1f0d','#1a3a1a'], accent: '#00e876', hi: '#ffde00', icon: '💰' },
    fitness:    { bg: ['#1a0505','#3d0f0f'], accent: '#ff4444', hi: '#ff8800', icon: '💪' },
    games:      { bg: ['#0a0520','#1e0a4a'], accent: '#a855f7', hi: '#ff00ff', icon: '🎮' },
    culinaria:  { bg: ['#2d1200','#5a2500'], accent: '#ff7a00', hi: '#ffde00', icon: '🍳' },
    saude:      { bg: ['#001a1a','#003333'], accent: '#00e8c8', hi: '#88ff88', icon: '🏥' },
    educacao:   { bg: ['#001030','#002060'], accent: '#4488ff', hi: '#ffcc00', icon: '📚' },
    default:    { bg: ['#1a0010','#330020'], accent: '#ff3c3c', hi: '#ffde00', icon: '🔥' },
  };

  function catOf(n) {
    const nl = (n || '').toLowerCase();
    if (/tech|ia|progra|código|software|app/.test(nl)) return 'tecnologia';
    if (/financ|invest|dinheiro|renda|cripto/.test(nl)) return 'financas';
    if (/fit|academia|treino|muscula|exerc/.test(nl)) return 'fitness';
    if (/game|jogo|gaming|gamer/.test(nl)) return 'games';
    if (/culinária|receita|cozinha/.test(nl)) return 'culinaria';
    if (/saúde|bem.estar|medicina/.test(nl)) return 'saude';
    if (/educação|curso|aprender/.test(nl)) return 'educacao';
    return 'default';
  }

  function buildScenes(script, pal) {
    const lines = (script || '').split('\n').map(l => l.trim())
      .filter(l => l.length > 8 && !l.startsWith('http') && !/^#{1,3}\s/.test(l) && !/^[-*]/.test(l) && !/^\d+:\d+/.test(l));

    const scenes = [];
    let block = [];
    for (const line of lines) {
      block.push(line.replace(/[*_~`#►•→]/g, '').trim());
      if (block.length >= 2 || /[!?]$/.test(line)) {
        const text = block.join(' ').replace(/\s+/g, ' ').substring(0, 140);
        if (text.length > 8) scenes.push({ text, color: scenes.length % 2 === 0 ? pal.bg[0] : pal.bg[1], accent: pal.accent, hi: pal.hi, icon: pal.icon });
        block = [];
        if (scenes.length >= 8) break;
      }
    }
    if (block.length > 0 && scenes.length < 8) {
      const text = block.join(' ').substring(0, 140);
      if (text.length > 8) scenes.push({ text, color: pal.bg[1], accent: pal.hi, hi: pal.accent, icon: pal.icon });
    }
    while (scenes.length < 4) {
      const defaults = ['🔥 ATENÇÃO: ASSISTA ATÉ O FIM!', '💡 INFORMAÇÃO EXCLUSIVA', '✨ O MÉTODO REVELADO', '🔔 CURTA E SE INSCREVA!'];
      scenes.push({ text: defaults[scenes.length % 4], color: pal.bg[scenes.length % 2], accent: pal.accent, hi: pal.hi, icon: pal.icon });
    }
    return scenes;
  }

  function drawFrame(canvas, scenes, progress) {
    if (!canvas || !scenes || scenes.length === 0) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.width, H = canvas.height;
    const sceneCount = scenes.length;
    const sceneIdx = Math.min(Math.floor(progress * sceneCount), sceneCount - 1);
    const sp = (progress * sceneCount) - sceneIdx;
    const scene = scenes[sceneIdx];
    const t = performance.now() * 0.001;

    ctx.fillStyle = scene.color || '#0a0a2e';
    ctx.fillRect(0, 0, W, H);

    ctx.strokeStyle = 'rgba(255,255,255,0.03)'; ctx.lineWidth = 1;
    const go = (t * 12) % 50;
    for (let x = -50 + go; x < W + H; x += 50) { ctx.beginPath(); ctx.moveTo(x,0); ctx.lineTo(x-H,H); ctx.stroke(); }

    const ox = W*0.5 + Math.sin(t*0.7)*W*0.2, oy = H*0.45 + Math.cos(t*0.5)*H*0.15;
    const g = ctx.createRadialGradient(ox,oy,0,ox,oy,W*0.4);
    g.addColorStop(0, (scene.accent||'#ff3c3c')+'3a'); g.addColorStop(1,'transparent');
    ctx.fillStyle = g; ctx.fillRect(0,0,W,H);

    const spd = 22 + sp * 18;
    for (let i = 0; i < 12; i++) {
      const px = (i*107 + t*spd) % W, py = H - ((t*spd*(0.6+i*0.08)) % (H+25)) + 12;
      ctx.fillStyle = (scene.accent||'#ff3c3c')+'25';
      ctx.beginPath(); ctx.arc(px,py,1.2+(i%3)*0.7,0,Math.PI*2); ctx.fill();
    }

    const fi = Math.min(1, sp*5), fo = sp > 0.82 ? 1-(sp-0.82)*5.88 : 1;
    ctx.globalAlpha = Math.max(0.05, fi * fo);

    const pgrd = ctx.createLinearGradient(0,0,W,0);
    pgrd.addColorStop(0,scene.accent); pgrd.addColorStop(1,(scene.hi||'#ffde00')+'00');
    ctx.fillStyle = pgrd; ctx.fillRect(0,0,W*progress,5);

    ctx.font = `${120+Math.sin(t)*4}px serif`; ctx.textAlign='center';
    ctx.fillStyle = 'rgba(255,255,255,0.05)'; ctx.fillText(scene.icon||'🔥', W*0.82, H*0.65);
    ctx.fillStyle = scene.accent; ctx.shadowColor = scene.accent; ctx.shadowBlur = 30;
    ctx.font = `${96+Math.sin(t)*3}px serif`;
    ctx.fillText(scene.icon||'🔥', W*0.82, H*0.62); ctx.shadowBlur = 0;

    ctx.shadowColor='rgba(0,0,0,0.85)'; ctx.shadowBlur=16; ctx.textAlign='left';
    const maxW = W*0.68;
    const words = (scene.text||'').split(' ');
    let lns = [], cl = '';
    ctx.font = 'bold 27px "Arial Black",Impact,sans-serif';
    for (const w of words) { const tst=cl+w+' '; if(ctx.measureText(tst).width>maxW&&cl){lns.push(cl.trim());cl=w+' ';if(lns.length>=4)break;}else cl=tst; }
    if(cl.trim()) lns.push(cl.trim());
    const lhPx=36, totH=lns.length*lhPx, sy=H*0.44-totH/2;
    lns.forEach((ln,i)=>{
      if(i===0){ctx.fillStyle=scene.hi||'#ffde00';ctx.shadowColor=scene.hi||'#ffde00';ctx.shadowBlur=20;ctx.font='bold 29px "Arial Black",Impact,sans-serif';}
      else{ctx.fillStyle='#fff';ctx.shadowColor='rgba(0,0,0,0.8)';ctx.shadowBlur=12;ctx.font='bold 25px "Arial Black",Impact,sans-serif';}
      ctx.fillText(ln, 20, sy + i*lhPx);
    });
    ctx.shadowBlur=0;

    ctx.globalAlpha=1;
    ctx.fillStyle='rgba(0,0,0,0.5)'; ctx.fillRect(0,H-6,W,6);
    const bg2=ctx.createLinearGradient(0,0,W,0);
    bg2.addColorStop(0,scene.accent); bg2.addColorStop(1,scene.hi||'#ffde00');
    ctx.fillStyle=bg2; ctx.fillRect(0,H-6,W*progress,6);

    ctx.fillStyle=(scene.accent||'#ff3c3c')+'cc';
    ctx.font='bold 13px Arial,sans-serif'; ctx.textAlign='left';
    ctx.fillText('▶ '+(niche||'').toUpperCase(), 16, H-14);
    ctx.fillStyle='rgba(255,255,255,0.35)'; ctx.font='11px monospace'; ctx.textAlign='right';
    ctx.fillText(`${sceneIdx+1}/${sceneCount}`, W-14, H-14);
    ctx.globalAlpha=1;
  }

  function init(canvas, nicheStr, script) {
    stop();
    niche = nicheStr || '';
    const pal = palettes[catOf(niche)] || palettes.default;
    currentScenes = buildScenes(script || '', pal);
    totalDur = 90;
    drawFrame(canvas, currentScenes, 0);
    return totalDur;
  }

  function play(canvas, nicheStr, audioElement, script, onUpdate) {
    if (playing) return;
    playing = true; niche = nicheStr || niche;
    const pal = palettes[catOf(niche)] || palettes.default;
    if (script) currentScenes = buildScenes(script, pal);
    audioEl = audioElement || audioEl;
    if (audioEl && !muted) { audioEl.currentTime = 0; audioEl.play().catch(()=>{}); }

    function tick() {
      if (!playing) return;
      let progress = 0, elapsed = 0;
      if (audioEl && audioEl.duration > 0) {
        elapsed = audioEl.currentTime; totalDur = audioEl.duration;
        progress = elapsed / totalDur;
        if (audioEl.ended || progress >= 1) {
          drawFrame(canvas, currentScenes, 1);
          if(onUpdate) onUpdate(1, totalDur, totalDur);
          playing = false; return;
        }
      } else {
        if (!tick._start) tick._start = performance.now();
        elapsed = (performance.now() - tick._start) / 1000;
        totalDur = 90; progress = Math.min(elapsed/totalDur, 1);
        if (progress >= 1) { playing = false; return; }
      }
      drawFrame(canvas, currentScenes, progress);
      if(onUpdate) onUpdate(progress, elapsed, totalDur);
      raf = requestAnimationFrame(tick);
    }
    raf = requestAnimationFrame(tick);
  }

  function pause() {
    playing = false;
    if(raf){cancelAnimationFrame(raf);raf=null;}
    if(audioEl && !audioEl.paused) audioEl.pause();
  }

  function stop() {
    playing = false;
    if(raf){cancelAnimationFrame(raf);raf=null;}
    if(audioEl){audioEl.pause();audioEl.currentTime=0;}
  }

  function toggleMute() {
    muted = !muted;
    if(audioEl) audioEl.muted = muted;
    return muted;
  }

  function setAudio(el) { audioEl = el; }
  function getPlaying() { return playing; }

  return { init, play, pause, stop, toggleMute, setAudio, getPlaying, buildScenes, catOf, palettes };
})();
