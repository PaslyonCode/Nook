(() => {
  'use strict';
  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => [...r.querySelectorAll(s)];
  const state = { slug: window.NOOK_PUBLIC_SLUG || 'blog', data:null, activeTag:'', currentCard:null };
  const el = {
    logo: $('#publicLogo'), logoFallback: $('#publicLogoFallback'), headerHtml: $('#publicHeaderHtml'),
    pages: $('#publicPages'), tags: $('#publicTags'), main: $('#publicMain'), feed: $('#publicFeed'),
    cardModal: $('#publicCardModal'), cardTitle: $('#publicCardTitle'), cardMeta: $('#publicCardMeta'), cardBody: $('#publicCardBody'),
    viewerModal: $('#publicViewerModal'), viewerTitle: $('#publicViewerTitle'), viewer: $('#publicViewer')
  };

  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const api = async (action, params={}) => {
    const u = new URL('public_api.php', document.baseURI); u.searchParams.set('action', action);
    Object.entries(params).forEach(([k,v]) => u.searchParams.set(k,v));
    const r = await fetch(u, {headers:{Accept:'application/json'}}); const t = await r.text();
    let d; try { d=JSON.parse(t); } catch { throw new Error(t.slice(0,180) || 'Invalid server response'); }
    if (!r.ok || d.ok===false) throw new Error(d.error || `HTTP ${r.status}`); return d;
  };
  const formatDate = v => { if(!v) return ''; const d=new Date(String(v).replace(' ','T')); return Number.isNaN(d.getTime())?v:d.toLocaleDateString(); };

  function renderChrome(data){
    const s=data.settings||{};
    if(s.logo_url){ el.logo.src=s.logo_url+'?v=1'; el.logo.hidden=false; el.logoFallback.hidden=true; }
    else { el.logo.hidden=true; el.logoFallback.hidden=false; }
    if((s.header_html||'').trim()){ el.headerHtml.innerHTML=s.header_html; el.headerHtml.hidden=false; } else { el.headerHtml.hidden=true; el.headerHtml.innerHTML=''; }
    el.pages.innerHTML=(data.sidebar?.pages||[]).map(p=>`<button class="public-page-link" data-page-id="${p.id}">${esc(p.title||'Без названия')}</button>`).join('');
    el.tags.innerHTML=(data.sidebar?.tags||[]).map(t=>`<button class="public-tag ${state.activeTag===t.public_tag?'active':''}" data-tag="${esc(t.public_tag)}">#${esc(t.public_tag)} <small>${t.cnt}</small></button>`).join('');
    $$('.public-page-link',el.pages).forEach(b=>b.onclick=()=>openPage(+b.dataset.pageId));
    $$('.public-tag',el.tags).forEach(b=>b.onclick=()=>loadState(state.activeTag===b.dataset.tag?'':b.dataset.tag));
  }

  function filePreview(file){
    if(file.preview_url) return `<img loading="lazy" src="${esc(file.preview_url)}" alt="">`;
    const label={image:'PHOTO',video:'VIDEO',pdf:'PDF',stl:'STL',file:'FILE'}[file.media_type]||'FILE';
    return `<div style="height:170px;display:grid;place-items:center;font-weight:800;color:#7a8494;background:#eef1f5">${label}</div>`;
  }
  function cardHtml(card){
    const files=card.files||[]; const media=files.slice(0,4);
    const mediaHtml=media.length?`<div class="public-card-media ${media.length===1?'one':''}">${media.map(filePreview).join('')}</div>`:'';
    const title=esc(card.title||'Без названия'); const excerpt=card.entry_type==='note'?esc(card.excerpt||''):esc(card.description||'');
    return `<article class="public-card" data-card-id="${card.id}">${mediaHtml}<div class="public-card-copy"><h3>${title}</h3>${excerpt?`<p>${excerpt}</p>`:''}${card.public_tag?`<div class="public-card-tag">#${esc(card.public_tag)}</div>`:''}</div></article>`;
  }
  function wireCards(root){ $$('.public-card',root).forEach(c=>c.onclick=()=>openCard(+c.dataset.cardId)); }
  function renderFeed(feed){
    const pinned=feed.filter(x=>+x.is_public_pinned===1), regular=feed.filter(x=>+x.is_public_pinned!==1);
    if(!feed.length){el.feed.innerHTML='<div class="public-empty">Пока здесь ничего нет.</div>';return;}
    el.feed.innerHTML=(pinned.length?`<section class="public-pinned-section"><div class="public-card-grid">${pinned.map(cardHtml).join('')}</div></section>`:'')+`<section class="public-regular-section"><div class="public-card-grid">${regular.map(cardHtml).join('')}</div></section>`;
    wireCards(el.feed);
  }
  async function loadState(tag=''){
    state.activeTag=tag; const data=await api('state',{slug:state.slug,tag}); state.data=data; renderChrome(data); renderFeed(data.feed||[]); window.scrollTo({top:0,behavior:'smooth'});
  }

  function fileTile(file){ return `<button class="public-file-tile" data-file-id="${file.id}" type="button">${filePreview(file)}<div class="public-file-name">${esc(file.original_filename)}</div></button>`; }
  function attachmentsHtml(files){ const a=files.filter(f=>f.role==='attachment'); if(!a.length)return''; return `<div class="public-attachments">${a.map(f=>`<button class="public-attachment" data-file-id="${f.id}" type="button"><span>${esc(f.original_filename)}</span><span>${esc((f.media_type||'file').toUpperCase())}</span></button>`).join('')}</div>`; }
  function wireFiles(root,card){ $$('[data-file-id]',root).forEach(b=>{const f=(card.files||[]).find(x=>+x.id===+b.dataset.fileId); if(f)b.onclick=()=>openViewer(f);}); }
  async function fetchCard(id){ const d=await api('card',{id}); return d.card; }
  async function openCard(id){ const card=await fetchCard(id); state.currentCard=card; if(+card.is_page===1){renderPage(card);return;} el.cardTitle.textContent=card.title||'Без названия'; el.cardMeta.textContent=formatDate(card.created_at)+(card.public_tag?` · #${card.public_tag}`:''); const content=(card.files||[]).filter(f=>f.role==='content'); el.cardBody.innerHTML=(card.entry_type==='note'?`<div class="public-note-body">${card.body_html||''}</div>`:'')+(content.length?`<div class="public-card-gallery">${content.map(fileTile).join('')}</div>`:'')+attachmentsHtml(card.files||[]); wireFiles(el.cardBody,card); openModal(el.cardModal); }
  async function openPage(id){ const card=await fetchCard(id); state.currentCard=card; renderPage(card); }
  function renderPage(card){ el.main.innerHTML=`<article class="public-page-view"><button class="public-back" type="button">← Вернуться</button><h1>${esc(card.title||'Без названия')}</h1><div class="public-page-content">${card.body_html||''}</div>${attachmentsHtml(card.files||[])}</article>`; $('.public-back',el.main).onclick=()=>restoreFeed(); wireFiles(el.main,card); window.scrollTo({top:0,behavior:'smooth'}); }
  function restoreFeed(){ el.main.innerHTML='<div id="publicFeed" class="public-feed"></div>'; el.feed=$('#publicFeed'); renderFeed(state.data?.feed||[]); }
  function openModal(m){m.hidden=false;document.body.style.overflow='hidden'} function closeModal(m){m.hidden=true;document.body.style.overflow=''; if(m===el.viewerModal)el.viewer.innerHTML='';}

  function openViewer(file){
    el.viewerTitle.textContent=file.original_filename||''; const u=file.url;
    if(file.media_type==='image') el.viewer.innerHTML=`<img src="${esc(u)}" alt="">`;
    else if(file.media_type==='video') el.viewer.innerHTML=`<video src="${esc(u)}" controls autoplay playsinline></video>`;
    else if(file.media_type==='pdf') el.viewer.innerHTML=`<iframe src="${esc(u)}"></iframe>`;
    else if(file.media_type==='stl'){ el.viewer.innerHTML='<canvas class="public-stl-canvas"></canvas>'; renderStl($('.public-stl-canvas',el.viewer),u); }
    else el.viewer.innerHTML=`<div style="padding:50px;color:#fff;text-align:center"><p>Предпросмотр недоступен.</p><a class="public-download" style="color:#fff" href="${esc(file.download_url)}">Скачать файл</a></div>`;
    openModal(el.viewerModal);
  }

  async function renderStl(canvas,url){
    const ctx=canvas.getContext('2d'); if(!ctx)return; let tris=[]; let rotX=-.45,rotY=.65,zoom=1; let dragging=false,lx=0,ly=0;
    const resize=()=>{const r=canvas.getBoundingClientRect();canvas.width=Math.max(400,Math.round(r.width*devicePixelRatio));canvas.height=Math.max(300,Math.round(r.height*devicePixelRatio));draw();};
    function parse(buf){const dv=new DataView(buf); if(buf.byteLength>=84){const n=dv.getUint32(80,true);if(84+n*50<=buf.byteLength){let o=84,a=[];for(let i=0;i<n;i++,o+=50){a.push([[dv.getFloat32(o+12,true),dv.getFloat32(o+16,true),dv.getFloat32(o+20,true)],[dv.getFloat32(o+24,true),dv.getFloat32(o+28,true),dv.getFloat32(o+32,true)],[dv.getFloat32(o+36,true),dv.getFloat32(o+40,true),dv.getFloat32(o+44,true)]]);}return a;}} const txt=new TextDecoder().decode(buf), nums=[...txt.matchAll(/vertex\s+([-+\deE.]+)\s+([-+\deE.]+)\s+([-+\deE.]+)/g)].map(m=>[+m[1],+m[2],+m[3]]),a=[];for(let i=0;i+2<nums.length;i+=3)a.push([nums[i],nums[i+1],nums[i+2]]);return a;}
    function draw(){if(!tris.length)return;const w=canvas.width,h=canvas.height;ctx.fillStyle='#151b26';ctx.fillRect(0,0,w,h);let pts=[];for(const t of tris)for(const p of t)pts.push(p);let min=[Infinity,Infinity,Infinity],max=[-Infinity,-Infinity,-Infinity];pts.forEach(p=>p.forEach((v,i)=>{min[i]=Math.min(min[i],v);max[i]=Math.max(max[i],v)}));const c=min.map((v,i)=>(v+max[i])/2),size=Math.max(...max.map((v,i)=>v-min[i]))||1,scale=Math.min(w,h)*.72/size*zoom;const sy=Math.sin(rotY),cy=Math.cos(rotY),sx=Math.sin(rotX),cx=Math.cos(rotX);const pr=p=>{let x=p[0]-c[0],y=p[1]-c[1],z=p[2]-c[2];let x1=x*cy+z*sy,z1=-x*sy+z*cy,y1=y*cx-z1*sx;return[w/2+x1*scale,h/2-y1*scale,z1]};const faces=tris.map(t=>{const q=t.map(pr);return{q,z:(q[0][2]+q[1][2]+q[2][2])/3}}).sort((a,b)=>a.z-b.z);faces.forEach(f=>{ctx.beginPath();ctx.moveTo(f.q[0][0],f.q[0][1]);ctx.lineTo(f.q[1][0],f.q[1][1]);ctx.lineTo(f.q[2][0],f.q[2][1]);ctx.closePath();ctx.fillStyle='rgba(203,213,225,.72)';ctx.fill();ctx.strokeStyle='rgba(71,85,105,.55)';ctx.stroke();});}
    canvas.onpointerdown=e=>{dragging=true;lx=e.clientX;ly=e.clientY;canvas.setPointerCapture(e.pointerId)};canvas.onpointermove=e=>{if(!dragging)return;rotY+=(e.clientX-lx)*.01;rotX+=(e.clientY-ly)*.01;lx=e.clientX;ly=e.clientY;draw()};canvas.onpointerup=()=>dragging=false;canvas.onwheel=e=>{e.preventDefault();zoom=Math.max(.2,Math.min(5,zoom*(e.deltaY>0?.9:1.1)));draw()};
    try{const r=await fetch(url);tris=parse(await r.arrayBuffer());resize();addEventListener('resize',resize,{once:true});}catch(e){ctx.fillStyle='#fff';ctx.fillText('STL preview error',20,30);}
  }

  $$('[data-close="card"]').forEach(x=>x.onclick=()=>closeModal(el.cardModal)); $$('[data-close="viewer"]').forEach(x=>x.onclick=()=>closeModal(el.viewerModal));
  addEventListener('keydown',e=>{if(e.key==='Escape'){if(!el.viewerModal.hidden)closeModal(el.viewerModal);else if(!el.cardModal.hidden)closeModal(el.cardModal);}});
  loadState().catch(e=>{el.feed.innerHTML=`<div class="public-empty">${esc(e.message)}</div>`});
})();
