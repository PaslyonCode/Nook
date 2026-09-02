(() => {
  'use strict';

  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => [...r.querySelectorAll(s)];
  const B = window.NookUXBridge = window.NookUXBridge || {};
  const selected = new Set();
  const meta = new Map();
  let spaces = [];
  let defaultSpaceId = 0;
  let saveTimer = null;
  let menuOutsideHandler = null;
  let lastMediaId = 0;
  let currentCardMedia = [];
  let mediaRepairReloaded = false;
  let galleryInstrumentPromise = null;
  let masonryRaf = 0;
  let masonryObserver = null;

  const lang = (document.documentElement.lang || 'ru').toLowerCase().startsWith('en') ? 'en' : 'ru';
  B.registerCards = (cards, append=false) => {
    const incoming=Array.isArray(cards)?cards:[];
    if(append&&Array.isArray(B.listCards)) B.listCards=[...B.listCards,...incoming]; else B.listCards=[...incoming];
  };

  const T = {
    ru: {
      default:'Нычка по умолчанию', defaultSet:'Сделать нычкой по умолчанию', protectedDefault:'Запароленную нычку нельзя сделать нычкой по умолчанию', leave:'Выйти из запароленной нычки', add:'Добавить нычку', edit:'Изменить', del:'Удалить', move:'Переместить в нычку', duplicate:'Создать копию', hide:'Сделать невидимой', show:'Сделать видимой', pin:'Закрепить', unpin:'Открепить', selected:'Выбрано', hash:'Присвоить хэш', publish:'Опубликовать', clear:'Снять выбор', confirmDelete:'Удалить выбранные записи в корзину?', confirmOneDelete:'Удалить запись в корзину?', confirmPublish:'Выбранные записи станут доступны на публичном фронтенде. Продолжить?', tagPrompt:'Хэш/хэштэг для выбранных записей:', password:'Пароль нычки', passwordRequired:'Введите пароль нычки', name:'Название нычки', passwordMode:'Пароль', keep:'Не менять', set:'Установить/сменить', remove:'Убрать пароль', noPassword:'Без пароля', save:'Сохранить', cancel:'Отмена', deletingSpace:'При удалении нычки ее записи будут перенесены в другую открытую нычку. Продолжить?', cannotDefaultProtected:'Для приватности запароленная нычка никогда не открывается автоматически.', saved:'Сохранено', saving:'Сохранение…', copied:'Копия создана', moved:'Перемещено', noOther:'Других нычек нет', makeVisible:'Сделать видимыми', makeHidden:'Сделать невидимыми', mediaRepaired:'Миниатюры обновлены', error:'Ошибка', cards:'зап.', lock:'Защищена паролем', filters:'Фильтры', unlock:'Открыть нычку', remember:'Запомнить доступ на 30 дней', badPassword:'Неверный пароль'
    },
    en: {
      default:'Default nook', defaultSet:'Make default nook', protectedDefault:'A protected nook cannot be the default', leave:'Leave protected nook', add:'Add nook', edit:'Edit', del:'Delete', move:'Move to nook', duplicate:'Duplicate', hide:'Make hidden', show:'Make visible', pin:'Pin', unpin:'Unpin', selected:'Selected', hash:'Assign tag', publish:'Publish', clear:'Clear selection', confirmDelete:'Move selected entries to trash?', confirmOneDelete:'Move this entry to trash?', confirmPublish:'Selected entries will become public. Continue?', tagPrompt:'Tag for selected entries:', password:'Nook password', passwordRequired:'Enter nook password', name:'Nook name', passwordMode:'Password', keep:'Keep unchanged', set:'Set/change', remove:'Remove password', noPassword:'No password', save:'Save', cancel:'Cancel', deletingSpace:'Deleting this nook moves its entries to another open nook. Continue?', cannotDefaultProtected:'For privacy, a protected nook is never opened automatically.', saved:'Saved', saving:'Saving…', copied:'Copy created', moved:'Moved', noOther:'No other nooks', makeVisible:'Make visible', makeHidden:'Make hidden', mediaRepaired:'Thumbnails updated', error:'Error', cards:'items', lock:'Password protected', filters:'Filters', unlock:'Open nook', remember:'Remember access for 30 days', badPassword:'Wrong password'
    }
  }[lang];

  function esc(v) { return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
  function core() { return window.NookUXCore || {}; }
  function coreState() { try { return core().getState?.() || {}; } catch { return {}; } }
  function detectedCoreSpaceId() {
    const s = coreState();
    return Number(
      s.spaceId || s.currentSpaceId || s.activeSpaceId || s.space_id ||
      s.currentSpace?.id || s.space?.id || 0
    );
  }
  function currentSpaceId() { return Number(detectedCoreSpaceId() || B.activeSpaceId || 0); }
  function currentSpace() { const id=currentSpaceId(); return spaces.find(s=>Number(s.id)===id) || null; }
  function modalOpen(el) { return !!el && (el.classList.contains('open') || el.getAttribute('aria-hidden') === 'false'); }
  function formCardId(form) { if(!form) return 0; const e=form.elements; return Number(e?.id?.value || e?.card_id?.value || form.dataset.cardId || 0); }
  function cardForm() { return $('#cardForm') || $('#editForm'); }
  function noteForm() { return $('#noteForm'); }

  async function req(action, { form=null, query=null }={}) {
    const u = new URL('ux_api.php', location.href); u.searchParams.set('action', action);
    if (query) Object.entries(query).forEach(([k,v]) => u.searchParams.set(k, String(v)));
    const r = await (B.nativeFetch || fetch)(u, { method:form?'POST':'GET', body:form||undefined, headers:{Accept:'application/json'}, credentials:'same-origin' });
    const text = await r.text(); let d;
    try { d=JSON.parse(text); } catch { throw Object.assign(new Error(text.slice(0,240)||`HTTP ${r.status}`), {status:r.status}); }
    if(!r.ok || d.ok===false) throw Object.assign(new Error(d.error||`HTTP ${r.status}`), d, {status:r.status});
    return d;
  }

  function toast(message, error=false) {
    const box = $('#statusBox');
    if (box) {
      box.textContent=message; box.hidden=false; box.className='status-box '+(error?'error':'success');
      if(!error) setTimeout(()=>{ box.hidden=true; },2600);
      return;
    }
    let t=$('#uxToast'); if(!t){t=document.createElement('div');t.id='uxToast';Object.assign(t.style,{position:'fixed',right:'18px',bottom:'18px',zIndex:'3000',background:'#202632',color:'#fff',padding:'10px 13px',borderRadius:'10px',boxShadow:'0 8px 30px #0004'});document.body.append(t)}
    t.textContent=message;t.style.background=error?'#a52c2c':'#202632';t.hidden=false;clearTimeout(t._tm);t._tm=setTimeout(()=>t.hidden=true,3000);
  }

  async function loadSpaces() {
    const d=await req('spaces'); spaces=d.spaces||[]; defaultSpaceId=Number(d.default_space_id)||0;
    B.spaces=spaces;B.defaultSpaceId=defaultSpaceId;
    const coreId=detectedCoreSpaceId();
    if(coreId) B.activeSpaceId=coreId;
    else if(!B.activeSpaceId) B.activeSpaceId=defaultSpaceId;
    renderSpaceUi();
    return d;
  }

  async function enforceInitialPrivacy() {
    const coreId=detectedCoreSpaceId();
    const current=spaces.find(s=>Number(s.id)===coreId);
    if(coreId && current && !current.protected){
      B.activeSpaceId=coreId;
      return;
    }
    if(coreId && current?.protected){
      // Do not reopen a protected nook automatically. The caller performs one
      // forced reload of the default nook after the core has finished startup.
      B.activeSpaceId=defaultSpaceId;
      return;
    }
    // If the core does not expose its current space, keep its normal startup state.
    // The default is only used as a safe fallback for the UX controls.
    if(!B.activeSpaceId) B.activeSpaceId=defaultSpaceId;
  }

  function findTopMenuButton() {
    const candidates=['#menuBtn','#mainMenuBtn','#moreMenuBtn','[data-menu-toggle]','[data-action="menu"]','.main-menu-button','.top-menu-button'];
    for(const s of candidates){const e=$(s);if(e)return e}
    return $$('button').find(b=>['⋮','...','•••'].includes((b.textContent||'').trim()) && b.getBoundingClientRect().top<140) || null;
  }

  function ensureTopActions() {
    let host=$('#uxTopActions'); if(host) return host;
    host=document.createElement('div');host.id='uxTopActions';host.className='ux-top-actions';
    host.innerHTML=`<button id="uxDefaultSpaceBtn" class="ux-top-action" type="button" title="${esc(T.defaultSet)}" aria-label="${esc(T.defaultSet)}">☆</button><button id="uxLeaveSpaceBtn" class="ux-top-action" type="button" title="${esc(T.leave)}" aria-label="${esc(T.leave)}" hidden>↪</button>`;
    const menu=findTopMenuButton();
    if(menu?.parentElement){menu.parentElement.insertBefore(host,menu)} else {Object.assign(host.style,{position:'fixed',top:'14px',right:'64px'});document.body.append(host)}
    $('#uxDefaultSpaceBtn',host).onclick=setCurrentDefault;
    $('#uxLeaveSpaceBtn',host).onclick=leaveProtected;
    return host;
  }

  function findSpaceAnchor() {
    for(const s of ['#currentSpaceName','#spaceCurrentName','#currentSpace','[data-current-space-name]','.current-space-name','.space-current']){const e=$(s);if(e&&!e.closest('.ux-space-switcher-host'))return e}
    const cur=currentSpace();
    if(cur){
      const nodes=$$('h1,h2,h3,button,span,div').filter(e=>!e.closest('.ux-space-switcher-host') && (e.textContent||'').trim()===cur.name && e.getBoundingClientRect().top<190);
      if(nodes.length)return nodes[0];
    }
    return null;
  }

  function ensureSpaceMenuPortal() {
    let portal=$('#uxSpaceMenuPortal');
    if(portal)return portal;
    portal=document.createElement('div');
    portal.id='uxSpaceMenuPortal';
    portal.className='ux-space-menu ux-space-menu-portal';
    portal.hidden=true;
    Object.assign(portal.style,{
      position:'fixed',zIndex:'2147483000',minWidth:'340px',maxWidth:'min(460px,calc(100vw - 24px))',
      maxHeight:'min(70vh,640px)',overflowY:'auto',background:'#fff',border:'1px solid #d8dee8',
      borderRadius:'12px',boxShadow:'0 18px 60px rgba(15,23,42,.22)',padding:'8px'
    });
    document.body.append(portal);
    return portal;
  }

  function positionSpaceMenuPortal(anchor) {
    const portal=ensureSpaceMenuPortal();
    const a=anchor || $('#uxSpaceCurrent') || findSpaceAnchor();
    if(!a)return;
    const r=a.getBoundingClientRect();
    const gap=6;
    let left=Math.max(8,Math.min(r.left,window.innerWidth-portal.offsetWidth-8));
    let top=r.bottom+gap;
    if(top+Math.min(portal.scrollHeight,640)>window.innerHeight-8){
      top=Math.max(8,r.top-gap-Math.min(portal.scrollHeight,640));
    }
    portal.style.left=`${Math.round(left)}px`;
    portal.style.top=`${Math.round(top)}px`;
  }

  function ensureSpaceSwitcher() {
    let host=$('#uxSpaceSwitcherHost'); if(host)return host;
    host=document.createElement('div');host.id='uxSpaceSwitcherHost';host.className='ux-space-switcher-host';
    host.innerHTML=`<button id="uxSpaceCurrent" class="ux-space-current" type="button"><span class="ux-space-current-name">—</span><span class="ux-chevron">▼</span></button>`;
    const anchor=findSpaceAnchor();
    if(anchor){
      const parent=anchor.parentElement;
      if(parent){anchor.style.display='none';parent.insertBefore(host,anchor.nextSibling)}
    } else {
      const header=$('.content-header') || $('main header') || $('main') || $('.content') || document.body;
      header.insertBefore(host,header.firstChild);
    }
    $('#uxSpaceCurrent',host).onclick=e=>{e.preventDefault();e.stopPropagation();toggleSpaceMenu($('#uxSpaceCurrent',host))};
    ensureSpaceMenuPortal();
    return host;
  }

  function renderSpaceUi() {
    ensureTopActions(); ensureSpaceSwitcher();
    const cur=currentSpace() || spaces.find(s=>Number(s.id)===defaultSpaceId) || spaces[0];
    const btn=$('#uxSpaceCurrent');
    if(btn&&cur){$('.ux-space-current-name',btn).textContent=cur.name;btn.classList.toggle('is-protected',!!cur.protected)}
    const def=$('#uxDefaultSpaceBtn'), leave=$('#uxLeaveSpaceBtn');
    if(def){def.hidden=!!cur?.protected;def.classList.toggle('is-active',Number(cur?.id)===defaultSpaceId);def.textContent=Number(cur?.id)===defaultSpaceId?'★':'☆';def.title=Number(cur?.id)===defaultSpaceId?T.default:T.defaultSet}
    if(leave)leave.hidden=!cur?.protected;
    if(!$('#uxSpaceMenuPortal')?.hidden)renderSpaceMenu();
    hideLegacySpaceMenuItems();
  }

  function renderSpaceMenu() {
    const box=ensureSpaceMenuPortal();
    const curId=currentSpaceId();
    box.innerHTML=spaces.map(s=>`<div data-ux-space-row="${s.id}" style="display:grid!important;grid-template-columns:minmax(0,1fr) 40px 40px!important;align-items:center!important;gap:6px!important;padding:4px!important;margin:0!important;overflow:visible!important;border-radius:9px!important;${Number(s.id)===curId?'background:#f5f7fb!important;':''}">
      <button type="button" data-ux-space-open="${s.id}" style="display:grid!important;grid-template-columns:24px minmax(0,1fr) auto auto!important;align-items:center!important;gap:7px!important;width:100%!important;min-width:0!important;height:40px!important;padding:0 8px!important;border:0!important;background:transparent!important;color:inherit!important;text-align:left!important;cursor:pointer!important;">
        <span>${s.protected?(s.unlocked?'🔓':'🔒'):'◇'}</span>
        <span style="overflow:hidden!important;text-overflow:ellipsis!important;white-space:nowrap!important;">${esc(s.name)}</span>
        ${s.is_default?'<span title="'+esc(T.default)+'">★</span>':''}
        <small style="opacity:.65;white-space:nowrap;">${Number(s.card_count)||0}</small>
      </button>
      <button type="button" data-ux-space-edit="${s.id}" title="${esc(T.edit)}" aria-label="${esc(T.edit)}" style="all:unset!important;box-sizing:border-box!important;display:grid!important;place-items:center!important;width:38px!important;height:38px!important;border:1px solid #cfd7e2!important;border-radius:8px!important;background:#fff!important;color:#344054!important;font:700 20px/1 Arial,sans-serif!important;cursor:pointer!important;visibility:visible!important;opacity:1!important;pointer-events:auto!important;">✎</button>
      <button type="button" data-ux-space-delete="${s.id}" title="${esc(T.del)}" aria-label="${esc(T.del)}" style="all:unset!important;box-sizing:border-box!important;display:grid!important;place-items:center!important;width:38px!important;height:38px!important;border:1px solid #e4c2bf!important;border-radius:8px!important;background:#fff!important;color:#a53028!important;font:700 22px/1 Arial,sans-serif!important;cursor:pointer!important;visibility:visible!important;opacity:1!important;pointer-events:auto!important;">×</button>
    </div>`).join('')+
      `<div style="height:1px;background:#e6eaf0;margin:7px 4px;"></div><button type="button" data-ux-space-add style="display:flex!important;align-items:center!important;gap:8px!important;width:100%!important;padding:10px 12px!important;border:0!important;background:transparent!important;border-radius:8px!important;cursor:pointer!important;text-align:left!important;"><b style="font-size:18px">＋</b><span>${esc(T.add)}</span></button>`;

    $$('[data-ux-space-open]',box).forEach(b=>b.onclick=e=>{e.preventDefault();e.stopPropagation();box.hidden=true;switchSpace(Number(b.dataset.uxSpaceOpen))});
    $$('[data-ux-space-edit]',box).forEach(b=>b.onclick=e=>{e.preventDefault();e.stopPropagation();box.hidden=true;openSpaceEditor(Number(b.dataset.uxSpaceEdit))});
    $$('[data-ux-space-delete]',box).forEach(b=>b.onclick=e=>{e.preventDefault();e.stopPropagation();box.hidden=true;deleteSpace(Number(b.dataset.uxSpaceDelete))});
    const add=$('[data-ux-space-add]',box); if(add)add.onclick=e=>{e.preventDefault();e.stopPropagation();box.hidden=true;openSpaceEditor(0)};
    positionSpaceMenuPortal($('#uxSpaceCurrent'));
  }

  function toggleSpaceMenu(anchor){
    const m=ensureSpaceMenuPortal();
    m.hidden=!m.hidden;
    if(!m.hidden){renderSpaceMenu();positionSpaceMenuPortal(anchor)}
  }

  // Capture clicks on the original current-space title as a fallback. This is
  // deliberately independent of the original Nook dropdown DOM: one click now
  // always opens the body-level UX menu containing edit/delete controls.
  document.addEventListener('click',e=>{
    const portal=$('#uxSpaceMenuPortal');
    const host=$('#uxSpaceSwitcherHost');
    if(portal && portal.contains(e.target))return;
    if(host && host.contains(e.target))return;
    const cur=currentSpace();
    if(cur){
      const candidate=e.target.closest?.('button,[role="button"],h1,h2,h3,div,span');
      if(candidate && !candidate.closest('.modal,.ux-dialog-backdrop,.ux-space-menu-portal') && candidate.getBoundingClientRect().top<180 && (candidate.textContent||'').trim()===cur.name){
        e.preventDefault();e.stopPropagation();e.stopImmediatePropagation();
        renderSpaceMenu();ensureSpaceMenuPortal().hidden=false;positionSpaceMenuPortal(candidate);return;
      }
    }
    if(portal && !portal.hidden)portal.hidden=true;
  },true);

  window.addEventListener('resize',()=>{const m=$('#uxSpaceMenuPortal');if(m&&!m.hidden)positionSpaceMenuPortal($('#uxSpaceCurrent'))});
  window.addEventListener('scroll',()=>{const m=$('#uxSpaceMenuPortal');if(m&&!m.hidden)positionSpaceMenuPortal($('#uxSpaceCurrent'))},true);

  function hideLegacySpaceMenuItems(){
    const rx=/^(Доступные нычки|Добавить нычку|Available nooks|Add nook)$/i;
    $$('button,a,[role="menuitem"]').forEach(e=>{if(e.closest('.ux-space-menu'))return;if(/Доступные нычки|Добавить нычку|Available nooks|Add nook/i.test((e.textContent||'').trim()))e.style.display='none'});
  }

  async function setCurrentDefault(){
    const s=currentSpace(); if(!s)return;
    if(s.protected){toast(T.protectedDefault,true);return}
    const fd=new FormData();fd.set('space_id',s.id);await req('set_default',{form:fd});await loadSpaces();toast(T.default);
  }

  async function leaveProtected(){
    const s=currentSpace(); if(!s?.protected)return;
    const fd=new FormData();fd.set('space_id',s.id);const d=await req('leave_space',{form:fd});B.activeSpaceId=Number(d.default_space_id)||defaultSpaceId;await loadSpaces();await switchSpace(B.activeSpaceId,{skipProtected:true});
  }

  function protectedSpaceUnlock(space) {
    return new Promise(resolve=>{
      const back=dialogBase('uxUnlockSpaceDialog',`${T.unlock}: ${esc(space.name)}`,
        `<div class="ux-dialog-grid"><label><span>${esc(T.password)}</span><input class="input" type="text" inputmode="text" autocomplete="off" data-ux-unlock-password></label><label class="ux-remember-row"><input type="checkbox" data-ux-unlock-remember checked> <span>${esc(T.remember)}</span></label><div class="ux-dialog-error" data-ux-error></div></div>`+
        `<div class="ux-dialog-actions"><button type="button" class="btn" data-cancel>${esc(T.cancel)}</button><button type="button" class="btn btn-primary" data-submit>${esc(T.unlock)}</button></div>`
      );
      const input=$('[data-ux-unlock-password]',back), error=$('[data-ux-error]',back), submit=$('[data-submit]',back);
      const close=value=>{back.remove();resolve(value)};
      $('[data-cancel]',back).onclick=()=>close(false);
      const attempt=async()=>{
        error.textContent='';submit.disabled=true;
        const fd=new FormData();fd.set('space_id',String(space.id));fd.set('password',input.value);fd.set('remember',$('[data-ux-unlock-remember]',back)?.checked?'1':'0');
        try{await req('unlock_space',{form:fd});space.unlocked=true;close(true)}catch(e){error.textContent=e.message||T.badPassword;input.focus();input.select()}finally{submit.disabled=false}
      };
      submit.onclick=attempt;
      input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();attempt()}});
      setTimeout(()=>input.focus(),0);
    });
  }

  async function performCoreSpaceSwitch(id,{forceReload=true}={}) {
    const c=core();let switched=false;
    if(typeof c.forceSpace==='function') switched=(await c.forceSpace(Number(id)))!==false;
    else if(typeof c.switchSpace==='function') switched=(await c.switchSpace(Number(id)))!==false;
    if(forceReload && typeof c.loadCards==='function') await c.loadCards(true);
    else if(forceReload && typeof c.loadState==='function') await c.loadState();
    return switched;
  }

  async function switchSpace(id,{skipProtected=false,forceReload=true}={}) {
    const target=spaces.find(s=>Number(s.id)===Number(id));if(!target)return false;
    $('#uxSpaceMenu')?.setAttribute('hidden','');
    const g=gallery(); if(g) g.style.visibility='hidden';
    try {
      if(target.protected && !skipProtected && !target.unlocked){
        const unlocked=await protectedSpaceUnlock(target);if(!unlocked){if(g)g.style.visibility='';return false}
      }
      B.activeSpaceId=Number(id);meta.clear();selected.clear();mediaRepairReloaded=false;currentCardMedia=[];
      const switched=await performCoreSpaceSwitch(Number(id),{forceReload});
      B.activeSpaceId=Number(id);
      try{localStorage.setItem('nook_ux_last_space_id',String(id))}catch(_){}
      await loadSpaces();
      if(!target.protected || skipProtected) await repairMediaForSpace(Number(id));
      renderSpaceUi();
      setTimeout(()=>{instrumentGallery();if(g)g.style.visibility=''},80);
      return switched;
    } catch(e){
      if(g)g.style.visibility='';toast(e.message||String(e),true);return false;
    }
  }

  function dialogBase(id,title,body,actions=''){
    let back=$('#'+id);if(back)back.remove();back=document.createElement('div');back.id=id;back.className='ux-dialog-backdrop';back.innerHTML=`<div class="ux-dialog" role="dialog" aria-modal="true"><h3>${esc(title)}</h3>${body}<div class="ux-dialog-error" data-ux-error></div>${actions}</div>`;document.body.append(back);back.addEventListener('mousedown',e=>{if(e.target===back)back.remove()});return back;
  }

  function askOpenPassword(title=T.passwordRequired){
    return new Promise(resolve=>{
      const back=dialogBase('uxPasswordDialog',title,
        `<div class="ux-dialog-grid"><label><span>${esc(T.password)}</span><input class="input" type="text" autocomplete="off" data-password></label></div>`,
        `<div class="ux-dialog-actions"><button type="button" class="btn" data-cancel>${esc(T.cancel)}</button><button type="button" class="btn btn-primary" data-ok>${esc(T.unlock)}</button></div>`
      );
      const input=$('[data-password]',back),err=$('[data-ux-error]',back);let settled=false;
      const done=v=>{if(settled)return;settled=true;back.remove();resolve(v)};
      $('[data-cancel]',back).onclick=()=>done(null);
      $('[data-ok]',back).onclick=()=>done(input.value);
      input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();done(input.value)}});
      back._showError=message=>{err.textContent=message||T.badPassword;input.focus();input.select()};
      setTimeout(()=>input.focus(),0);
    });
  }

  function openSpaceEditor(id){
    const s=spaces.find(x=>Number(x.id)===Number(id));const isEdit=!!s;
    const back=dialogBase('uxSpaceEditor',isEdit?T.edit:T.add,`<form data-ux-space-form class="ux-dialog-grid"><label>${esc(T.name)}<input class="input" name="name" required maxlength="160" value="${esc(s?.name||'')}"></label>${isEdit?`<label>${esc(T.passwordMode)}<select name="password_mode"><option value="keep">${esc(T.keep)}</option><option value="set">${esc(T.set)}</option><option value="remove">${esc(T.remove)}</option></select></label>`:`<label>${esc(T.password)}<input type="password" name="password" autocomplete="new-password" placeholder="${esc(T.noPassword)}"></label>`}<label data-new-pass ${isEdit?'hidden':''}>${esc(T.password)}<input type="password" name="new_password" autocomplete="new-password"></label><div class="ux-dialog-actions"><button type="button" class="btn" data-cancel>${esc(T.cancel)}</button><button type="submit" class="btn btn-primary">${esc(T.save)}</button></div></form>`);
    const f=$('[data-ux-space-form]',back);$('[data-cancel]',back).onclick=()=>back.remove();
    const mode=f.elements.password_mode;if(mode)mode.onchange=()=>{$('[data-new-pass]',back).hidden=mode.value!=='set'};
    f.onsubmit=async e=>{e.preventDefault();const fd=new FormData();fd.set('name',f.elements.name.value.trim());if(isEdit){fd.set('space_id',String(id));fd.set('password_mode',mode.value);if(mode.value==='set')fd.set('password',f.elements.new_password.value)}else fd.set('password',f.elements.password.value);try{await req(isEdit?'space_update':'space_create',{form:fd});back.remove();await loadSpaces();}catch(err){if(err.code==='SPACE_PASSWORD_REQUIRED'){const p=await askOpenPassword(T.passwordRequired);if(p!==null){fd.set('current_password',p);try{await req('space_update',{form:fd});back.remove();await loadSpaces()}catch(e2){$('[data-ux-error]',back).textContent=e2.message}}}else $('[data-ux-error]',back).textContent=err.message}};
    f.elements.name.focus();
  }

  async function deleteSpace(id){
    const s=spaces.find(x=>Number(x.id)===Number(id));if(!s||!confirm(T.deletingSpace))return;
    const fd=new FormData();fd.set('space_id',String(id));
    try{const d=await req('space_delete',{form:fd});if(currentSpaceId()===id)B.activeSpaceId=Number(d.moved_to)||defaultSpaceId;await loadSpaces();await refreshGallery()}
    catch(err){if(err.code==='SPACE_PASSWORD_REQUIRED'){const p=await askOpenPassword(T.passwordRequired);if(p===null)return;fd.set('space_password',p);try{const d=await req('space_delete',{form:fd});if(currentSpaceId()===id)B.activeSpaceId=Number(d.moved_to)||defaultSpaceId;await loadSpaces();await refreshGallery()}catch(e2){toast(e2.message,true)}}else toast(err.message,true)}
  }

  function gallery(){return $('#gallery')||$('.gallery')}
  function tileCandidates(){const g=gallery();if(!g)return[];return [...g.children].filter(el=>!el.matches('.empty-state,[data-gallery-sentinel],.gallery-sentinel,#gallerySentinel') && (el.matches('.card-tile,.note-tile,.gallery-card,[data-card-id],[data-id]') || el.querySelector('img,video,.note-preview,.tile-images,.empty-thumb')))}
  function tileId(tile){return Number(tile?.dataset?.uxCardId||tile?.dataset?.cardId||tile?.dataset?.id||0)}

  function filterSnapshot(){
    const st=coreState();
    const value=(sels, fallback='')=>{for(const sel of sels){const el=$(sel);if(el&&String(el.value??'').trim()!=='')return String(el.value).trim()}return String(fallback??'').trim()};
    const q=value(['#searchInput','[name="q"]','input[type="search"]'],st.q||st.search||'');
    const dateFrom=value(['#dateFromInput','#dateFrom','[name="date_from"]'],st.dateFrom||st.date_from||'');
    const dateTo=value(['#dateToInput','#dateTo','[name="date_to"]'],st.dateTo||st.date_to||'');
    const type=value(['#typeFilter','#contentTypeFilter','[name="type_filter"]','[name="media_type"]'],st.type||st.typeFilter||st.mediaType||'');
    const visibility=value(['#visibilityFilter','[name="visibility"]'],st.visibility||st.visibilityFilter||'');
    let tag=String(st.tag||st.activeTag||'').trim();
    if(!tag){const active=$('.tag-chip.active,.tag-button.active,[data-tag].active,[aria-pressed="true"][data-tag]');tag=String(active?.dataset?.tag||active?.textContent||'').trim()}
    const trash=!!(st.trashMode||st.inTrash||st.showTrash||document.body.classList.contains('trash-mode'));
    return {space_id:currentSpaceId(),q,date_from:dateFrom,date_to:dateTo,tag,type,visibility,trash:trash?1:0};
  }

  async function fetchVisibleCardIds(){
    const snap=filterSnapshot(); if(!snap.space_id)return [];
    try{const d=await req('nav_cards',{query:snap});return (d.ids||[]).map(Number).filter(Boolean)}catch(e){console.warn('[Nook UX] visible ids',e);return []}
  }

  async function assignTileIds(){
    const tiles=tileCandidates();
    const coreCards=(B.listCards||[]).map(c=>Number(c?.id||0)).filter(Boolean);
    if(coreCards.length)tiles.forEach((tile,i)=>{if(!tileId(tile)&&coreCards[i])tile.dataset.uxCardId=String(coreCards[i])});
    if(tiles.some(tile=>!tileId(tile))){
      const ids=await fetchVisibleCardIds();
      tiles.forEach((tile,i)=>{if(!tileId(tile)&&ids[i])tile.dataset.uxCardId=String(ids[i]);else if(!tile.dataset.uxCardId&&tileId(tile))tile.dataset.uxCardId=String(tileId(tile))});
    }
    return tiles;
  }

  async function fetchMeta(ids){
    const need=ids.filter(id=>id&&!meta.has(id));if(!need.length)return {repair:null};
    try{
      const d=await req('cards_meta',{query:{ids:JSON.stringify(need)}});
      for(const c of d.cards||[])meta.set(Number(c.id),c);
      return d;
    }catch(e){console.warn('[Nook UX] meta',e);return {repair:null}}
  }

  function patchTileVisual(tile,m){
    if(!tile||!m)return;
    const type=String(m.primary_media_type||'').toLowerCase();
    const labels=$$('*',tile).filter(el=>el.children.length===0&&/^(Файл|File)$/i.test((el.textContent||'').trim()));
    if(type==='image')labels.forEach(el=>el.textContent=lang==='en'?'Photo':'Фото');
    else if(type==='video')labels.forEach(el=>el.textContent=lang==='en'?'Video':'Видео');
    else if(type==='pdf')labels.forEach(el=>el.textContent='PDF');
    else if(type==='stl')labels.forEach(el=>el.textContent='STL');
    if(type==='image'&&m.primary_thumb_url&&!tile.querySelector('img')){
      const host=tile.querySelector('.tile-img-wrap,.tile-images,.card-preview,.empty-thumb')||tile;
      if(host){const img=document.createElement('img');img.src=m.primary_thumb_url;img.alt='';img.loading='lazy';img.decoding='async';img.addEventListener('load',scheduleGalleryMasonry,{once:true});if(host.classList.contains('empty-thumb'))host.replaceWith(img);else host.prepend(img)}
    }
  }

  function scheduleGalleryMasonry(){
    if(masonryRaf)return;
    masonryRaf=requestAnimationFrame(()=>{masonryRaf=0;layoutGalleryMasonry()});
  }

  function layoutGalleryMasonry(){
    const g=gallery();if(!g)return;
    const cs=getComputedStyle(g);
    const row=Math.max(2,parseFloat(cs.gridAutoRows)||8);
    const gap=Math.max(0,parseFloat(cs.rowGap)||14);
    for(const tile of tileCandidates()){
      const h=Math.max(1,tile.scrollHeight||0,tile.getBoundingClientRect().height);
      const span=Math.max(1,Math.ceil((h+gap)/(row+gap)));
      const next=`span ${span}`;
      if(tile.style.gridRowEnd!==next)tile.style.gridRowEnd=next;
      if(tile.dataset.uxMasonryBound!=='1'){
        tile.dataset.uxMasonryBound='1';
        for(const img of $$('img',tile)){
          if(img.dataset.uxMasonryImg==='1')continue;
          img.dataset.uxMasonryImg='1';
          if(!img.complete)img.addEventListener('load',scheduleGalleryMasonry,{once:true});
        }
      }
      if(masonryObserver) masonryObserver.observe(tile);
    }
    for(const sentinel of $$('[data-gallery-sentinel],.gallery-sentinel,#gallerySentinel',g)){
      sentinel.style.gridColumn='1 / -1';
      sentinel.style.gridRowEnd='auto';
    }
  }

  function ensureMasonryObserver(){
    if(masonryObserver||typeof ResizeObserver!=='function')return;
    masonryObserver=new ResizeObserver(()=>scheduleGalleryMasonry());
  }

  async function instrumentGallery(){
    if(galleryInstrumentPromise)return galleryInstrumentPromise;
    galleryInstrumentPromise=(async()=>{
      const g=gallery();if(!g)return;g.classList.add('nook-ux-grid');ensureMasonryObserver();
      const tiles=await assignTileIds();const ids=tiles.map(tileId).filter(Boolean);
      const d=await fetchMeta(ids);
      tiles.forEach(tile=>{instrumentTile(tile);patchTileVisual(tile,meta.get(tileId(tile)))});
      updateBulkBar();scheduleGalleryMasonry();document.documentElement.classList.remove('nook-ux-booting');B.booting=false;
      const r=d?.repair||{};
      if(!mediaRepairReloaded&&(Number(r.type_fixed)||Number(r.preview_fixed))){
        mediaRepairReloaded=true;
        await refreshGallery();
      }
    })().finally(()=>{galleryInstrumentPromise=null});
    return galleryInstrumentPromise;
  }

  function instrumentTile(tile){
    const id=tileId(tile);if(!id)return;tile.dataset.uxCardId=String(id);
    if(tile.dataset.uxReady==='1'){syncTile(tile);return}tile.dataset.uxReady='1';
    const cb=document.createElement('input');cb.type='checkbox';cb.className='ux-card-select';cb.title='Выбрать';cb.addEventListener('click',e=>e.stopPropagation());cb.onchange=()=>{if(cb.checked)selected.add(id);else selected.delete(id);syncTile(tile);updateBulkBar()};tile.prepend(cb);
    const tools=document.createElement('div');tools.className='ux-card-tools';tools.innerHTML=`<button type="button" class="ux-card-tool ux-quick-pin" title="${esc(T.pin)}">📎</button><div style="position:relative"><button type="button" class="ux-card-tool ux-quick-menu" title="Меню">⋮</button><div class="ux-card-popover" hidden><button type="button" data-op="move">${esc(T.move)}</button><button type="button" data-op="duplicate">${esc(T.duplicate)}</button><button type="button" data-op="hidden"></button><button type="button" class="danger" data-op="delete">${esc(T.del)}</button></div></div>`;tile.append(tools);
    $('.ux-quick-pin',tools).onclick=e=>{e.preventDefault();e.stopPropagation();quickPin(id)};
    $('.ux-quick-menu',tools).onclick=e=>{e.preventDefault();e.stopPropagation();const p=$('.ux-card-popover',tools);closeCardPopovers(p);p.hidden=!p.hidden};
    $$('[data-op]',tools).forEach(b=>b.onclick=e=>{e.preventDefault();e.stopPropagation();$('.ux-card-popover',tools).hidden=true;quickAction(id,b.dataset.op)});
    syncTile(tile);
  }

  function closeCardPopovers(except=null){$$('.ux-card-popover').forEach(p=>{if(p!==except)p.hidden=true})}
  document.addEventListener('click',()=>closeCardPopovers());
  function syncTile(tile){const id=tileId(tile),m=meta.get(id)||B.cards?.get?.(id)||{};tile.classList.toggle('ux-selected',selected.has(id));const cb=$('.ux-card-select',tile);if(cb)cb.checked=selected.has(id);const pin=$('.ux-quick-pin',tile);if(pin){pin.classList.toggle('ux-pin-active',!!Number(m.is_pinned));pin.title=Number(m.is_pinned)?T.unpin:T.pin;pin.textContent='📎'}const hid=$('[data-op="hidden"]',tile);if(hid)hid.textContent=Number(m.is_hidden)?T.show:T.hide}

  function ensureBulkBar(){let b=$('#uxBulkBar');if(b)return b;b=document.createElement('div');b.id='uxBulkBar';b.className='ux-bulkbar';b.hidden=true;b.innerHTML=`<span class="ux-bulk-count"></span><button data-bulk="tag">${esc(T.hash)}</button><button data-bulk="move">${esc(T.move)}</button><button data-bulk="pin">${esc(T.pin)}</button><button data-bulk="hidden"></button><button data-bulk="publish">${esc(T.publish)}</button><button class="danger" data-bulk="delete">${esc(T.del)}</button><button class="ux-close-selection" data-bulk="clear" title="${esc(T.clear)}">×</button>`;document.body.append(b);$$('[data-bulk]',b).forEach(x=>x.onclick=()=>bulkAction(x.dataset.bulk));return b}
  function selectedMeta(){return [...selected].map(id=>meta.get(id)||{});}
  function bulkHiddenTarget(){const list=selectedMeta();return list.length>0&&list.every(m=>Number(m.is_hidden)===1)?0:1;}
  function updateBulkBar(){const b=ensureBulkBar();b.hidden=selected.size===0;$('.ux-bulk-count',b).textContent=`${T.selected}: ${selected.size}`;const hb=$('[data-bulk="hidden"]',b);if(hb)hb.textContent=bulkHiddenTarget()?T.makeHidden:T.makeVisible;$$('[data-ux-card-id]').forEach(syncTile)}
  function clearSelection(){selected.clear();updateBulkBar()}

  async function quickPin(id){const m=meta.get(id)||{};const value=Number(m.is_pinned)?0:1;const fd=new FormData();fd.set('card_id',id);fd.set('op','pin');fd.set('value',value);try{await req('card_action',{form:fd});m.is_pinned=value;meta.set(id,m);const tile=$(`[data-ux-card-id="${id}"]`);if(tile)syncTile(tile);await refreshGallery()}catch(e){toast(e.message,true)}}
  async function quickAction(id,op){
    const m=meta.get(id)||{};
    if(op==='move'){openMoveDialog([id]);return}
    if(op==='delete'&&!confirm(T.confirmOneDelete))return;
    const fd=new FormData();fd.set('card_id',id);fd.set('op',op);if(op==='hidden')fd.set('value',Number(m.is_hidden)?0:1);
    try{const d=await req('card_action',{form:fd});if(op==='delete')$(`[data-ux-card-id="${id}"]`)?.remove();else if(op==='hidden'){m.is_hidden=Number(m.is_hidden)?0:1;meta.set(id,m);const tile=$(`[data-ux-card-id="${id}"]`);if(tile)syncTile(tile)}else if(op==='duplicate'){toast(T.copied);await refreshGallery()}}catch(e){toast(e.message,true)}
  }

  async function bulkAction(op){
    if(op==='clear'){clearSelection();return}const ids=[...selected];if(!ids.length)return;
    if(op==='move'){openMoveDialog(ids);return}
    if(op==='delete'&&!confirm(T.confirmDelete))return;
    if(op==='publish'&&!confirm(T.confirmPublish))return;
    const fd=new FormData();fd.set('card_ids',JSON.stringify(ids));fd.set('op',op);
    if(op==='tag'){const tag=prompt(T.tagPrompt);if(tag===null||!tag.trim())return;fd.set('tag',tag)}
    if(op==='hidden')fd.set('value',String(bulkHiddenTarget()));
    if(op==='pin'||op==='publish')fd.set('value','1');
    try{await req('bulk_action',{form:fd});if(op==='delete')ids.forEach(id=>$(`[data-ux-card-id="${id}"]`)?.remove());clearSelection();await refreshGallery()}catch(e){toast(e.message,true)}
  }

  function openMoveDialog(ids){
    const sourceSpaces=new Set(ids.map(id=>Number((meta.get(id)||{}).space_id)).filter(Boolean));
    const choices=spaces.filter(s=>!sourceSpaces.has(Number(s.id)) || sourceSpaces.size>1);
    if(!choices.length){toast(T.noOther,true);return}
    const back=dialogBase('uxMoveDialog',T.move,
      `<div class="ux-space-pick-list">${choices.map(s=>`<button type="button" class="ux-space-pick" data-target="${s.id}">${s.protected?'🔒':'◇'} <span>${esc(s.name)}</span></button>`).join('')}</div>`+
      `<div class="ux-dialog-actions"><button type="button" class="btn" data-cancel>${esc(T.cancel)}</button></div>`
    );
    $('[data-cancel]',back).onclick=()=>back.remove();
    $$('[data-target]',back).forEach(b=>{
      b.onclick=async()=>{
        const target=Number(b.dataset.target);
        const fd=new FormData();
        fd.set('card_ids',JSON.stringify(ids));
        fd.set('op','move');
        fd.set('target_space_id',String(target));
        try{
          await req('bulk_action',{form:fd});
          back.remove();
          ids.forEach(id=>$(`[data-ux-card-id="${id}"]`)?.remove());
          clearSelection();toast(T.moved);await refreshGallery();
        }catch(e){
          if(e.code==='SPACE_PASSWORD_REQUIRED'){
            const p=await askOpenPassword(T.passwordRequired);if(p===null)return;
            fd.set('space_password',p);
            try{
              await req('bulk_action',{form:fd});
              back.remove();
              ids.forEach(id=>$(`[data-ux-card-id="${id}"]`)?.remove());
              clearSelection();toast(T.moved);await refreshGallery();
            }catch(e2){$('[data-ux-error]',back).textContent=e2.message}
          }else $('[data-ux-error]',back).textContent=e.message;
        }
      };
    });
  }

  async function refreshGallery(){
    const c=core();
    try{if(typeof c.loadCards==='function'){await c.loadCards(true);setTimeout(instrumentGallery,60);return}if(typeof c.loadState==='function'){await c.loadState();setTimeout(instrumentGallery,80);return}}catch(e){console.warn(e)}
    const search=$('#searchInput')||$('input[type="search"]');if(search){search.dispatchEvent(new Event('input',{bubbles:true}));search.dispatchEvent(new Event('change',{bubbles:true}));setTimeout(instrumentGallery,250)}
  }

  function currentRecordId(){
    const c=core();
    const coreId=Number(c.getCurrentCardId?.()||c.getCurrentCard?.()?.id||0);if(coreId)return coreId;
    if(modalOpen($('#cardModal')))return formCardId(cardForm());
    if(modalOpen($('#noteModal')))return formCardId(noteForm());
    return 0;
  }

  async function waitForRecordId(timeout=1800){
    const started=Date.now();
    while(Date.now()-started<timeout){const id=currentRecordId();if(id)return id;await new Promise(r=>setTimeout(r,30))}
    return 0;
  }

  function nativeCardMedia(cardId){
    const card=core().getCurrentCard?.();if(!card||Number(card.id)!==Number(cardId))return[];
    const out=[],seen=new Set();
    for(const key of ['media','images','files','attachments'])for(const item of (Array.isArray(card[key])?card[key]:[])){const id=Number(item?.id||0);if(!id||seen.has(id))continue;seen.add(id);out.push(item)}
    return out;
  }

  async function loadCurrentMedia(cardId=0){
    const id=Number(cardId||currentRecordId());if(!id){currentCardMedia=[];return[]}
    try{
      const native=nativeCardMedia(id),nativeMap=new Map(native.map(m=>[Number(m.id),m]));
      const d=await req('card_media',{query:{card_id:id}});
      currentCardMedia=(d.media||[]).map(m=>{const base=nativeMap.get(Number(m.id))||{};return {...base,...m,id:Number(m.id),url:m.url||m.file_url||base.url,thumb_url:m.thumb_url||base.thumb_url||'',media_type:String(m.media_type||base.media_type||'file').toLowerCase()}});
      return currentCardMedia;
    }catch(e){console.warn('[Nook UX] card media',e);currentCardMedia=nativeCardMedia(id).map(m=>({...m,id:Number(m.id),url:m.url||m.file_url}));return currentCardMedia}
  }

  function nativeCardMediaContainer(){
    const modal=$('#cardModal');if(!modal)return null;
    for(const sel of ['#cardImages','#cardMediaList','#mediaList','.card-images','.card-media-list','[data-card-media-list]']){const el=$(sel,modal);if(el)return el}
    return null;
  }

  function nativeMediaItems(){
    const host=nativeCardMediaContainer();if(!host)return[];
    const found=[];
    for(const el of [...host.children]){
      const button=el.matches?.('[data-media-id]')?el:el.querySelector?.('[data-media-id]');
      const id=Number(button?.dataset?.mediaId||el.dataset?.mediaId||0);
      if(id)found.push({el,id,button:button||el});
    }
    if(found.length)return found;
    return $$('[data-media-id]',host).map(button=>({el:button.closest('.viewer-image-wrap,.media-item,.card-media-item')||button,id:Number(button.dataset.mediaId||0),button})).filter(x=>x.id);
  }

  function cleanupLegacyMediaUx(){
    $('#uxCardMediaGallery')?.remove();$('#uxGalleryViewer')?.remove();document.body.classList.remove('ux-viewer-open');
    nativeCardMediaContainer()?.classList.remove('ux-native-card-media-hidden');
  }

  function mediaModal(){return $('#mediaModal')||$('#viewerModal')||$('[data-media-modal]')}
  function mediaModalOpen(){const m=mediaModal();return modalOpen(m)}

  function ensureNativeMediaNav(){
    const m=mediaModal();if(!m)return null;
    let nav=$('#uxNativeMediaNav',m);if(nav)return nav;
    const win=$('.modal-window',m)||$('.viewer-window',m)||m;
    win.classList.add('ux-media-nav-host');
    nav=document.createElement('div');nav.id='uxNativeMediaNav';nav.className='ux-native-media-nav';
    nav.innerHTML=`<button type="button" class="ux-native-media-arrow prev" data-ux-media-prev aria-label="Previous">‹</button><button type="button" class="ux-native-media-arrow next" data-ux-media-next aria-label="Next">›</button>`;
    win.append(nav);
    $('[data-ux-media-prev]',nav).onclick=e=>{e.preventDefault();e.stopPropagation();stepNativeMedia(-1)};
    $('[data-ux-media-next]',nav).onclick=e=>{e.preventDefault();e.stopPropagation();stepNativeMedia(1)};
    return nav;
  }

  function mediaIdFromUrl(value){
    if(!value)return 0;
    try{
      const u=new URL(String(value),location.href);
      return Number(u.searchParams.get('id')||0);
    }catch(_){
      const m=String(value).match(/[?&]id=(\d+)/);return m?Number(m[1]):0;
    }
  }

  function currentViewerMediaId(){
    const m=mediaModal();if(!m)return 0;
    const nodes=$$('img,video,iframe,object,embed,source,a[href]',m).filter(el=>!el.closest?.('#uxNativeMediaNav,.modal-header'));
    for(const el of nodes){
      const values=[el.currentSrc,el.src,el.data,el.href,el.getAttribute?.('src'),el.getAttribute?.('data'),el.getAttribute?.('href')];
      for(const value of values){const id=mediaIdFromUrl(value);if(id)return id}
    }
    return 0;
  }

  function nativeMediaIndex(){
    let id=Number(lastMediaId||0);
    let i=currentCardMedia.findIndex(m=>Number(m.id)===id);
    if(i>=0)return i;
    id=currentViewerMediaId();
    if(id){lastMediaId=id;i=currentCardMedia.findIndex(m=>Number(m.id)===id)}
    return i;
  }

  function syncNativeMediaNav(){
    const nav=ensureNativeMediaNav();if(!nav)return;
    const i=nativeMediaIndex(),count=currentCardMedia.length;
    nav.hidden=!mediaModalOpen()||count<2;
    const prev=$('[data-ux-media-prev]',nav),next=$('[data-ux-media-next]',nav);
    if(prev)prev.disabled=i<=0;if(next)next.disabled=i<0||i>=count-1;
  }

  function visibleViewerImage(){
    const m=mediaModal();if(!m)return null;
    const candidates=$$('img',m).filter(img=>!img.closest?.('#uxNativeMediaNav,.modal-header')&&!img.classList.contains('logo'));
    if(!candidates.length)return null;
    const direct=candidates.find(img=>mediaIdFromUrl(img.currentSrc||img.src));
    if(direct)return direct;
    return candidates.sort((a,b)=>{
      const ar=a.getBoundingClientRect(),br=b.getBoundingClientRect();
      return (br.width*br.height)-(ar.width*ar.height);
    })[0]||null;
  }

  function replacePhotoInOpenViewer(media){
    if(!mediaModalOpen()||!media)return false;
    const type=String(media.media_type||'').toLowerCase();
    const mime=String(media.mime||'').toLowerCase();
    if(type!=='image'&&!mime.startsWith('image/'))return false;
    const img=visibleViewerImage();if(!img)return false;
    const url=media.url||media.file_url;if(!url)return false;
    // Reuse the native viewer DOM. This avoids opening another viewer and keeps
    // Escape handling entirely in the core Nook modal.
    img.removeAttribute('srcset');
    img.src=String(url);
    img.alt=String(media.original_filename||media.title||'');
    const link=img.closest('a');if(link&&mediaIdFromUrl(link.href))link.href=String(url);
    for(const sel of ['#mediaTitle','#viewerTitle','[data-media-title]','.media-viewer-title']){
      const title=$(sel,mediaModal());if(title){title.textContent=String(media.original_filename||media.title||'');break}
    }
    return true;
  }

  function clickNativeMediaItem(media){
    const item=nativeMediaItems().find(x=>Number(x.id)===Number(media?.id));
    if(!item)return false;
    const target=item.button||item.el;if(!target)return false;
    try{
      target.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:window}));
      return true;
    }catch(_){try{target.click();return true}catch(__){return false}}
  }

  function openNativeMedia(media){
    if(!media)return;
    lastMediaId=Number(media.id||0);
    // Photo-to-photo navigation is performed inside the already open native
    // viewer. For other types we first replay the exact native thumbnail click,
    // because that supplies the core with its original media object.
    if(replacePhotoInOpenViewer(media)){
      requestAnimationFrame(syncNativeMediaNav);return;
    }
    if(clickNativeMediaItem(media)){
      setTimeout(syncNativeMediaNav,30);return;
    }
    const c=core();
    if(typeof c.openMediaViewer==='function'){
      c.openMediaViewer(media);setTimeout(syncNativeMediaNav,30);
    }
  }

  function stepNativeMedia(dir){
    let i=nativeMediaIndex();
    if(i<0&&currentCardMedia.length){
      // Last-resort synchronization for an old viewer that does not expose the
      // current file URL in a standard element.
      i=0;lastMediaId=Number(currentCardMedia[0]?.id||0);
    }
    const target=currentCardMedia[i+Number(dir||0)];if(!target)return;
    openNativeMedia(target);
  }

  function instrumentNativeCardMedia(){
    cleanupLegacyMediaUx();
    const items=nativeMediaItems();if(!items.length)return;
    // v3.5.6: media reordering was removed completely. The native card media
    // strip remains untouched so Nook's normal click, file drop and paste
    // behaviour continue to work without any sorting handlers.
    for(const {el,id,button} of items){
      if(el) el.dataset.uxMediaId=String(id);
      if(button && button.dataset.uxMediaNavBound!=='1'){
        button.dataset.uxMediaNavBound='1';
        button.addEventListener('click',()=>{lastMediaId=id;setTimeout(syncNativeMediaNav,0)},true);
      }
    }
  }

  async function refreshCardMedia(){
    const id=await waitForRecordId();if(!id)return;
    await loadCurrentMedia(id);instrumentNativeCardMedia();syncNativeMediaNav();
  }

  function ensureCardMoveButton(){
    const modal=$('#cardModal');if(!modal||!modalOpen(modal))return;let b=$('#moveCardBtn',modal)||$('[data-action="move-card"]',modal)||$('#uxCardMoveBtn',modal);if(b){b.hidden=false;b.style.display='';return b}
    b=document.createElement('button');b.id='uxCardMoveBtn';b.type='button';b.className='btn ux-card-move-btn';b.textContent=T.move;b.onclick=e=>{e.preventDefault();const id=Number(currentRecordId());if(id)openMoveDialog([id])};
    const actions=$('.modal-actions',modal)||$('.card-actions',modal)||$('.modal-window',modal);actions.append(b);return b;
  }
  async function repairMediaForSpace(spaceId){if(!spaceId)return;try{const d=await req('repair_media',{query:{space_id:spaceId}});const r=d.repair||{};if(Number(r.type_fixed)+Number(r.preview_fixed)>0){meta.clear();await refreshGallery();toast(T.mediaRepaired)}}catch(e){console.warn('[Nook UX] media repair',e)}}

  function activateLiveEdit(){
    const modal=$('#cardModal'),form=cardForm();if(!modal||!form||!modalOpen(modal))return;modal.classList.add('ux-live-edit');
    const edit=$('#editCardBtn',modal)||$('[data-edit-toggle]',modal);const editable=$('[name="title"]',form);if(edit&&editable?.readOnly){try{edit.click()}catch{}}
    for(const el of $$('input[name="title"],textarea[name="description"],input[name="hashtags"],input[name="tags"],input[name="is_hidden"]',form)){el.readOnly=false;el.disabled=false;if(el.dataset.uxLiveBound!=='1'){el.dataset.uxLiveBound='1';el.addEventListener(el.type==='checkbox'?'change':'input',scheduleCardSave)}}
    const tools=$('#imageEditTools',modal)||$('[data-media-edit-tools]',modal);if(tools)tools.hidden=false;
    $$('.edit-only',modal).forEach(e=>e.hidden=false);
    if(!$('.ux-live-save-state',form)){const st=document.createElement('div');st.className='ux-live-save-state';const actions=$('.modal-actions',form);form.insertBefore(st,actions||null)}
    ensureCardMoveButton();instrumentNativeCardMedia();
  }
  function scheduleCardSave(){clearTimeout(saveTimer);const st=$('.ux-live-save-state',cardForm());if(st)st.textContent=T.saving;saveTimer=setTimeout(saveCardLive,750)}
  async function saveCardLive(){const form=cardForm(),id=formCardId(form);if(!form||!id)return;const fd=new FormData();fd.set('card_id',String(id));for(const name of ['title','description'])if(form.elements[name])fd.set(name,form.elements[name].value);const tagEl=form.elements.hashtags||form.elements.tags;if(tagEl)fd.set('tags',tagEl.value);const hidden=form.elements.is_hidden;if(hidden)fd.set('is_hidden',hidden.checked?'1':'0');try{await req('card_metadata_save',{form:fd});const st=$('.ux-live-save-state',form);if(st)st.textContent=T.saved;const h=$('#cardTitle')||$('#cardModalTitle');if(h&&form.elements.title)h.textContent=form.elements.title.value||'—'}catch(e){const st=$('.ux-live-save-state',form);if(st)st.textContent=e.message}}

  function setInputFiles(input,files){if(!input||!files?.length)return false;try{const dt=new DataTransfer();for(const f of files)dt.items.add(f);input.files=dt.files;input.dispatchEvent(new Event('change',{bubbles:true}));return true}catch(e){console.warn('[Nook UX] file transfer',e);return false}}
  function cardFileInput(){const modal=$('#cardModal');if(!modal)return null;return $('#addMediaInput',modal)||$('#addImagesInput',modal)||$$('input[type="file"][multiple]',modal).find(x=>x.accept||x.id)||null}
  function noteAttachmentInput(){const modal=$('#noteModal');if(!modal)return null;return $('#noteAttachmentsInput',modal)||$('#noteAttachmentInput',modal)||$('input[type="file"][data-note-attachment]',modal)||$('input[type="file"][name*="attachment"]',modal)||null}
  function installOneModalDrop(m,inputGetter,isNote=false){
    if(!m||m.dataset.uxDrop==='1')return;m.dataset.uxDrop='1';
    m.addEventListener('dragover',e=>{if(!(e.dataTransfer?.files?.length||e.dataTransfer?.types?.includes('Files')))return;if(isNote&&e.target.closest?.('.codex-editor,.ce-block'))return;e.preventDefault();m.classList.add('ux-drop-active')});
    m.addEventListener('dragleave',e=>{if(!m.contains(e.relatedTarget))m.classList.remove('ux-drop-active')});
    m.addEventListener('drop',e=>{if(!e.dataTransfer?.files?.length)return;if(isNote&&e.target.closest?.('.codex-editor,.ce-block'))return;const input=inputGetter();if(!input)return;e.preventDefault();e.stopPropagation();m.classList.remove('ux-drop-active');setInputFiles(input,[...e.dataTransfer.files])});
  }
  function installModalFileDrop(){installOneModalDrop($('#cardModal'),cardFileInput,false);installOneModalDrop($('#noteModal'),noteAttachmentInput,true)}
  document.addEventListener('paste',e=>{
    const files=[...(e.clipboardData?.files||[])];if(!files.length)return;
    let input=null;
    if(modalOpen($('#cardModal')))input=cardFileInput();
    else if(modalOpen($('#noteModal'))&&!document.activeElement?.closest?.('.codex-editor,.ce-block'))input=noteAttachmentInput();
    if(!input)return;e.preventDefault();e.stopImmediatePropagation();setInputFiles(input,files);
  },true);

  async function onAnyRecordOpen(){
    const id=await waitForRecordId();if(!id)return;
    if(modalOpen($('#cardModal')))await refreshCardMedia();
    setTimeout(()=>{activateLiveEdit();ensureCardMoveButton();instrumentNativeCardMedia()},30);
  }

  function watchModals(){
    for(const id of ['cardModal','noteModal','mediaModal']){
      const m=$('#'+id);if(!m)continue;let wasOpen=modalOpen(m);
      new MutationObserver(()=>{
        const open=modalOpen(m);
        if(open&&!wasOpen){if(id==='cardModal'||id==='noteModal')onAnyRecordOpen();if(id==='mediaModal')setTimeout(syncNativeMediaNav,0)}
        if(!open&&wasOpen&&id==='cardModal'){currentCardMedia=[];lastMediaId=0;cleanupLegacyMediaUx()}
        wasOpen=open;
      }).observe(m,{attributes:true,attributeFilter:['class','aria-hidden']});
    }
    installModalFileDrop();
    document.addEventListener('click',e=>{
      const button=e.target.closest?.('#cardModal [data-media-id]');
      const id=Number(button?.dataset?.mediaId||0);if(!id)return;
      lastMediaId=id;setTimeout(syncNativeMediaNav,0);
    },true);
    document.addEventListener('keydown',e=>{
      if(!mediaModalOpen())return;
      if(e.key==='ArrowLeft'){e.preventDefault();e.stopPropagation();stepNativeMedia(-1)}
      else if(e.key==='ArrowRight'){e.preventDefault();e.stopPropagation();stepNativeMedia(1)}
      // Escape is deliberately left to the native Nook handler. It closes only
      // the media viewer and leaves the card modal open underneath.
    },true);
  }

  function findContentHeader(){
    return $('.content-header')||$('main .content-header')||$$('header').find(h=>/Записи|Entries/i.test(h.textContent||''))||null;
  }

  function compactFieldFor(control){
    const wrap=document.createElement('div');wrap.className='ux-compact-filter-field';
    if(control.id==='searchInput'||control.type==='search')wrap.classList.add('is-search');
    if(control.type==='date')wrap.classList.add('is-date');
    if(control.tagName==='SELECT')wrap.classList.add('is-select');
    const label=control.id?document.querySelector(`label[for="${CSS.escape(control.id)}"]`):null;
    const labelText=(label?.textContent||control.getAttribute('aria-label')||control.title||'').trim();
    if(labelText)control.title=labelText;
    if(label)label.classList.add('ux-filter-source-label-hidden');
    wrap.append(control);return wrap;
  }

  function moveFiltersToTop(){
    const header=findContentHeader();if(!header)return;
    let host=$('#uxCompactFilters');if(!host){host=document.createElement('div');host.id='uxCompactFilters';host.className='ux-compact-filters'}
    const topActions=ensureTopActions();
    const targetParent=topActions?.parentElement||header;
    if(host.parentElement!==targetParent||host.nextSibling!==topActions){
      if(topActions&&topActions.parentElement===targetParent)targetParent.insertBefore(host,topActions);else targetParent.append(host);
    }
    const controls=[];
    for(const sel of ['#searchInput','#typeFilter','#contentTypeFilter','#visibilityFilter','#dateFromInput','#dateToInput','#resetFiltersBtn']){
      const el=$(sel);if(el&&!controls.includes(el))controls.push(el);
    }
    for(const control of controls){
      if(control.closest('#uxCompactFilters'))continue;
      if(control.id==='resetFiltersBtn'){
        const wrap=document.createElement('div');wrap.className='ux-compact-filter-field is-reset';control.title=control.textContent.trim();control.textContent=lang==='en'?'Reset':'Сброс';wrap.append(control);host.append(wrap);
      }else host.append(compactFieldFor(control));
    }
    // Hide only the now-empty source filter panels. Hashtags are never moved.
    $$('.filters-panel,[data-filters-panel]').forEach(panel=>{
      if(panel.closest('#uxCompactFilters'))return;
      const live=panel.querySelector('input:not([type="hidden"]),select,button:not([hidden])');
      if(!live)panel.classList.add('ux-filter-source-empty');
    });
    host.hidden=host.children.length===0;
  }

  window.addEventListener('resize',scheduleGalleryMasonry,{passive:true});

  async function visibleGalleryMatchesSpace(spaceId){
    const ids=tileCandidates().map(tileId).filter(Boolean).slice(0,60);if(!ids.length)return true;
    try{const d=await req('cards_meta',{query:{ids:ids.join(',')}});return (d.cards||[]).every(c=>Number(c.space_id)===Number(spaceId))}catch{return false}
  }

  async function enforceVisibleDefault(spaceId){
    if(!spaceId)return;
    for(let attempt=0;attempt<3;attempt++){
      await new Promise(r=>setTimeout(r,attempt===0?220:420));
      const ok=await visibleGalleryMatchesSpace(spaceId);if(ok){B.activeSpaceId=Number(spaceId);renderSpaceUi();document.documentElement.classList.remove('nook-ux-booting');return}
      await performCoreSpaceSwitch(Number(spaceId),{forceReload:true});
    }
    B.activeSpaceId=Number(spaceId);renderSpaceUi();document.documentElement.classList.remove('nook-ux-booting');
  }

  function observeAll(){
    let scheduled=false;
    const enhance=()=>{scheduled=false;hideLegacySpaceMenuItems();ensureTopActions();if(!$('#uxSpaceSwitcherHost'))ensureSpaceSwitcher();moveFiltersToTop();instrumentGallery();scheduleGalleryMasonry();if(modalOpen($('#cardModal'))){activateLiveEdit();instrumentNativeCardMedia()}};
    const schedule=()=>{if(scheduled)return;scheduled=true;requestAnimationFrame(enhance)};
    new MutationObserver(schedule).observe(document.body,{childList:true,subtree:true});
    document.addEventListener('nookux:list',()=>{schedule();setTimeout(renderSpaceUi,30)});
  }

  async function init(){
    cleanupLegacyMediaUx();ensureBulkBar();watchModals();
    const boot=window.NookUXBootstrap || {};
    let safeId=0,forceDefault=boot.forcedDefault===true;
    let restoredNormal=false;
    try{
      await loadSpaces();
      const before=detectedCoreSpaceId();
      const beforeSpace=spaces.find(s=>Number(s.id)===Number(before));
      const rememberedId=Number(boot.lastSpaceId||0);
      const rememberedSpace=spaces.find(s=>Number(s.id)===rememberedId);

      // If the privacy bootstrap is missing entirely, retain the conservative
      // fallback for protected nooks. When the bootstrap is present, its canonical
      // last-space decision is authoritative; stale core/localStorage keys cannot
      // force a normal nook back to the default.
      if(!('forcedDefault' in boot) && beforeSpace?.protected) forceDefault=true;

      if(forceDefault){
        safeId=defaultSpaceId;
        if(safeId){
          B.activeSpaceId=Number(safeId);
          await performCoreSpaceSwitch(Number(safeId),{forceReload:true});
        }
      }else if(rememberedSpace && !rememberedSpace.protected){
        // Normal nook -> reload -> the same normal nook, even if the core happened
        // to restore another server-side/default nook during startup.
        safeId=rememberedId;
        B.activeSpaceId=Number(safeId);
        if(Number(before)!==Number(safeId)){
          await performCoreSpaceSwitch(Number(safeId),{forceReload:true});
          restoredNormal=true;
        }
      }else{
        safeId=before||B.activeSpaceId||defaultSpaceId;
      }

      if(safeId){
        try{localStorage.setItem('nook_ux_last_space_id',String(safeId))}catch(_){}
      }
      await repairMediaForSpace(Number(safeId||currentSpaceId()));
    }catch(e){console.warn('[Nook UX] startup:',e);toast(e.message||String(e),true)}
    ensureTopActions();ensureSpaceSwitcher();renderSpaceUi();moveFiltersToTop();instrumentGallery();observeAll();
    if((forceDefault||restoredNormal)&&safeId)enforceVisibleDefault(Number(safeId));else{document.documentElement.classList.remove('nook-ux-booting');B.booting=false}
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
