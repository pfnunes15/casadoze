/* ===========================================================================
   As ilustrações dos produtos.

   A Casa não tem fotografias dos produtos: cada um é desenhado. Esta é a mesma
   biblioteca que pinta a maquete aprovada — veio de docs/maquete-bundle.html,
   onde está empacotada —, e por isso os cartões ficam iguais aos dela e não
   parecidos.

   Pinta num canvas de 600 por 800 e expõe uma função só:

       CovilArt.paint(canvas, desenho, semente)

   `desenho` é uma das oito chaves de DRAW — candle, oil, crystal, amulet, moon,
   sage, tarot, grimoire — e diz-se por produto, em Características > Desenho.
   A `semente` fixa o acaso: o grão, o nevoeiro e as poeiras são aleatórios, mas
   com a mesma semente saem sempre iguais, e por isso um produto não muda de
   aspecto de cada vez que a página abre.

   Não mexer à mão. Se a maquete mudar, volta-se a tirar de lá.
   =========================================================================== */
(function(){
function drawLib(){
    
    const W = 600, H = 800;
    function rng(seed){return function(){seed|=0;seed=seed+0x6D2B79F5|0;let t=Math.imul(seed^seed>>>15,1|seed);t=t+Math.imul(t^t>>>7,61|t)^t;return((t^t>>>14)>>>0)/4294967296}}
    function layer(fn){const c=document.createElement('canvas');c.width=W;c.height=H;fn(c.getContext('2d'));return c}
    function base(g,c1,c2,glow,gx,gy,gr){const lg=g.createLinearGradient(0,0,0,H);lg.addColorStop(0,c1);lg.addColorStop(1,c2);g.fillStyle=lg;g.fillRect(0,0,W,H);const rg=g.createRadialGradient(gx,gy,0,gx,gy,gr||W*.9);rg.addColorStop(0,glow);rg.addColorStop(1,'rgba(0,0,0,0)');g.fillStyle=rg;g.fillRect(0,0,W,H)}
    function fog(g,r,y0,y1,a){for(let i=0;i<14;i++){const x=r()*W,y=y0+r()*(y1-y0),s=120+r()*220;const rg=g.createRadialGradient(x,y,0,x,y,s);rg.addColorStop(0,'rgba(200,195,210,'+(a*(.4+r()*.6))+')');rg.addColorStop(1,'rgba(200,195,210,0)');g.fillStyle=rg;g.fillRect(x-s,y-s,s*2,s*2)}}
    function finish(g,seed){const r=rng(seed);for(let i=0;i<5000;i++){g.fillStyle='rgba(255,245,230,'+(r()*.05)+')';g.fillRect(r()*W,r()*H,1.6,1.6)}for(let i=0;i<2500;i++){g.fillStyle='rgba(0,0,0,'+(r()*.12)+')';g.fillRect(r()*W,r()*H,2,2)}const v=g.createRadialGradient(W/2,H/2,H*.25,W/2,H/2,H*.75);v.addColorStop(0,'rgba(0,0,0,0)');v.addColorStop(1,'rgba(0,0,0,.7)');g.fillStyle=v;g.fillRect(0,0,W,H)}
    function glowAt(g,x,y,rad,col){const rg=g.createRadialGradient(x,y,0,x,y,rad);rg.addColorStop(0,col);rg.addColorStop(1,'rgba(0,0,0,0)');g.fillStyle=rg;g.fillRect(x-rad,y-rad,rad*2,rad*2)}
    function drawCandleBody(g,x,top,w,bottom,a){
      const bg=g.createLinearGradient(x-w/2,0,x+w/2,0);
      bg.addColorStop(0,'rgba(90,72,52,'+a+')');bg.addColorStop(.35,'rgba(226,208,172,'+a+')');bg.addColorStop(.6,'rgba(196,172,132,'+a+')');bg.addColorStop(1,'rgba(60,46,32,'+a+')');
      g.fillStyle=bg;g.fillRect(x-w/2,top,w,bottom-top);
      g.beginPath();g.ellipse(x,top,w/2,w*.12,0,0,Math.PI*2);const tg=g.createRadialGradient(x,top,0,x,top,w/2);tg.addColorStop(0,'rgba(255,230,170,'+a+')');tg.addColorStop(1,'rgba(170,140,100,'+a+')');g.fillStyle=tg;g.fill();
      g.fillStyle='rgba(232,214,180,'+(a*.9)+')';
      [[-.35,60],[.1,95],[.38,40]].forEach(function(o){const dx=x+o[0]*w,l=o[1];g.beginPath();g.moveTo(dx-8,top+4);g.lineTo(dx-6,top+l);g.arc(dx,top+l,6,Math.PI,0,true);g.lineTo(dx+8,top+4);g.fill()});
      g.strokeStyle='rgba(20,12,6,'+a+')';g.lineWidth=3;g.beginPath();g.moveTo(x,top);g.lineTo(x+2,top-26);g.stroke();
    }
    function drawFlame(g,x,y,s){
      glowAt(g,x,y,260*s,'rgba(255,160,70,'+(.45*s)+')');glowAt(g,x,y,90*s,'rgba(255,210,140,'+(.5*s)+')');
      const fg=g.createRadialGradient(x,y+10*s,2,x,y,46*s);fg.addColorStop(0,'#fffbe8');fg.addColorStop(.4,'#ffd27a');fg.addColorStop(1,'rgba(230,110,30,0)');
      g.fillStyle=fg;g.beginPath();g.moveTo(x,y-58*s);g.bezierCurveTo(x+26*s,y-20*s,x+22*s,y+22*s,x,y+22*s);g.bezierCurveTo(x-22*s,y+22*s,x-26*s,y-20*s,x,y-58*s);g.fill();
    }
    const DRAW = {
      candle(g){
        base(g,'#1b120b','#050302','rgba(224,134,60,.32)',W/2,300,520);const r=rng(11);
        const tg=g.createLinearGradient(0,640,0,H);tg.addColorStop(0,'#2a1a0e');tg.addColorStop(1,'#0a0604');g.fillStyle=tg;g.fillRect(0,650,W,H-650);
        glowAt(g,W/2,690,240,'rgba(224,134,60,.25)');
        drawCandleBody(g,170,470,62,690,.55);drawFlame(g,170,448,.6);
        drawCandleBody(g,W/2+20,380,150,720,1);drawFlame(g,W/2+20,350,1);
        g.strokeStyle='rgba(230,220,210,.08)';g.lineWidth=3;
        for(let k=0;k<3;k++){g.beginPath();let x=W/2+20,y=250;g.moveTo(x,y);for(let i=0;i<8;i++){x+=(r()-.5)*50;y-=30;g.quadraticCurveTo(x+(r()-.5)*60,y+15,x,y)}g.stroke()}
        fog(g,r,560,760,.05);
      },
      oil(g){
        base(g,'#150f09','#040302','rgba(210,120,50,.28)',W/2,470,480);const r=rng(22);const cx=W/2,cy=520,R=150;
        g.fillStyle='#0c0805';g.fillRect(0,680,W,H);glowAt(g,cx,680,260,'rgba(200,110,40,.18)');
        g.fillStyle='rgba(0,0,0,.6)';g.beginPath();g.ellipse(cx,678,170,22,0,0,Math.PI*2);g.fill();
        g.save();g.beginPath();g.arc(cx,cy,R,0,Math.PI*2);g.rect(cx-32,330,64,120);g.clip();
        const lq=g.createLinearGradient(0,cy-60,0,cy+R);lq.addColorStop(0,'#c77428');lq.addColorStop(1,'#3a1605');g.fillStyle=lq;g.fillRect(cx-R,cy-50,R*2,R*2);
        g.fillStyle='rgba(255,200,130,.35)';g.fillRect(cx-R,cy-52,R*2,4);g.restore();
        g.fillStyle='rgba(255,255,255,.04)';g.beginPath();g.arc(cx,cy,R,0,Math.PI*2);g.fill();g.fillRect(cx-32,330,64,100);
        g.strokeStyle='rgba(255,235,210,.28)';g.lineWidth=2;g.beginPath();g.arc(cx,cy,R,-Math.PI/2+.22,Math.PI*1.5-.22);g.stroke();
        g.beginPath();g.moveTo(cx-32,330);g.lineTo(cx-32,cy-R+6);g.moveTo(cx+32,330);g.lineTo(cx+32,cy-R+6);g.stroke();
        g.strokeStyle='rgba(255,240,220,.35)';g.lineWidth=6;g.beginPath();g.arc(cx,cy,R-22,Math.PI*1.1,Math.PI*1.45);g.stroke();
        const ck=g.createLinearGradient(cx-38,0,cx+38,0);ck.addColorStop(0,'#5a3b22');ck.addColorStop(.5,'#9a714a');ck.addColorStop(1,'#4a2f1a');g.fillStyle=ck;g.fillRect(cx-38,270,76,62);
        g.strokeStyle='rgba(0,0,0,.4)';g.lineWidth=1;for(let i=0;i<6;i++){g.beginPath();g.moveTo(cx-38,280+i*9);g.lineTo(cx+38,280+i*9);g.stroke()}
        g.strokeStyle='#b89a6a';g.lineWidth=3;g.beginPath();g.moveTo(cx-34,338);g.lineTo(cx+34,338);g.moveTo(cx-34,344);g.lineTo(cx+34,344);g.stroke();
        g.beginPath();g.moveTo(cx+30,342);g.quadraticCurveTo(cx+70,380,cx+60,430);g.stroke();
        g.save();g.translate(cx,cy+20);g.rotate(-.04);g.fillStyle='#d8c9a6';g.fillRect(-90,-40,180,96);g.strokeStyle='#5a4630';g.lineWidth=1.5;g.strokeRect(-84,-34,168,84);
        g.fillStyle='#2a1c10';g.font='26px "IM Fell English SC", Georgia, serif';g.textAlign='center';g.fillText('LUA NEGRA',0,6);
        g.font='14px "IBM Plex Mono", monospace';g.fillText('Nº 13 · 30 ML',0,34);
        g.beginPath();g.arc(0,-18,11,0,Math.PI*2);g.fill();g.fillStyle='#d8c9a6';g.beginPath();g.arc(5,-21,10,0,Math.PI*2);g.fill();g.restore();
        fog(g,r,600,800,.04);
      },
      crystal(g){
        base(g,'#130c1b','#040306','rgba(150,100,210,.32)',W/2,420,460);const r=rng(33);fog(g,r,120,520,.035);
        const specs=[[300,640,70,330,-.05],[220,660,52,230,-.35],[385,655,56,250,.28],[165,690,40,150,-.6],[445,690,44,170,.55],[270,700,40,140,-.15],[340,705,38,120,.18]];
        g.fillStyle='#17121a';g.beginPath();g.moveTo(90,780);g.bezierCurveTo(110,660,220,640,300,650);g.bezierCurveTo(400,640,500,670,520,780);g.closePath();g.fill();
        specs.sort(function(a,b){return b[3]-a[3]});
        specs.forEach(function(sp){const x=sp[0],y=sp[1],w=sp[2],h=sp[3],a=sp[4];
          g.save();g.translate(x,y);g.rotate(a);const Lx=-w/2,Rr=w/2,sh=-h,tip=-h-w*.9;
          let lg=g.createLinearGradient(Lx,0,0,0);lg.addColorStop(0,'#2e1a45');lg.addColorStop(1,'#6a47a0');
          g.fillStyle=lg;g.beginPath();g.moveTo(Lx,0);g.lineTo(Lx,sh);g.lineTo(0,tip);g.lineTo(0,0);g.closePath();g.fill();
          lg=g.createLinearGradient(0,0,Rr,0);lg.addColorStop(0,'#b99be0');lg.addColorStop(1,'#553585');
          g.fillStyle=lg;g.beginPath();g.moveTo(0,0);g.lineTo(0,tip);g.lineTo(Rr,sh);g.lineTo(Rr,0);g.closePath();g.fill();
          g.fillStyle='rgba(255,240,255,.18)';g.beginPath();g.moveTo(0,tip);g.lineTo(Rr,sh);g.lineTo(0,sh+w*.15);g.closePath();g.fill();
          g.strokeStyle='rgba(240,225,255,.35)';g.lineWidth=1.2;g.beginPath();g.moveTo(0,0);g.lineTo(0,tip);g.stroke();
          g.fillStyle='rgba(0,0,0,.35)';g.fillRect(Lx,-h*.25,w,h*.25);g.restore();});
        glowAt(g,W/2,380,160,'rgba(210,180,255,.12)');
        for(let i=0;i<40;i++){g.fillStyle='rgba(255,240,255,'+(r()*.5)+')';g.fillRect(180+r()*240,260+r()*380,2,2)}
      },
      amulet(g){
        base(g,'#0e1013','#030304','rgba(170,190,215,.22)',W/2,470,420);const r=rng(44);const cx=W/2,cy=500,R=135;
        g.strokeStyle='#8a7446';g.lineWidth=3;
        [-1,1].forEach(function(s){for(let i=0;i<=26;i++){const t=i/26;const x=cx+s*(1-t)*230+s*t*8;const y=-10+t*(cy-R-8+10)+Math.sin(t*Math.PI)*-40*(1-t);g.beginPath();g.ellipse(x,y,7,4.5,t*1.2*s+.8,0,Math.PI*2);g.stroke()}});
        const cres=layer(function(c){const gg=c.createRadialGradient(cx-40,cy-40,10,cx,cy,R);gg.addColorStop(0,'#f2d894');gg.addColorStop(.5,'#b58c3e');gg.addColorStop(1,'#4d3614');
          c.fillStyle=gg;c.beginPath();c.arc(cx,cy,R,0,Math.PI*2);c.fill();
          c.globalCompositeOperation='destination-out';c.beginPath();c.arc(cx+62,cy-28,R*.86,0,Math.PI*2);c.fill();
          c.globalCompositeOperation='source-atop';c.strokeStyle='rgba(60,40,10,.6)';c.lineWidth=2;c.beginPath();c.arc(cx,cy,R-12,0,Math.PI*2);c.stroke();
          for(let i=0;i<300;i++){c.fillStyle='rgba(40,25,5,'+(r()*.25)+')';c.fillRect(cx-R+r()*R*2,cy-R+r()*R*2,2,2)}});
        g.filter='blur(14px)';g.globalAlpha=.5;g.drawImage(cres,12,24);g.filter='none';g.globalAlpha=1;g.drawImage(cres,0,0);
        g.strokeStyle='#c9a55a';g.lineWidth=6;g.beginPath();g.arc(cx-8,cy-R-10,11,0,Math.PI*2);g.stroke();
        const sx=cx-78,sy=cy+30;glowAt(g,sx,sy,70,'rgba(190,215,255,.25)');
        const st=g.createRadialGradient(sx-8,sy-10,2,sx,sy,26);st.addColorStop(0,'#ffffff');st.addColorStop(.4,'#cfe0f2');st.addColorStop(1,'#5f7390');
        g.fillStyle=st;g.beginPath();g.ellipse(sx,sy,24,28,0,0,Math.PI*2);g.fill();g.strokeStyle='#8a6a2e';g.lineWidth=4;g.stroke();
        fog(g,r,560,800,.05);
      },
      moon(g){
        base(g,'#0b0e16','#020304','rgba(190,205,230,.22)',W/2,250,420);const r=rng(55);
        for(let i=0;i<160;i++){g.fillStyle='rgba(230,235,255,'+(r()*.6)+')';const s=r()*1.8;g.fillRect(r()*W,r()*420,s,s)}
        const mx=W/2+30,my=250,mr=118;glowAt(g,mx,my,300,'rgba(220,228,245,.2)');
        const mg=g.createRadialGradient(mx-30,my-30,10,mx,my,mr);mg.addColorStop(0,'#f4f1e6');mg.addColorStop(1,'#a8a9a6');g.fillStyle=mg;g.beginPath();g.arc(mx,my,mr,0,Math.PI*2);g.fill();
        g.save();g.beginPath();g.arc(mx,my,mr,0,Math.PI*2);g.clip();
        for(let i=0;i<14;i++){const x=mx+(r()-.5)*mr*1.6,y=my+(r()-.5)*mr*1.6,s=6+r()*26;g.fillStyle='rgba(90,92,95,'+(.12+r()*.18)+')';g.beginPath();g.arc(x,y,s,0,Math.PI*2);g.fill()}
        g.restore();
        [[430,'#1b2029',.8],[500,'#12161d',1],[580,'#0a0c10',1.2],[660,'#050608',1.4]].forEach(function(rd,k){const y=rd[0],amp=rd[2];
          g.fillStyle=rd[1];g.beginPath();g.moveTo(0,H);let x=0;g.lineTo(0,y);while(x<W){x+=20+r()*50;g.lineTo(x,y-r()*110*amp+(k===0?20:0))}g.lineTo(W,H);g.closePath();g.fill();fog(g,r,y-30,y+40,.035)});
        g.fillStyle='#050608';g.fillRect(150,500,26,110);g.beginPath();g.moveTo(144,502);g.lineTo(163,462);g.lineTo(182,502);g.fill();
        g.fillStyle='rgba(255,190,110,.9)';g.fillRect(160,520,4,7);glowAt(g,162,523,20,'rgba(255,170,90,.35)');
      },
      sage(g){
        base(g,'#0e120b','#030402','rgba(142,156,120,.28)',W/2,380,460);const r=rng(66);
        for(let k=0;k<5;k++){g.strokeStyle='rgba(225,225,215,'+(.05+r()*.05)+')';g.lineWidth=2+r()*4;g.beginPath();let x=W/2-40+k*12,y=250;g.moveTo(x,y);for(let i=0;i<7;i++){const nx=x+(r()-.5)*70,ny=y-34;g.bezierCurveTo(x+(r()-.5)*60,y-12,nx+(r()-.5)*60,ny+12,nx,ny);x=nx;y=ny}g.stroke()}
        g.save();g.translate(W/2,470);g.rotate(-.35);
        g.strokeStyle='#5d5a3c';g.lineWidth=3;for(let i=0;i<9;i++){g.beginPath();g.moveTo(-18+i*4.5,120);g.lineTo(-12+i*3+(r()-.5)*8,300);g.stroke()}
        glowAt(g,0,-215,90,'rgba(230,120,50,.45)');
        for(let i=0;i<120;i++){const t=r();const y=-220+t*350;const spread=40+(1-Math.abs(t-.45))*45;const x=(r()-.5)*spread*2;const l=40+r()*55;const a=(x/spread)*.5+(r()-.5)*.5;
          g.fillStyle='hsla('+(80+r()*20)+','+(10+r()*10)+'%,'+(38+r()*26)+'%,.9)';
          g.save();g.translate(x,y);g.rotate(a);g.beginPath();g.ellipse(0,0,l*.18,l*.5,0,0,Math.PI*2);g.fill();
          g.strokeStyle='rgba(255,255,240,.12)';g.lineWidth=1;g.beginPath();g.moveTo(0,-l*.45);g.lineTo(0,l*.45);g.stroke();g.restore()}
        g.fillStyle='rgba(20,14,10,.8)';g.beginPath();g.ellipse(0,-215,38,18,0,0,Math.PI*2);g.fill();
        for(let i=0;i<14;i++){g.fillStyle='rgba(255,'+Math.round(120+r()*80)+',60,'+(.4+r()*.5)+')';g.fillRect(-26+r()*52,-222+r()*12,3,3)}
        g.strokeStyle='#c7ab7a';g.lineWidth=2.5;for(let i=0;i<9;i++){const y=-150+i*30;g.beginPath();g.moveTo(-48+i*2,y);g.lineTo(48-i*2,y+22);g.stroke()}
        g.restore();fog(g,r,560,800,.05);
      },
      tarot(g){
        base(g,'#150b0e','#050304','rgba(190,70,60,.22)',W/2,420,460);const r=rng(77);g.fillStyle='#0b0708';g.fillRect(0,640,W,H);
        [[-.28,'moon'],[.28,'star'],[0,'sun']].forEach(function(cd){const a=cd[0],m=cd[1];
          g.save();g.translate(W/2,700);g.rotate(a);g.translate(0,-230);
          g.fillStyle='rgba(0,0,0,.55)';g.filter='blur(12px)';g.fillRect(-100,-160,220,360);g.filter='none';
          const cw=200,chh=330;g.fillStyle='#1c1418';g.fillRect(-cw/2,-chh/2,cw,chh);
          g.strokeStyle='#b8995c';g.lineWidth=2;g.strokeRect(-cw/2+10,-chh/2+10,cw-20,chh-20);g.lineWidth=1;g.strokeRect(-cw/2+16,-chh/2+16,cw-32,chh-32);
          g.fillStyle='#d6c08e';g.strokeStyle='#d6c08e';g.textAlign='center';
          if(m==='sun'){glowAt(g,0,-10,110,'rgba(230,170,80,.25)');g.fillStyle='#d6c08e';g.beginPath();g.arc(0,-10,34,0,Math.PI*2);g.lineWidth=2;g.stroke();
            for(let i=0;i<16;i++){const t=i/16*Math.PI*2;g.beginPath();g.moveTo(Math.cos(t)*44,-10+Math.sin(t)*44);g.lineTo(Math.cos(t)*(i%2?58:70),-10+Math.sin(t)*(i%2?58:70));g.stroke()}
            g.font='22px "IM Fell English SC", Georgia, serif';g.fillText('XIX',0,-chh/2+48);g.font='16px "IM Fell English SC", Georgia, serif';g.fillText('O SOL',0,chh/2-32);}
          else if(m==='moon'){g.beginPath();g.arc(0,-10,40,0,Math.PI*2);g.fill();g.fillStyle='#1c1418';g.beginPath();g.arc(16,-18,36,0,Math.PI*2);g.fill();
            g.fillStyle='#d6c08e';g.font='22px "IM Fell English SC", Georgia, serif';g.fillText('XVIII',0,-chh/2+48);}
          else{g.save();g.translate(0,-10);g.beginPath();for(let i=0;i<10;i++){const t=i/10*Math.PI*2-Math.PI/2,rr=i%2?16:44;g.lineTo(Math.cos(t)*rr,Math.sin(t)*rr)}g.closePath();g.fill();g.restore();
            g.font='22px "IM Fell English SC", Georgia, serif';g.fillText('XVII',0,-chh/2+48);}
          for(let i=0;i<10;i++){g.fillStyle='rgba(214,192,142,'+(.3+r()*.5)+')';g.fillRect(-80+r()*160,-140+r()*260,2,2)}
          g.restore();});
        fog(g,r,600,800,.04);
      },
      grimoire(g){
        base(g,'#130e09','#040302','rgba(200,140,70,.24)',W/2,300,480);const r=rng(88);g.fillStyle='#0b0805';g.fillRect(0,660,W,H);
        g.save();g.translate(W/2,430);g.rotate(-.07);
        g.fillStyle='rgba(0,0,0,.6)';g.filter='blur(16px)';g.fillRect(-160,-200,340,440);g.filter='none';
        g.fillStyle='#cdbf9f';g.fillRect(-150,-205,318,418);
        g.strokeStyle='rgba(90,70,40,.35)';g.lineWidth=1;for(let i=0;i<12;i++){g.beginPath();g.moveTo(150,-200+i*2);g.lineTo(166,-200+i*2);g.stroke();g.beginPath();g.moveTo(152,-205);g.lineTo(152+i*1.3,213);g.stroke()}
        const cg=g.createLinearGradient(-160,-210,160,210);cg.addColorStop(0,'#4a2c19');cg.addColorStop(1,'#1d110a');g.fillStyle=cg;g.fillRect(-160,-212,312,420);
        for(let i=0;i<1400;i++){g.fillStyle='rgba(0,0,0,'+(r()*.2)+')';g.fillRect(-160+r()*312,-212+r()*420,2,2)}
        g.fillStyle='rgba(0,0,0,.35)';g.fillRect(-160,-212,26,420);
        g.strokeStyle='rgba(190,150,90,.55)';g.lineWidth=2;g.strokeRect(-120,-180,240,356);g.lineWidth=1;g.strokeRect(-112,-172,224,340);
        g.fillStyle='#8f7446';[[-160,-212,1,1],[152,-212,-1,1],[-160,208,1,-1],[152,208,-1,-1]].forEach(function(c){g.beginPath();g.moveTo(c[0],c[1]);g.lineTo(c[0]+40*c[2],c[1]);g.lineTo(c[0],c[1]+40*c[3]);g.closePath();g.fill()});
        g.strokeStyle='#d0b074';g.lineWidth=2;g.beginPath();g.arc(0,-10,74,0,Math.PI*2);g.stroke();g.beginPath();g.arc(0,-10,62,0,Math.PI*2);g.stroke();
        g.beginPath();for(let i=0;i<3;i++){const t=i/3*Math.PI*2-Math.PI/2;g.lineTo(Math.cos(t)*62,-10+Math.sin(t)*62)}g.closePath();g.stroke();
        g.beginPath();g.moveTo(-26,-4);g.quadraticCurveTo(0,-26,26,-4);g.quadraticCurveTo(0,18,-26,-4);g.stroke();
        g.fillStyle='#d0b074';g.beginPath();g.arc(0,-4,6,0,Math.PI*2);g.fill();
        [[-100,-10,1],[100,-10,-1]].forEach(function(c){g.fillStyle='#d0b074';g.beginPath();g.arc(c[0],c[1],12,0,Math.PI*2);g.fill();g.fillStyle='#2d1a0f';g.beginPath();g.arc(c[0]+6*c[2],c[1]-3,11,0,Math.PI*2);g.fill()});
        g.fillStyle='#d0b074';g.font='18px "IM Fell English SC", Georgia, serif';g.textAlign='center';g.fillText('LIBER NOCTIS',0,130);
        g.restore();glowAt(g,W/2,300,180,'rgba(255,200,130,.06)');fog(g,r,560,800,.045);
      }
    };
    return {DRAW: DRAW, finish: finish};
  }
var lib=null;
window.CovilArt={paint:function(cv,key,seed){if(!cv)return;lib=lib||drawLib();var g=cv.getContext('2d');g.clearRect(0,0,600,800);lib.DRAW[key](g);lib.finish(g,seed||100);}};
window.dispatchEvent(new Event('covilart'));
})();
