(() => {
  'use strict';

  // Nook UX v3.4 boot layer.

  const bridge = window.NookUXBridge = window.NookUXBridge || {};
  bridge.listCards = bridge.listCards || [];
  bridge.cards = bridge.cards || new Map();
  bridge.activeSpaceId = bridge.activeSpaceId || 0;
  bridge.defaultSpaceId = bridge.defaultSpaceId || 0;
  bridge.spaces = bridge.spaces || [];

  bridge.registerCards = bridge.registerCards || ((cards, append = false) => {
    if (!Array.isArray(cards)) return;
    if (!append) { bridge.listCards = []; bridge.cards = new Map(); }
    const known = new Set((bridge.listCards || []).map(card => Number(card?.id)).filter(Boolean));
    for (const card of cards) {
      const id = Number(card?.id || 0); if (!id) continue;
      bridge.cards.set(id, card);
      if (!known.has(id)) { bridge.listCards.push(card); known.add(id); }
    }
    document.dispatchEvent(new CustomEvent('nookux:list', {detail:{append:Boolean(append), ids:cards.map(c=>Number(c?.id||0)).filter(Boolean)}}));
  });
  bridge.booting = true;

  // Keep the gallery hidden briefly while the normal Nook application restores its
  // current space. UX v3 no longer monkey-patches window.fetch: doing so could block
  // the core application's startup requests on some installations.
  document.documentElement.classList.add('nook-ux-booting');
  bridge.nativeFetch = bridge.nativeFetch || window.fetch.bind(window);

  // Emergency fail-safe: never leave the gallery hidden if an add-on fails.
  window.setTimeout(() => {
    document.documentElement.classList.remove('nook-ux-booting');
    bridge.booting = false;
  }, 5000);
})();
