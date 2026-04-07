// thumbnail.js — Canvas-based thumbnail generator
const ThumbnailGenerator = (() => {
  const palettes = {
    tecnologia:{bg:['#0a0a2e','#1a1a5e'],accent:'#00d4ff',highlight:'#00ff88'},
    financas:{bg:['#0d1f0d','#1a3a1a'],accent:'#00e876',highlight:'#ffde00'},
    fitness:{bg:['#1a0505','#3d0f0f'],accent:'#ff4444',highlight:'#ff8800'},
    games:{bg:['#0a0520','#1e0a4a'],accent:'#a855f7',highlight:'#ff00ff'},
    culinaria:{bg:['#2d1200','#5a2500'],accent:'#ff7a00',highlight:'#ffde00'},
    default:{bg:['#1a0010','#330020'],accent:'#ff3c3c',highlight:'#ffde00'},
  };
  const hooks={
    tecnologia:['ISSO VAI MUDAR TUDO','O SEGREDO DA IA','NÃO IGNORE ISSO'],
    financas:['RICO EM 30 DIAS','O SEGREDO DOS RICOS','PARE DE SER POBRE'],
    fitness:['CORPO PERFEITO','PARE DE ERRAR','O MÉTODO REAL'],
    games:['SEGREDO REVELADO','IMPOSSÍVEL?','FIQUEI CHOCADO'],
    default:['VOCÊ NÃO VAI ACREDITAR','CHOCANTE','REVELADO'],
  };
  const emojis={tecnologia:['🤖','💻','⚡'],financas:['💰','📈','💎'],fitness:['💪','🔥','⚡'],games:['🎮','⚔️','🏆'],default:['🔥','💥','🚀']};

  function cat(niche){
    const n=niche.toLowerCase();
    if(/tech|ia|progra|código|software/.test(n)) return 'tecnologia';
    if(/financ|invest|dinheiro|renda/.test(n)) return 'financas';
    if(/fit|academia|treino|muscula/.test(n)) return 'fitness';
    if(/game|jogo|gaming/.test(n)) return 'games';
    return 'default';
  }

  function roundRect(ctx,x,y,w,h,r){ctx.beginPath();ctx.moveTo(x+r,y);ctx.lineTo(x+w-r,y);ctx.quadraticCurveTo(x+w,y,x+w,y+r);ctx.lineTo(x+w,y+h-r);ctx.quadraticCurveTo(x+w,y+h,x+w-r,y+h);ctx.lineTo(x+r,y+h);ctx.quadraticCurveTo(x,y+h,x,y+h-r);ctx.lineTo(x,y+r);ctx.quadraticCurveTo(x,y,x+r,y);ctx.closePath();}

  function draw(canvas, niche, title){
    const ctx=canvas.getContext('2d'), W=canvas.width, H=canvas.height;
    const c=cat(niche), pal=palettes[c]||palettes.default;
    const ac=pal.accent, hl=pal.highlight;
    const hook=(hooks[c]||hooks.default)[Math.floor(Math.random()*(hooks[c]||hooks.default).length)];
    const emoji=(emojis[c]||emojis.default)[Math.floor(Math.random()*(emojis[c]||emojis.default).length)];

    // BG
    const bg=ctx.createLinearGradient(0,0,W,H);
    bg.addColorStop(0,pal.bg[0]); bg.addColorStop(1,pal.bg[1]);
    ctx.fillStyle=bg; ctx.fillRect(0,0,W,H);

    // Grid
    ctx.strokeStyle='rgba(255,255,255,0.04)'; ctx.lineWidth=1;
    for(let x=0;x<W;x+=60){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,H);ctx.stroke();}
    for(let y=0;y<H;y+=60){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(W,y);ctx.stroke();}

    // Glow
    const g=ctx.createRadialGradient(W*.3,H*.4,0,W*.3,H*.4,350);
    const hex=h=>{const r=parseInt(h.slice(1,3),16),g2=parseInt(h.slice(3,5),16),b=parseInt(h.slice(5,7),16);return `rgb(${r},${g2},${b})`;};
    g.addColorStop(0,hex(ac).replace('rgb','rgba').replace(')',',0.3)'));
    g.addColorStop(1,'transparent');
    ctx.fillStyle=g; ctx.fillRect(0,0,W,H);

    // Hook badge
    const bh=68;
    const bGrad=ctx.createLinearGradient(0,0,W,0);
    bGrad.addColorStop(0,ac); bGrad.addColorStop(1,hl);
    ctx.fillStyle=bGrad; ctx.fillRect(0,0,W,bh);
    ctx.shadowColor='rgba(0,0,0,0.5)'; ctx.shadowBlur=8;
    ctx.fillStyle='#000'; ctx.font='bold 34px "Arial Black",Impact,sans-serif';
    ctx.textAlign='center';
    ctx.fillText(hook,W/2+2,bh-16+2);
    ctx.fillStyle='#fff'; ctx.fillText(hook,W/2,bh-16);
    ctx.shadowBlur=0;

    // Emoji
    ctx.font='180px serif'; ctx.textAlign='center';
    ctx.fillStyle='rgba(255,255,255,0.1)';
    ctx.fillText(emoji,W*.78,H*.72);
    ctx.fillStyle=ac; ctx.shadowColor=ac; ctx.shadowBlur=40;
    ctx.fillText(emoji,W*.78,H*.70);
    ctx.shadowBlur=0; ctx.fillStyle='white'; ctx.fillText(emoji,W*.78,H*.70);

    // Title box
    ctx.fillStyle='rgba(0,0,0,0.45)';
    roundRect(ctx,40,bh+16,W*.62,H-bh-90,14); ctx.fill();

    // Title text
    const titleUp=title.replace(/[^\w\sÀ-ÿ]/g,' ').toUpperCase().trim();
    ctx.shadowColor='rgba(0,0,0,0.9)'; ctx.shadowBlur=16;
    ctx.fillStyle='#fff'; ctx.font='bold 68px "Arial Black",Impact,sans-serif';
    ctx.textAlign='left';
    const words=titleUp.split(' '); let lines=[],line='';
    for(const w of words){const t=line+w+' ';if(ctx.measureText(t).width>W*.57&&line){lines.push(line.trim());line=w+' ';if(lines.length>=3)break;}else line=t;}
    if(line.trim()) lines.push(line.trim());
    lines.forEach((l,i)=>{
      if(i===0){ctx.fillStyle=hl;ctx.shadowColor=hl;ctx.shadowBlur=28;}
      else{ctx.fillStyle='#fff';ctx.shadowColor='rgba(0,0,0,0.9)';ctx.shadowBlur=14;}
      ctx.fillText(l,60,bh+100+i*84);
    });
    ctx.shadowBlur=0;

    // Bottom bar
    const tagY=H-50;
    const botGrad=ctx.createLinearGradient(0,tagY-20,0,H);
    botGrad.addColorStop(0,'rgba(0,0,0,0)'); botGrad.addColorStop(1,'rgba(0,0,0,0.8)');
    ctx.fillStyle=botGrad; ctx.fillRect(0,tagY-20,W,70);
    ctx.fillStyle=ac; ctx.font='bold 20px Arial,sans-serif'; ctx.textAlign='left';
    ctx.fillText('▶ '+niche.toUpperCase(),60,H-18);

    // Subscribe btn
    ctx.fillStyle='#ff0000';
    roundRect(ctx,W-240,H-62,190,42,21); ctx.fill();
    ctx.fillStyle='#fff'; ctx.font='bold 16px "Arial Black",sans-serif'; ctx.textAlign='center';
    ctx.fillText('INSCREVA-SE',W-145,H-34);

    // Border
    ctx.strokeStyle=ac; ctx.lineWidth=6; ctx.shadowColor=ac; ctx.shadowBlur=18;
    ctx.strokeRect(3,3,W-6,H-6); ctx.shadowBlur=0;
  }

  return { draw };
})();
