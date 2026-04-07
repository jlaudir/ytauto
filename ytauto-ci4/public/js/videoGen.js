// videoGen.js — Animated video preview on Canvas
const VideoGenerator = (() => {
  let raf=null, playing=false, muted=false, startTs=null, pausedAt=0;
  let totalDur=90, niche='', scenes=[];

  const sceneMap={
    tecnologia:[{color:'#0a0a2e',accent:'#00d4ff',text:'🚀 O QUE VOCÊ VAI APRENDER HOJE'},{color:'#080818',accent:'#00ff88',text:'💡 FATO CHOCANTE SOBRE IA'},{color:'#0d1020',accent:'#a855f7',text:'⚡ VEJA ISSO NA PRÁTICA'},{color:'#0a0a2e',accent:'#00d4ff',text:'✅ SE INSCREVA E NÃO PERCA A PARTE 2'}],
    financas:[{color:'#0d1f0d',accent:'#00e876',text:'💰 A VERDADE SOBRE FINANÇAS'},{color:'#0a1a0a',accent:'#ffde00',text:'📈 ESSE DADO VAI TE SURPREENDER'},{color:'#061206',accent:'#00e876',text:'💎 A ESTRATÉGIA DOS MILIONÁRIOS'},{color:'#0d1f0d',accent:'#ffde00',text:'🔔 ATIVA O SININHO AGORA!'}],
    fitness:[{color:'#1a0505',accent:'#ff4444',text:'💪 TRANSFORME SEU CORPO'},{color:'#150303',accent:'#ff8800',text:'🔥 O QUE A INDÚSTRIA ESCONDE'},{color:'#180404',accent:'#ff4444',text:'⚡ O TREINO PERFEITO'},{color:'#1a0505',accent:'#ff8800',text:'👊 DEIXA O LIKE!'}],
    default:[{color:'#1a0010',accent:'#ff3c3c',text:'🔥 ATENÇÃO: ASSISTA ATÉ O FIM'},{color:'#140008',accent:'#ffde00',text:'⚡ INFORMAÇÃO EXCLUSIVA'},{color:'#160010',accent:'#ff3c3c',text:'✨ O MÉTODO REVELADO'},{color:'#1a0010',accent:'#ffde00',text:'👍 SEU LIKE FAZ A DIFERENÇA!'}],
  };

  function catOf(n){const nl=n.toLowerCase();if(/tech|ia|progra/.test(nl))return'tecnologia';if(/financ|invest|dinheiro/.test(nl))return'financas';if(/fit|academia|treino/.test(nl))return'fitness';return'default';}

  function drawFrame(canvas, scene, sceneProgress, elapsed) {
    const ctx=canvas.getContext('2d'),W=canvas.width,H=canvas.height,t=elapsed*.001;
    ctx.fillStyle=scene.color; ctx.fillRect(0,0,W,H);
    // grid
    ctx.strokeStyle='rgba(255,255,255,0.04)'; ctx.lineWidth=1;
    const off=(t*20)%60;
    for(let x=-60+off;x<W;x+=60){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x+H,H);ctx.stroke();}
    // glow orb
    const ox=W*.5+Math.sin(t*.8)*W*.15,oy=H*.5+Math.cos(t*.6)*H*.12;
    const g=ctx.createRadialGradient(ox,oy,0,ox,oy,250);
    g.addColorStop(0,scene.accent+'44'); g.addColorStop(1,'transparent');
    ctx.fillStyle=g; ctx.fillRect(0,0,W,H);
    // particles
    for(let i=0;i<10;i++){const px=((i*137+t*30)%W),py=H-((t*35*(1+i*.1))%(H+20));ctx.fillStyle=scene.accent+'33';ctx.beginPath();ctx.arc(px,py,1.5+(i%3),0,Math.PI*2);ctx.fill();}
    // fade
    const fi=Math.min(1,sceneProgress*8),fo=sceneProgress>.8?1-(sceneProgress-.8)*5:1;
    ctx.globalAlpha=fi*fo;
    // progress bar
    const barGrad=ctx.createLinearGradient(0,0,W,0);
    barGrad.addColorStop(0,scene.accent); barGrad.addColorStop(1,scene.accent+'00');
    ctx.fillStyle=barGrad; ctx.fillRect(0,0,W*sceneProgress,5);
    // text
    ctx.fillStyle=scene.accent; ctx.shadowColor=scene.accent; ctx.shadowBlur=25;
    ctx.font=`bold ${22+Math.sin(t)*2}px "Arial Black",Impact,sans-serif`;
    ctx.textAlign='center';
    const words=scene.text.split(' '); let lns=[],ln='';
    for(const w of words){const t2=ln+w+' ';if(ctx.measureText(t2).width>W*.7&&ln){lns.push(ln.trim());ln=w+' ';}else ln=t2;}
    if(ln.trim())lns.push(ln.trim());
    lns.forEach((l,i)=>{
      if(i===0){ctx.fillStyle=scene.accent;ctx.shadowColor=scene.accent;ctx.shadowBlur=22;}
      else{ctx.fillStyle='#fff';ctx.shadowColor='rgba(0,0,0,0.8)';ctx.shadowBlur=12;}
      ctx.fillText(l,W/2,H*.45+i*34);
    });
    ctx.shadowBlur=0;
    // niche label
    ctx.fillStyle=scene.accent+'cc'; ctx.font='bold 14px Arial,sans-serif'; ctx.globalAlpha=fi*fo;
    ctx.fillText('► '+niche.toUpperCase()+' ◄',W/2,H-20);
    // bottom progress
    ctx.globalAlpha=1;
    ctx.fillStyle='rgba(0,0,0,0.4)'; ctx.fillRect(0,H-7,W,7);
    const pg=ctx.createLinearGradient(0,0,W,0);
    pg.addColorStop(0,scene.accent); pg.addColorStop(1,scene.accent+'88');
    ctx.fillStyle=pg; ctx.fillRect(0,H-7,W*sceneProgress,7);
    ctx.globalAlpha=1;
  }

  function init(canvas, n) {
    stop();
    niche=n;
    const c=catOf(n);
    scenes=sceneMap[c]||sceneMap.default;
    totalDur=45+Math.floor(Math.random()*45);
    pausedAt=0;
    drawFrame(canvas, scenes[0], 0, 0);
    return totalDur;
  }

  function play(canvas, n, onUpdate) {
    if(playing) return;
    playing=true; niche=n;
    const c=catOf(n);
    scenes=sceneMap[c]||sceneMap.default;
    const sceneDur=(totalDur/scenes.length)*1000;
    let last=null;
    function tick(ts){
      if(!last) last=ts-pausedAt*1000;
      const elapsed=ts-last, totalMs=totalDur*1000;
      const progress=Math.min(elapsed/totalMs,1);
      const sceneIdx=Math.min(Math.floor(elapsed/sceneDur),scenes.length-1);
      const sp=(elapsed%sceneDur)/sceneDur;
      drawFrame(canvas,scenes[sceneIdx],sp,elapsed);
      if(onUpdate) onUpdate(progress,elapsed/1000,totalDur);
      if(progress>=1){playing=false;pausedAt=0;return;}
      raf=requestAnimationFrame(tick);
    }
    raf=requestAnimationFrame(tick);
  }

  function pause(){playing=false;if(raf)cancelAnimationFrame(raf);}
  function stop(){playing=false;if(raf)cancelAnimationFrame(raf);pausedAt=0;}
  function toggleMute(){muted=!muted;if(muted&&window.speechSynthesis)speechSynthesis.cancel();return muted;}
  function getPlaying(){return playing;}

  return{init,play,pause,stop,toggleMute,getPlaying};
})();
