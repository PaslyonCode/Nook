// assets/app.js
(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  const I18N = {
    ru: {
      serverNonJson: 'Сервер вернул не JSON. Начало ответа: {text}',
      sessionExpired: 'Сессия истекла. Нужно войти заново.',
      apiError: 'Ошибка API',
      video: 'Видео',
      photo: 'Фото',
      selectMedia: 'Выберите хотя бы один фото- или видеофайл.',
      noTags: 'Пока нет хэштэгов',
      openMediaGroup: 'Открыть группу медиафайлов',
      restore: 'Восстановить',
      openNote: 'Открыть заметку',
      untitled: 'Без заголовка',
      emptyNote: 'Пустая заметка',
      hidden: 'невидимая',
      note: 'заметка',
      trash: 'Корзина',
      trashEmpty: 'Корзина пуста',
      nothingFound: 'Ничего не найдено',
      deletedWillAppear: 'Удаленные записи будут появляться здесь.',
      changeFilters: 'Измените фильтры или добавьте фото, видео или заметку.',
      foundZero: 'Найдено: 0',
      inTrash: 'В корзине',
      found: 'Найдено',
      loading: 'Загрузка...',
      loadError: 'Ошибка загрузки',
      groupNoFiles: 'В группе нет файлов.',
      onlyFileCannotDelete: 'Единственный файл удалить нельзя',
      delete: 'Удалить',
      inTrashPrefix: 'В корзине · ',
      created: 'Создано: ',
      updated: ' · Обновлено: ',
      adding: 'Добавление...',
      filesAdded: 'Файлы добавлены.',
      addMedia: 'Добавить фото/видео',
      cannotDeleteOnlyFile: 'Нельзя удалить единственный файл. Удалите всю группу.',
      confirmDeleteFile: 'Удалить этот файл из группы?',
      fileDeleted: 'Файл удален.',
      unsavedAutosave: 'Есть несохраненные изменения. Автосохранение — каждые 5 минут.',
      newNoteNotCreated: 'Новая заметка еще не создана. Нажмите «Сохранить».',
      editorMissing: 'Настоящий Editor.js не найден: assets/vendor/editorjs/editorjs.umd.js',
      quotePlaceholder: 'Цитата',
      quoteCaptionPlaceholder: 'Автор / источник',
      imageCaptionPlaceholder: 'Подпись к изображению',
      editorPlaceholder: 'Введите текст заметки. Нажмите “+” или выделите текст для форматирования.',
      newNote: 'Новая заметка',
      autosaveInfo: 'Автосохранение каждые 5 минут. Можно сохранить вручную.',
      saving: 'Сохранение...',
      autosaved: 'Автосохранено: ',
      saved: 'Сохранено: ',
      noteSaved: 'Заметка сохранена.',
      saveError: 'Ошибка сохранения: ',
      save: 'Сохранить',
      entry: 'запись',
      group: 'группу',
      noteAcc: 'заметку',
      confirmMoveToTrash: 'Отправить {label} в корзину?',
      movedToTrash: 'Запись отправлена в корзину.',
      restored: 'Запись восстановлена.',
      confirmEmptyTrash: 'Окончательно удалить все записи из корзины и связанные файлы?',
      trashEmptied: 'Корзина очищена.',
      noMediaSelected: 'Не выбраны фото или видео.',
      groupSaved: 'Группа сохранена.',
      changesSaved: 'Данные обновлены.',
      saveChanges: 'Сохранить изменения',
      editor: {
        ui: { blockTunes: { toggler: { 'Click to tune': 'Настроить', 'or drag to move': 'или перетащите' } }, inlineToolbar: { converter: { 'Convert to': 'Преобразовать в' } }, toolbar: { toolbox: { Add: 'Добавить' } } },
        toolNames: { Text: 'Текст', Heading: 'Заголовок', List: 'Список', Checklist: 'Чек-лист', Quote: 'Цитата', Delimiter: 'Разделитель', Table: 'Таблица', Image: 'Изображение' },
        tools: { warning: { Title: 'Название', Message: 'Сообщение' }, link: { 'Add a link': 'Добавить ссылку' }, stub: { 'The block can not be displayed correctly.': 'Блок не может быть показан корректно.' } },
        blockTunes: { delete: { Delete: 'Удалить', 'Click to delete': 'Нажмите для удаления' }, moveUp: { 'Move up': 'Выше' }, moveDown: { 'Move down': 'Ниже' } }
      }
    },
    en: {
      serverNonJson: 'The server did not return JSON. Response starts with: {text}',
      sessionExpired: 'Session expired. Please sign in again.',
      apiError: 'API error',
      video: 'Video',
      photo: 'Photo',
      selectMedia: 'Select at least one photo or video file.',
      noTags: 'No hashtags yet',
      openMediaGroup: 'Open media group',
      restore: 'Restore',
      openNote: 'Open note',
      untitled: 'Untitled',
      emptyNote: 'Empty note',
      hidden: 'hidden',
      note: 'note',
      trash: 'Trash',
      trashEmpty: 'Trash is empty',
      nothingFound: 'Nothing found',
      deletedWillAppear: 'Deleted entries will appear here.',
      changeFilters: 'Change filters or add a photo, video, or note.',
      foundZero: 'Found: 0',
      inTrash: 'In trash',
      found: 'Found',
      loading: 'Loading...',
      loadError: 'Loading error',
      groupNoFiles: 'This group has no files.',
      onlyFileCannotDelete: 'The only file cannot be deleted',
      delete: 'Delete',
      inTrashPrefix: 'In trash · ',
      created: 'Created: ',
      updated: ' · Updated: ',
      adding: 'Adding...',
      filesAdded: 'Files added.',
      addMedia: 'Add photo/video',
      cannotDeleteOnlyFile: 'You cannot delete the only file. Delete the whole group instead.',
      confirmDeleteFile: 'Delete this file from the group?',
      fileDeleted: 'File deleted.',
      unsavedAutosave: 'There are unsaved changes. Autosave runs every 5 minutes.',
      newNoteNotCreated: 'The new note has not been created yet. Click “Save”.',
      editorMissing: 'Editor.js was not found: assets/vendor/editorjs/editorjs.umd.js',
      quotePlaceholder: 'Quote',
      quoteCaptionPlaceholder: 'Author / source',
      imageCaptionPlaceholder: 'Image caption',
      editorPlaceholder: 'Enter note text. Press “+” or select text to format it.',
      newNote: 'New note',
      autosaveInfo: 'Autosave every 5 minutes. You can also save manually.',
      saving: 'Saving...',
      autosaved: 'Autosaved: ',
      saved: 'Saved: ',
      noteSaved: 'Note saved.',
      saveError: 'Save error: ',
      save: 'Save',
      entry: 'entry',
      group: 'group',
      noteAcc: 'note',
      confirmMoveToTrash: 'Move this {label} to trash?',
      movedToTrash: 'Entry moved to trash.',
      restored: 'Entry restored.',
      confirmEmptyTrash: 'Permanently delete all entries from trash and remove related files?',
      trashEmptied: 'Trash emptied.',
      noMediaSelected: 'No photo or video selected.',
      groupSaved: 'Group saved.',
      changesSaved: 'Data updated.',
      saveChanges: 'Save changes',
      editor: {
        ui: { blockTunes: { toggler: { 'Click to tune': 'Click to tune', 'or drag to move': 'or drag to move' } }, inlineToolbar: { converter: { 'Convert to': 'Convert to' } }, toolbar: { toolbox: { Add: 'Add' } } },
        toolNames: { Text: 'Text', Heading: 'Heading', List: 'List', Checklist: 'Checklist', Quote: 'Quote', Delimiter: 'Delimiter', Table: 'Table', Image: 'Image' },
        tools: { warning: { Title: 'Title', Message: 'Message' }, link: { 'Add a link': 'Add a link' }, stub: { 'The block can not be displayed correctly.': 'The block can not be displayed correctly.' } },
        blockTunes: { delete: { Delete: 'Delete', 'Click to delete': 'Click to delete' }, moveUp: { 'Move up': 'Move up' }, moveDown: { 'Move down': 'Move down' } }
      }
    }
  };

  const currentLang = window.NOOK_LANG === 'en' ? 'en' : 'ru';
  function tr(key, vars = {}) {
    const dict = I18N[currentLang] || I18N.ru;
    let value = dict[key] ?? I18N.ru[key] ?? key;
    Object.entries(vars).forEach(([name, replacement]) => {
      value = String(value).replaceAll('{' + name + '}', String(replacement));
    });
    return value;
  }


  const state = {
    q: '',
    dateFrom: '',
    dateTo: '',
    tag: '',
    trashMode: false,
    selectedFiles: [],
    previewUrls: [],
    currentCard: null,
    editSnapshot: null,
    editMode: false,
    debounceTimer: null,
    availableTags: [],
    activeSuggestBox: null,
    currentNote: null,
    noteDirty: false,
    noteSaving: false,
    noteAutosaveTimer: null,
    noteEditorInstance: null,
  };

  const els = {
    gallery: $('#gallery'),
    contentTitle: $('#contentTitle'),
    resultInfo: $('#resultInfo'),
    statusBox: $('#statusBox'),
    tagsList: $('#tagsList'),
    searchInput: $('#searchInput'),
    dateFromInput: $('#dateFromInput'),
    dateToInput: $('#dateToInput'),
    resetFiltersBtn: $('#resetFiltersBtn'),
    clearTagBtn: $('#clearTagBtn'),
    trashModeBtn: $('#trashModeBtn'),
    backFromTrashBtn: $('#backFromTrashBtn'),
    emptyTrashBtn: $('#emptyTrashBtn'),
    newNoteBtn: $('#newNoteBtn'),
    dropZone: $('#dropZone'),
    chooseFilesBtn: $('#chooseFilesBtn'),
    fileInput: $('#fileInput'),

    uploadModal: $('#uploadModal'),
    uploadPreview: $('#uploadPreview'),
    uploadForm: $('#uploadForm'),
    saveUploadBtn: $('#saveUploadBtn'),
    uploadHiddenInput: $('#uploadHiddenInput'),

    cardModal: $('#cardModal'),
    cardModalTitle: $('#cardModalTitle'),
    cardModalDate: $('#cardModalDate'),
    cardImages: $('#cardImages'),
    editForm: $('#editForm'),
    imageEditTools: $('#imageEditTools'),
    addImagesBtn: $('#addImagesBtn'),
    addImagesInput: $('#addImagesInput'),
    imageManageList: $('#imageManageList'),
    cardFileList: $('#cardFileList'),
    editCardBtn: $('#editCardBtn'),
    saveEditBtn: $('#saveEditBtn'),
    cancelEditBtn: $('#cancelEditBtn'),
    deleteCardBtn: $('#deleteCardBtn'),
    restoreCardBtn: $('#restoreCardBtn'),
    editHiddenInput: $('#editHiddenInput'),

    noteModal: $('#noteModal'),
    noteModalTitle: $('#noteModalTitle'),
    noteModalMeta: $('#noteModalMeta'),
    noteForm: $('#noteForm'),
    noteTitleInput: $('#noteTitleInput'),
    noteHashtagsInput: $('#noteHashtagsInput'),
    noteHiddenInput: $('#noteHiddenInput'),
    noteEditor: $('#noteEditor'),
    noteAutosaveInfo: $('#noteAutosaveInfo'),
    deleteNoteBtn: $('#deleteNoteBtn'),
    restoreNoteBtn: $('#restoreNoteBtn'),
    saveNoteBtn: $('#saveNoteBtn'),

    mediaModal: $('#mediaModal'),
    mediaViewer: $('#mediaViewer'),
    mediaModalTitle: $('#mediaModalTitle'),
    mediaModalCaption: $('#mediaModalCaption'),
  };

  function apiUrl(action, params = {}) {
    const url = new URL('api.php', window.location.href);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null && String(value) !== '') {
        url.searchParams.set(key, value);
      }
    });
    return url;
  }

  async function readJsonResponse(response) {
    const text = await response.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (error) {
      throw new Error(tr('serverNonJson', { text: text.slice(0, 240) }));
    }

    if (response.status === 401 && data.auth === false) {
      window.location.href = 'index.php';
      throw new Error(tr('sessionExpired'));
    }

    if (!response.ok || data.ok === false) {
      throw new Error(data.error || tr('apiError'));
    }

    return data;
  }

  async function getJson(action, params = {}) {
    const response = await fetch(apiUrl(action, params), {
      headers: { 'Accept': 'application/json' },
    });
    return readJsonResponse(response);
  }

  async function postForm(action, formData) {
    formData.set('action', action);
    const response = await fetch('api.php', {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData,
    });
    return readJsonResponse(response);
  }

  function showStatus(message, type = 'info') {
    els.statusBox.textContent = message;
    els.statusBox.className = 'status-box ' + type;
    els.statusBox.hidden = false;

    if (type !== 'error') {
      window.clearTimeout(showStatus.timer);
      showStatus.timer = window.setTimeout(() => {
        els.statusBox.hidden = true;
      }, 3500);
    }
  }

  function clearStatus() {
    els.statusBox.hidden = true;
    els.statusBox.textContent = '';
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function stripHtml(value) {
    const box = document.createElement('div');
    box.innerHTML = String(value ?? '');
    return (box.textContent || box.innerText || '').replace(/\s+/g, ' ').trim();
  }


  function safeJsonParse(value, fallback = null) {
    if (!value) return fallback;
    try { return JSON.parse(value); } catch (error) { return fallback; }
  }

  function blockContentText(html) {
    return String(html ?? '').replace(/<br\s*\/?>/gi, '\n').trim();
  }

  function htmlToEditorData(html) {
    const source = String(html ?? '').trim();
    if (!source) return { time: Date.now(), blocks: [], version: '2.x' };
    const box = document.createElement('div');
    box.innerHTML = source;
    const blocks = [];

    const addParagraph = (inner) => {
      const text = String(inner ?? '').trim();
      if (text) blocks.push({ type: 'paragraph', data: { text } });
    };

    const children = Array.from(box.children.length ? box.children : box.childNodes);
    if (!children.length) {
      addParagraph(escapeHtml(source));
    }

    children.forEach((node) => {
      if (node.nodeType === Node.TEXT_NODE) {
        const text = node.textContent.trim();
        if (text) addParagraph(escapeHtml(text));
        return;
      }
      if (node.nodeType !== Node.ELEMENT_NODE) return;
      const tag = node.tagName.toLowerCase();
      if (/^h[1-6]$/.test(tag)) {
        blocks.push({ type: 'header', data: { text: node.innerHTML.trim(), level: Math.min(6, Math.max(1, Number(tag.slice(1)))) } });
      } else if (tag === 'img') {
        blocks.push({ type: 'image', data: { file: { url: node.getAttribute('src') || '' }, caption: node.getAttribute('alt') || '', width: node.style.width || node.getAttribute('width') || '100%', withBorder: false, withBackground: false, stretched: false } });
      } else if (tag === 'ul' || tag === 'ol') {
        blocks.push({ type: 'list', data: { style: tag === 'ol' ? 'ordered' : 'unordered', items: Array.from(node.querySelectorAll(':scope > li')).map((li) => ({ content: li.innerHTML.trim(), items: [] })) } });
      } else if (tag === 'blockquote') {
        blocks.push({ type: 'quote', data: { text: node.innerHTML.trim(), caption: '', alignment: 'left' } });
      } else if (tag === 'table') {
        const rows = Array.from(node.querySelectorAll('tr')).map((tr) => Array.from(tr.children).map((td) => td.innerHTML.trim()));
        blocks.push({ type: 'table', data: { withHeadings: false, content: rows } });
      } else if (tag === 'hr') {
        blocks.push({ type: 'delimiter', data: {} });
      } else {
        addParagraph(node.innerHTML || escapeHtml(node.textContent || ''));
      }
    });

    return { time: Date.now(), blocks, version: '2.x' };
  }

  function normalizeEditorData(data) {
    if (!data || !Array.isArray(data.blocks)) return { time: Date.now(), blocks: [], version: '2.x' };
    return data;
  }

  function getNoteInitialData(card) {
    if (!card) return { time: Date.now(), blocks: [], version: '2.x' };
    const jsonData = safeJsonParse(card.body_json || '', null);
    if (jsonData && Array.isArray(jsonData.blocks)) return jsonData;
    return htmlToEditorData(card.body_html || '');
  }

  function listItemsToHtml(items, nested = false) {
    const arr = Array.isArray(items) ? items : [];
    return arr.map((item) => {
      if (typeof item === 'string') return `<li>${item}</li>`;
      const content = item && item.content ? item.content : (item && item.text ? item.text : '');
      const children = item && Array.isArray(item.items) && item.items.length ? `<ul>${listItemsToHtml(item.items, true)}</ul>` : '';
      return `<li>${content}${children}</li>`;
    }).join('');
  }

  function editorDataToHtml(data) {
    const saved = normalizeEditorData(data);
    return saved.blocks.map((block) => {
      const d = block.data || {};
      switch (block.type) {
        case 'header': {
          const level = Math.min(6, Math.max(1, Number(d.level || 2)));
          return `<h${level}>${d.text || ''}</h${level}>`;
        }
        case 'list': {
          const tag = d.style === 'ordered' ? 'ol' : 'ul';
          return `<${tag}>${listItemsToHtml(d.items || [])}</${tag}>`;
        }
        case 'checklist': {
          const items = Array.isArray(d.items) ? d.items : [];
          return `<ul class="checklist-view">${items.map((item) => `<li>${item.checked ? '☑' : '☐'} ${item.text || ''}</li>`).join('')}</ul>`;
        }
        case 'quote':
          return `<blockquote>${d.text || ''}${d.caption ? `<footer>${d.caption}</footer>` : ''}</blockquote>`;
        case 'delimiter':
          return '<hr>';
        case 'table': {
          const rows = Array.isArray(d.content) ? d.content : [];
          return `<table>${rows.map((row) => `<tr>${(row || []).map((cell) => `<td>${cell || ''}</td>`).join('')}</tr>`).join('')}</table>`;
        }
        case 'image': {
          const url = d.file && d.file.url ? d.file.url : (d.url || '');
          if (!url) return '';
          const caption = d.caption || '';
          const width = d.width ? ` style="width:${escapeHtml(d.width)};max-width:100%;height:auto"` : '';
          return `<figure><img src="${escapeHtml(url)}" alt="${escapeHtml(stripHtml(caption))}"${width}>${caption ? `<figcaption>${caption}</figcaption>` : ''}</figure>`;
        }
        case 'paragraph':
        default:
          return `<p>${d.text || ''}</p>`;
      }
    }).join('\n');
  }

  function formatDateTime(value) {
    if (!value) return '';
    const safe = String(value).replace(' ', 'T');
    const date = new Date(safe);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString(currentLang === 'ru' ? 'ru-RU' : 'en-US', {
      year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit',
    });
  }

  function hashtagsToText(tags) {
    return (tags || []).map((tag) => '#' + tag).join(' ');
  }

  function hideHashtagSuggestions() {
    if (state.activeSuggestBox) state.activeSuggestBox.hidden = true;
    state.activeSuggestBox = null;
  }

  function getCurrentTagToken(input) {
    const value = input.value;
    const cursor = input.selectionStart ?? value.length;
    const left = value.slice(0, cursor);
    const match = left.match(/(?:^|[\s,;])#?([\p{L}\p{N}_-]*)$/u);
    if (!match) return null;
    const token = match[1] || '';
    const tokenStart = cursor - token.length - (left.endsWith('#' + token) ? 1 : 0);
    return { token, tokenStart, cursor };
  }

  function insertHashtagSuggestion(input, tag) {
    const tokenInfo = getCurrentTagToken(input);
    if (!tokenInfo) return;
    const value = input.value;
    const before = value.slice(0, Math.max(0, tokenInfo.tokenStart));
    const after = value.slice(tokenInfo.cursor);
    const separatorBefore = before === '' || /[\s,;]$/.test(before) ? '' : ' ';
    const next = before + separatorBefore + '#' + tag + ' ' + after.replace(/^\s+/, '');
    input.value = next;
    input.focus();
    const newCursor = (before + separatorBefore + '#' + tag + ' ').length;
    input.setSelectionRange(newCursor, newCursor);
    hideHashtagSuggestions();
    markNoteDirty();
  }

  function renderHashtagSuggestions(input) {
    if (input.readOnly || !state.availableTags.length) {
      hideHashtagSuggestions();
      return;
    }
    const wrapper = input.closest('.hashtag-input-wrap');
    const box = wrapper ? $('.hashtag-suggestions', wrapper) : null;
    if (!box) return;
    const tokenInfo = getCurrentTagToken(input);
    if (!tokenInfo || tokenInfo.token.length < 1) {
      box.hidden = true;
      return;
    }
    const query = tokenInfo.token.toLowerCase();
    const matches = state.availableTags.filter((tag) => tag.toLowerCase().includes(query)).slice(0, 12);
    if (!matches.length) {
      box.hidden = true;
      return;
    }
    box.innerHTML = matches.map((tag) => (
      `<button type="button" class="hashtag-suggestion" data-tag="${escapeHtml(tag)}">#${escapeHtml(tag)}</button>`
    )).join('');
    $$('.hashtag-suggestion', box).forEach((button) => {
      button.addEventListener('mousedown', (event) => {
        event.preventDefault();
        insertHashtagSuggestion(input, button.dataset.tag || '');
      });
    });
    box.hidden = false;
    state.activeSuggestBox = box;
  }

  function setupHashtagAutocomplete() {
    $$('input[data-hashtag-input]').forEach((input) => {
      if (input.dataset.autocompleteReady === '1') return;
      input.dataset.autocompleteReady = '1';
      const wrapper = document.createElement('div');
      wrapper.className = 'hashtag-input-wrap';
      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);
      const box = document.createElement('div');
      box.className = 'hashtag-suggestions';
      box.hidden = true;
      wrapper.appendChild(box);
      input.addEventListener('input', () => renderHashtagSuggestions(input));
      input.addEventListener('focus', () => renderHashtagSuggestions(input));
      input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') hideHashtagSuggestions();
      });
      input.addEventListener('blur', () => {
        window.setTimeout(() => {
          if (state.activeSuggestBox === box) hideHashtagSuggestions();
        }, 150);
      });
    });
  }

  function modalByName(name) {
    if (name === 'upload') return els.uploadModal;
    if (name === 'card') return els.cardModal;
    if (name === 'note') return els.noteModal;
    if (name === 'media') return els.mediaModal;
    return null;
  }

  function updateBodyModalState() {
    const hasOpenModal = [els.uploadModal, els.cardModal, els.noteModal, els.mediaModal].some((modal) => modal && modal.classList.contains('open'));
    document.body.classList.toggle('modal-open', hasOpenModal);
  }

  function openModal(name) {
    const modal = modalByName(name);
    if (!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    updateBodyModalState();
  }

  function closeModal(name) {
    const modal = modalByName(name);
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    hideHashtagSuggestions();

    if (name === 'upload') {
      revokePreviewUrls();
      state.selectedFiles = [];
      els.fileInput.value = '';
      els.uploadForm.reset();
      els.uploadPreview.innerHTML = '';
    }
    if (name === 'card') {
      state.currentCard = null;
      state.editMode = false;
      if (els.addImagesInput) els.addImagesInput.value = '';
    }
    if (name === 'note') {
      stopNoteAutosave();
      destroyNoteEditor();
      state.currentNote = null;
      state.noteDirty = false;
    }
    if (name === 'media') {
      els.mediaViewer.innerHTML = '';
      els.mediaModalCaption.textContent = '';
    }
    updateBodyModalState();
  }

  function revokePreviewUrls() {
    state.previewUrls.forEach((url) => URL.revokeObjectURL(url));
    state.previewUrls = [];
  }

  function isVideoMedia(media) {
    return (media.media_type || '').toLowerCase() === 'video' || String(media.mime || '').startsWith('video/');
  }

  function isAcceptedUpload(file) {
    return file && (file.type.startsWith('image/') || file.type.startsWith('video/'));
  }

  function renderUploadPreview(files) {
    revokePreviewUrls();
    els.uploadPreview.innerHTML = '';
    files.forEach((file) => {
      const url = URL.createObjectURL(file);
      state.previewUrls.push(url);
      const item = document.createElement('div');
      item.className = 'preview-item';
      const mediaHtml = file.type.startsWith('video/')
        ? `<video src="${url}" muted playsinline preload="metadata"></video><div class="media-type-badge">${tr('video')}</div>`
        : `<img src="${url}" alt="${escapeHtml(file.name)}">`;
      item.innerHTML = `
        <div class="preview-media-wrap">${mediaHtml}</div>
        <div class="preview-caption" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</div>
      `;
      els.uploadPreview.appendChild(item);
    });
  }

  function openUploadForFiles(fileList) {
    const files = Array.from(fileList || []).filter(isAcceptedUpload);
    if (!files.length) {
      showStatus(tr('selectMedia'), 'error');
      return;
    }
    state.selectedFiles = files;
    renderUploadPreview(files);
    openModal('upload');
  }

  function renderTags(tags) {
    state.availableTags = (tags || []).map((tag) => tag.name).filter(Boolean);
    if (!tags || !tags.length) {
      els.tagsList.className = 'tags-list muted';
      els.tagsList.textContent = tr('noTags');
      return;
    }
    els.tagsList.className = 'tags-list';
    els.tagsList.innerHTML = '';
    tags.forEach((tag) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'tag-filter' + (state.tag === tag.name ? ' active' : '');
      button.innerHTML = `<span>#${escapeHtml(tag.name)}</span><small>${tag.cards_count}</small>`;
      button.addEventListener('click', () => {
        state.tag = state.tag === tag.name ? '' : tag.name;
        loadCards();
      });
      els.tagsList.appendChild(button);
    });
  }

  function renderTileMedia(media) {
    if (isVideoMedia(media)) {
      return `<video src="${escapeHtml(media.url)}" muted playsinline preload="metadata"></video><span class="video-play-mark">▶</span>`;
    }
    return `<img src="${escapeHtml(media.thumb_url || media.url)}" alt="" loading="lazy">`;
  }

  function renderMediaTile(card) {
    const tile = document.createElement('article');
    tile.className = 'card-tile image-only-tile' + (state.trashMode ? ' in-trash' : '');
    tile.tabIndex = 0;
    tile.setAttribute('aria-label', tr('openMediaGroup'));
    const mediaFiles = card.images || [];
    const shownMedia = mediaFiles.slice(0, 4);
    const extraCount = Math.max(0, mediaFiles.length - shownMedia.length);
    const mediaHtml = shownMedia.map((media, index) => {
      const overlay = extraCount > 0 && index === shownMedia.length - 1 ? `<span class="more-overlay">+${extraCount}</span>` : '';
      return `
        <div class="tile-img-wrap ${isVideoMedia(media) ? 'is-video' : 'is-image'}">
          ${renderTileMedia(media)}
          ${overlay}
        </div>
      `;
    }).join('');
    tile.innerHTML = `
      <div class="tile-images ${mediaFiles.length > 1 ? 'multi' : 'single'}">
        ${mediaHtml || '<div class="empty-thumb"></div>'}
      </div>
      ${state.trashMode ? `<button class="tile-restore-btn" type="button">${tr('restore')}</button>` : ''}
    `;
    tile.addEventListener('click', (event) => {
      if (event.target.closest('.tile-restore-btn')) return;
      openEntry(card.id);
    });
    tile.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') openEntry(card.id);
    });
    const restoreBtn = $('.tile-restore-btn', tile);
    if (restoreBtn) restoreBtn.addEventListener('click', () => restoreEntry(card.id));
    return tile;
  }

  function renderNoteTile(card) {
    const tile = document.createElement('article');
    tile.className = 'card-tile note-tile' + (card.is_hidden ? ' hidden-note-tile' : '') + (state.trashMode ? ' in-trash' : '');
    tile.tabIndex = 0;
    tile.setAttribute('aria-label', tr('openNote'));
    const title = card.title || tr('untitled');
    const snippet = card.body_text || stripHtml(card.body_html || '') || tr('emptyNote');
    tile.innerHTML = `
      <div class="note-tile-icon">✎</div>
      <h3>${escapeHtml(title)}</h3>
      <p>${escapeHtml(snippet).slice(0, 320)}</p>
      <div class="note-tile-footer">
        ${card.is_hidden ? `<span class="badge">${tr('hidden')}</span>` : `<span class="badge">${tr('note')}</span>`}
        ${state.trashMode ? `<button class="tile-restore-btn" type="button">${tr('restore')}</button>` : ''}
      </div>
    `;
    tile.addEventListener('click', (event) => {
      if (event.target.closest('.tile-restore-btn')) return;
      openEntry(card.id);
    });
    tile.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') openEntry(card.id);
    });
    const restoreBtn = $('.tile-restore-btn', tile);
    if (restoreBtn) restoreBtn.addEventListener('click', () => restoreEntry(card.id));
    return tile;
  }

  function renderEntryTile(card) {
    return card.entry_type === 'note' ? renderNoteTile(card) : renderMediaTile(card);
  }

  function renderGallery(cards) {
    els.gallery.innerHTML = '';
    els.contentTitle.textContent = state.trashMode ? tr('trash') : 'Nook';
    els.trashModeBtn.hidden = state.trashMode;
    els.backFromTrashBtn.hidden = !state.trashMode;
    els.emptyTrashBtn.hidden = !state.trashMode;
    els.gallery.classList.toggle('trash-view', state.trashMode);

    if (!cards.length) {
      els.gallery.innerHTML = `
        <div class="empty-state">
          <h3>${state.trashMode ? tr('trashEmpty') : tr('nothingFound')}</h3>
          <p>${state.trashMode ? tr('deletedWillAppear') : tr('changeFilters')}</p>
        </div>
      `;
      els.resultInfo.textContent = tr('foundZero');
      return;
    }
    const fragment = document.createDocumentFragment();
    cards.forEach((card) => fragment.appendChild(renderEntryTile(card)));
    els.gallery.appendChild(fragment);
    els.resultInfo.textContent = `${state.trashMode ? tr('inTrash') : tr('found')}: ${cards.length}`;
  }

  async function loadCards(keepStatus = false) {
    try {
      if (!keepStatus) clearStatus();
      els.resultInfo.textContent = tr('loading');
      const data = await getJson('list', {
        q: state.q,
        date_from: state.dateFrom,
        date_to: state.dateTo,
        tag: state.tag,
        mode: state.trashMode ? 'trash' : '',
      });
      renderTags(data.tags || []);
      renderGallery(data.cards || []);
    } catch (error) {
      showStatus(error.message, 'error');
      els.resultInfo.textContent = tr('loadError');
    }
  }

  async function openEntry(id) {
    try {
      const data = await getJson('get', { id });
      if (data.card.entry_type === 'note') {
        renderNoteModal(data.card);
      } else {
        renderCardModal(data.card, false);
      }
    } catch (error) {
      showStatus(error.message, 'error');
    }
  }

  function resetFilters() {
    state.q = '';
    state.dateFrom = '';
    state.dateTo = '';
    state.tag = '';
    els.searchInput.value = '';
    els.dateFromInput.value = '';
    els.dateToInput.value = '';
    loadCards();
  }

  function renderManageThumb(media) {
    if (isVideoMedia(media)) return `<div class="manage-video-thumb"><span>▶</span></div>`;
    return `<img src="${escapeHtml(media.thumb_url || media.url)}" alt="">`;
  }

  function renderImageManager(card) {
    const mediaFiles = card.images || [];
    if (!els.imageManageList) return;
    if (!mediaFiles.length) {
      els.imageManageList.innerHTML = `<div class="muted">${tr('groupNoFiles')}</div>`;
      return;
    }
    els.imageManageList.innerHTML = mediaFiles.map((media) => {
      const disabled = mediaFiles.length <= 1 ? `disabled title="${tr('onlyFileCannotDelete')}"` : '';
      const typeLabel = isVideoMedia(media) ? tr('video') : tr('photo');
      return `
        <div class="image-manage-item">
          ${renderManageThumb(media)}
          <div class="image-manage-name" title="${escapeHtml(media.original_filename)}"><strong>${typeLabel}</strong> · ${escapeHtml(media.original_filename)}</div>
          <button class="btn btn-danger delete-image-list-btn" type="button" data-image-id="${media.id}" ${disabled}>${tr('delete')}</button>
        </div>
      `;
    }).join('');
    $$('.delete-image-list-btn', els.imageManageList).forEach((button) => {
      button.addEventListener('click', () => deleteImage(button.dataset.imageId));
    });
  }

  function setEditMode(enabled) {
    state.editMode = enabled;
    const form = els.editForm;
    $$('input[name="title"], textarea[name="description"], input[name="hashtags"]', form).forEach((el) => {
      el.readOnly = !enabled;
    });
    if (els.editHiddenInput) els.editHiddenInput.disabled = !enabled;
    els.editCardBtn.hidden = enabled || state.trashMode || (state.currentCard && state.currentCard.is_deleted);
    els.saveEditBtn.hidden = !enabled;
    els.cancelEditBtn.hidden = !enabled;
    els.imageEditTools.hidden = !enabled;
    els.cardModal.classList.toggle('editing', enabled);
    $$('.delete-image-btn', els.cardImages).forEach((button) => { button.hidden = !enabled; });
    if (!enabled) hideHashtagSuggestions();
    if (enabled && state.currentCard) renderImageManager(state.currentCard);
  }

  function renderViewerMedia(media) {
    if (isVideoMedia(media)) {
      return `
        <button class="viewer-image viewer-media-button" type="button" data-media-id="${media.id}">
          <div class="viewer-media-frame is-video">
            <video src="${escapeHtml(media.url)}" muted playsinline preload="metadata"></video>
            <span class="video-play-mark big">▶</span>
          </div>
          <span>${escapeHtml(media.original_filename)}</span>
        </button>
      `;
    }
    return `
      <button class="viewer-image viewer-media-button" type="button" data-media-id="${media.id}">
        <div class="viewer-media-frame is-image">
          <img src="${escapeHtml(media.thumb_url || media.url)}" alt="${escapeHtml(media.original_filename)}">
        </div>
        <span>${escapeHtml(media.original_filename)}</span>
      </button>
    `;
  }

  function findCurrentMedia(mediaId) {
    if (!state.currentCard) return null;
    return (state.currentCard.images || []).find((media) => String(media.id) === String(mediaId)) || null;
  }

  function openMediaViewer(media) {
    if (!media) return;
    els.mediaModalTitle.textContent = isVideoMedia(media) ? tr('video') : tr('photo');
    els.mediaModalCaption.textContent = media.original_filename || '';
    els.mediaViewer.innerHTML = isVideoMedia(media)
      ? `<video class="full-media" src="${escapeHtml(media.url)}" controls autoplay playsinline preload="metadata"></video>`
      : `<img class="full-media" src="${escapeHtml(media.url)}" alt="${escapeHtml(media.original_filename)}">`;
    openModal('media');
  }

  function renderCardModal(card, keepEditMode = false) {
    state.currentCard = card;
    state.editSnapshot = {
      title: card.title || '',
      description: card.description || '',
      hashtags: hashtagsToText(card.tags),
      isHidden: !!Number(card.is_hidden),
    };
    const mediaFiles = card.images || [];
    els.cardModalTitle.textContent = card.title || tr('untitled');
    const deleted = card.is_deleted || card.deleted_at;
    els.cardModalDate.textContent = (deleted ? tr('inTrashPrefix') : '') + tr('created') + formatDateTime(card.created_at) + tr('updated') + formatDateTime(card.updated_at);
    els.cardImages.innerHTML = '';
    mediaFiles.forEach((media) => {
      const item = document.createElement('div');
      item.className = 'viewer-image-wrap';
      item.innerHTML = `
        ${renderViewerMedia(media)}
        <button class="delete-image-btn edit-only" type="button" data-image-id="${media.id}" ${mediaFiles.length <= 1 ? `disabled title="${tr('onlyFileCannotDelete')}"` : ''} hidden>${tr('delete')}</button>
      `;
      els.cardImages.appendChild(item);
    });
    $$('.viewer-media-button', els.cardImages).forEach((button) => {
      button.addEventListener('click', () => openMediaViewer(findCurrentMedia(button.dataset.mediaId)));
    });
    $$('.delete-image-btn', els.cardImages).forEach((button) => {
      button.addEventListener('click', () => deleteImage(button.dataset.imageId));
    });
    els.editForm.elements.id.value = card.id;
    els.editForm.elements.title.value = card.title || '';
    els.editForm.elements.description.value = card.description || '';
    els.editForm.elements.hashtags.value = hashtagsToText(card.tags);
    if (els.editHiddenInput) els.editHiddenInput.checked = !!Number(card.is_hidden);
    els.deleteCardBtn.hidden = !!deleted;
    els.restoreCardBtn.hidden = !deleted;
    if (els.cardFileList) {
      els.cardFileList.innerHTML = '';
      els.cardFileList.hidden = true;
    }
    renderImageManager(card);
    setEditMode(keepEditMode && !deleted);
    if (deleted) {
      $$('input[name="title"], textarea[name="description"], input[name="hashtags"]', els.editForm).forEach((el) => { el.readOnly = true; });
      if (els.editHiddenInput) els.editHiddenInput.disabled = true;
      els.imageEditTools.hidden = true;
      els.saveEditBtn.hidden = true;
      els.cancelEditBtn.hidden = true;
      els.editCardBtn.hidden = true;
    }
    openModal('card');
  }

  async function addImagesToCurrentCard(fileList) {
    const files = Array.from(fileList || []).filter(isAcceptedUpload);
    if (!state.currentCard || !files.length) {
      showStatus(tr('selectMedia'), 'error');
      return;
    }
    const formData = new FormData();
    formData.set('id', state.currentCard.id);
    files.forEach((file) => formData.append('images[]', file));
    els.addImagesBtn.disabled = true;
    els.addImagesBtn.textContent = tr('adding');
    try {
      const data = await postForm('add_images', formData);
      renderCardModal(data.card, true);
      showStatus(tr('filesAdded'), 'success');
      await loadCards(true);
    } catch (error) {
      showStatus(error.message, 'error');
    } finally {
      els.addImagesBtn.disabled = false;
      els.addImagesBtn.textContent = tr('addMedia');
      els.addImagesInput.value = '';
    }
  }

  async function deleteImage(imageId) {
    if (!state.currentCard || !imageId) return;
    if ((state.currentCard.images || []).length <= 1) {
      showStatus(tr('cannotDeleteOnlyFile'), 'error');
      return;
    }
    if (!window.confirm(tr('confirmDeleteFile'))) return;
    const formData = new FormData();
    formData.set('id', state.currentCard.id);
    formData.set('image_id', imageId);
    try {
      const data = await postForm('delete_image', formData);
      renderCardModal(data.card, true);
      showStatus(tr('fileDeleted'), 'success');
      await loadCards(true);
    } catch (error) {
      showStatus(error.message, 'error');
    }
  }

  function stopNoteAutosave() {
    if (state.noteAutosaveTimer) window.clearInterval(state.noteAutosaveTimer);
    state.noteAutosaveTimer = null;
  }

  function startNoteAutosave() {
    stopNoteAutosave();
    state.noteAutosaveTimer = window.setInterval(() => {
      if (state.currentNote && state.currentNote.id && !state.currentNote.is_deleted && state.noteDirty && !state.noteSaving) {
        saveCurrentNote(true);
      }
    }, 5 * 60 * 1000);
  }

  function markNoteDirty() {
    if (!els.noteModal || !els.noteModal.classList.contains('open')) return;
    state.noteDirty = true;
    if (els.noteAutosaveInfo) els.noteAutosaveInfo.textContent = state.currentNote && state.currentNote.id
      ? tr('unsavedAutosave')
      : tr('newNoteNotCreated');
  }

  function destroyNoteEditor() {
    if (state.noteEditorInstance && typeof state.noteEditorInstance.destroy === 'function') {
      state.noteEditorInstance.destroy();
    }
    state.noteEditorInstance = null;
  }

  function initNoteEditor(data = null, readOnly = false) {
    destroyNoteEditor();
    els.noteEditor.innerHTML = '';
    if (!window.EditorJS) {
      throw new Error(tr('editorMissing'));
    }

    const tools = {};
    if (window.Header) tools.header = { class: window.Header, inlineToolbar: ['link'], config: { levels: [2, 3, 4], defaultLevel: 2 } };
    if (window.EditorjsList) tools.list = { class: window.EditorjsList, inlineToolbar: true, config: { defaultStyle: 'unordered' } };
    if (window.Checklist) tools.checklist = { class: window.Checklist, inlineToolbar: true };
    if (window.Quote) tools.quote = { class: window.Quote, inlineToolbar: true, config: { quotePlaceholder: tr('quotePlaceholder'), captionPlaceholder: tr('quoteCaptionPlaceholder') } };
    if (window.Delimiter) tools.delimiter = window.Delimiter;
    if (window.Table) tools.table = { class: window.Table, inlineToolbar: true, config: { rows: 2, cols: 3 } };
    const EditorImageClass = window.ResizableImageTool || window.ImageTool;
    if (EditorImageClass) {
      tools.image = {
        class: EditorImageClass,
        config: {
          uploader: {
            uploadByFile: async (file) => {
              const url = await uploadEditorImage(file);
              return { success: 1, file: { url } };
            },
          },
          captionPlaceholder: tr('imageCaptionPlaceholder'),
        },
      };
    }

    state.noteEditorInstance = new window.EditorJS({
      holder: els.noteEditor,
      placeholder: tr('editorPlaceholder'),
      readOnly,
      autofocus: !readOnly,
      data: normalizeEditorData(data),
      inlineToolbar: true,
      tools,
      i18n: { messages: I18N[currentLang].editor },
      onChange: () => markNoteDirty(),
    });
  }

  async function getNoteEditorData() {
    if (!state.noteEditorInstance || typeof state.noteEditorInstance.save !== 'function') {
      return { time: Date.now(), blocks: [], version: '2.x' };
    }
    return normalizeEditorData(await state.noteEditorInstance.save());
  }

  async function getNoteEditorHtml() {
    return editorDataToHtml(await getNoteEditorData());
  }

  function setNoteReadOnly(readOnly) {
    els.noteTitleInput.readOnly = readOnly;
    els.noteHashtagsInput.readOnly = readOnly;
    els.noteHiddenInput.disabled = readOnly;
    if (state.noteEditorInstance && state.noteEditorInstance.readOnly && typeof state.noteEditorInstance.readOnly.toggle === 'function') {
      state.noteEditorInstance.readOnly.toggle(readOnly);
    }
  }

  function renderNoteModal(card = null) {
    stopNoteAutosave();
    state.currentNote = card || null;
    state.noteDirty = false;
    const isNew = !card;
    const deleted = !!(card && (card.is_deleted || card.deleted_at));
    els.noteForm.reset();
    els.noteForm.elements.id.value = card ? card.id : '';
    els.noteTitleInput.value = card ? (card.title || '') : '';
    els.noteHashtagsInput.value = card ? hashtagsToText(card.tags) : '';
    els.noteHiddenInput.checked = card ? !!Number(card.is_hidden) : false;
    initNoteEditor(getNoteInitialData(card), deleted);
    els.noteModalTitle.textContent = isNew ? tr('newNote') : (card.title || tr('untitled'));
    els.noteModalMeta.textContent = isNew
      ? tr('newNote')
      : (deleted ? tr('inTrashPrefix') : '') + tr('created') + formatDateTime(card.created_at) + tr('updated') + formatDateTime(card.updated_at);
    els.noteAutosaveInfo.textContent = isNew
      ? tr('newNoteNotCreated')
      : tr('autosaveInfo');
    els.deleteNoteBtn.hidden = isNew || deleted;
    els.restoreNoteBtn.hidden = !deleted;
    els.saveNoteBtn.hidden = deleted;
    setNoteReadOnly(deleted);
    openModal('note');
    if (!isNew && !deleted) startNoteAutosave();
    els.noteTitleInput.focus();
  }

  async function saveCurrentNote(isAuto = false) {
    if (state.noteSaving) return;
    const formData = new FormData();
    const id = els.noteForm.elements.id.value;
    const action = id ? 'update_note' : 'create_note';
    if (id) formData.set('id', id);
    formData.set('title', els.noteTitleInput.value || '');
    formData.set('hashtags', els.noteHashtagsInput.value || '');
    formData.set('is_hidden', els.noteHiddenInput.checked ? '1' : '0');
    const editorData = await getNoteEditorData();
    formData.set('body_json', JSON.stringify(editorData));
    formData.set('body_html', editorDataToHtml(editorData));
    state.noteSaving = true;
    if (!isAuto) {
      els.saveNoteBtn.disabled = true;
      els.saveNoteBtn.textContent = tr('saving');
    }
    try {
      const data = await postForm(action, formData);
      if (data.tags) renderTags(data.tags);
      state.currentNote = data.card;
      state.noteDirty = false;
      els.noteForm.elements.id.value = data.card.id;
      els.noteModalTitle.textContent = data.card.title || tr('untitled');
      els.noteModalMeta.textContent = tr('created') + formatDateTime(data.card.created_at) + tr('updated') + formatDateTime(data.card.updated_at);
      els.noteAutosaveInfo.textContent = (isAuto ? tr('autosaved') : tr('saved')) + new Date().toLocaleTimeString(currentLang === 'ru' ? 'ru-RU' : 'en-US', { hour: '2-digit', minute: '2-digit' });
      els.deleteNoteBtn.hidden = false;
      els.restoreNoteBtn.hidden = true;
      els.saveNoteBtn.hidden = false;
      startNoteAutosave();
      await loadCards(true);
      if (!isAuto) showStatus(tr('noteSaved'), 'success');
    } catch (error) {
      showStatus(error.message, 'error');
      if (els.noteAutosaveInfo) els.noteAutosaveInfo.textContent = tr('saveError') + error.message;
    } finally {
      state.noteSaving = false;
      els.saveNoteBtn.disabled = false;
      els.saveNoteBtn.textContent = tr('save');
    }
  }

  async function uploadEditorImage(file) {
    const formData = new FormData();
    formData.append('image', file);
    const data = await postForm('note_upload_image', formData);
    return data.file && data.file.url ? data.file.url : data.url;
  }

  async function deleteEntry(id, label = tr('entry')) {
    if (!id) return;
    if (!window.confirm(tr('confirmMoveToTrash', { label }))) return;
    const formData = new FormData();
    formData.set('id', id);
    try {
      const data = await postForm('delete', formData);
      if (data.tags) renderTags(data.tags);
      closeModal('card');
      closeModal('note');
      showStatus(tr('movedToTrash'), 'success');
      await loadCards(true);
    } catch (error) {
      showStatus(error.message, 'error');
    }
  }

  async function restoreEntry(id) {
    if (!id) return;
    const formData = new FormData();
    formData.set('id', id);
    try {
      const data = await postForm('restore', formData);
      if (data.tags) renderTags(data.tags);
      closeModal('card');
      closeModal('note');
      showStatus(tr('restored'), 'success');
      await loadCards(true);
    } catch (error) {
      showStatus(error.message, 'error');
    }
  }

  async function emptyTrash() {
    if (!window.confirm(tr('confirmEmptyTrash'))) return;
    try {
      const data = await postForm('empty_trash', new FormData());
      if (data.tags) renderTags(data.tags);
      showStatus(tr('trashEmptied'), 'success');
      await loadCards(true);
    } catch (error) {
      showStatus(error.message, 'error');
    }
  }


  function bindLanguageSwitches() {
    $$('.language-select').forEach((select) => {
      select.value = currentLang;
      select.addEventListener('change', () => {
        const nextLang = select.value === 'en' ? 'en' : 'ru';
        window.localStorage.setItem('nook_lang', nextLang);
        document.cookie = `nook_lang=${nextLang}; path=/; max-age=31536000; SameSite=Lax`;
        window.location.reload();
      });
    });
  }

  function bindEvents() {
    bindLanguageSwitches();
    setupHashtagAutocomplete();

    els.newNoteBtn.addEventListener('click', () => renderNoteModal(null));

    els.trashModeBtn.addEventListener('click', () => {
      state.trashMode = true;
      loadCards();
    });
    els.backFromTrashBtn.addEventListener('click', () => {
      state.trashMode = false;
      loadCards();
    });
    els.emptyTrashBtn.addEventListener('click', emptyTrash);

    els.chooseFilesBtn.addEventListener('click', () => els.fileInput.click());
    els.fileInput.addEventListener('change', () => openUploadForFiles(els.fileInput.files));

    ['dragenter', 'dragover'].forEach((eventName) => {
      els.dropZone.addEventListener(eventName, (event) => {
        event.preventDefault();
        els.dropZone.classList.add('drag-over');
      });
    });
    ['dragleave', 'drop'].forEach((eventName) => {
      els.dropZone.addEventListener(eventName, (event) => {
        event.preventDefault();
        els.dropZone.classList.remove('drag-over');
      });
    });
    els.dropZone.addEventListener('drop', (event) => openUploadForFiles(event.dataTransfer.files));
    els.dropZone.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        els.fileInput.click();
      }
    });

    els.searchInput.addEventListener('input', () => {
      state.q = els.searchInput.value.trim();
      window.clearTimeout(state.debounceTimer);
      state.debounceTimer = window.setTimeout(loadCards, 250);
    });
    els.dateFromInput.addEventListener('change', () => { state.dateFrom = els.dateFromInput.value; loadCards(); });
    els.dateToInput.addEventListener('change', () => { state.dateTo = els.dateToInput.value; loadCards(); });
    els.resetFiltersBtn.addEventListener('click', resetFilters);
    els.clearTagBtn.addEventListener('click', () => { state.tag = ''; loadCards(); });

    $$('[data-close-modal]').forEach((button) => {
      button.addEventListener('click', () => closeModal(button.dataset.closeModal));
    });
    document.addEventListener('click', (event) => {
      if (!event.target.closest('.hashtag-input-wrap')) hideHashtagSuggestions();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        hideHashtagSuggestions();
        if (els.mediaModal.classList.contains('open')) { closeModal('media'); return; }
        if (els.noteModal.classList.contains('open')) { closeModal('note'); return; }
        if (els.uploadModal.classList.contains('open')) closeModal('upload');
        if (els.cardModal.classList.contains('open')) closeModal('card');
      }
    });

    els.uploadForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!state.selectedFiles.length) {
        showStatus(tr('noMediaSelected'), 'error');
        return;
      }
      const formData = new FormData(els.uploadForm);
      state.selectedFiles.forEach((file) => formData.append('images[]', file));
      els.saveUploadBtn.disabled = true;
      els.saveUploadBtn.textContent = tr('saving');
      try {
        const data = await postForm('create', formData);
        if (data.tags) renderTags(data.tags);
        closeModal('upload');
        showStatus(tr('groupSaved'), 'success');
        await loadCards(true);
      } catch (error) {
        showStatus(error.message, 'error');
      } finally {
        els.saveUploadBtn.disabled = false;
        els.saveUploadBtn.textContent = tr('save');
      }
    });

    els.editCardBtn.addEventListener('click', () => setEditMode(true));
    els.cancelEditBtn.addEventListener('click', () => {
      if (!state.editSnapshot) return;
      els.editForm.elements.title.value = state.editSnapshot.title;
      els.editForm.elements.description.value = state.editSnapshot.description;
      els.editForm.elements.hashtags.value = state.editSnapshot.hashtags;
      if (els.editHiddenInput) els.editHiddenInput.checked = !!state.editSnapshot.isHidden;
      setEditMode(false);
    });
    els.editForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(els.editForm);
      els.saveEditBtn.disabled = true;
      els.saveEditBtn.textContent = tr('saving');
      try {
        const data = await postForm('update', formData);
        if (data.tags) renderTags(data.tags);
        renderCardModal(data.card, false);
        showStatus(tr('changesSaved'), 'success');
        await loadCards(true);
      } catch (error) {
        showStatus(error.message, 'error');
      } finally {
        els.saveEditBtn.disabled = false;
        els.saveEditBtn.textContent = tr('saveChanges');
      }
    });
    els.addImagesBtn.addEventListener('click', () => { els.addImagesInput.value = ''; els.addImagesInput.click(); });
    els.addImagesInput.addEventListener('change', () => addImagesToCurrentCard(els.addImagesInput.files));
    els.deleteCardBtn.addEventListener('click', () => deleteEntry(els.editForm.elements.id.value, tr('group')));
    els.restoreCardBtn.addEventListener('click', () => restoreEntry(els.editForm.elements.id.value));

    els.noteForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      await saveCurrentNote(false);
    });
    ['input', 'change'].forEach((eventName) => {
      els.noteTitleInput.addEventListener(eventName, markNoteDirty);
      els.noteHashtagsInput.addEventListener(eventName, markNoteDirty);
      els.noteHiddenInput.addEventListener(eventName, markNoteDirty);
    });
    els.deleteNoteBtn.addEventListener('click', () => deleteEntry(els.noteForm.elements.id.value, tr('noteAcc')));
    els.restoreNoteBtn.addEventListener('click', () => restoreEntry(els.noteForm.elements.id.value));
  }

  document.addEventListener('DOMContentLoaded', () => {
    bindEvents();
    loadCards();
  });
})();
