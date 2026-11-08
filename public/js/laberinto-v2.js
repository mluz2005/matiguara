/* =========================================================================
   laberinto-v2.js — «El camino a la escuela» (MatiGuará)
   Juego en el navegador. El servidor entrega la semilla del laberinto y los
   ejercicios (window.LB_CFG); acá solo se dibuja, se camina y se responde.
   Cada respuesta se envía a /movimiento y al llegar se cierra con /cerrar.
   Los textos en guaraní son BORRADORES a validar con una persona hablante.
   ========================================================================= */
(function(){
'use strict';

/* ================= recursos y configuración (vienen del servidor) ================= */
var CFG=window.LB_CFG;
var IMG=CFG.imgs;
var SND=CFG.snds;

/* ================= utilidades ================= */
function $(s){return document.querySelector(s)}
function el(tag,cls,html){var e=document.createElement(tag);if(cls)e.className=cls;if(html!=null)e.innerHTML=html;return e}
function lsGet(k,d){try{var v=localStorage.getItem(k);return v?JSON.parse(v):d}catch(e){return d}}
function lsSet(k,v){try{localStorage.setItem(k,JSON.stringify(v))}catch(e){}}
function lsDel(k){try{localStorage.removeItem(k)}catch(e){}}
function mulberry32(a){return function(){a|=0;a=a+0x6D2B79F5|0;var t=Math.imul(a^a>>>15,1|a);t=t+Math.imul(t^t>>>7,61|t)^t;return((t^t>>>14)>>>0)/4294967296}}
function rint(r,a,b){return a+Math.floor(r()*(b-a+1))}
function pick(r,arr){return arr[Math.floor(r()*arr.length)]}
function shuffle(r,arr){var a=arr.slice();for(var i=a.length-1;i>0;i--){var j=Math.floor(r()*(i+1));var t=a[i];a[i]=a[j];a[j]=t}return a}
function lowerFirst(s){return s.charAt(0).toLowerCase()+s.slice(1)}
function cap(s){return s.charAt(0).toUpperCase()+s.slice(1)}
var REDUCE=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ================= datos del juego ================= */
var GRADOS={1:{max:20,txt:'hasta 20'},2:{max:100,txt:'hasta 100'},3:{max:1000,txt:'hasta 1000'}};
var NIVELES=[
 {n:1,nombre:'Sumas simples',op:'suma',dur:'easy'},
 {n:2,nombre:'Sumas con llevada',op:'suma',dur:'hard'},
 {n:3,nombre:'Restas simples',op:'resta',dur:'easy'},
 {n:4,nombre:'Restas con préstamo',op:'resta',dur:'hard'},
 {n:5,nombre:'Sumas y restas',op:'mezcla'},
 {n:6,nombre:'¿Cuánto falta?',op:'falta'},
 {n:7,nombre:'Dos pasos',op:'dos'},
 {n:8,nombre:'Dos pasos y cuánto falta',op:'dos_falta'}
];
var ESC=['chacra','arroyo','campo','pueblo'];
var ESCDATA={
 chacra:{nombre:'Chacra y mandiocal',emo:'🌱',bg:'#7DB46A',wall:'#2F8F4E',wallD:'#216B38',path:'#C9683F',pathD:'#B25A35',deco:'🌿',
   obs:[['🧺','Canasto de mandioca'],['🪵','Árbol caído']],o:{sg:'mandioca',pl:'mandiocas',fem:true,gn:"mandi'o"}},
 arroyo:{nombre:'Arroyo',emo:'💧',bg:'#8FCBE0',wall:'#3F8FD2',wallD:'#2B6FAE',path:'#E8C98A',pathD:'#D6B472',deco:'🐟',
   obs:[['🪨','Piedras del arroyo'],['🚧','Puente roto']],o:{sg:'pez',pl:'peces',fem:false,gn:'pira'}},
 campo:{nombre:'Campo con ganado',emo:'🐄',bg:'#A5D170',wall:'#4F9A3A',wallD:'#3A7A2B',path:'#D9B27A',pathD:'#C69E64',deco:'🌳',
   obs:[['🐄','Vacas en el camino'],['🐂','Toro en el portón']],o:{sg:'vaca',pl:'vacas',fem:true,gn:'vaka'}},
 pueblo:{nombre:'Pueblo y chipería',emo:'🏘️',bg:'#C9B79B',wall:'#D98B6C',wallD:'#B76A4D',path:'#CFC6B6',pathD:'#BDB3A1',deco:'🪟',
   obs:[['🚪','Portón cerrado'],['🛒','Compra de chipas']],o:{sg:'chipa',pl:'chipas',fem:true,gn:'chipa'}}
};
function escDe(nivel){return ESC[Math.ceil(nivel/2)-1]}

/* mensajes (borrador en guaraní: pendiente de validar) */
var MSG={
 inicio:[['¡Buen día! Hay obstáculos en el camino. ¡Vamos a la escuela!','Mba\'éichapa! Ñande rape henyhẽ mba\'e rehe. Jaha mbo\'ehaópe!'],['Tocá un lugar del camino y caminamos juntos.','Eikutu ñande rape ha jaha oñondive.']],
 obstaculo:[['¡Ey, algo bloquea el camino! Resolvamos este problema.','Oĩ mba\'e ojokóva ñande rape. Jajapo ko problema!'],['Un obstáculo. ¡Con números lo arreglamos!','Peteĩ mba\'e ojokóva. Papapy ndive jajapo!']],
 acierto:[['¡Muy bien! El camino se abre.','Iporãiterei! Ñande rape ojepe\'a.'],['¡Eso es! Seguimos para la escuela.','Upéicha! Jaha gueteri mbo\'ehaópe.']],
 error:[['Casi. Mirá la pista y probá de nuevo.','Sapy\'ami. Ehecha pe pytyvõ ha eha\'ã jey.'],['Está cerca. ¡Probá otra vez!','Hi\'aguĩma. Eha\'ã jey!']],
 solucion:[['No pasa nada. Mirá cómo se resuelve; luego va uno parecido.','Ndaipóri problema. Ehecha mba\'éichapa ojejapo; upéi oúta ambue.'],['Entre todos lo arreglamos. Después va uno parecido.','Ñande ñepytyvõ jajapo. Upéi oúta ambue ojogua.']],
 reparacion:[['En reparación. Tocalo cuando quieras probar uno parecido.','Ojejapo gueteri. Eikutu eipota ramo ambue.']],
 atencion:[['¡Mirá bien!','Ehecha porã!']],
 chipa:[['¡Una chipa! Se la llevamos a la maestra.','Peteĩ chipa! Jaru ñande mbo\'ehárape.'],['¡Qué rica! La guardamos para el recreo.','Chipa ipirũ! Jajapyhy recreo guarã.']],
 pista:[['Te doy una pista.','Ame\'ẽta ndéve peteĩ pytyvõ.']],
 flecha:[['¡Por acá se llega a la escuela!','Péva rupi jaguahẽ mbo\'ehaópe!']],
 llegada:[['¡Llegaste a la escuela! ¡Suena la campana!','Ereguahẽ mbo\'ehaópe! Oñembopu pe campana!']],
 maestra:[['¡Llegaste justo para el recreo! ¿Compartimos las chipas?','Ereguahẽ recreo guarã! Jaja\'o chipa?'],['¡Qué bien caminaste! Gracias por las chipas.','Iporãiterei reguata! Aguyje chipa rehe.']],
 poco:[['Falta poquito para terminar por hoy. ¡Sigamos!','Sapy\'ami opa hag̃ua ko árape. Jaha gueteri!']],
 sinChipas:[['¡Llegaste! Otro día buscamos chipas en los callejones.','Ereguahẽ! Ambue árape jaheka chipa.']]
};

/* ================= utilidades de aritmética (pistas) ================= */
function digits(n){return String(n).split('').map(Number).reverse()}
function carries(a,b){var c=0,pos=[],i=0;while(a>0||b>0||c>0){var s=(a%10)+(b%10)+c;c=s>=10?1:0;if(c)pos.push(i);a=Math.floor(a/10);b=Math.floor(b/10);i++}return pos}
function borrows(a,b){var br=0,pos=[],i=0;while(a>0||b>0){var da=a%10-br,db=b%10;if(da<db){br=1;pos.push(i)}else br=0;a=Math.floor(a/10);b=Math.floor(b/10);i++}return pos}
function eqHTML(d){
  var q='<span class="q">?</span>';
  if(d.tipo==='suma')return d.a+' + '+d.b+' = '+q;
  if(d.tipo==='resta')return d.a+' − '+d.b+' = '+q;
  if(d.tipo==='cuanto_falta')return d.a+' + '+q+' = '+d.c;
  if(d.tipo==='dos_pasos')return d.sg==='+-'?d.a+' + '+d.b+' − '+d.c+' = '+q:d.a+' − '+d.b+' + '+d.c+' = '+q;
  return d.a+' + '+d.b+' + '+q+' = '+d.c;
}
function eqText(d){return eqHTML(d).replace(/<[^>]+>/g,'')}

/* ---- pistas y soluciones ---- */
var PLACE=['Unidades','Decenas','Centenas'];
function addSteps(a,b){
  var lines=[],c=0,da=digits(a),db=digits(b),n=Math.max(da.length,db.length);
  for(var i=0;i<n;i++){
    var x=da[i]||0,y=db[i]||0;if(!x&&!y&&!c)continue;
    var s=x+y+c,t=PLACE[i]+': '+x+' + '+y+(c?' + '+c:'')+' = '+s+'.';
    if(s>=10){t+=' Escribo '+(s-10)+' y llevo 1.';c=1}else c=0;
    lines.push(t);
  }
  return lines;
}
function subSteps(a,b){
  var lines=[],br=0,da=digits(a),db=digits(b);
  for(var i=0;i<da.length;i++){
    var base=da[i],y=db[i]||0,P=PLACE[i],cur,t;
    if(i>0&&base===0&&y===0&&!br&&i>=db.length&&i===da.length-1)break;
    if(br&&base===0){t=P+': hay 0 y ya presté 1. Pido 1 a la columna siguiente: queda 9. 9 − '+y+' = '+(9-y)+'.';br=1}
    else if(br){
      cur=base-1;
      if(cur<y){t=P+': '+base+' − 1 que presté = '+cur+'. '+cur+' − '+y+' no alcanza: pido 1 prestado y hago '+(cur+10)+' − '+y+' = '+(cur+10-y)+'.';br=1}
      else{t=P+': '+base+' − 1 que presté = '+cur+'. '+cur+' − '+y+' = '+(cur-y)+'.';br=0}
    }else if(base<y){t=P+': '+base+' − '+y+' no alcanza. Pido 1 prestado y hago '+(base+10)+' − '+y+' = '+(base+10-y)+'.';br=1}
    else{t=P+': '+base+' − '+y+' = '+(base-y)+'.';br=0}
    lines.push(t);
  }
  return lines;
}
function solFor(d,g){
  var a=d.a,b=d.b,c=d.c,s=d.s,ans=d.ans,es=[],eq=[],t,u;
  if(d.tipo==='suma'){
    if(g===1){
      if(ans<=10)es=['Empezá en '+a+' y contá '+b+' más: llegás a '+ans+'.'];
      else{t=10-a;es=['Completá hasta 10: '+a+' + '+t+' = 10.','Te sobran '+(b-t)+': 10 + '+(b-t)+' = '+ans+'.']}
      eq=[a+' + '+b+' = '+ans];
    }else{
      es=addSteps(a,b).concat(['Resultado: '+a+' + '+b+' = '+ans+'.']);
      var ua=a%10,ub=b%10;eq=[ua+' + '+ub+' = '+(ua+ub),(a-ua)+' + '+(b-ub)+' = '+(a-ua+b-ub),(a-ua+b-ub)+' + '+(ua+ub)+' = '+ans];
    }
  }else if(d.tipo==='resta'){
    if(g===1){
      if(a<=10)es=['Empezá en '+a+' y contá '+b+' hacia atrás: llegás a '+ans+'.'];
      else{u=a-10;es=['Primero quitá '+u+' para llegar a 10: '+a+' − '+u+' = 10.','Todavía quitá '+(b-u)+': 10 − '+(b-u)+' = '+ans+'.']}
    }else es=subSteps(a,b).concat(['Resultado: '+a+' − '+b+' = '+ans+'.']);
    eq=[a+' − '+b+' = '+ans];
  }else if(d.tipo==='cuanto_falta'){
    es=['Contá desde '+a+' hasta '+c+': '+c+' − '+a+' = '+ans+'.','Comprobamos: '+a+' + '+ans+' = '+c+'.'];
    eq=[c+' − '+a+' = '+ans,a+' + '+ans+' = '+c];
  }else if(d.tipo==='dos_pasos'){
    if(d.sg==='+-'){es=['Primero: '+a+' + '+b+' = '+s+'.','Después: '+s+' − '+c+' = '+ans+'.'];eq=[a+' + '+b+' = '+s,s+' − '+c+' = '+ans]}
    else{es=['Primero: '+a+' − '+b+' = '+s+'.','Después: '+s+' + '+c+' = '+ans+'.'];eq=[a+' − '+b+' = '+s,s+' + '+c+' = '+ans]}
  }else{
    es=['Primero juntamos: '+a+' + '+b+' = '+s+'.','Para llegar a '+c+' faltan: '+c+' − '+s+' = '+ans+'.'];
    eq=[a+' + '+b+' = '+s,c+' − '+s+' = '+ans];
  }
  return{es:es,gn:'Ñemyesakã: '+eq.join(' · ')};
}
function hintFor(d,g){
  var a=d.a,b=d.b,c=d.c;
  if(d.tipo==='suma'){
    if(g===1)return d.hard?['Primero completá hasta 10 y después sumá lo que sobra.','Eipapa raẽ 10 peve, upéi embojoaju pe hembýva.']:['Empezá en el número más grande y contá lo que falta con los dedos.','Eñepyrũ pe tuichavéva-gui ha eipapa pe hembýva.'];
    return[d.hard?'Sumá primero las unidades. Si pasan de 9, llevás 1 a la columna siguiente.':'Sumá unidades con unidades, decenas con decenas'+(g===3?' y centenas con centenas':'')+'.','Embojoaju raẽ '+(a%10)+' + '+(b%10)+'. Upéi pe ambue.'];
  }
  if(d.tipo==='resta'){
    if(g===1)return d.hard?['Primero quitá hasta llegar a 10.','Eipe\'a raẽ 10 peve.']:['Empezá en '+a+' y contá '+b+' hacia atrás.','Eñepyrũ '+a+'-gui ha eipapa '+b+' tapykuépe.'];
    return[d.hard?'Si en las unidades no alcanza, pedí una decena prestada.':'Restá unidades con unidades y decenas con decenas'+(g===3?' y centenas con centenas':'')+'.',d.hard?'Ndoikói ramo, ejerure peteĩ decena.':'Eipe\'a raẽ '+(b%10)+' '+(a%10)+'-gui. Upéi pe ambue.'];
  }
  if(d.tipo==='cuanto_falta')return['Pensá: ¿cuánto le sumás a '+a+' para llegar a '+c+'? Contá desde '+a+'.','Eipapa '+a+'-gui '+c+' peve.'];
  if(d.tipo==='dos_pasos')return['Hacelo en dos pasos. Primero: '+a+(d.sg==='+-'?' + ':' − ')+b+'.','Ejapo mokõi ñepyrũ. Peteĩha: '+a+(d.sg==='+-'?' + ':' − ')+b+'.'];
  return['Primero juntá lo que ya hay: '+a+' + '+b+'. Después pensá cuánto falta para '+c+'.','Embojoaju raẽ '+a+' + '+b+'. Upéi eipapa '+c+' peve.'];
}

/* ---- material de apoyo visual ---- */
function dots(n,cls,fade,q){
  var h='<div class="dots '+(cls||'')+'" role="img" aria-label="'+n+' puntos">',i;
  for(i=0;i<n;i++)h+='<span class="'+(fade&&i>=n-fade?'gone':'')+'"></span>';
  for(i=0;i<(q||0);i++)h+='<span class="q"></span>';
  return h+'</div>';
}
function blocks(n,cls){
  var hh=Math.floor(n/100),tt=Math.floor((n%100)/10),uu=n%10,h='<div class="blocks '+(cls||'')+'" role="img" aria-label="'+n+'">',i;
  for(i=0;i<hh;i++)h+='<i class="h"></i>';
  for(i=0;i<tt;i++)h+='<i class="t"></i>';
  for(i=0;i<uu;i++)h+='<i class="u"></i>';
  return h+'</div>';
}
function grp(lab,html){return '<div class="grp"><span class="lab">'+lab+'</span>'+html+'</div>'}
function aidHTML(ex){
  var d=ex.d,g=ex.g,rep=function(n,cls){return g===1?dots(n,cls):blocks(n,cls)},op=function(s){return '<span class="op" aria-hidden="true">'+s+'</span>'};
  var h='<div class="aid">';
  if(d.tipo==='suma')h+=grp('Hay '+d.a,rep(d.a))+op('+')+grp('Se suman '+d.b,rep(d.b,'b'));
  else if(d.tipo==='resta'){
    if(g===1)h+=grp('Hay '+d.a+' y se quitan '+d.b,dots(d.a,'',d.b));
    else h+=grp('Hay '+d.a,rep(d.a))+op('−')+grp('Se quitan '+d.b,rep(d.b,'b'));
  }else if(d.tipo==='cuanto_falta'){
    if(g===1)h+=grp('Ya hay '+d.a+'. Faltan ? para llegar a '+d.c,dots(d.a,'',0,d.c-d.a));
    else h+=grp('Ya hay '+d.a,rep(d.a))+op('+ ?')+grp('Se necesitan '+d.c,rep(d.c,'b'));
  }else if(d.tipo==='dos_pasos')h+=grp('Primer paso: hay '+d.a,rep(d.a))+op(d.sg==='+-'?'+':'−')+grp(d.b+'',rep(d.b,'b'))+'<span class="lab">Después '+(d.sg==='+-'?'se quitan ':'se suman ')+d.c+'</span>';
  else h+=grp('Hay '+d.a,rep(d.a))+op('+')+grp('Después '+d.b,rep(d.b,'b'))+'<span class="lab">Hay que llegar a '+d.c+'</span>';
  return h+'</div>';
}


/* ---- ejercicio del servidor (fila de contenido_nivel) -> objeto del juego ---- */
function dFromRow(r,g){
  var a=r.valor_a,b=r.valor_b,t=r.tipo_operacion,ans=r.respuesta_correcta,sg;
  if(t==='suma')return{tipo:'suma',a:a,b:b,ans:ans,hard:g===1?(a+b>10):carries(a,b).length>0};
  if(t==='resta')return{tipo:'resta',a:a,b:b,ans:ans,hard:g===1?(a>10):borrows(a,b).length>0};
  if(t==='cuanto_falta')return{tipo:'cuanto_falta',a:a,b:ans,c:b,ans:ans};
  if(t==='dos_pasos'){sg=(r.operador||'')+(r.operador_2||'');return{tipo:'dos_pasos',a:a,b:b,c:r.valor_c,sg:sg,s:sg==='+-'?a+b:a-b,ans:ans}}
  return{tipo:'dos_pasos_falta',a:a,b:b,c:r.valor_c,s:a+b,ans:ans};
}
function exFromRow(r,g){
  var d=dFromRow(r,g),h=hintFor(d,g),s=solFor(d,g);
  return{id:r.id,g:g,d:d,tipo_operacion:r.tipo_operacion,respuesta_correcta:r.respuesta_correcta,
    opciones_json:r.opciones_json.slice(),enunciado_es:r.enunciado_es,enunciado_gn:r.enunciado_gn,
    pista_es:h[0],pista_gn:h[1],sol_es:s.es,sol_gn:s.gn,eq:eqHTML(d)};
}
/* ================= laberinto ================= */
function cfgNivel(n){return n<=2?{cw:4,ch:6,nobs:4,nch:3}:(n<=5?{cw:5,ch:7,nobs:5,nch:4}:{cw:6,ch:8,nobs:6,nch:5})}
var DIRS=[[1,0],[-1,0],[0,1],[0,-1]];
function bfsPath(grid,from,to){
  var W=grid[0].length,H=grid.length,prev={},q=[from],sk=from[1]*W+from[0],tk=to[1]*W+to[0];
  prev[sk]=-1;
  while(q.length){
    var c=q.shift();if(c[0]===to[0]&&c[1]===to[1])break;
    for(var i=0;i<4;i++){
      var nx=c[0]+DIRS[i][0],ny=c[1]+DIRS[i][1];
      if(nx<0||ny<0||nx>=W||ny>=H||!grid[ny][nx])continue;
      var k=ny*W+nx;if(k in prev)continue;prev[k]=c[1]*W+c[0];q.push([nx,ny]);
    }
  }
  if(!(tk in prev))return null;
  var path=[],k2=tk;
  while(k2!==-1&&k2!==sk){path.push([k2%W,Math.floor(k2/W)]);k2=prev[k2]}
  return path.reverse();
}
function buildMaze(grado,nivel,seed,land,nobs){
  var cfg=cfgNivel(nivel);if(nobs)cfg.nobs=nobs;
  if(land){var sw=cfg.cw;cfg.cw=cfg.ch;cfg.ch=sw}
  var cw=cfg.cw,ch=cfg.ch,W=2*cw+1,H=2*ch+1;
  for(var attempt=0;attempt<120;attempt++){
    var s2=seed+attempt*7919,r=mulberry32(s2),x,y,i;
    var vis=[],adj=[];
    for(y=0;y<ch;y++){vis.push([]);for(x=0;x<cw;x++){vis[y].push(false);adj.push([])}}
    var sy=rint(r,0,ch-1),ey=rint(r,0,ch-1),stack=[[0,sy]];
    vis[sy][0]=true;
    while(stack.length){
      var cur=stack[stack.length-1],nb=[];
      for(i=0;i<4;i++){var nx=cur[0]+DIRS[i][0],ny=cur[1]+DIRS[i][1];if(nx>=0&&ny>=0&&nx<cw&&ny<ch&&!vis[ny][nx])nb.push([nx,ny])}
      if(!nb.length){stack.pop();continue}
      var n=pick(r,nb);vis[n[1]][n[0]]=true;
      adj[cur[1]*cw+cur[0]].push(n[1]*cw+n[0]);adj[n[1]*cw+n[0]].push(cur[1]*cw+cur[0]);
      stack.push(n);
    }
    var grid=[];
    for(y=0;y<H;y++){grid.push([]);for(x=0;x<W;x++)grid[y].push(0)}
    for(y=0;y<ch;y++)for(x=0;x<cw;x++){
      grid[2*y+1][2*x+1]=1;
      adj[y*cw+x].forEach(function(k){var nx2=k%cw,ny2=Math.floor(k/cw);grid[y+ny2+1][x+nx2+1]=1});
    }
    var house=[0,2*sy+1],school=[W-1,2*ey+1];
    grid[house[1]][house[0]]=1;grid[school[1]][school[0]]=1;
    var route=bfsPath(grid,house,school);
    if(!route)continue;
    var pas=[];
    for(i=0;i<route.length-1;i++){var t=route[i];if((t[0]%2===0)!==(t[1]%2===0))pas.push(t)}
    if(pas.length<cfg.nobs*2+1)continue;
    var leaves=[];
    for(y=0;y<ch;y++)for(x=0;x<cw;x++){
      if(adj[y*cw+x].length===1&&!(x===0&&y===sy)&&!(x===cw-1&&y===ey))leaves.push([2*x+1,2*y+1]);
    }
    if(leaves.length<3)continue;
    /* obstáculos: uno por tramo de la ruta, siempre sobre un paso obligatorio */
    var obstacles=[],seg=pas.length/cfg.nobs;
    for(i=0;i<cfg.nobs;i++){
      var lo=Math.floor(i*seg+seg*.25),hi=Math.max(lo,Math.floor(i*seg+seg*.75)-1);
      var t2=pas[Math.min(pas.length-1,rint(r,lo,hi))];
      obstacles.push({x:t2[0],y:t2[1]});
    }
    var ch2=shuffle(r,leaves).slice(0,Math.min(cfg.nch,leaves.length));
    return{W:W,H:H,grid:grid,house:house,school:school,route:route,obstacles:obstacles,chipas:ch2.map(function(p){return{x:p[0],y:p[1]}}),seed:s2};
  }
  return null;
}


/* ================= estado ================= */
var S=null,P=null;
var stage=$('#stage'),cv=$('#cv'),ctx=cv.getContext('2d'),stat=document.createElement('canvas'),sctx=stat.getContext('2d');
var ts=30,ox=0,oy=0,cssW=0,cssH=0,dpr=1,STEP=REDUCE?80:135;
var sound=lsGet('lb_sound_v1',true);
var KS='lb_s_'+CFG.sesionId,KQ='lb_q_'+CFG.sesionId;

function newGame(land){
  var maze=buildMaze(CFG.grado,CFG.nivel,CFG.semilla,!!land,CFG.obstaculos.length),sc=escDe(CFG.nivel);
  if(!maze){S=null;return false}
  S={grado:CFG.grado,nivel:CFG.nivel,avatar:CFG.avatar,seed:CFG.semilla,land:!!land,maze:maze,sc:sc,
    tx:maze.house[0],ty:maze.house[1],px:maze.house[0],py:maze.house[1],step:null,queue:[],mode:'walk',
    obs:maze.obstacles.map(function(o,i){var pr=ESCDATA[sc].obs[i%2];return{idx:i,x:o.x,y:o.y,emo:pr[0],name:pr[1],state:'pending',round:0,ex:null,exRound:-1}}),
    chip:maze.chipas.map(function(c){return{x:c.x,y:c.y,got:false}}),
    st:{puntos:0,racha:0,mejor:0,hintEx:0,arrow:false,solObs:{},first:0,done:0,att:null},
    arrowOk:false,arrowFx:null,mark:null,fx:[],bump:0,shift:0,shiftT:0,warned:false,timeUp:false,
    finMs:CFG.limiteSeg>0?Date.now()+CFG.restanteSeg*1000:0,elBase:0,tStart:performance.now(),won:false};
  P=null;
  return true;
}
function elapsed(){return S.elBase+(performance.now()-S.tStart)}
function saveSession(){
  if(!S)return;
  lsSet(KS,{land:S.land,x:S.tx,y:S.ty,w:S.won?1:0,
    obs:S.obs.map(function(o){return{s:o.state,r:o.round}}),chip:S.chip.map(function(c){return c.got?1:0}),
    st:S.st,arrowOk:S.arrowOk,el:Math.round(elapsed())});
}
function restoreSession(d){
  S.tx=S.px=d.x;S.ty=S.py=d.y;
  d.obs.forEach(function(o,i){if(S.obs[i]){S.obs[i].state=o.s;S.obs[i].round=o.r}});
  d.chip.forEach(function(c,i){if(S.chip[i])S.chip[i].got=!!c});
  S.st=d.st;S.arrowOk=!!d.arrowOk;S.elBase=d.el||0;S.tStart=performance.now();
}
function limpiarViejos(){
  try{
    var del=[];
    for(var i=0;i<localStorage.length;i++){
      var k=localStorage.key(i);
      if(k&&(k.indexOf('lb_s_')===0||k.indexOf('lb_q_')===0)&&k!==KS&&k!==KQ)del.push(k);
    }
    del.forEach(function(k){localStorage.removeItem(k)});
  }catch(e){}
}

/* ================= comunicación con el servidor ================= */
function csrf(){var m=document.querySelector('meta[name="csrf-token"]');return m?m.getAttribute('content'):''}
function post(url,data){
  return fetch(url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf(),'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(data)})
    .then(function(res){if(res.ok)return res.json();var e=new Error('http '+res.status);e.status=res.status;throw e});
}
/* Cola de respuestas: se guarda en el navegador y se reintenta hasta que el servidor la reciba */
var Q=lsGet(KQ,[]),busy=false,waiters=[],fatalGo=false;
function irAlDescanso(){if(fatalGo)return;fatalGo=true;location.href=CFG.urls.descanso}
function enqueue(item){Q.push(item);lsSet(KQ,Q);pump()}
function pump(){
  if(busy)return;
  if(!Q.length){waiters.splice(0).forEach(function(f){f()});return}
  busy=true;
  post(CFG.urls.movimiento,Q[0]).then(function(){Q.shift();lsSet(KQ,Q);busy=false;pump()}).catch(function(e){
    busy=false;
    if(e&&(e.status===403||e.status===401)){irAlDescanso();return}
    if(e&&e.status===422){Q.shift();lsSet(KQ,Q);pump();return}
    setTimeout(pump,2500);
  });
}
function whenEmpty(ms){
  return new Promise(function(res){
    if(!Q.length&&!busy)return res();
    var t=setTimeout(res,ms);
    waiters.push(function(){clearTimeout(t);res()});
    pump();
  });
}
function setPose(p){var i=$('#g-img');if(i.dataset.p!==p){i.dataset.p=p;i.src=IMG[p]||IMG.cara}}
function say(ctxName,pose){
  var arr=MSG[ctxName];if(!arr)return;var m=arr[Math.floor(Math.random()*arr.length)];
  $('#g-es').textContent=m[0];$('#g-gn').textContent=m[1];setPose(pose||'cara');
}

/* ================= sonido ================= */
var actx=null,aud={};
function audCtx(){try{if(!actx)actx=new (window.AudioContext||window.webkitAudioContext)();if(actx.state==='suspended')actx.resume();return actx}catch(e){return null}}
function tone(freqs,dur,type,gain){
  if(!sound)return;var c=audCtx();if(!c)return;
  freqs.forEach(function(f,i){
    var o=c.createOscillator(),g=c.createGain(),t0=c.currentTime+i*dur*.55;
    o.type=type||'sine';o.frequency.value=f;g.gain.setValueAtTime(0,t0);g.gain.linearRampToValueAtTime(gain||.15,t0+.01);g.gain.exponentialRampToValueAtTime(.0001,t0+dur);
    o.connect(g);g.connect(c.destination);o.start(t0);o.stop(t0+dur+.05);
  });
}
function play(k){
  if(!sound)return;
  try{if(!aud[k]){aud[k]=new Audio(SND[k])}aud[k].currentTime=0;var p=aud[k].play();if(p&&p.catch)p.catch(function(){})}catch(e){}
}
function bell(){tone([880,1174,880,1174],.9,'sine',.18)}
function pop(){tone([660,990],.16,'triangle',.14)}

/* ================= dibujo ================= */
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}
var FONT='"Nunito",system-ui,sans-serif';
var EMO='"Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji",sans-serif';
function emo(c,ch,cx,cy,size){c.font=size+'px '+EMO;c.textAlign='center';c.textBaseline='middle';c.fillText(ch,cx,cy+size*.06)}
function fit(){
  if(!S)return;
  var r=stage.getBoundingClientRect();if(r.width<10||r.height<10)return;
  cssW=r.width;cssH=r.height;dpr=Math.min(2,window.devicePixelRatio||1);
  cv.width=Math.round(cssW*dpr);cv.height=Math.round(cssH*dpr);cv.style.width=cssW+'px';cv.style.height=cssH+'px';
  var W=S.maze.W,H=S.maze.H;
  ts=Math.max(12,Math.floor(Math.min((cssW-6)/W,(cssH-6)/H)));
  ox=Math.floor((cssW-ts*W)/2);oy=Math.floor((cssH-ts*H)/2);
  stat.width=cv.width;stat.height=cv.height;
  drawStatic();
  adjustView();
}
function drawStatic(){
  var e=ESCDATA[S.sc],m=S.maze,c=sctx,r=mulberry32(m.seed+5);
  c.setTransform(dpr,0,0,dpr,0,0);c.clearRect(0,0,cssW,cssH);
  c.fillStyle=e.bg;c.fillRect(0,0,cssW,cssH);
  stage.style.background=e.bg;
  var x,y,px,py;
  for(y=0;y<m.H;y++)for(x=0;x<m.W;x++){
    px=ox+x*ts;py=oy+y*ts;
    if(m.grid[y][x]){
      c.fillStyle=e.path;c.fillRect(px,py,ts+.5,ts+.5);
      c.fillStyle=e.pathD;
      for(var k=0;k<2;k++){c.beginPath();c.arc(px+r()*ts,py+r()*ts,Math.max(1,ts*.045),0,6.3);c.fill()}
    }
  }
  for(y=0;y<m.H;y++)for(x=0;x<m.W;x++){
    if(m.grid[y][x])continue;
    px=ox+x*ts;py=oy+y*ts;
    c.fillStyle=e.wallD;rr(c,px+1,py+3,ts-2,ts-3,ts*.28);c.fill();
    c.fillStyle=e.wall;rr(c,px+1,py+1,ts-2,ts-4,ts*.28);c.fill();
    if(r()<.24){c.globalAlpha=.92;emo(c,e.deco,px+ts/2,py+ts/2,ts*.58);c.globalAlpha=1}
  }
  emo(c,'🏠',ox+m.house[0]*ts+ts/2,oy+m.house[1]*ts+ts/2,ts*.95);
  emo(c,'🏫',ox+m.school[0]*ts+ts/2,oy+m.school[1]*ts+ts/2,ts*.95);
}
function drawChipa(c,cx,cy,rad){
  c.save();c.translate(cx,cy);
  c.lineWidth=rad*.62;c.strokeStyle='#1B3361';c.beginPath();c.arc(0,0,rad*.6,0,6.2832);c.stroke();
  c.lineWidth=rad*.4;c.strokeStyle='#E0A33A';c.beginPath();c.arc(0,0,rad*.6,0,6.2832);c.stroke();
  c.lineWidth=rad*.1;c.strokeStyle='#FFE3A0';c.setLineDash([rad*.28,rad*.3]);c.beginPath();c.arc(0,0,rad*.6,0,6.2832);c.stroke();
  c.restore();
}
function tileC(x,y){return[ox+x*ts+ts/2,oy+y*ts+ts/2]}
function draw(t){
  var c=ctx,i;
  c.setTransform(dpr,0,0,dpr,0,0);c.clearRect(0,0,cssW,cssH);
  S.shift+=(S.shiftT-S.shift)*(REDUCE?1:.2);if(Math.abs(S.shiftT-S.shift)<.5)S.shift=S.shiftT;
  c.translate(0,-S.shift);
  c.drawImage(stat,0,0,cssW,cssH);
  var pulse=REDUCE?0:Math.sin(t/260);
  S.chip.forEach(function(ch,k){
    if(ch.got)return;var p=tileC(ch.x,ch.y);
    drawChipa(c,p[0],p[1]+(REDUCE?0:Math.sin(t/330+k)*1.6),ts*.42);
  });
  S.obs.forEach(function(o){
    if(o.state==='open')return;
    var p=tileC(o.x,o.y),rep=o.state==='repair';
    c.fillStyle=rep?'#6FD0E0':'#FFC94D';c.strokeStyle='#1B3361';c.lineWidth=2.5;
    c.beginPath();c.arc(p[0],p[1],ts*.47*(1+.05*pulse),0,6.3);c.fill();c.stroke();
    c.fillStyle='#1B3361';emo(c,rep?'🛠️':o.emo,p[0],p[1],ts*.6);
    c.fillStyle='#1B3361';c.beginPath();c.arc(p[0]+ts*.3,p[1]-ts*.3,ts*.17,0,6.3);c.fill();
    c.fillStyle='#FFFDF4';c.font='800 '+Math.round(ts*.24)+'px '+FONT;c.textAlign='center';c.textBaseline='middle';
    c.fillText(rep?'↻':'?',p[0]+ts*.3,p[1]-ts*.29);
  });
  if(S.mark){
    var k=(t-S.mark.t)/550;
    if(k>=1)S.mark=null;else{var mp=tileC(S.mark.x,S.mark.y);c.strokeStyle='#FFFDF4';c.lineWidth=3;c.globalAlpha=1-k;c.beginPath();c.arc(mp[0],mp[1],ts*(.3+k*.4),0,6.3);c.stroke();c.globalAlpha=1}
  }
  var sp=S.step?Math.abs(Math.sin(((t-S.step.t0)/S.step.dur)*Math.PI)):0;
  var pp=[ox+S.px*ts+ts/2,oy+S.py*ts+ts/2],bump=(t-S.bump<260&&!REDUCE)?Math.sin((t-S.bump)/30)*3:0;
  c.fillStyle='rgba(0,0,0,.22)';c.beginPath();c.ellipse(pp[0]+bump,pp[1]+ts*.34,ts*.3,ts*.1,0,0,6.3);c.fill();
  c.fillStyle='#000';emo(c,S.avatar,pp[0]+bump,pp[1]-sp*ts*.12,ts*.86);
  if(S.arrowFx&&performance.now()<S.arrowFx.until){
    var a=S.arrowFx,off=ts*(1.05+.14*(REDUCE?0:Math.sin(t/180))),ax=pp[0]+a.dx*off,ay=pp[1]+a.dy*off,L=ts*.5,Wd=ts*.36;
    c.save();c.translate(ax,ay);c.rotate(Math.atan2(a.dy,a.dx));
    c.fillStyle='#FFC94D';c.strokeStyle='#1B3361';c.lineWidth=3;c.lineJoin='round';
    c.beginPath();c.moveTo(L*.7,0);c.lineTo(-L*.3,-Wd);c.lineTo(-L*.3,-Wd*.4);c.lineTo(-L*.8,-Wd*.4);c.lineTo(-L*.8,Wd*.4);c.lineTo(-L*.3,Wd*.4);c.lineTo(-L*.3,Wd);c.closePath();c.fill();c.stroke();
    c.restore();
  }
  for(i=S.fx.length-1;i>=0;i--){
    var f=S.fx[i],q=(t-f.t)/700;
    if(q>=1){S.fx.splice(i,1);continue}
    var fp=tileC(f.x,f.y);
    if(f.k==='ring'){c.strokeStyle='#FFFDF4';c.lineWidth=4;c.globalAlpha=1-q;c.beginPath();c.arc(fp[0],fp[1],ts*(.4+q*.9),0,6.3);c.stroke();c.globalAlpha=1}
    else{c.globalAlpha=1-q;c.fillStyle='#1B3361';c.font='800 '+Math.round(ts*.5)+'px '+FONT;c.textAlign='center';c.fillText(f.txt,fp[0],fp[1]-ts*(.5+q*.8));c.globalAlpha=1}
  }
}

/* ================= movimiento ================= */
function obsAt(x,y){for(var i=0;i<S.obs.length;i++)if(S.obs[i].x===x&&S.obs[i].y===y)return S.obs[i];return null}
function curTile(){return S.step?[S.step.tx,S.step.ty]:[S.tx,S.ty]}
function update(t){
  if(S.mode!=='walk')return;
  if(!S.step&&S.queue.length){
    var nx=S.queue[0],ob=obsAt(nx[0],nx[1]);
    if(ob&&ob.state!=='open'){S.queue.length=0;openObstacle(ob);return}
    S.queue.shift();S.step={fx:S.px,fy:S.py,tx:nx[0],ty:nx[1],t0:t,dur:STEP};
  }
  if(S.step){
    var k=Math.min(1,(t-S.step.t0)/S.step.dur);
    S.px=S.step.fx+(S.step.tx-S.step.fx)*k;S.py=S.step.fy+(S.step.ty-S.step.fy)*k;
    if(k>=1){S.tx=S.step.tx;S.ty=S.step.ty;S.px=S.tx;S.py=S.ty;S.step=null;arrive()}
  }
}
function arrive(){
  var i,c;
  for(i=0;i<S.chip.length;i++){
    c=S.chip[i];
    if(!c.got&&c.x===S.tx&&c.y===S.ty){
      c.got=true;pop();S.fx.push({k:'txt',x:c.x,y:c.y,t:performance.now(),txt:'+1'});
      say('chipa','guino');updateHUD();
    }
  }
  if(S.tx===S.maze.school[0]&&S.ty===S.maze.school[1]){win();return}
  saveSession();
}
function adjustView(){
  if(!S||!P)return;
  var pn=$('#panel');if(pn.hidden)return;
  var gb=$('#s-juego').getBoundingClientRect(),sb=stage.getBoundingClientRect();
  pn.style.maxHeight=Math.max(200,gb.bottom-sb.top-8)+'px';
  var top=pn.getBoundingClientRect().top-sb.top,o=P.o,bottom=oy+(Math.max(S.ty,o.y)+1)*ts+8,need=Math.max(0,bottom-top),maxUp=Math.max(0,oy+Math.min(S.ty,o.y)*ts-6);
  S.shiftT=Math.max(0,Math.min(need,maxUp));
}
function tapTile(x,y){
  if(!S||S.mode!=='walk'||!$('#modal').hidden)return;
  var m=S.maze;if(x<0||y<0||x>=m.W||y>=m.H||!m.grid[y][x]){S.bump=performance.now();return}
  var o=curTile(),path=bfsPath(m.grid,o,[x,y]);
  if(!path||!path.length)return;
  S.queue=path;S.mark={x:x,y:y,t:performance.now()};
}
function stepDir(d){
  if(!S||S.mode!=='walk'||!$('#modal').hidden)return;
  var v={u:[0,-1],d:[0,1],l:[-1,0],r:[1,0]}[d],base=S.queue.length?S.queue[S.queue.length-1]:curTile(),nx=base[0]+v[0],ny=base[1]+v[1];
  if(nx<0||ny<0||nx>=S.maze.W||ny>=S.maze.H||!S.maze.grid[ny][nx]){S.bump=performance.now();return}
  if(S.queue.length<2)S.queue.push([nx,ny]);
}
function useArrow(){
  var path=bfsPath(S.maze.grid,curTile(),S.maze.school);if(!path||!path.length)return;
  var o=curTile(),n=path[0];
  S.arrowFx={dx:n[0]-o[0],dy:n[1]-o[1],until:performance.now()+6000};
  S.st.arrow=true;say('flecha','guino');updateHUD();saveSession();
}

/* ================= HUD ================= */
function starsFor(){
  var sol=Object.keys(S.st.solObs).length;
  return sol>0?1:((S.st.hintEx>0||S.st.arrow)?2:3);
}
function starsSVG(n,total){var h='';for(var i=1;i<=total;i++)h+='<svg class="'+(i<=n?'on':'off')+'" viewBox="0 0 24 24" aria-hidden="true"><use href="#lb-star"/></svg>';return h}
function updateHUD(){
  var n=starsFor(),got=S.chip.filter(function(c){return c.got}).length,first=true;
  $('#h-stars').innerHTML=starsSVG(n,3);$('#h-stars').setAttribute('aria-label',n+' de 3 estrellas posibles');
  $('#h-chipas').textContent=got+'/'+S.chip.length;
  $('#h-pts').textContent=S.st.puntos;
  var r='<span aria-hidden="true">🏠</span>';
  S.obs.forEach(function(o){var cls=o.state==='open'?'done':(first?'now':'');if(o.state!=='open')first=false;r+='<i class="'+cls+'"></i>'});
  r+='<span aria-hidden="true">🏫</span>';
  $('#h-route').innerHTML=r;
}
function setupGameUI(){
  var e=ESCDATA[S.sc];
  $('#h-nivel').textContent='Nivel '+S.nivel+' · '+e.nombre;
  $('#h-grado').textContent=S.grado+'.° grado · '+GRADOS[S.grado].txt;
  $('#panel').hidden=true;$('#modal').hidden=true;$('#rest').hidden=true;$('#arr').hidden=true;
  updateHUD();
}

/* ================= panel de ejercicio ================= */
var optBtns=[];
function openObstacle(o){
  if(S.mode==='panel')return;
  S.mode='panel';
  if(!o.ex||o.exRound!==o.round){o.ex=exFromRow(CFG.obstaculos[o.idx].ejercicios[Math.min(o.round,1)],S.grado);o.exRound=o.round}
  var ex=o.ex;
  P={o:o,ex:ex,attempts:0,hint:false,hintCounted:false,aid:false,done:false,locked:false,t0:performance.now(),pause:null,solved:false,shown:false};
  if(S.st.att&&S.st.att.i===o.idx&&S.st.att.r===o.round)P.attempts=Math.min(1,S.st.att.n);
  $('#p-emo').textContent=o.emo;$('#p-title').textContent=o.name;
  $('#p-att').textContent='Obstáculo '+(o.idx+1)+' de '+S.obs.length+(o.round>0?' · uno parecido':'');
  $('#p-es').textContent=ex.enunciado_es;$('#p-gn').textContent=ex.enunciado_gn;
  $('#p-eq').innerHTML=ex.eq;
  ['p-aid','p-hint','p-sol'].forEach(function(id){$('#'+id).hidden=true;$('#'+id).innerHTML=''});
  var box=$('#p-opts');box.innerHTML='';box.className='p-opts'+(ex.opciones_json.length===3?' n3':'');
  optBtns=ex.opciones_json.map(function(v,i){
    var b=el('button','opt','<span class="k" aria-hidden="true">'+(i+1)+'</span><span>'+v+'</span>');
    b.type='button';b.setAttribute('aria-label','Respuesta '+v);
    b.addEventListener('click',function(){choose(i)});box.appendChild(b);return b;
  });
  var fb=$('#p-fb');fb.className='p-fb';fb.innerHTML='';
  $('#t-hint').disabled=false;$('#t-aid').disabled=false;$('#t-next').hidden=true;
  $('#panel').hidden=false;$('#panel').scrollTop=0;$('#s-juego').classList.add('pn');
  say('obstaculo','piensa');
  if(P.attempts>=1)showHint(true);
}
function logMov(val,ok,ms,tipoErr,counted){
  enqueue({contenido_id:P.ex.id,respuesta:String(val),tiempo_ms:ms,intento:counted?(ok?P.attempts+1:P.attempts):0});
}
function setFb(cls,html){var f=$('#p-fb');f.className='p-fb '+cls;f.innerHTML=html}
function choose(i){
  if(!P||P.locked||P.done||optBtns[i].disabled)return;
  var ex=P.ex,val=ex.opciones_json[i],ms=Math.round(performance.now()-P.t0),ok=val===ex.respuesta_correcta;
  if(ok)return correct(i,ms,val);
  if(ms<1000)return attention(i,ms,val);
  wrong(i,ms,val);
}
function attention(i,ms,val){
  logMov(val,false,ms,'atencion',false);
  P.locked=true;optBtns.forEach(function(b){b.disabled=true});
  var m=MSG.atencion[0];
  setFb('no','<span aria-hidden="true">👀</span> '+m[0]+' <span class="gn">'+m[1]+'</span><div class="pause" style="flex:1;margin-left:8px"></div>');
  say('atencion','mira');
  setTimeout(function(){
    if(!P)return;P.locked=false;P.t0=performance.now();
    optBtns.forEach(function(b){if(!b.classList.contains('bad'))b.disabled=false});
    setFb('','');
  },2000);
}
function correct(i,ms,val){
  P.done=true;P.solved=true;S.st.att=null;
  var pts=P.attempts===0?10:5;S.st.puntos+=pts;
  if(P.attempts===0){S.st.racha++;S.st.mejor=Math.max(S.st.mejor,S.st.racha);if(P.o.round===0)S.st.first++}
  logMov(val,true,ms,null,true);
  optBtns.forEach(function(b,k){b.disabled=true;if(k===i)b.classList.add('good')});
  optBtns[i].insertAdjacentHTML('beforeend','<span aria-hidden="true">✔</span>');
  var m=MSG.acierto[Math.floor(Math.random()*MSG.acierto.length)];
  setFb('ok','<span aria-hidden="true">✔</span> '+m[0]+' <b>+'+pts+'</b>');
  $('#g-es').textContent=m[0];$('#g-gn').textContent=m[1];setPose('festeja');
  play('ok');
  P.o.state='open';
  S.st.done=S.obs.filter(function(o){return o.state==='open'}).length;
  S.fx.push({k:'ring',x:P.o.x,y:P.o.y,t:performance.now()});
  $('#t-hint').disabled=true;$('#t-aid').disabled=true;
  $('#t-next').hidden=true;
  updateHUD();
  var mine=P;P.timer=setTimeout(function(){if(P===mine)closePanel(true)},REDUCE?900:1150);
}
function wrong(i,ms,val){
  P.attempts++;S.st.racha=0;S.st.att={i:P.o.idx,r:P.o.round,n:P.attempts};
  logMov(val,false,ms,null,true);
  optBtns[i].disabled=true;optBtns[i].classList.add('bad');
  optBtns[i].insertAdjacentHTML('beforeend','<span aria-hidden="true">✖</span>');
  play('bad');
  if(P.attempts===1){
    showHint(true);
    var m=MSG.error[Math.floor(Math.random()*MSG.error.length)];
    setFb('no','<span aria-hidden="true">✖</span> '+m[0]+' <span class="gn">'+m[1]+'</span>');
    $('#g-es').textContent=m[0];$('#g-gn').textContent=m[1];setPose('guino');
    P.t0=performance.now();
  }else{
    P.done=true;P.shown=true;
    S.st.solObs[P.o.idx]=true;
    var ex=P.ex;
    optBtns.forEach(function(b,k){b.disabled=true;if(ex.opciones_json[k]===ex.respuesta_correcta){b.classList.add('good');b.insertAdjacentHTML('beforeend','<span aria-hidden="true">✔</span>')}});
    var sol=$('#p-sol');sol.hidden=false;
    sol.innerHTML='<b>Así se resuelve</b><br>'+ex.sol_es.join('<br>')+'<br><span class="gn">'+ex.sol_gn+'</span>';
    $('#p-hint').hidden=true;
    var m2=MSG.solucion[Math.floor(Math.random()*MSG.solucion.length)];
    setFb('no',m2[0]+' <span class="gn">'+m2[1]+'</span>');
    $('#g-es').textContent=m2[0];$('#g-gn').textContent=m2[1];setPose('cara');
    $('#t-hint').disabled=true;$('#t-aid').disabled=true;
    var btn=$('#t-next');btn.hidden=false;btn.textContent='Seguir ➜';
    $('#panel').scrollTop=$('#panel').scrollHeight;
    updateHUD();
  }
  saveSession();
}
function showHint(auto){
  if(!P||P.done)return;
  if(!P.hintCounted){P.hintCounted=true;S.st.hintEx++}
  P.hint=true;var h=$('#p-hint');h.hidden=false;
  h.innerHTML='<b>Pista</b><br>'+P.ex.pista_es+'<br><span class="gn">'+P.ex.pista_gn+'</span>';
  if(!auto){say('pista','guino')}
  $('#t-hint').disabled=true;updateHUD();
}
function toggleAid(){
  if(!P)return;var a=$('#p-aid');
  if(a.hidden){a.innerHTML=aidHTML(P.ex);a.hidden=false;P.aid=true}else a.hidden=true;
}
function closePanel(stepIn){
  if(!P)return;
  clearTimeout(P.timer);
  var o=P.o,wasSolved=P.solved,wasShown=P.shown;S.st.att=null;
  $('#panel').hidden=true;$('#s-juego').classList.remove('pn');
  var hadRound=o.round;
  if(wasShown){
    if(hadRound>=1){o.state='open';S.fx.push({k:'ring',x:o.x,y:o.y,t:performance.now()});stepIn=true}
    else{o.state='repair';o.round=hadRound+1;o.ex=null}
  }
  P=null;S.mode='walk';S.shiftT=0;
  if(S.timeUp){showRest();return}
  if(wasShown&&hadRound===0)say('reparacion','cara');
  if(stepIn&&o.state==='open')S.queue=[[o.x,o.y]];
  updateHUD();saveSession();
}


/* ================= límite de tiempo por sesión ================= */
/* El corte real lo hace el servidor (middleware limite.juego). Acá solo se avisa y se deja
   terminar el ejercicio en curso: nunca se interrumpe una respuesta a la mitad. */
function tickLimit(){
  if(!S||!S.finMs||S.mode==='win'||S.mode==='rest'||S.mode==='closing')return;
  var left=S.finMs-Date.now();
  if(!S.warned&&left<=Math.min(60000,CFG.limiteSeg*150)){S.warned=true;if(S.mode==='walk')say('poco','cara')}
  if(left<=0&&!S.timeUp){S.timeUp=true;if(S.mode==='walk')showRest()}
}
function showRest(){
  S.mode='rest';S.queue.length=0;saveSession();
  var m=Math.max(1,Math.round(CFG.limiteSeg/60));
  $('#rest-t').textContent='Jugaste '+m+(m===1?' minuto':' minutos')+'. ¡Buen trabajo!';
  $('#rest-img').src=IMG.guino;$('#rest').hidden=false;$('#rest-go').focus();bell();
}
function salirADescansar(){
  var b=$('#rest-go');b.disabled=true;
  whenEmpty(4000).then(irAlDescanso);
}

/* ================= llegada a la escuela ================= */
function win(){
  S.mode='win';S.won=true;S.queue.length=0;
  saveSession();
  bell();say('llegada','festeja');
  S.fx.push({k:'ring',x:S.tx,y:S.ty,t:performance.now()});
  setTimeout(cerrarPartida,1300);
}
function cerrarPartida(){
  S.mode='closing';
  var st=S.st,got=S.chip.filter(function(c){return c.got}).length,tot=S.chip.length;
  var n=starsFor();
  $('#arr').hidden=false;
  $('#arr-stars').innerHTML=starsSVG(n,3);
  $('#arr-msg').textContent='Guardando tu camino…';
  $('#arr-medals').innerHTML='';$('#arr-retry').hidden=true;$('#arr-go').hidden=true;
  var payload={pistas:st.hintEx,flecha:!!st.arrow,chipas_recogidas:got,chipas_total:tot,duracion_s:Math.min(7200,Math.round(elapsed()/1000))};
  whenEmpty(8000).then(function(){return post(CFG.urls.cerrar,payload)}).then(function(r){
    var dest=r.redirect_url;
    $('#arr-stars').innerHTML=starsSVG(r.estrellas||n,3);
    $('#arr-msg').textContent=(r.estrellas===3?'¡Camino perfecto!':'¡Llegaste a la escuela!');
    $('#arr-medals').innerHTML=(r.medallas||[]).map(function(m){return '<div class="lb-medal"><span aria-hidden="true">'+(m.emoji||'🏅')+'</span> '+m.nombre+'</div>'}).join('');
    var b=$('#arr-go');b.hidden=false;b.onclick=function(){location.href=dest};
    lsDel(KS);
    setTimeout(function(){location.href=dest},REDUCE?1500:2600);
  }).catch(function(e){
    if(e&&(e.status===403||e.status===401)){location.href=CFG.urls.juegos;return}
    $('#arr-msg').textContent='No pudimos guardar. Revisá tu conexión.';
    var b=$('#arr-retry');b.hidden=false;b.onclick=function(){cerrarPartida()};
  });
}
function goHome(){
  if(S&&S.mode!=='win'&&S.mode!=='closing')saveSession();
  location.href=CFG.urls.juegos;
}
/* ================= música de fondo (archivo propio del juego; comparte la preferencia 'matiguara_musica' con los otros juegos) ================= */
var mus=document.getElementById('lb-music'),musOn=false,musTried=false,MUS_VOL=.3,MUS_VOL_BAJO=.1;
function musPref(){try{return localStorage.getItem('matiguara_musica')!=='0'}catch(e){return true}}
function musSave(v){try{localStorage.setItem('matiguara_musica',v?'1':'0')}catch(e){}}
function musPaint(){var b=$('#b-mus');if(!b)return;b.textContent=musOn?'🎵':'🔇';b.setAttribute('aria-pressed',String(musOn))}
function musPlay(){
  if(!mus)return;mus.volume=MUS_VOL;
  musOn=true;musPaint();
  var p=mus.play();
  if(p&&p.then){p.then(function(){musTried=true}).catch(function(){musOn=false;musPaint()})}
  else musTried=true;
}
function musToggle(){
  if(!mus)return false;musTried=true;
  if(musOn&&!mus.paused){mus.pause();musOn=false;musSave(false);musPaint()}else{musSave(true);musPlay()}
  return musOn;
}
function musDuck(on){if(mus&&musOn)mus.volume=on?MUS_VOL_BAJO:MUS_VOL}
/* Los navegadores (sobre todo Chrome en celular) solo dejan sonar después de un toque COMPLETO (al soltar), no al apoyar el dedo:
   se intenta en cada toque o tecla hasta que suene, salvo que sea el propio botón de música o que la música esté apagada */
function musFirst(e){
  if(musTried||!mus)return;
  if(e&&e.target&&e.target.closest&&e.target.closest('#b-mus'))return;
  if(musPref())musPlay();
}
['pointerup','touchend','click','keydown'].forEach(function(ev){document.addEventListener(ev,musFirst,true)});
document.addEventListener('visibilitychange',function(){if(!mus)return;if(document.hidden){mus.pause()}else if(musOn){mus.play().catch(function(){musOn=false;musPaint()})}});
if(mus)mus.addEventListener('error',function(){try{console.warn('No se pudo cargar la música del laberinto',mus.currentSrc)}catch(e){}});
window.MusicaPropia={alternar:musToggle};

/* ================= eventos ================= */
$('#b-home').addEventListener('click',goHome);
var bMus=$('#b-mus');if(bMus){if(mus)bMus.addEventListener('click',musToggle);else bMus.hidden=true}
$('#b-snd').addEventListener('click',function(){sound=!sound;lsSet('lb_sound_v1',sound);this.setAttribute('aria-pressed',String(sound));this.textContent=sound?'🔊':'🔇'});
$('#b-pad').addEventListener('click',function(){var d=$('#dpad');d.classList.toggle('on');this.setAttribute('aria-pressed',String(d.classList.contains('on')));lsSet('lb_pad_set',1);setTimeout(fit,30)});
$('#b-arrow').addEventListener('click',function(){
  if(!S||S.mode!=='walk')return;
  if(!S.arrowOk&&!S.st.arrow){$('#modal').hidden=false;$('#m-yes').focus();return}
  useArrow();
});
$('#m-yes').addEventListener('click',function(){S.arrowOk=true;$('#modal').hidden=true;useArrow()});
$('#rest-go').addEventListener('click',salirADescansar);
$('#m-no').addEventListener('click',function(){$('#modal').hidden=true});
$('#t-hint').addEventListener('click',function(){showHint(false)});
$('#t-aid').addEventListener('click',toggleAid);
$('#t-say').addEventListener('click',function(){if(P)speak(P.ex.enunciado_es)});
$('#t-next').addEventListener('click',function(){closePanel(true)});
cv.addEventListener('pointerdown',function(e){
  if(!S)return;audCtx();var r=cv.getBoundingClientRect();
  tapTile(Math.floor((e.clientX-r.left-ox)/ts),Math.floor((e.clientY-r.top-oy+S.shift)/ts));
});
Array.prototype.forEach.call(document.querySelectorAll('#dpad button'),function(b){
  var iv=null;
  function stop(){clearInterval(iv);iv=null}
  b.addEventListener('pointerdown',function(e){e.preventDefault();audCtx();stepDir(b.dataset.d);stop();iv=setInterval(function(){stepDir(b.dataset.d)},170)});
  ['pointerup','pointerleave','pointercancel'].forEach(function(ev){b.addEventListener(ev,stop)});
});
document.addEventListener('keydown',function(e){
  if(!S)return;
  if(e.ctrlKey||e.metaKey||e.altKey)return;
  if(S.mode==='panel'){
    if(e.key>='1'&&e.key<='4'){var i=Number(e.key)-1;if(optBtns[i]){e.preventDefault();choose(i)}}
    else if((e.key==='Enter'||e.key===' ')&&!$('#t-next').hidden&&document.activeElement===document.body){e.preventDefault();closePanel(true)}
    return;
  }
  if(S.mode!=='walk')return;
  var m={ArrowUp:'u',ArrowDown:'d',ArrowLeft:'l',ArrowRight:'r',w:'u',s:'d',a:'l',d:'r',W:'u',S:'d',A:'l',D:'r'}[e.key];
  if(m){e.preventDefault();stepDir(m)}
});
function speak(text){
  try{
    if(!window.speechSynthesis)return;speechSynthesis.cancel();
    var u=new SpeechSynthesisUtterance(text),vs=speechSynthesis.getVoices(),pri=['es-py','es-mx','es-us','es-ar','es-419','es'],v=null;
    for(var i=0;i<pri.length&&!v;i++)v=vs.filter(function(x){return x.lang.replace('_','-').toLowerCase().indexOf(pri[i])===0})[0]||null;
    if(v)u.voice=v;u.lang=v?v.lang:'es-ES';u.rate=.9;u.onstart=function(){musDuck(true)};u.onend=u.onerror=function(){musDuck(false)};speechSynthesis.speak(u);
  }catch(e){}
}
if('ResizeObserver' in window){new ResizeObserver(function(){fit()}).observe(stage);new ResizeObserver(function(){adjustView()}).observe($('#panel'))}
window.addEventListener('resize',function(){fit()});


/* ================= arranque ================= */
var lastT=0;
function loop(t){
  lastT=t;
  if(S&&cssW>0){tickLimit();update(t);draw(t)}
  requestAnimationFrame(loop);
}
function start(){
  limpiarViejos();
  var saved=lsGet(KS,null);
  var land=saved?!!saved.land:(window.innerWidth>window.innerHeight*1.1);
  if(!newGame(land)){
    $('#g-es').textContent='No pudimos armar el camino. Avisale a tu maestra.';
    return;
  }
  if(saved){try{restoreSession(saved)}catch(e){lsDel(KS);newGame(land)}}
  setupGameUI();
  $('#b-snd').textContent=sound?'🔊':'🔇';$('#b-snd').setAttribute('aria-pressed',String(sound));
  musOn=false;musPaint();
  if(!lsGet('lb_pad_set',null)&&matchMedia('(pointer:coarse)').matches){$('#dpad').classList.add('on');$('#b-pad').setAttribute('aria-pressed','true')}
  requestAnimationFrame(function(){fit()});
  if(saved&&saved.w){S.won=true;S.mode='win';cerrarPartida()}
  else say('inicio','guino');
  pump();
}
start();
requestAnimationFrame(loop);
window.__lb={get S(){return S},get P(){return P},get Q(){return Q},CFG:CFG,buildMaze:buildMaze,bfsPath:bfsPath,choose:choose,closePanel:closePanel,tapTile:tapTile,exFromRow:exFromRow};
})();
