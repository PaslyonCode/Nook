(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
  const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

  const I18N = {
    ru: {
      loginHint:'Войдите, чтобы открыть хранилище.',username:'Логин',password:'Пароль',signIn:'Войти',defaultCredentials:'По умолчанию: admin / admin123',
      brandSubtitle:'Личное хранилище медиа и заметок',currentNook:'Текущая нычка',noteButton:'Заметка',batchMode:'Групповое добавление',sharedHashtag:'Хэштэг для всех записей',sharedHidden:'Невидимые записи',dropTitle:'Перетащите файлы сюда',dropHint:'Фото, видео, PDF и STL · можно вставить Ctrl+V',chooseFiles:'Выбрать файлы',filters:'Фильтры',searchPlaceholder:'Поиск по всем полям',allTypes:'Все типы',photos:'Фото',videos:'Видео',documents:'Документы PDF',notes:'Заметки',resetFilters:'Сбросить фильтры',hashtags:'Хэштэги',all:'все',trash:'Корзина',entries:'Записи',backToEntries:'Вернуться к записям',availableNooks:'Доступные нычки',addNook:'Добавить нычку',editNook:'Изменить нычку',settings:'Настройки',export:'Экспорт',import:'Импорт',language:'Язык',logout:'Выйти',loading:'Загрузка…',
      newGroup:'Новая группа',title:'Заголовок',description:'Описание',hidden:'Невидимая',cancel:'Отмена',close:'Закрыть',save:'Сохранить',delete:'Удалить',edit:'Редактировать',addFiles:'Добавить файлы',moveToNook:'Переместить в нычку',note:'Заметка',noteTitle:'Заголовок заметки',insertImage:'Вставить картинку',attachments:'Вложения',download:'Скачать',storageFolder:'Папка хранения',storageHelp:'Используйте абсолютный путь к папке, доступной PHP на запись.',name:'Имя',spacePasswordOptional:'Пароль нычки (необязательно)',newSpacePassword:'Пароль новой нычки',newPasswordHint:'Введите новый пароль для нычки.',passwordAction:'Действие с паролем',keepPassword:'Не менять',setPassword:'Установить новый',removePassword:'Убрать пароль',unlockNook:'Открыть нычку',rememberAccess:'Запомнить доступ на 30 дней',unlock:'Открыть',move:'Переместить',exportHelp:'Архив содержит базу и все файлы и сохраняется в подпапке exports.',createExport:'Создать экспорт',importHelp:'Поместите ZIP или распакованную папку в подпапку imports выбранного хранилища.',
      noEntries:'Ничего не найдено',found:'Найдено: {count}',created:'Создано: {count}',saved:'Сохранено',deleted:'Перемещено в корзину',restored:'Восстановлено',confirmDelete:'Переместить запись в корзину?',confirmPermanent:'Окончательно удалить все записи в корзине и связанные файлы?',confirmSpaceDelete:'Удалить нычку? Все записи будут перенесены в другую доступную нычку.',wrongPassword:'Неверный пароль',storageRequired:'Сначала укажите папку хранения в настройках.',autosaved:'Автосохранено',saving:'Сохранение…',changed:'Изменено',editorLoading:'Загрузка редактора…',noteCreated:'Черновик заметки создан',selectFiles:'Выберите хотя бы один поддерживаемый файл.',batchUploading:'Загрузка {count} файлов…',file:'Файл',photo:'Фото',video:'Видео',pdf:'PDF',stl:'STL',noteType:'Заметка',protected:'защищена',open:'Открыть',switchTo:'Переключиться',rename:'Изменить',remove:'Удалить',restore:'Восстановить',emptyTrash:'Очистить корзину',create:'Создать',packageReady:'Архив создан',importConfirm:'Импорт заменит текущую базу и содержимое хранилища. Продолжить?',integrityOk:'Импорт завершен, целостность проверена.',openViewer:'Открыть просмотр',unsupportedPreview:'Для этого типа доступно скачивание.',spaceLocked:'Нычка защищена паролем.',noOtherNooks:'Нет другой нычки для перемещения.',movedToNook:'Запись перемещена в нычку «{name}».',pin:'Закрепить',unpin:'Открепить',pinned:'Запись закреплена',unpinned:'Запись откреплена',pastedFiles:'Вставлено файлов: {count}',
    },
    en: {
      loginHint:'Sign in to open the storage.',username:'Username',password:'Password',signIn:'Sign in',defaultCredentials:'Default: admin / admin123',
      brandSubtitle:'Personal media and notes storage',currentNook:'Current nook',noteButton:'Note',batchMode:'Batch import',sharedHashtag:'Hashtag for all entries',sharedHidden:'Hidden entries',dropTitle:'Drop files here',dropHint:'Photos, videos, PDF and STL · Ctrl+V also works',chooseFiles:'Choose files',filters:'Filters',searchPlaceholder:'Search all fields',allTypes:'All types',photos:'Photos',videos:'Videos',documents:'PDF documents',notes:'Notes',resetFilters:'Reset filters',hashtags:'Hashtags',all:'all',trash:'Trash',entries:'Entries',backToEntries:'Back to entries',availableNooks:'Available nooks',addNook:'Add nook',editNook:'Edit nook',settings:'Settings',export:'Export',import:'Import',language:'Language',logout:'Log out',loading:'Loading…',
      newGroup:'New group',title:'Title',description:'Description',hidden:'Hidden',cancel:'Cancel',close:'Close',save:'Save',delete:'Delete',edit:'Edit',addFiles:'Add files',moveToNook:'Move to nook',note:'Note',noteTitle:'Note title',insertImage:'Insert image',attachments:'Attachments',download:'Download',storageFolder:'Storage folder',storageHelp:'Use an absolute folder path writable by PHP.',name:'Name',spacePasswordOptional:'Nook password (optional)',newSpacePassword:'New nook password',newPasswordHint:'Enter a new password for the nook.',passwordAction:'Password action',keepPassword:'Keep unchanged',setPassword:'Set new password',removePassword:'Remove password',unlockNook:'Unlock nook',rememberAccess:'Remember access for 30 days',unlock:'Unlock',move:'Move',exportHelp:'The package contains the database and all files and is saved under exports.',createExport:'Create export',importHelp:'Put a ZIP or extracted package folder under the selected storage imports folder.',
      noEntries:'Nothing found',found:'Found: {count}',created:'Created: {count}',saved:'Saved',deleted:'Moved to trash',restored:'Restored',confirmDelete:'Move this entry to trash?',confirmPermanent:'Permanently delete all trash entries and their files?',confirmSpaceDelete:'Delete this nook? Its entries will be moved to another available nook.',wrongPassword:'Wrong password',storageRequired:'Configure the storage folder first.',autosaved:'Autosaved',saving:'Saving…',changed:'Changed',editorLoading:'Loading editor…',noteCreated:'Note draft created',selectFiles:'Select at least one supported file.',batchUploading:'Uploading {count} files…',file:'File',photo:'Photo',video:'Video',pdf:'PDF',stl:'STL',noteType:'Note',protected:'protected',open:'Open',switchTo:'Switch',rename:'Edit',remove:'Delete',restore:'Restore',emptyTrash:'Empty trash',create:'Create',packageReady:'Export package created',importConfirm:'Import will replace the current database and managed storage content. Continue?',integrityOk:'Import completed and integrity was verified.',openViewer:'Open viewer',unsupportedPreview:'Download is available for this file type.',spaceLocked:'This nook is password-protected.',noOtherNooks:'There is no other nook to move this entry to.',movedToNook:'Entry moved to “{name}”.',pin:'Pin',unpin:'Unpin',pinned:'Entry pinned',unpinned:'Entry unpinned',pastedFiles:'Pasted files: {count}',
    },
  };

  const state = {
    lang: ['ru','en'].includes(localStorage.getItem('nook_lang')) ? localStorage.getItem('nook_lang') : 'ru',
    app: null, spaces: [], tags: [], currentCard: null, editMode: false,
    page: 1, hasMore: false, loading: false, total: 0, trash: false,
    filters: { q:'', date_from:'', date_to:'', tag:'', type:'all' },
    selectedFiles: [], previewUrls: [], pendingUnlock: null, noteAutosaveTimer: null,
    mediaAutosaveTimer: null, mediaAutosaveBusy: false, mediaAutosavePending: false, mediaLastSnapshot: '', mediaUploadBusy: false,
    observer: null, viewerCleanup: null, noteEditor: null, noteEditorCardId: 0, noteEditorLoading: false,
  };

  const els = {};
  function bindEls() {
    const ids = ['gallery','loadSentinel','statusBox','emptyTrashBtn','resultInfo','currentSpaceName','trashCount','trashBtn','backFromTrashBtn','contentTitle','mainMenuBtn','mainMenu','languageSelect','dropZone','chooseFilesBtn','fileInput','batchUploadInput','batchHashtagInput','batchHiddenInput','newNoteBtn','searchInput','dateFromInput','dateToInput','typeFilter','resetFiltersBtn','clearTagBtn','tagsList','uploadModal','uploadPreview','uploadForm','saveUploadBtn','cardModal','cardMedia','cardForm','cardTitle','cardDate','cardAutosaveState','cardPinBtn','cardEditTools','addMediaBtn','addMediaInput','moveCardBtn','deleteCardBtn','cancelCardEditBtn','editCardBtn','saveCardBtn','noteModal','noteForm','noteEditor','noteAutosaveState','notePinBtn','attachmentInput','addAttachmentBtn','attachmentList','deleteNoteBtn','moveNoteBtn','saveNoteBtn','viewerModal','viewerBody','viewerTitle','viewerMeta','viewerDownload','settingsModal','settingsForm','storageRootInput','spacesModal','spacesList','spaceFormModal','spaceForm','spaceFormTitle','passwordModeLabel','spacePasswordLabel','spacePasswordText','spacePasswordHint','unlockModal','unlockForm','moveModal','moveForm','moveSpaceSelect','exportModal','createExportBtn','exportsList','importModal','importsList'];
    for (const id of ids) els[id] = document.getElementById(id);
  }

  function tr(key, vars = {}) {
    let text = (I18N[state.lang] && I18N[state.lang][key]) || I18N.ru[key] || key;
    for (const [name, value] of Object.entries(vars)) text = text.replaceAll(`{${name}}`, String(value));
    return text;
  }
  function applyLanguage() {
    document.documentElement.lang = state.lang;
    $$('[data-i18n]').forEach((el) => { const k=el.dataset.i18n; if (I18N[state.lang][k]) el.textContent=tr(k); });
    $$('[data-i18n-placeholder]').forEach((el) => { el.placeholder=tr(el.dataset.i18nPlaceholder); });
    if (els.languageSelect) els.languageSelect.value = state.lang;
    const loginLang = $('#loginLanguage'); if (loginLang) loginLang.value=state.lang;
    if (state.currentCard) syncPinButton(state.currentCard);
    if (els.spaceForm) {
      const isEdit = !!els.spaceForm.elements.space_id.value;
      if (els.spaceFormTitle) {
        els.spaceFormTitle.textContent = tr(isEdit ? 'editNook' : 'addNook');
        els.spaceFormTitle.dataset.i18n = isEdit ? 'editNook' : 'addNook';
      }
      syncSpacePasswordControls();
    }
  }
  function escapeHtml(value) { return String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c])); }
  function formatBytes(bytes) { const n=Number(bytes)||0; if(n<1024)return `${n} B`; if(n<1048576)return `${(n/1024).toFixed(1)} KB`; if(n<1073741824)return `${(n/1048576).toFixed(1)} MB`; return `${(n/1073741824).toFixed(2)} GB`; }
  function formatDate(value) { if(!value)return ''; const d=new Date(String(value).replace(' ','T')); return Number.isNaN(d.getTime())?value:d.toLocaleString(state.lang==='ru'?'ru-RU':'en-US'); }

  function showStatus(message, type='info') { if(!els.statusBox)return; els.statusBox.textContent=message; els.statusBox.className=`status-box ${type}`; els.statusBox.hidden=false; if(type!=='error')setTimeout(()=>{els.statusBox.hidden=true;},3500); }

  class SpaceLockedError extends Error { constructor(spaceId){super(tr('spaceLocked'));this.spaceId=spaceId;} }
  async function readJson(response) {
    const text=await response.text(); let data;
    try{data=JSON.parse(text);}catch{throw new Error(`Server did not return JSON: ${text.slice(0,180)}`);}
    if(response.status===401){location.href='index.php';throw new Error('Authentication required.');}
    if(data.space_locked) throw new SpaceLockedError(data.space_id);
    if(!response.ok||data.ok===false)throw new Error(data.error||'API error');
    return data;
  }
  async function api(action, options={}) {
    const {method='GET', params={}, formData=null}=options;
    if(method==='GET'){
      const url=new URL('api.php',location.href);url.searchParams.set('action',action);Object.entries(params).forEach(([k,v])=>{if(v!==''&&v!=null)url.searchParams.set(k,v);});
      return readJson(await fetch(url,{headers:{Accept:'application/json'}}));
    }
    const fd=formData||new FormData();fd.set('action',action);return readJson(await fetch('api.php',{method:'POST',headers:{Accept:'application/json'},body:fd}));
  }
  async function withUnlock(task) {
    try{return await task();}catch(error){if(!(error instanceof SpaceLockedError))throw error;return new Promise((resolve,reject)=>{state.pendingUnlock={task,resolve,reject};openUnlock(error.spaceId);});}
  }

  function modal(name) {
    const direct = document.getElementById(`${name}Modal`);
    if (direct) return direct;
    const camelName = String(name).replace(/-([a-z])/g, (_match, letter) => letter.toUpperCase());
    return document.getElementById(`${camelName}Modal`);
  }
  function openModal(name) { const m=modal(name);if(!m)return;m.classList.add('open');m.setAttribute('aria-hidden','false');document.body.classList.add('modal-open'); }
  function closeModal(name) { const m=modal(name);if(!m)return;if(name==='card'&&state.editMode)saveMediaCard(true).catch(()=>{});m.classList.remove('open');m.setAttribute('aria-hidden','true');if(name==='viewer')cleanupViewer();if(name==='upload')clearUploadPreview();if(name==='card'){stopMediaAutosave();state.editMode=false;}if(name==='note'){stopNoteAutosave();if(state.currentCard?.is_draft){const fd=new FormData();fd.set('id',state.currentCard.id);api('discard_draft',{method:'POST',formData:fd}).catch(()=>{});}state.currentCard=null;}if(!$('.modal.open'))document.body.classList.remove('modal-open'); }

  async function loadState() {
    const data=await api('state'); state.app=data;state.spaces=data.spaces||[];state.tags=data.tags||[];
    els.currentSpaceName.textContent=data.current_space?.name||'—';els.trashCount.textContent=data.trash_count?`(${data.trash_count})`:'';els.storageRootInput.value=data.storage_root||'';
    renderTags();renderSpaces();renderMoveOptions();
    if(!data.storage_configured){openModal('settings');showStatus(tr('storageRequired'),'error');}
    if(!data.current_space_unlocked) openUnlock(data.current_space?.id||0);
  }

  function renderTags(){els.tagsList.innerHTML='';if(!state.tags.length){els.tagsList.textContent='—';return;}for(const tag of state.tags){const b=document.createElement('button');b.className='tag-filter'+(state.filters.tag===tag.name?' active':'');b.textContent=`#${tag.name} (${tag.cards_count})`;b.onclick=()=>{state.filters.tag=state.filters.tag===tag.name?'':tag.name;resetGallery();};els.tagsList.appendChild(b);}}

  async function loadCards(append=false){if(state.loading||(!append&&state.page<1))return;state.loading=true;els.loadSentinel.hidden=false;try{const data=await withUnlock(()=>api('list',{params:{...state.filters,page:state.page,trash:state.trash?1:0}}));state.total=data.total;state.hasMore=data.has_more;state.tags=data.tags||state.tags;renderTags();if(!append)els.gallery.innerHTML='';for(const card of data.cards)els.gallery.appendChild(renderCard(card));if(!append&&!data.cards.length)els.gallery.innerHTML=`<div class="empty-state">${tr('noEntries')}</div>`;els.resultInfo.textContent=tr('found',{count:data.total});state.page++;}catch(e){showStatus(e.message,'error');}finally{state.loading=false;els.loadSentinel.hidden=!state.hasMore;}}
  function resetGallery(){state.page=1;state.hasMore=true;els.gallery.innerHTML='';loadCards(false);}

  function dominantType(card){if(card.entry_type==='note')return 'note';return card.media?.[0]?.media_type||'file';}
  function typeLabel(type){return {image:tr('photo'),video:tr('video'),pdf:'PDF',stl:'STL',file:tr('file'),note:tr('noteType')}[type]||type;}
  function renderCard(card){const article=document.createElement('article');article.className='card-tile';article.dataset.id=card.id;if(card.entry_type==='note'){article.innerHTML=`<div class="note-tile"><h3>${escapeHtml(card.title||tr('note'))}</h3><p>${escapeHtml(card.snippet||'')}</p></div><div class="tile-footer"><span>${tr('noteType')}</span><span>${formatDate(card.created_at)}</span></div>`;}else{const media=(card.media||[]).slice(0,4);article.innerHTML=`<div class="tile-media ${media.length>1?'multi':'single'}">${media.map((m,i)=>`<div class="tile-media-item"><img loading="lazy" src="${escapeHtml(m.preview_url)}" alt=""><span class="type-badge">${typeLabel(m.media_type)}</span>${i===3&&card.media_count>4?`<span class="more-badge">+${card.media_count-4}</span>`:''}</div>`).join('')}</div><div class="tile-footer"><span>${typeLabel(dominantType(card))}</span><span>${formatDate(card.created_at)}</span></div>`;}article.onclick=()=>openCard(card.id);return article;}

  function openMoveForCurrentCard() {
    if (!state.currentCard) return;
    const count = renderMoveOptions(state.currentCard.space_id);
    if (!count) {
      showStatus(tr('noOtherNooks'), 'error');
      return;
    }
    els.moveForm.elements.id.value = state.currentCard.id;
    openModal('move');
  }

  async function openCard(id){try{const data=await withUnlock(()=>api('get',{params:{id}}));state.currentCard=data.card;if(data.card.entry_type==='note')await renderNote(data.card);else renderMediaCard(data.card);}catch(e){showStatus(e.message,'error');}}

  function syncPinButton(card) {
    const button = card?.entry_type === 'note' ? els.notePinBtn : els.cardPinBtn;
    if (!button) return;
    button.hidden = !card || !!card.deleted_at;
    button.classList.toggle('active', !!card?.is_pinned);
    const label = tr(card?.is_pinned ? 'unpin' : 'pin');
    button.title = label;
    button.setAttribute('aria-label', label);
  }

  async function saveDraftNoteBeforePin() {
    if (!state.currentCard || state.currentCard.entry_type !== 'note' || !state.currentCard.is_draft) return;
    const fd = new FormData(els.noteForm);
    const content = await collectNoteContent();
    fd.set('body_json', content.json);
    fd.set('body_html', content.html);
    fd.set('is_hidden', els.noteForm.elements.is_hidden.checked ? '1' : '0');
    els.noteAutosaveState.textContent = tr('saving');
    const data = await api('note_save', { method:'POST', formData:fd });
    state.currentCard = data.card;
    els.noteAutosaveState.textContent = tr('saved');
    els.deleteNoteBtn.hidden = false;
  }

  async function togglePinCurrent() {
    if (!state.currentCard || state.currentCard.deleted_at) return;
    const button = state.currentCard.entry_type === 'note' ? els.notePinBtn : els.cardPinBtn;
    if (button) button.disabled = true;
    try {
      if (state.currentCard.entry_type === 'note' && state.currentCard.is_draft) {
        await saveDraftNoteBeforePin();
      }
      const fd = new FormData();
      fd.set('id', state.currentCard.id);
      const wasPinned = !!state.currentCard.is_pinned;
      const data = await withUnlock(() => api('pin_toggle', { method:'POST', formData:fd }));
      state.currentCard = data.card;
      syncPinButton(data.card);
      showStatus(tr(wasPinned ? 'unpinned' : 'pinned'), 'success');
      resetGallery();
    } catch (e) {
      showStatus(e.message, 'error');
    } finally {
      if (button) button.disabled = false;
    }
  }

  function setCardEdit(enabled){state.editMode=enabled;for(const el of ['title','description','hashtags']){const f=els.cardForm.elements[el];if(f)f.readOnly=!enabled;}els.cardForm.elements.is_hidden.disabled=!enabled;els.cardEditTools.hidden=!enabled;els.editCardBtn.hidden=enabled;els.saveCardBtn.hidden=!enabled;els.cancelCardEditBtn.hidden=!enabled;if(els.moveCardBtn)els.moveCardBtn.hidden=enabled||!!state.currentCard?.deleted_at||state.spaces.filter(s=>Number(s.id)!==Number(state.currentCard?.space_id)).length===0;$$('.media-delete-btn',els.cardMedia).forEach(b=>b.hidden=!enabled);if(!enabled)stopMediaAutosave();}
  function mediaThumbHtml(m){return `<button class="media-open-btn" type="button" data-media-id="${m.id}"><img loading="lazy" src="${escapeHtml(m.preview_url)}" alt=""><span class="media-item-name">${escapeHtml(m.name)} · ${typeLabel(m.media_type)}</span></button><button class="media-delete-btn" type="button" data-delete-media="${m.id}" hidden>×</button>`;}
  function mediaFormSnapshot(){return JSON.stringify({id:els.cardForm.elements.id.value,title:els.cardForm.elements.title.value,description:els.cardForm.elements.description.value,hashtags:els.cardForm.elements.hashtags.value,is_hidden:els.cardForm.elements.is_hidden.checked?1:0});}
  function stopMediaAutosave(){if(state.mediaAutosaveTimer)clearTimeout(state.mediaAutosaveTimer);state.mediaAutosaveTimer=null;state.mediaAutosavePending=false;}
  function scheduleMediaAutosave(){if(!state.editMode||!state.currentCard||state.currentCard.entry_type!=='media')return;if(state.mediaAutosaveTimer)clearTimeout(state.mediaAutosaveTimer);els.cardAutosaveState.textContent=tr('saving');state.mediaAutosaveTimer=setTimeout(()=>saveMediaCard(true),1200);}
  function renderMediaCard(card,startEditing=false){stopMediaAutosave();state.currentCard=card;els.cardTitle.textContent=card.title||typeLabel(dominantType(card));els.cardDate.textContent=formatDate(card.created_at);els.cardAutosaveState.textContent='';els.cardMedia.innerHTML=(card.media||[]).map(m=>`<div class="media-item">${mediaThumbHtml(m)}</div>`).join('');els.cardForm.elements.id.value=card.id;els.cardForm.elements.title.value=card.title||'';els.cardForm.elements.description.value=card.description||'';els.cardForm.elements.hashtags.value=(card.tags||[]).map(t=>`#${t}`).join(' ');els.cardForm.elements.is_hidden.checked=!!card.is_hidden;els.deleteCardBtn.textContent=card.deleted_at?tr('restore'):tr('delete');state.mediaLastSnapshot=mediaFormSnapshot();syncPinButton(card);setCardEdit(!!startEditing);$$('[data-media-id]',els.cardMedia).forEach(b=>b.onclick=()=>openViewer(card.media.find(m=>String(m.id)===b.dataset.mediaId)));$$('[data-delete-media]',els.cardMedia).forEach(b=>b.onclick=()=>deleteMedia(Number(b.dataset.deleteMedia)));openModal('card');}

  async function saveMediaCard(auto=false){if(!state.currentCard||state.currentCard.entry_type!=='media')return;if(state.mediaAutosaveBusy){state.mediaAutosavePending=true;return;}const snapshot=mediaFormSnapshot();if(auto&&snapshot===state.mediaLastSnapshot){els.cardAutosaveState.textContent=tr('autosaved');return;}state.mediaAutosaveBusy=true;if(auto)els.cardAutosaveState.textContent=tr('saving');const fd=new FormData(els.cardForm);fd.set('is_hidden',els.cardForm.elements.is_hidden.checked?'1':'0');try{const data=await withUnlock(()=>api('media_update',{method:'POST',formData:fd}));state.currentCard=data.card;state.mediaLastSnapshot=mediaFormSnapshot();els.cardTitle.textContent=data.card.title||typeLabel(dominantType(data.card));els.cardAutosaveState.textContent=auto?tr('autosaved'):tr('saved');if(!auto){renderMediaCard(data.card,false);showStatus(tr('saved'),'success');}resetGallery();}catch(e){if(!auto)showStatus(e.message,'error');else els.cardAutosaveState.textContent=e.message;}finally{state.mediaAutosaveBusy=false;if(state.mediaAutosavePending){state.mediaAutosavePending=false;scheduleMediaAutosave();}}}
  async function deleteMedia(id){if(!confirm(tr('confirmDelete')))return;try{const fd=new FormData();fd.set('media_id',id);const data=await api('media_delete',{method:'POST',formData:fd});state.currentCard=data.card;renderMediaCard(data.card,true);resetGallery();}catch(e){showStatus(e.message,'error');}}

  function acceptedFiles(list){return Array.from(list||[]).filter(f=>f.type.startsWith('image/')||f.type.startsWith('video/')||/\.(jpg|jpeg|png|gif|webp|mp4|webm|ogv|ogg|mov|avi|mkv|m4v|pdf|stl)$/i.test(f.name));}
  function clipboardFiles(event) {
    const clipboard = event.clipboardData;
    if (!clipboard) return [];
    const candidates = [];
    for (const file of Array.from(clipboard.files || [])) candidates.push(file);
    for (const item of Array.from(clipboard.items || [])) {
      if (item.kind !== 'file') continue;
      const file = item.getAsFile();
      if (file) candidates.push(file);
    }
    const seen = new Set();
    return candidates.filter(file => {
      const key = [file.name, file.size, file.type, file.lastModified].join('|');
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });
  }

  async function handleClipboardPaste(event) {
    if ($('.modal.open')) return;
    const files = acceptedFiles(clipboardFiles(event));
    if (!files.length) return;
    event.preventDefault();
    showStatus(tr('pastedFiles', { count:files.length }), 'info');
    await handleFiles(files);
  }
  function clearUploadPreview(){state.previewUrls.forEach(URL.revokeObjectURL);state.previewUrls=[];state.selectedFiles=[];els.fileInput.value='';els.uploadPreview.innerHTML='';els.uploadForm.reset();}
  function previewFile(file){const url=URL.createObjectURL(file);state.previewUrls.push(url);if(file.type.startsWith('image/'))return `<img src="${url}" alt="">`;if(file.type.startsWith('video/'))return `<video src="${url}" muted preload="metadata"></video>`;return `<img src="data:image/svg+xml,${encodeURIComponent(`<svg xmlns='http://www.w3.org/2000/svg' width='400' height='260'><rect width='100%' height='100%' fill='%23182234'/><text x='50%' y='50%' fill='white' text-anchor='middle' font-size='40'>${file.name.toLowerCase().endsWith('.stl')?'STL':'PDF'}</text></svg>`)}">`;}
  async function handleFiles(fileList){const files=acceptedFiles(fileList);if(!files.length){showStatus(tr('selectFiles'),'error');return;}if(els.batchUploadInput.checked){await uploadSeparate(files);return;}state.selectedFiles=files;els.uploadForm.reset();els.uploadForm.elements.title.value=files.length===1?files[0].name:`${files[0].name} +${files.length-1}`;els.uploadPreview.innerHTML=files.map(f=>`<div class="preview-item">${previewFile(f)}<span>${escapeHtml(f.name)}</span></div>`).join('');openModal('upload');await saveUpload(true,files);}

  function waitVideo(video,event){return new Promise((resolve,reject)=>{const ok=()=>{cleanup();resolve();},bad=()=>{cleanup();reject(new Error('Video preview failed'));},cleanup=()=>{video.removeEventListener(event,ok);video.removeEventListener('error',bad);};video.addEventListener(event,ok,{once:true});video.addEventListener('error',bad,{once:true});});}
  async function videoPreviewBlob(file){const url=URL.createObjectURL(file),video=document.createElement('video');video.muted=true;video.playsInline=true;video.preload='metadata';video.src=url;try{await waitVideo(video,'loadedmetadata');if(video.duration>0.3){const p=waitVideo(video,'seeked');video.currentTime=Math.min(1,video.duration/3);await p;}else if(video.readyState<2)await waitVideo(video,'loadeddata');const scale=Math.min(640/video.videoWidth,640/video.videoHeight,1),canvas=document.createElement('canvas');canvas.width=Math.max(1,Math.round(video.videoWidth*scale));canvas.height=Math.max(1,Math.round(video.videoHeight*scale));canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);return await new Promise(r=>canvas.toBlob(r,'image/jpeg',.82));}finally{URL.revokeObjectURL(url);video.removeAttribute('src');}}
  async function appendVideoPreviews(fd,files){for(const f of files){if(!f.type.startsWith('video/'))continue;try{const b=await videoPreviewBlob(f);if(b)fd.append('video_previews[]',b,`${f.name}_preview.jpg`);}catch{}}}
  async function uploadSeparate(files){const fd=new FormData();files.forEach(f=>fd.append('files[]',f));fd.set('hashtag',els.batchHashtagInput.value.trim());fd.set('is_hidden',els.batchHiddenInput.checked?'1':'0');showStatus(tr('batchUploading',{count:files.length}));await appendVideoPreviews(fd,files);try{const data=await withUnlock(()=>api('media_create_separate',{method:'POST',formData:fd}));showStatus(tr('created',{count:data.created}),'success');resetGallery();}catch(e){showStatus(e.message,'error');}}
  async function saveUpload(auto=false,sourceFiles=null){if(state.mediaUploadBusy)return;const files=sourceFiles||state.selectedFiles;if(!files.length)return;state.mediaUploadBusy=true;const fd=new FormData(els.uploadForm);files.forEach(f=>fd.append('files[]',f));fd.set('is_hidden',els.uploadForm.elements.is_hidden.checked?'1':'0');els.saveUploadBtn.disabled=true;els.saveUploadBtn.textContent=tr('saving');try{await appendVideoPreviews(fd,files);const created=await withUnlock(()=>api('media_create',{method:'POST',formData:fd}));let card=created.cards?.[0];if(!card)throw new Error('Created entry was not returned.');const modalStillOpen=els.uploadModal.classList.contains('open');if(modalStillOpen){const meta=new FormData(els.uploadForm);meta.set('id',card.id);meta.set('is_hidden',els.uploadForm.elements.is_hidden.checked?'1':'0');const updated=await withUnlock(()=>api('media_update',{method:'POST',formData:meta}));card=updated.card;closeModal('upload');renderMediaCard(card,true);}showStatus(tr(auto?'autosaved':'saved'),'success');resetGallery();}catch(e){showStatus(e.message,'error');}finally{state.mediaUploadBusy=false;els.saveUploadBtn.disabled=false;els.saveUploadBtn.textContent=tr('save');}}
  async function addMedia(files){files=acceptedFiles(files);if(!files.length)return;const fd=new FormData();fd.set('id',state.currentCard.id);files.forEach(f=>fd.append('files[]',f));await appendVideoPreviews(fd,files);try{const data=await api('media_add',{method:'POST',formData:fd});state.currentCard=data.card;renderMediaCard(data.card,true);resetGallery();}catch(e){showStatus(e.message,'error');}}

  function startNoteAutosave(){stopNoteAutosave();state.noteAutosaveTimer=setInterval(()=>saveNote(true),3*60*1000);}
  function stopNoteAutosave(){if(state.noteAutosaveTimer)clearInterval(state.noteAutosaveTimer);state.noteAutosaveTimer=null;}
  async function newNote(){try{const data=await withUnlock(()=>api('note_draft',{method:'POST'}));await renderNote(data.card);showStatus(tr('noteCreated'),'success');}catch(e){showStatus(e.message,'error');}}

  function legacyHtmlToEditorData(html) {
    const source=document.createElement('div');source.innerHTML=String(html||'');const blocks=[];
    const paragraph=(value)=>{if(String(value||'').replace(/<br\s*\/?>/gi,'').trim())blocks.push({type:'paragraph',data:{text:String(value)}});};
    const addNode=(node)=>{
      if(node.nodeType===Node.TEXT_NODE){if(node.textContent.trim())paragraph(escapeHtml(node.textContent));return;}
      if(node.nodeType!==Node.ELEMENT_NODE)return;
      const tag=node.tagName.toLowerCase();
      if(tag==='br')return;
      if(/^h[1-6]$/.test(tag)){blocks.push({type:'header',data:{text:node.innerHTML,level:Number(tag[1])}});return;}
      if(tag==='ul'||tag==='ol'){blocks.push({type:'list',data:{style:tag==='ol'?'ordered':'unordered',items:[...node.children].filter(el=>el.tagName==='LI').map(el=>el.innerHTML)}});return;}
      if(tag==='blockquote'){blocks.push({type:'quote',data:{text:node.innerHTML,caption:'',alignment:'left'}});return;}
      if(tag==='pre'){blocks.push({type:'code',data:{code:node.textContent||''}});return;}
      if(tag==='hr'){blocks.push({type:'delimiter',data:{}});return;}
      if(tag==='table'){
        const rows=[...node.querySelectorAll('tr')].map(row=>[...row.children].filter(cell=>/^(TD|TH)$/.test(cell.tagName)).map(cell=>cell.innerHTML));
        blocks.push({type:'table',data:{withHeadings:!!node.querySelector('tr:first-child th'),content:rows.length?rows:[['','']]}});return;
      }
      if(tag==='img'){
        const width=(node.style.width||'').trim();
        blocks.push({type:'image',data:{file:{url:node.getAttribute('src')||'',mediaId:Number(node.dataset.mediaId||0)||undefined},caption:node.getAttribute('alt')||'',width:/^(25|50|75|100)%$/.test(width)?width:'100%',withBorder:false,withBackground:false,stretched:false}});return;
      }
      if(tag==='p'&&node.children.length===1&&node.firstElementChild?.tagName==='IMG'){addNode(node.firstElementChild);return;}
      if(tag==='div'&&[...node.children].some(el=>/^(P|DIV|H[1-6]|UL|OL|BLOCKQUOTE|PRE|HR|TABLE|IMG)$/.test(el.tagName))){[...node.childNodes].forEach(addNode);return;}
      paragraph(tag==='p'||tag==='div'?node.innerHTML:node.outerHTML);
    };
    [...source.childNodes].forEach(addNode);
    return {time:Date.now(),version:'2.31.0',blocks};
  }

  function noteEditorData(card) {
    let data=card?.body_json;
    if(typeof data==='string'){try{data=JSON.parse(data);}catch{data=null;}}
    if(data&&Array.isArray(data.blocks)){
      const legacy=data.blocks.length===1&&data.blocks[0]?.type==='raw'&&typeof data.blocks[0]?.data?.html==='string';
      if(!legacy)return data;
      return legacyHtmlToEditorData(card?.body_html||data.blocks[0].data.html);
    }
    return legacyHtmlToEditorData(card?.body_html||'');
  }

  function listItemsHtml(items,style) {
    const tag=style==='ordered'?'ol':'ul';
    const rows=(Array.isArray(items)?items:[]).map(item=>{
      const data=typeof item==='string'?{content:item,items:[]}:(item||{});
      const content=data.content??data.text??'';
      return `<li>${content}${Array.isArray(data.items)&&data.items.length?listItemsHtml(data.items,style):''}</li>`;
    }).join('');
    return `<${tag}>${rows}</${tag}>`;
  }

  function editorDataToHtml(data) {
    return (data?.blocks||[]).map(block=>{
      const d=block?.data||{};
      switch(block?.type){
        case 'header':{const level=Math.min(4,Math.max(1,Number(d.level)||2));return `<h${level}>${d.text||''}</h${level}>`;}
        case 'list':return listItemsHtml(d.items,d.style||d.type);
        case 'checklist':return `<div>${(d.items||[]).map(item=>`<p>${item?.checked?'☑':'☐'} ${item?.text||''}</p>`).join('')}</div>`;
        case 'quote':return `<blockquote>${d.text||''}${d.caption?`<p><em>${d.caption}</em></p>`:''}</blockquote>`;
        case 'warning':return `<div>${d.title?`<strong>${d.title}</strong>`:''}${d.message?`<p>${d.message}</p>`:''}</div>`;
        case 'delimiter':return '<hr>';
        case 'table':{
          const rows=Array.isArray(d.content)?d.content:[];
          return `<table>${rows.map((row,ri)=>`<tr>${(row||[]).map(cell=>`<${ri===0&&d.withHeadings?'th':'td'}>${cell??''}</${ri===0&&d.withHeadings?'th':'td'}>`).join('')}</tr>`).join('')}</table>`;
        }
        case 'code':return `<pre><code>${escapeHtml(d.code||'')}</code></pre>`;
        case 'raw':return String(d.html||'');
        case 'embed':return d.source?`<p><a href="${escapeHtml(d.source)}">${escapeHtml(d.caption||d.source)}</a></p>`:'';
        case 'image':{
          const url=d.file?.url||d.url||'';if(!url)return '';
          const rawWidth=String(d.width||d.imageWidth||'100%');const width=/^(25|50|75|100)%$/.test(rawWidth)?rawWidth:'100%';
          const mediaId=Number(d.file?.mediaId||d.file?.id||d.mediaId||0);
          return `<div><img src="${escapeHtml(url)}" alt="${escapeHtml(String(d.caption||'').replace(/<[^>]*>/g,''))}"${mediaId?` data-media-id="${mediaId}"`:''} style="width:${width}">${d.caption?`<p>${d.caption}</p>`:''}</div>`;
        }
        case 'paragraph':default:return d.text?`<p>${d.text}</p>`:'';
      }
    }).join('');
  }

  async function imageToolUpload(file) {
    const uploaded=await uploadNoteFiles([file],'inline');const media=uploaded[0];
    if(!media)throw new Error(state.lang==='ru'?'Изображение не было загружено.':'The image was not uploaded.');
    return {success:1,file:{url:media.url,name:media.name||file.name,mediaId:Number(media.id)||0}};
  }

  async function initializeNoteEditor(card) {
    if(typeof window.EditorJS!=='function')throw new Error('Локальное ядро Editor.js не загрузилось.');
    const data=noteEditorData(card);state.noteEditorLoading=true;
    try{
      if(state.noteEditor){
        try{await state.noteEditor.isReady;state.noteEditor.destroy();}catch(_error){}
        state.noteEditor=null;
      }
      els.noteEditor.replaceChildren();
      state.noteEditor=new window.EditorJS({
        holder:els.noteEditor,data,autofocus:true,logLevel:'ERROR',
        tools:{
          image:{
            class:window.ImageTool,
            inlineToolbar:true,
            config:{
              types:'image/*',captionPlaceholder:state.lang==='ru'?'Подпись':'Caption',
              uploader:{uploadByFile:imageToolUpload,uploadByUrl:async()=>({success:0})}
            }
          }
        },
        onChange:()=>{if(!state.noteEditorLoading)els.noteAutosaveState.textContent=tr('changed');}
      });
      await state.noteEditor.isReady;
      state.noteEditorCardId=Number(card.id)||0;
    }finally{state.noteEditorLoading=false;}
  }

  async function collectNoteContent() {
    let data=noteEditorData(state.currentCard);
    if(state.noteEditor&&state.noteEditorCardId===Number(state.currentCard?.id||0)){
      await state.noteEditor.isReady;
      data=await state.noteEditor.save();
    }
    return {data,json:JSON.stringify(data),html:editorDataToHtml(data)};
  }

  async function renderNote(card){
    state.currentCard=card;els.noteForm.elements.id.value=card.id;els.noteForm.elements.title.value=card.title||'';els.noteForm.elements.hashtags.value=(card.tags||[]).map(t=>`#${t}`).join(' ');els.noteForm.elements.is_hidden.checked=!!card.is_hidden;renderAttachments(card.media||[]);els.deleteNoteBtn.hidden=!!card.is_draft;els.deleteNoteBtn.textContent=card.deleted_at?tr('restore'):tr('delete');if(els.moveNoteBtn)els.moveNoteBtn.hidden=!!card.is_draft||!!card.deleted_at||state.spaces.filter(s=>Number(s.id)!==Number(card.space_id)).length===0;els.noteAutosaveState.textContent=tr('editorLoading');syncPinButton(card);openModal('note');
    try{await initializeNoteEditor(card);els.noteAutosaveState.textContent='';startNoteAutosave();}catch(error){els.noteAutosaveState.textContent='';els.noteEditor.innerHTML=`<div class="editor-error">${escapeHtml(error.message||String(error))}</div>`;showStatus(error.message||String(error),'error');}
  }

  async function saveNote(auto=false){if(!state.currentCard)return;const fd=new FormData(els.noteForm);if(!auto)els.noteAutosaveState.textContent=tr('saving');try{const content=await collectNoteContent();fd.set('body_json',content.json);fd.set('body_html',content.html);fd.set('is_hidden',els.noteForm.elements.is_hidden.checked?'1':'0');const data=await api('note_save',{method:'POST',formData:fd});state.currentCard=data.card;syncPinButton(data.card);els.deleteNoteBtn.hidden=!!data.card.is_draft;els.noteAutosaveState.textContent=auto?tr('autosaved'):tr('saved');resetGallery();}catch(e){els.noteAutosaveState.textContent='';if(!auto)showStatus(e.message,'error');}}
  function renderAttachments(media){const attachments=media.filter(m=>m.role==='attachment');els.attachmentList.innerHTML=attachments.map(m=>`<div class="attachment-row"><button type="button" data-att-open="${m.id}">${escapeHtml(m.name)} · ${typeLabel(m.media_type)} · ${formatBytes(m.size_bytes)}</button><a class="btn" href="${m.url}&download=1">↓</a><button class="btn btn-danger" type="button" data-att-delete="${m.id}">×</button></div>`).join('');$$('[data-att-open]',els.attachmentList).forEach(b=>b.onclick=()=>openViewer(media.find(m=>String(m.id)===b.dataset.attOpen)));$$('[data-att-delete]',els.attachmentList).forEach(b=>b.onclick=()=>deleteNoteAttachment(Number(b.dataset.attDelete)));}
  async function uploadNoteFiles(files,role){if(!state.currentCard)return;const fd=new FormData();fd.set('id',state.currentCard.id);Array.from(files||[]).forEach(f=>fd.append('files[]',f));if(role==='attachment')await appendVideoPreviews(fd,Array.from(files||[]));const action=role==='inline'?'note_inline_upload':'note_attachment_upload';const data=await api(action,{method:'POST',formData:fd});state.currentCard=data.card;renderAttachments(data.card.media||[]);return data.uploaded||[];}
  async function deleteNoteAttachment(id){const fd=new FormData();fd.set('media_id',id);const data=await api('media_delete',{method:'POST',formData:fd});state.currentCard=data.card;renderAttachments(data.card.media||[]);}

  async function softDeleteCurrent(){if(!state.currentCard)return;const isDeleted=!!state.currentCard.deleted_at;if(!isDeleted&&!confirm(tr('confirmDelete')))return;const fd=new FormData();fd.set('id',state.currentCard.id);await api(isDeleted?'restore':'delete',{method:'POST',formData:fd});closeModal(state.currentCard.entry_type==='note'?'note':'card');showStatus(isDeleted?tr('restored'):tr('deleted'),'success');resetGallery();loadState();}

  function cleanupViewer(){if(state.viewerCleanup)state.viewerCleanup();state.viewerCleanup=null;els.viewerBody.innerHTML='';}
  async function openViewer(media){if(!media)return;cleanupViewer();els.viewerTitle.textContent=media.name;els.viewerMeta.textContent=`${typeLabel(media.media_type)} · ${formatBytes(media.size_bytes)}`;els.viewerDownload.href=`${media.url}&download=1`;if(media.media_type==='image'){els.viewerBody.innerHTML=`<img src="${escapeHtml(media.url)}" alt="">`;}else if(media.media_type==='video'){const v=document.createElement('video');v.src=media.url;v.controls=true;v.autoplay=true;v.playsInline=true;els.viewerBody.appendChild(v);state.viewerCleanup=()=>{v.pause();v.removeAttribute('src');v.load();};if(!media.has_real_preview)v.addEventListener('loadeddata',()=>saveOpenedVideoPreview(media,v),{once:true});}else if(media.media_type==='pdf'){els.viewerBody.innerHTML=`<iframe src="${escapeHtml(media.url)}"></iframe>`;}else if(media.media_type==='stl'){const canvas=document.createElement('canvas');els.viewerBody.appendChild(canvas);viewStl(canvas,media.url);}else{els.viewerBody.innerHTML=`<div class="muted">${tr('unsupportedPreview')}</div>`;}openModal('viewer');}
  async function saveOpenedVideoPreview(media,video){try{const scale=Math.min(640/video.videoWidth,640/video.videoHeight,1),c=document.createElement('canvas');c.width=Math.max(1,Math.round(video.videoWidth*scale));c.height=Math.max(1,Math.round(video.videoHeight*scale));c.getContext('2d').drawImage(video,0,0,c.width,c.height);const blob=await new Promise(r=>c.toBlob(r,'image/jpeg',.82));if(!blob)return;const fd=new FormData();fd.set('media_id',media.id);fd.append('preview',blob,'preview.jpg');const data=await api('save_video_preview',{method:'POST',formData:fd});media.preview_url=data.preview_url;media.has_real_preview=true;}catch{}}

  async function viewStl(canvas,url){const response=await fetch(url);const buffer=await response.arrayBuffer();let triangles=[];const dv=new DataView(buffer);const expected=buffer.byteLength>=84?84+dv.getUint32(80,true)*50:0;if(expected===buffer.byteLength){const n=dv.getUint32(80,true);for(let i=0;i<n;i++){let o=84+i*50+12;const tri=[];for(let v=0;v<3;v++,o+=12)tri.push([dv.getFloat32(o,true),dv.getFloat32(o+4,true),dv.getFloat32(o+8,true)]);triangles.push(tri);}}else{const text=new TextDecoder().decode(buffer);const verts=[...text.matchAll(/vertex\s+([-+\deE.]+)\s+([-+\deE.]+)\s+([-+\deE.]+)/g)].map(m=>[+m[1],+m[2],+m[3]]);for(let i=0;i+2<verts.length;i+=3)triangles.push([verts[i],verts[i+1],verts[i+2]]);}drawStl(canvas,triangles);}
  function drawStl(canvas,tris){const ctx=canvas.getContext('2d'),pts=tris.flat();if(!pts.length)return;const mins=[Infinity,Infinity,Infinity],maxs=[-Infinity,-Infinity,-Infinity];pts.forEach(p=>p.forEach((v,i)=>{mins[i]=Math.min(mins[i],v);maxs[i]=Math.max(maxs[i],v);}));const center=mins.map((v,i)=>(v+maxs[i])/2),span=Math.max(...maxs.map((v,i)=>v-mins[i]))||1;let ax=.55,ay=.65,zoom=1,drag=false,lx=0,ly=0;const resize=()=>{canvas.width=canvas.clientWidth*devicePixelRatio;canvas.height=canvas.clientHeight*devicePixelRatio;render();};const project=p=>{let x=(p[0]-center[0])/span,y=(p[1]-center[1])/span,z=(p[2]-center[2])/span;let x1=x*Math.cos(ay)+z*Math.sin(ay),z1=-x*Math.sin(ay)+z*Math.cos(ay),y1=y*Math.cos(ax)-z1*Math.sin(ax);return [canvas.width/2+x1*canvas.height*.75*zoom,canvas.height/2-y1*canvas.height*.75*zoom];};const render=()=>{ctx.fillStyle='#121826';ctx.fillRect(0,0,canvas.width,canvas.height);ctx.strokeStyle='#73a1ff';ctx.lineWidth=devicePixelRatio;for(const t of tris.slice(0,150000)){const p=t.map(project);ctx.beginPath();ctx.moveTo(...p[0]);ctx.lineTo(...p[1]);ctx.lineTo(...p[2]);ctx.closePath();ctx.stroke();}};canvas.onpointerdown=e=>{drag=true;lx=e.clientX;ly=e.clientY;canvas.setPointerCapture(e.pointerId);};canvas.onpointermove=e=>{if(!drag)return;ay+=(e.clientX-lx)*.01;ax+=(e.clientY-ly)*.01;lx=e.clientX;ly=e.clientY;render();};canvas.onpointerup=()=>drag=false;canvas.onwheel=e=>{e.preventDefault();zoom=Math.max(.25,Math.min(5,zoom*(e.deltaY>0?.9:1.1)));render();};new ResizeObserver(resize).observe(canvas);state.viewerCleanup=()=>{};}

  function renderSpaces(){if(!els.spacesList)return;els.spacesList.innerHTML=state.spaces.map(s=>`<div class="space-row"><div><strong>${escapeHtml(s.name)}</strong> ${s.protected?'🔒':''}${s.current?' · ✓':''}</div><div class="space-row-actions"><button class="btn" type="button" data-space-switch="${s.id}">${tr('switchTo')}</button><button class="btn" type="button" data-space-edit="${s.id}">${tr('rename')}</button><button class="btn btn-danger" type="button" data-space-delete="${s.id}">${tr('remove')}</button></div></div>`).join('');$$('[data-space-switch]',els.spacesList).forEach(b=>b.onclick=()=>switchSpace(Number(b.dataset.spaceSwitch)));$$('[data-space-edit]',els.spacesList).forEach(b=>b.onclick=()=>editSpace(Number(b.dataset.spaceEdit)));$$('[data-space-delete]',els.spacesList).forEach(b=>b.onclick=()=>deleteSpace(Number(b.dataset.spaceDelete)));}
  function renderMoveOptions(sourceSpaceId=null){if(!els.moveSpaceSelect)return 0;const sourceId=Number(sourceSpaceId||state.currentCard?.space_id||state.app?.current_space?.id||0);const destinations=state.spaces.filter(s=>Number(s.id)!==sourceId);els.moveSpaceSelect.innerHTML=destinations.map(s=>`<option value="${s.id}">${escapeHtml(s.name)}${s.protected?' 🔒':''}</option>`).join('');return destinations.length;}
  function openUnlock(spaceId){els.unlockForm.elements.space_id.value=spaceId;els.unlockForm.elements.password.value='';openModal('unlock');}
  async function switchSpace(id){try{const fd=new FormData();fd.set('space_id',id);await withUnlock(()=>api('space_switch',{method:'POST',formData:fd}));closeModal('spaces');await loadState();resetGallery();}catch(e){showStatus(e.message,'error');}}
  function syncSpacePasswordControls() {
    const isEdit = !!els.spaceForm.elements.space_id.value;
    const mode = isEdit ? els.spaceForm.elements.password_mode.value : 'set';
    const needsPassword = !isEdit || mode === 'set';
    els.spacePasswordLabel.hidden = isEdit && !needsPassword;
    els.spacePasswordHint.hidden = !isEdit || !needsPassword;
    els.spaceForm.elements.password.disabled = !needsPassword;
    els.spaceForm.elements.password.required = isEdit && mode === 'set';
    els.spacePasswordText.textContent = isEdit ? tr('newSpacePassword') : tr('spacePasswordOptional');
    if (!needsPassword) els.spaceForm.elements.password.value = '';
  }

  function openNewSpaceForm() {
    els.spaceForm.reset();
    els.spaceForm.elements.space_id.value = '';
    els.spaceForm.elements.password_mode.value = 'keep';
    els.passwordModeLabel.hidden = true;
    els.spaceFormTitle.textContent = tr('addNook');
    els.spaceFormTitle.dataset.i18n = 'addNook';
    syncSpacePasswordControls();
    openModal('space-form');
    window.setTimeout(() => els.spaceForm.elements.name.focus(), 0);
  }

  function editSpace(id){
    const space=state.spaces.find(x=>Number(x.id)===Number(id));
    if(!space){showStatus('Space was not found.','error');return;}
    els.spaceForm.reset();
    els.spaceForm.elements.space_id.value=space.id;
    els.spaceForm.elements.name.value=space.name;
    els.spaceForm.elements.password_mode.value='keep';
    els.passwordModeLabel.hidden=false;
    els.spaceFormTitle.textContent=tr('editNook');
    els.spaceFormTitle.dataset.i18n='editNook';
    syncSpacePasswordControls();
    openModal('space-form');
    window.setTimeout(() => els.spaceForm.elements.name.focus(),0);
  }
  async function deleteSpace(id){if(!confirm(tr('confirmSpaceDelete')))return;const fd=new FormData();fd.set('space_id',id);try{await withUnlock(()=>api('space_delete',{method:'POST',formData:fd}));await loadState();renderSpaces();resetGallery();}catch(e){showStatus(e.message,'error');}}

  async function loadExports(){const data=await api('export_list');els.exportsList.innerHTML=(data.exports||[]).map(x=>`<div class="package-row"><span>${escapeHtml(x.name)} · ${formatBytes(x.size)}</span><a class="btn" href="download.php?name=${encodeURIComponent(x.name)}">${tr('download')}</a></div>`).join('');}
  async function loadImports(){const data=await api('import_list');els.importsList.innerHTML=(data.packages||[]).map(x=>`<div class="package-row"><span>${escapeHtml(x.label||x.name)}</span><button class="btn btn-primary" data-import="${escapeHtml(x.name)}">${tr('import')}</button></div>`).join('');$$('[data-import]',els.importsList).forEach(b=>b.onclick=()=>runImport(b.dataset.import));}
  async function runImport(name){if(!confirm(tr('importConfirm')))return;const fd=new FormData();fd.set('package',name);try{await api('import_run',{method:'POST',formData:fd});alert(tr('integrityOk'));location.reload();}catch(e){showStatus(e.message,'error');}}

  function bindEvents(){
    els.languageSelect.onchange=()=>{state.lang=els.languageSelect.value;localStorage.setItem('nook_lang',state.lang);applyLanguage();resetGallery();};
    els.mainMenuBtn.onclick=e=>{e.stopPropagation();els.mainMenu.hidden=!els.mainMenu.hidden;};document.addEventListener('click',e=>{if(!e.target.closest('#mainMenu')&&!e.target.closest('#mainMenuBtn'))els.mainMenu.hidden=true;});
    $$('[data-menu-action]').forEach(b=>b.onclick=async()=>{els.mainMenu.hidden=true;const a=b.dataset.menuAction;if(a==='settings')openModal('settings');if(a==='spaces'){renderSpaces();openModal('spaces');}if(a==='add-space')openNewSpaceForm();if(a==='export'){openModal('export');await loadExports();}if(a==='import'){openModal('import');await loadImports();}});
    $$('[data-close]').forEach(b=>b.onclick=()=>closeModal(b.dataset.close));document.addEventListener('keydown',e=>{if(e.key==='Escape'){const open=$$('.modal.open').pop();if(open)closeModal(open.id.replace('Modal',''));}});
    els.chooseFilesBtn.onclick=()=>els.fileInput.click();els.fileInput.onchange=()=>handleFiles(els.fileInput.files);['dragover','dragenter'].forEach(n=>els.dropZone.addEventListener(n,e=>{e.preventDefault();els.dropZone.classList.add('drag-over');}));['dragleave','drop'].forEach(n=>els.dropZone.addEventListener(n,e=>{e.preventDefault();els.dropZone.classList.remove('drag-over');}));els.dropZone.ondrop=e=>handleFiles(e.dataTransfer.files);els.dropZone.onkeydown=e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();els.fileInput.click();}};
    document.addEventListener('paste',e=>{handleClipboardPaste(e).catch(err=>showStatus(err.message,'error'));});
    els.batchUploadInput.checked=localStorage.getItem('nook_batch')==='1';els.batchHiddenInput.checked=localStorage.getItem('nook_batch_hidden')==='1';const syncBatch=()=>{els.batchHashtagInput.disabled=!els.batchUploadInput.checked;els.batchHiddenInput.disabled=!els.batchUploadInput.checked;};syncBatch();els.batchUploadInput.onchange=()=>{localStorage.setItem('nook_batch',els.batchUploadInput.checked?'1':'0');syncBatch();};els.batchHiddenInput.onchange=()=>localStorage.setItem('nook_batch_hidden',els.batchHiddenInput.checked?'1':'0');
    els.uploadForm.onsubmit=e=>{e.preventDefault();saveUpload(false);};els.editCardBtn.onclick=()=>setCardEdit(true);els.cancelCardEditBtn.onclick=()=>renderMediaCard(state.currentCard);els.cardForm.onsubmit=e=>{e.preventDefault();saveMediaCard(false);};els.cardForm.addEventListener('input',scheduleMediaAutosave);els.cardForm.addEventListener('change',scheduleMediaAutosave);els.addMediaBtn.onclick=()=>els.addMediaInput.click();els.addMediaInput.onchange=()=>addMedia(els.addMediaInput.files);els.deleteCardBtn.onclick=softDeleteCurrent;els.moveCardBtn.onclick=openMoveForCurrentCard;els.cardPinBtn.onclick=togglePinCurrent;
    els.newNoteBtn.onclick=newNote;els.noteForm.onsubmit=async e=>{e.preventDefault();await saveNote(false);};els.deleteNoteBtn.onclick=softDeleteCurrent;els.moveNoteBtn.onclick=openMoveForCurrentCard;els.notePinBtn.onclick=togglePinCurrent;els.addAttachmentBtn.onclick=()=>els.attachmentInput.click();els.attachmentInput.onchange=async()=>{await uploadNoteFiles(els.attachmentInput.files,'attachment');els.attachmentInput.value='';};
    const debounce=(fn,ms=300)=>{let t;return(...args)=>{clearTimeout(t);t=setTimeout(()=>fn(...args),ms);};};els.searchInput.oninput=debounce(()=>{state.filters.q=els.searchInput.value.trim();resetGallery();});els.dateFromInput.onchange=()=>{state.filters.date_from=els.dateFromInput.value;resetGallery();};els.dateToInput.onchange=()=>{state.filters.date_to=els.dateToInput.value;resetGallery();};els.typeFilter.onchange=()=>{state.filters.type=els.typeFilter.value;resetGallery();};els.resetFiltersBtn.onclick=()=>{state.filters={q:'',date_from:'',date_to:'',tag:'',type:'all'};els.searchInput.value='';els.dateFromInput.value='';els.dateToInput.value='';els.typeFilter.value='all';resetGallery();};els.clearTagBtn.onclick=()=>{state.filters.tag='';resetGallery();};
    els.trashBtn.onclick=()=>{state.trash=true;els.backFromTrashBtn.hidden=false;els.emptyTrashBtn.hidden=false;els.contentTitle.textContent=tr('trash');resetGallery();};els.backFromTrashBtn.onclick=()=>{state.trash=false;els.backFromTrashBtn.hidden=true;els.emptyTrashBtn.hidden=true;els.contentTitle.textContent=tr('entries');resetGallery();};els.emptyTrashBtn.onclick=async()=>{if(!confirm(tr('confirmPermanent')))return;try{await api('empty_trash',{method:'POST'});await loadState();resetGallery();}catch(e){showStatus(e.message,'error');}};
    els.settingsForm.onsubmit=async e=>{e.preventDefault();try{await api('settings_save',{method:'POST',formData:new FormData(els.settingsForm)});closeModal('settings');await loadState();resetGallery();showStatus(tr('saved'),'success');}catch(err){showStatus(err.message,'error');}};
    els.spaceForm.elements.password_mode.onchange=syncSpacePasswordControls;
    els.spaceForm.onsubmit=async e=>{
      e.preventDefault();
      const submitButton=els.spaceForm.querySelector('[type="submit"]');
      const fd=new FormData(els.spaceForm),id=String(fd.get('space_id')||'').trim();
      if(submitButton)submitButton.disabled=true;
      try{
        if(id) await withUnlock(()=>api('space_update',{method:'POST',formData:fd}));
        else await api('space_create',{method:'POST',formData:fd});
        closeModal('space-form');
        await loadState();
        renderSpaces();
        openModal('spaces');
        showStatus(tr('saved'),'success');
      }catch(err){showStatus(err.message,'error');}
      finally{if(submitButton)submitButton.disabled=false;}
    };
    els.unlockForm.onsubmit=async e=>{e.preventDefault();const fd=new FormData(els.unlockForm);try{await api('space_unlock',{method:'POST',formData:fd});closeModal('unlock');await loadState();if(state.pendingUnlock){const p=state.pendingUnlock;state.pendingUnlock=null;try{p.resolve(await p.task());}catch(err){p.reject(err);}}else resetGallery();}catch(err){showStatus(err.message,'error');}};
    els.moveForm.onsubmit=async e=>{e.preventDefault();if(!state.currentCard)return;const submit=els.moveForm.querySelector('[type="submit"]');const destination=state.spaces.find(s=>String(s.id)===String(els.moveForm.elements.space_id.value));if(submit)submit.disabled=true;try{if(state.currentCard.entry_type==='note'&&!state.currentCard.deleted_at){const noteFd=new FormData(els.noteForm);const content=await collectNoteContent();noteFd.set('body_json',content.json);noteFd.set('body_html',content.html);noteFd.set('is_hidden',els.noteForm.elements.is_hidden.checked?'1':'0');const saved=await api('note_save',{method:'POST',formData:noteFd});state.currentCard=saved.card;}const sourceType=state.currentCard.entry_type;await withUnlock(()=>api('move',{method:'POST',formData:new FormData(els.moveForm)}));closeModal('move');closeModal(sourceType==='note'?'note':'card');showStatus(tr('movedToNook',{name:destination?.name||''}),'success');await loadState();resetGallery();}catch(err){showStatus(err.message,'error');}finally{if(submit)submit.disabled=false;}};
    els.createExportBtn.onclick=async()=>{els.createExportBtn.disabled=true;try{await api('export_create',{method:'POST'});showStatus(tr('packageReady'),'success');await loadExports();}catch(e){showStatus(e.message,'error');}finally{els.createExportBtn.disabled=false;}};
    state.observer=new IntersectionObserver(entries=>{if(entries[0].isIntersecting&&state.hasMore)loadCards(true);},{rootMargin:'800px'});state.observer.observe(els.loadSentinel);
  }

  document.addEventListener('DOMContentLoaded',async()=>{bindEls();applyLanguage();const loginLang=$('#loginLanguage');if(loginLang)loginLang.onchange=()=>{state.lang=loginLang.value;localStorage.setItem('nook_lang',state.lang);applyLanguage();};if(!els.gallery)return;bindEvents();try{await loadState();resetGallery();}catch(e){showStatus(e.message,'error');}});




  /* NOOK_UX_CORE_BRIDGE_V3 */
  window.NookUXCore = Object.assign(window.NookUXCore || {}, {
    getState: () => (typeof state !== 'undefined' ? state : {}),
    loadCards: (typeof loadCards === 'function' ? loadCards : null),
    loadState: (typeof loadState === 'function' ? loadState : null),
    openCard: (typeof openCard === 'function' ? openCard : null),
    openMediaViewer: (typeof openMediaViewer === 'function' ? openMediaViewer : null),
    getCurrentCard: () => (typeof state !== 'undefined' && state ? (state.currentCard || state.currentNote || null) : null),
    getCurrentCardId: () => {
      if (typeof state === 'undefined' || !state) return 0;
      return Number(state.currentCard?.id || state.currentNote?.id || state.currentCardId || state.current_card_id || 0);
    },
    setCurrentCardMediaOrder: (ids) => {
      if (typeof state === 'undefined' || !state || !Array.isArray(ids)) return false;
      const card = state.currentCard || state.currentNote;
      if (!card) return false;
      for (const key of ['media','images','files','attachments']) {
        if (!Array.isArray(card[key])) continue;
        const byId = new Map(card[key].map(item => [Number(item?.id), item]));
        const ordered = ids.map(id => byId.get(Number(id))).filter(Boolean);
        for (const item of card[key]) if (!ordered.includes(item)) ordered.push(item);
        card[key] = ordered;
        return true;
      }
      return false;
    },
    openModal: (typeof openModal === 'function' ? openModal : null),
    closeModal: (typeof closeModal === 'function' ? closeModal : null),
    editSpace: (typeof editSpace === 'function' ? editSpace : null),
    switchSpace: async (id) => {
      const num = Number(id);
      const fn = typeof switchSpace === 'function' ? switchSpace
        : (typeof selectSpace === 'function' ? selectSpace
        : (typeof changeSpace === 'function' ? changeSpace
        : (typeof setCurrentSpace === 'function' ? setCurrentSpace
        : (typeof activateSpace === 'function' ? activateSpace : null))));
      if (!fn) return false;
      const result = fn(num);
      return result?.then ? await result : result;
    },
    forceSpace: async (id) => {
      const num = Number(id);
      const apply = () => {
        if (typeof state === 'undefined' || !state) return;
        state.spaceId = num;
        state.currentSpaceId = num;
        state.activeSpaceId = num;
        state.space_id = num;
        try {
          const list = Array.isArray(state.spaces) ? state.spaces : [];
          const found = list.find(s => Number(s?.id) === num);
          if (found) {
            if (state.currentSpace && typeof state.currentSpace === 'object') state.currentSpace = found;
            if (state.space && typeof state.space === 'object') state.space = found;
          }
        } catch (_) {}
        try {
          for (const key of ['nook_ux_last_space_id','nook_space_id','nookSpaceId','space_id','current_space_id','nook_current_space_id','currentSpaceId','activeSpaceId','nook_active_space_id']) {
            localStorage.setItem(key, String(num));
          }
        } catch (_) {}
      };
      apply();
      const fn = typeof switchSpace === 'function' ? switchSpace
        : (typeof selectSpace === 'function' ? selectSpace
        : (typeof changeSpace === 'function' ? changeSpace
        : (typeof setCurrentSpace === 'function' ? setCurrentSpace
        : (typeof activateSpace === 'function' ? activateSpace : null))));
      if (fn) {
        try {
          const result = fn(num);
          if (result?.then) await result;
        } catch (_) {}
      }
      // Some core switchers return before their asynchronous UI work is complete.
      // Re-apply the target and explicitly reload the gallery so the visible cards
      // can never remain from the previously protected nook.
      apply();
      if (typeof loadCards === 'function') await loadCards(true);
      else if (typeof loadState === 'function') await loadState();
      apply();
      return true;
    }
  });
})();
