(function (g) {
  'use strict';

  const ICONS = {
    header: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="M5 4v16M19 4v16M5 12h14" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    list: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    checklist: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="m3 6 2 2 4-4M11 6h10M3 13l2 2 4-4M11 13h10M3 20l2 2 4-4M11 20h10" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    quote: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="M7 17H4a2 2 0 0 1-2-2v-3a6 6 0 0 1 6-6v3a3 3 0 0 0-3 3h2v5Zm12 0h-3a2 2 0 0 1-2-2v-3a6 6 0 0 1 6-6v3a3 3 0 0 0-3 3h2v5Z" fill="currentColor"/></svg>',
    warning: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 3 2 21h20L12 3Z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 9v5M12 18h.01" stroke="currentColor" stroke-width="2"/></svg>',
    delimiter: '<svg width="18" height="18" viewBox="0 0 24 24"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>',
    table: '<svg width="18" height="18" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="1" fill="none" stroke="currentColor"/><path d="M3 10h18M9 4v16M15 4v16" stroke="currentColor"/></svg>',
    code: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="m8 9-3 3 3 3M16 9l3 3-3 3M14 5l-4 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    raw: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="m8 5-6 7 6 7M16 5l6 7-6 7M14 3l-4 18" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    embed: '<svg width="18" height="18" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="m10 9 5 3-5 3V9Z" fill="currentColor"/></svg>',
    marker: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="m4 17 3 3 13-13-3-3L4 17Zm11-11 3 3M3 21h8" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    underline: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="M6 3v7a6 6 0 0 0 12 0V3M4 21h16" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    inlineCode: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="m9 8-4 4 4 4M15 8l4 4-4 4" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    strike: '<svg width="18" height="18" viewBox="0 0 24 24"><path d="M7 7c0-2 2-3 5-3 2.5 0 4 .8 5 2M7 17c1 2 3 3 5 3 3 0 5-1.4 5-3.5 0-2.2-2-3-5-3.5M3 12h18" fill="none" stroke="currentColor" stroke-width="2"/></svg>'
  };

  const editable = (tag, className, html, readOnly) => {
    const el = document.createElement(tag);
    el.className = className || '';
    el.contentEditable = String(!readOnly);
    el.innerHTML = html || '';
    el.spellcheck = true;
    return el;
  };

  class HeaderTool {
    constructor({ data, config, readOnly }) {
      this.data = Object.assign({ text: '', level: (config && config.defaultLevel) || 2 }, data || {});
      this.config = Object.assign({ levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 }, config || {});
      this.readOnly = !!readOnly;
      this.el = null;
    }
    static get isReadOnlySupported() { return true; }
    static get toolbox() { return { title: 'Заголовок', icon: ICONS.header }; }
    static get sanitize() { return { text: { br: true, b: true, strong: true, i: true, em: true, a: { href: true, target: true, rel: true }, mark: true, u: true, s: true, span: { class: true } }, level: false }; }
    render() {
      const level = this.config.levels.includes(Number(this.data.level)) ? Number(this.data.level) : this.config.defaultLevel;
      this.el = editable('div', 'nook-ej-full-header', this.data.text, this.readOnly);
      this.el.dataset.level = String(level);
      return this.el;
    }
    save(block) { return { text: block.innerHTML || '', level: Number(block.dataset.level || this.config.defaultLevel) }; }
    renderSettings() {
      return this.config.levels.map(level => ({
        icon: `<b>H${level}</b>`,
        label: `Заголовок ${level}`,
        isActive: () => Number(this.el && this.el.dataset.level) === level,
        onActivate: () => { if (this.el) this.el.dataset.level = String(level); }
      }));
    }
  }

  class ListTool {
    constructor({ data, readOnly }) {
      this.data = Object.assign({ style: 'unordered', items: [] }, data || {});
      if (this.data.type && !this.data.style) this.data.style = this.data.type;
      this.readOnly = !!readOnly;
      this.root = null;
    }
    static get isReadOnlySupported() { return true; }
    static get toolbox() { return { title: 'Список', icon: ICONS.list }; }
    static get sanitize() { return { style: {}, type: {}, items: { br: true, b: true, strong: true, i: true, em: true, a: { href: true }, mark: true, u: true, s: true, span: { class: true } } }; }
    render() {
      const tag = this.data.style === 'ordered' ? 'ol' : 'ul';
      this.root = document.createElement(tag);
      this.root.className = 'nook-ej-full-list';
      this.root.dataset.style = this.data.style || 'unordered';
      const items = Array.isArray(this.data.items) && this.data.items.length ? this.data.items : [''];
      items.forEach(item => this.addItem(typeof item === 'string' ? item : (item && (item.content || item.text)) || ''));
      return this.root;
    }
    addItem(text) {
      const li = editable('li', '', text, this.readOnly);
      if (!this.readOnly) {
        li.addEventListener('keydown', e => {
          if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const next = this.addItem('');
            li.after(next);
            placeCaret(next);
          }
          if (e.key === 'Backspace' && !li.textContent && this.root.children.length > 1) {
            const prev = li.previousElementSibling || li.nextElementSibling;
            li.remove();
            if (prev) placeCaret(prev, true);
          }
        });
      }
      this.root.appendChild(li);
      return li;
    }
    save(block) { return { style: block.dataset.style || 'unordered', items: [...block.children].map(li => li.innerHTML) }; }
    renderSettings() {
      return [
        { icon: '•', label: 'Маркированный', isActive: () => this.root && this.root.dataset.style === 'unordered', onActivate: () => this.setStyle('unordered') },
        { icon: '1.', label: 'Нумерованный', isActive: () => this.root && this.root.dataset.style === 'ordered', onActivate: () => this.setStyle('ordered') }
      ];
    }
    setStyle(style) {
      if (!this.root || this.root.dataset.style === style) return;
      const replacement = document.createElement(style === 'ordered' ? 'ol' : 'ul');
      replacement.className = this.root.className;
      replacement.dataset.style = style;
      while (this.root.firstChild) replacement.appendChild(this.root.firstChild);
      this.root.replaceWith(replacement);
      this.root = replacement;
    }
  }

  class ChecklistTool {
    constructor({ data, readOnly }) { this.data = Object.assign({ items: [] }, data || {}); this.readOnly = !!readOnly; this.root = null; }
    static get isReadOnlySupported() { return true; }
    static get toolbox() { return { title: 'Чек-лист', icon: ICONS.checklist }; }
    static get sanitize() { return { items: { text: { br: true, b: true, i: true, a: { href: true }, mark: true, u: true, s: true, span: { class: true } }, checked: false } }; }
    render() {
      this.root = document.createElement('div'); this.root.className = 'nook-ej-full-checklist';
      const items = Array.isArray(this.data.items) && this.data.items.length ? this.data.items : [{ text: '', checked: false }];
      items.forEach(item => this.addRow(item));
      return this.root;
    }
    addRow(item) {
      const row = document.createElement('label'); row.className = 'nook-ej-full-check-row';
      const cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = !!item.checked; cb.disabled = this.readOnly;
      const text = editable('span', '', item.text || '', this.readOnly);
      if (!this.readOnly) text.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); const next = this.addRow({ text: '', checked: false }); row.after(next); placeCaret(next.querySelector('span')); } });
      row.append(cb, text); this.root.appendChild(row); return row;
    }
    save(block) { return { items: [...block.querySelectorAll('.nook-ej-full-check-row')].map(row => ({ text: row.querySelector('span').innerHTML, checked: row.querySelector('input').checked })) }; }
  }

  class QuoteTool {
    constructor({ data, readOnly }) { this.data = Object.assign({ text: '', caption: '', alignment: 'left' }, data || {}); this.readOnly = !!readOnly; this.root = null; }
    static get isReadOnlySupported() { return true; }
    static get toolbox() { return { title: 'Цитата', icon: ICONS.quote }; }
    static get sanitize() { return { text: { br: true, b: true, i: true, a: { href: true }, mark: true, u: true, s: true, span: { class: true } }, caption: { br: true, b: true, i: true, a: { href: true } }, alignment: false }; }
    render() {
      const wrap = document.createElement('blockquote'); wrap.className = 'nook-ej-full-quote'; wrap.dataset.alignment = this.data.alignment || 'left';
      const text = editable('div', 'nook-ej-full-quote-text', this.data.text, this.readOnly); text.dataset.field = 'text';
      const cap = editable('div', 'nook-ej-full-quote-caption', this.data.caption, this.readOnly); cap.dataset.field = 'caption'; cap.dataset.placeholder = 'Автор / источник';
      wrap.append(text, cap); this.root = wrap; return wrap;
    }
    save(block) { return { text: block.querySelector('[data-field="text"]').innerHTML, caption: block.querySelector('[data-field="caption"]').innerHTML, alignment: block.dataset.alignment || 'left' }; }
    renderSettings() { return ['left', 'center'].map(v => ({ icon: v === 'left' ? '≡' : '☰', label: v === 'left' ? 'По левому краю' : 'По центру', isActive: () => this.root && this.root.dataset.alignment === v, onActivate: () => { if (this.root) this.root.dataset.alignment = v; } })); }
  }

  class WarningTool {
    constructor({ data, readOnly }) { this.data = Object.assign({ title: '', message: '' }, data || {}); this.readOnly = !!readOnly; }
    static get isReadOnlySupported() { return true; }
    static get toolbox() { return { title: 'Примечание', icon: ICONS.warning }; }
    static get sanitize() { return { title: { b: true, i: true }, message: { br: true, b: true, i: true, a: { href: true }, mark: true, u: true, s: true, span: { class: true } } }; }
    render() {
      const wrap = document.createElement('aside'); wrap.className = 'nook-ej-full-warning';
      const title = editable('div', 'nook-ej-full-warning-title', this.data.title, this.readOnly); title.dataset.field = 'title'; title.dataset.placeholder = 'Заголовок';
      const msg = editable('div', 'nook-ej-full-warning-message', this.data.message, this.readOnly); msg.dataset.field = 'message'; msg.dataset.placeholder = 'Текст примечания';
      wrap.append(title, msg); return wrap;
    }
    save(block) { return { title: block.querySelector('[data-field="title"]').innerHTML, message: block.querySelector('[data-field="message"]').innerHTML }; }
  }

  class DelimiterTool {
    static get isReadOnlySupported() { return true; }
    static get toolbox() { return { title: 'Разделитель', icon: ICONS.delimiter }; }
    render() { const el = document.createElement('div'); el.className = 'nook-ej-full-delimiter'; el.innerHTML = '<span></span><span></span><span></span>'; return el; }
    save() { return {}; }
  }

  class TableTool {
    constructor({ data, readOnly }) {
      this.data = Object.assign({ withHeadings: false, stretched: false, content: [['', ''], ['', '']] }, data || {});
      this.readOnly = !!readOnly; this.root = null;
    }
    static get isReadOnlySupported() { return true; }
    static get toolbox() { return { title: 'Таблица', icon: ICONS.table }; }
    static get sanitize() { return { withHeadings: false, stretched: false, content: { br: true, b: true, i: true, a: { href: true }, mark: true, u: true, s: true, span: { class: true } } }; }
    render() {
      const root = document.createElement('div'); root.className = 'nook-ej-full-table-wrap'; root.dataset.headings = this.data.withHeadings ? '1' : '0'; root.dataset.stretched = this.data.stretched ? '1' : '0';
      const table = document.createElement('table'); table.className = 'nook-ej-full-table';
      const rows = Array.isArray(this.data.content) && this.data.content.length ? this.data.content : [['', ''], ['', '']];
      const cols = Math.max(1, ...rows.map(r => Array.isArray(r) ? r.length : 1));
      rows.forEach((row, ri) => { const tr = document.createElement('tr'); for (let ci = 0; ci < cols; ci++) { const cell = document.createElement(ri === 0 && root.dataset.headings === '1' ? 'th' : 'td'); cell.contentEditable = String(!this.readOnly); cell.innerHTML = (Array.isArray(row) && row[ci] != null) ? String(row[ci]) : ''; tr.appendChild(cell); } table.appendChild(tr); });
      root.appendChild(table); this.root = root; return root;
    }
    save(block) { return { withHeadings: block.dataset.headings === '1', stretched: block.dataset.stretched === '1', content: [...block.querySelectorAll('tr')].map(tr => [...tr.children].map(td => td.innerHTML)) }; }
    renderSettings() {
      return [
        { icon: '+R', label: 'Добавить строку', onActivate: () => this.addRow() },
        { icon: '−R', label: 'Удалить строку', onActivate: () => this.removeRow() },
        { icon: '+C', label: 'Добавить столбец', onActivate: () => this.addColumn() },
        { icon: '−C', label: 'Удалить столбец', onActivate: () => this.removeColumn() },
        { icon: 'H', label: 'Первая строка — заголовок', isActive: () => this.root && this.root.dataset.headings === '1', onActivate: () => this.toggleHeadings() },
        { icon: '↔', label: 'Растянуть', isActive: () => this.root && this.root.dataset.stretched === '1', onActivate: () => { if (this.root) this.root.dataset.stretched = this.root.dataset.stretched === '1' ? '0' : '1'; } }
      ];
    }
    table() { return this.root && this.root.querySelector('table'); }
    addRow() { const table = this.table(); if (!table) return; const cols = table.rows[0] ? table.rows[0].cells.length : 2; const tr = document.createElement('tr'); for (let i = 0; i < cols; i++) { const td = document.createElement('td'); td.contentEditable = String(!this.readOnly); tr.appendChild(td); } table.appendChild(tr); }
    removeRow() { const table = this.table(); if (table && table.rows.length > 1) table.deleteRow(table.rows.length - 1); }
    addColumn() { const table = this.table(); if (!table) return; [...table.rows].forEach((tr, ri) => { const cell = document.createElement(ri === 0 && this.root.dataset.headings === '1' ? 'th' : 'td'); cell.contentEditable = String(!this.readOnly); tr.appendChild(cell); }); }
    removeColumn() { const table = this.table(); if (!table || !table.rows[0] || table.rows[0].cells.length <= 1) return; [...table.rows].forEach(tr => tr.deleteCell(tr.cells.length - 1)); }
    toggleHeadings() {
      if (!this.root) return; this.root.dataset.headings = this.root.dataset.headings === '1' ? '0' : '1'; const first = this.table() && this.table().rows[0]; if (!first) return;
      [...first.cells].forEach(old => { const cell = document.createElement(this.root.dataset.headings === '1' ? 'th' : 'td'); cell.contentEditable = String(!this.readOnly); cell.innerHTML = old.innerHTML; old.replaceWith(cell); });
    }
  }

  class CodeTool {
    constructor({ data, readOnly }) { this.data = Object.assign({ code: '' }, data || {}); this.readOnly = !!readOnly; }
    static get isReadOnlySupported() { return true; }
    static get enableLineBreaks() { return true; }
    static get toolbox() { return { title: 'Код', icon: ICONS.code }; }
    render() { const ta = document.createElement('textarea'); ta.className = 'nook-ej-full-code'; ta.value = this.data.code || ''; ta.readOnly = this.readOnly; ta.spellcheck = false; ta.placeholder = 'Вставьте код…'; return ta; }
    save(block) { return { code: block.value || '' }; }
  }

  class RawTool {
    constructor({ data, readOnly }) { this.data = Object.assign({ html: '' }, data || {}); this.readOnly = !!readOnly; }
    static get isReadOnlySupported() { return true; }
    static get enableLineBreaks() { return true; }
    static get toolbox() { return { title: 'HTML', icon: ICONS.raw }; }
    render() { const ta = document.createElement('textarea'); ta.className = 'nook-ej-full-raw'; ta.value = this.data.html || ''; ta.readOnly = this.readOnly; ta.spellcheck = false; ta.placeholder = '<div>HTML-фрагмент</div>'; return ta; }
    save(block) { return { html: block.value || '' }; }
  }

  class EmbedTool {
    constructor({ data, readOnly }) { this.data = Object.assign({ service: '', source: '', embed: '', width: 580, height: 320, caption: '' }, data || {}); this.readOnly = !!readOnly; this.root = null; }
    static get isReadOnlySupported() { return true; }
    static get toolbox() { return { title: 'Видео / Embed', icon: ICONS.embed }; }
    static get sanitize() { return { service: false, source: false, embed: false, width: false, height: false, caption: { br: true, b: true, i: true, a: { href: true } } }; }
    render() {
      const root = document.createElement('div'); root.className = 'nook-ej-full-embed'; this.root = root;
      const input = document.createElement('input'); input.className = 'nook-ej-full-embed-input'; input.type = 'url'; input.placeholder = 'YouTube / Vimeo URL'; input.value = this.data.source || '';
      input.disabled = this.readOnly;
      const preview = document.createElement('div'); preview.className = 'nook-ej-full-embed-preview';
      const cap = editable('div', 'nook-ej-full-embed-caption', this.data.caption || '', this.readOnly); cap.dataset.placeholder = 'Подпись';
      root.append(input, preview, cap);
      const refresh = () => { const meta = normalizeEmbed(input.value); preview.innerHTML = meta.embed ? `<iframe src="${escapeAttr(meta.embed)}" loading="lazy" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>` : ''; root.dataset.service = meta.service; root.dataset.embed = meta.embed; };
      input.addEventListener('change', refresh); input.addEventListener('paste', () => setTimeout(refresh, 0)); if (input.value || this.data.embed) refresh();
      return root;
    }
    save(block) { const input = block.querySelector('input'); const meta = normalizeEmbed(input.value); return { service: meta.service, source: input.value.trim(), embed: meta.embed, width: 580, height: 320, caption: block.querySelector('.nook-ej-full-embed-caption').innerHTML }; }
  }

  class InlineWrapTool {
    constructor({ api }) { this.api = api; this.button = null; }
    static get isInline() { return true; }
    command() { return ''; }
    tagName() { return 'SPAN'; }
    className() { return ''; }
    icon() { return ''; }
    title() { return ''; }
    render() { const btn = document.createElement('button'); btn.type = 'button'; btn.classList.add(this.api.styles.inlineToolButton); btn.innerHTML = this.icon(); btn.title = this.title(); this.button = btn; return btn; }
    surround(range) {
      if (!range || range.collapsed) return;
      const ancestor = closestTag(range.commonAncestorContainer, this.tagName(), this.className());
      if (ancestor) { unwrap(ancestor); return; }
      const el = document.createElement(this.tagName().toLowerCase()); if (this.className()) el.className = this.className();
      try { range.surroundContents(el); } catch (_) { const fragment = range.extractContents(); el.appendChild(fragment); range.insertNode(el); }
      if (g.getSelection) { const sel = g.getSelection(); sel.removeAllRanges(); const r = document.createRange(); r.selectNodeContents(el); sel.addRange(r); }
    }
    checkState(selection) { const node = selection && selection.anchorNode; const active = !!closestTag(node, this.tagName(), this.className()); if (this.button) this.button.classList.toggle(this.api.styles.inlineToolButtonActive, active); return active; }
  }
  class MarkerTool extends InlineWrapTool { static get sanitize() { return { mark: { class: true } }; } icon() { return ICONS.marker; } title() { return 'Маркер'; } tagName() { return 'MARK'; } className() { return 'cdx-marker'; } }
  class UnderlineTool extends InlineWrapTool { static get sanitize() { return { u: {} }; } icon() { return ICONS.underline; } title() { return 'Подчеркнуть'; } tagName() { return 'U'; } }
  class InlineCodeTool extends InlineWrapTool { static get sanitize() { return { span: { class: true } }; } icon() { return ICONS.inlineCode; } title() { return 'Код в строке'; } tagName() { return 'SPAN'; } className() { return 'inline-code'; } }
  class StrikeTool extends InlineWrapTool { static get sanitize() { return { s: {} }; } icon() { return ICONS.strike; } title() { return 'Зачеркнуть'; } tagName() { return 'S'; } }

  function placeCaret(el, end) { try { const range = document.createRange(), sel = g.getSelection(); range.selectNodeContents(el); range.collapse(!end); sel.removeAllRanges(); sel.addRange(range); } catch (_) {} }
  function closestTag(node, tag, className) { let el = node && (node.nodeType === 1 ? node : node.parentElement); while (el && el !== document.body) { if (el.tagName === tag && (!className || el.classList.contains(className))) return el; el = el.parentElement; } return null; }
  function unwrap(el) { const p = el.parentNode; if (!p) return; while (el.firstChild) p.insertBefore(el.firstChild, el); p.removeChild(el); }
  function escapeAttr(value) { return String(value || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
  function normalizeEmbed(source) {
    const s = String(source || '').trim(); let m;
    if ((m = s.match(/(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i))) return { service: 'youtube', embed: `https://www.youtube-nocookie.com/embed/${m[1]}` };
    if ((m = s.match(/vimeo\.com\/(?:video\/)?(\d+)/i))) return { service: 'vimeo', embed: `https://player.vimeo.com/video/${m[1]}` };
    return { service: '', embed: '' };
  }

  const FullTools = { HeaderTool, ListTool, ChecklistTool, QuoteTool, WarningTool, DelimiterTool, TableTool, CodeTool, RawTool, EmbedTool, MarkerTool, UnderlineTool, InlineCodeTool, StrikeTool };

  function toolConfig(klass, inline, extra) { return Object.assign({ class: klass }, inline ? { inlineToolbar: true } : {}, extra || {}); }
  function enrich(config) {
    const cfg = Object.assign({}, config || {});
    const tools = Object.assign({}, cfg.tools || {});
    tools.header = toolConfig(HeaderTool, true, { shortcut: 'CMD+SHIFT+H', config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } });
    tools.list = toolConfig(ListTool, true, { shortcut: 'CMD+SHIFT+L' });
    tools.checklist = toolConfig(ChecklistTool, true);
    tools.quote = toolConfig(QuoteTool, true, { shortcut: 'CMD+SHIFT+Q' });
    tools.warning = toolConfig(WarningTool, true);
    tools.delimiter = toolConfig(DelimiterTool, false);
    tools.table = toolConfig(TableTool, true);
    tools.code = toolConfig(CodeTool, false, { shortcut: 'CMD+SHIFT+C' });
    tools.raw = toolConfig(RawTool, false);
    tools.embed = toolConfig(EmbedTool, false);
    tools.marker = { class: MarkerTool, shortcut: 'CMD+SHIFT+M' };
    tools.underline = { class: UnderlineTool, shortcut: 'CMD+U' };
    tools.inlineCode = { class: InlineCodeTool, shortcut: 'CMD+SHIFT+K' };
    tools.strike = { class: StrikeTool, shortcut: 'CMD+SHIFT+X' };
    // Nook's existing image/resizable-image tool is deliberately preserved.
    cfg.tools = tools;
    cfg.defaultBlock = cfg.defaultBlock || 'paragraph';
    cfg.inlineToolbar = ['bold', 'italic', 'underline', 'marker', 'inlineCode', 'strike', 'link'];
    cfg.minHeight = Math.max(280, Number(cfg.minHeight || 0));
    cfg.placeholder = cfg.placeholder || 'Начните писать. Нажмите + для добавления блока.';
    cfg.i18n = mergeI18n(cfg.i18n);
    return cfg;
  }

  function mergeI18n(existing) {
    const base = existing && typeof existing === 'object' ? JSON.parse(JSON.stringify(existing)) : {};
    base.messages = base.messages || {};
    base.messages.ui = Object.assign({
      blockTunes: { toggler: { 'Click to tune': 'Настройки блока', 'or drag to move': 'или перетащите' } },
      inlineToolbar: { converter: { 'Convert to': 'Преобразовать в' } },
      toolbar: { toolbox: { Add: 'Добавить' } },
      popover: { Filter: 'Поиск', 'Nothing found': 'Ничего не найдено', 'Convert to': 'Преобразовать в' }
    }, base.messages.ui || {});
    base.messages.toolNames = Object.assign({
      Text: 'Текст', Heading: 'Заголовок', Image: 'Изображение', List: 'Список', Checklist: 'Чек-лист', Quote: 'Цитата', Warning: 'Примечание', Code: 'Код', Delimiter: 'Разделитель', 'Raw HTML': 'HTML', Table: 'Таблица', Embed: 'Видео / Embed', Link: 'Ссылка', Marker: 'Маркер', Bold: 'Полужирный', Italic: 'Курсив', Underline: 'Подчеркнуть', InlineCode: 'Код в строке'
    }, base.messages.toolNames || {});
    base.messages.tools = Object.assign({
      link: { 'Add a link': 'Вставьте ссылку' },
      stub: { 'The block can not be displayed correctly.': 'Блок не может быть отображен корректно.' },
      header: { 'Heading 1': 'Заголовок 1', 'Heading 2': 'Заголовок 2', 'Heading 3': 'Заголовок 3', 'Heading 4': 'Заголовок 4', 'Heading 5': 'Заголовок 5', 'Heading 6': 'Заголовок 6' },
      image: { 'Select an Image': 'Выбрать изображение', Caption: 'Подпись', 'With border': 'С рамкой', 'Stretch image': 'Растянуть', 'With background': 'С фоном', 'Couldn’t upload image. Please try another.': 'Не удалось загрузить изображение.' }
    }, base.messages.tools || {});
    return base;
  }

  function resolveHolder(holder) {
    if (holder && holder.nodeType === 1) return holder;
    if (typeof holder === 'string') return document.getElementById(holder);
    return document.getElementById('noteEditor') || document.getElementById('editorjs');
  }

  function enhanceSurface(editor, cfg) {
    const holder = resolveHolder(cfg.holder);
    if (!holder) return;
    holder.__nookEditorInstance = editor;
    if (holder.dataset.nookEditorSurfaceReady === '1') return;
    holder.dataset.nookEditorSurfaceReady = '1';
    holder.classList.add('nook-editorjs-full-ready');
    // Clicking the empty area below the last block should focus the last block, exactly
    // where Editor.js can expose its native block toolbar (+ and settings buttons).
    holder.addEventListener('pointerdown', (event) => {
      if (event.target.closest('.ce-block,.ce-toolbar,.ce-popover,.ce-inline-toolbar,.ce-settings,button,input,textarea,a')) return;
      requestAnimationFrame(() => {
        try { holder.__nookEditorInstance.caret.setToLastBlock('end'); } catch (_) {}
      });
    });
  }

  function wrap(EditorCtor) {
    if (typeof EditorCtor !== 'function' || EditorCtor.__nookFullEditor) return EditorCtor;
    function NookFullEditor(config) {
      const cfg = enrich(config);
      const editor = new EditorCtor(cfg);
      Promise.resolve(editor.isReady).then(() => enhanceSurface(editor, cfg)).catch(() => {});
      return editor;
    }
    NookFullEditor.prototype = EditorCtor.prototype;
    try { Object.setPrototypeOf(NookFullEditor, EditorCtor); } catch (_) {}
    Object.defineProperty(NookFullEditor, '__nookFullEditor', { value: true });
    return NookFullEditor;
  }

  function installNow() {
    if (typeof g.EditorJS === 'function' && !g.EditorJS.__nookFullEditor) { g.EditorJS = wrap(g.EditorJS); return true; }
    return !!(g.EditorJS && g.EditorJS.__nookFullEditor);
  }

  if (!installNow()) {
    let current = g.EditorJS;
    try {
      const desc = Object.getOwnPropertyDescriptor(g, 'EditorJS');
      if (!desc || desc.configurable) {
        Object.defineProperty(g, 'EditorJS', { configurable: true, enumerable: true, get() { return current; }, set(v) { current = wrap(v); } });
      }
    } catch (_) {}
    let attempts = 0;
    const timer = setInterval(() => { attempts += 1; if (installNow() || attempts > 600) clearInterval(timer); }, 25);
  }

  g.NookEditorJSFull = { enrichConfig: enrich, tools: FullTools, version: '2026-08-14-full-2' };
})(window);
