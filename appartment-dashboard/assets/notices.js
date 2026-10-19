/* global appartmentNotices */
(() => {
    'use strict';
    const config = window.appartmentNotices;
    const panel = document.getElementById('appartment-notice-panel');
    if (!config || !panel) return;
    const overlay = document.getElementById('appartment-notice-overlay');
    const live = document.getElementById('appartment-notice-live');
    const close = document.getElementById('appartment-notice-close');
    const toggle = document.getElementById('appartment-notice-toggle');
    const notices = new Map();
    const sent = new WeakMap();
    let hidden = true, previousFocus, timer, saving = false;
    try { hidden = localStorage.getItem(config.storage) !== 'show'; } catch (_) { /* Storage is optional. */ }
    function preference() {
        document.documentElement.classList.toggle('appartment-hide-notices', hidden);
        toggle.textContent = hidden ? 'Mostra avvisi nelle pagine' : 'Nascondi avvisi nelle pagine';
        toggle.setAttribute('aria-pressed', String(hidden));
    }
    preference();
    function updateCounts() {
        for (const [node, marker] of notices) {
            if (!node.isConnected) { marker.remove(); notices.delete(node); }
        }
        document.querySelectorAll('.appartment-notice-count').forEach(node => { node.textContent = notices.size; });
        document.getElementById('appartment-notice-empty').hidden = notices.size > 0;
    }
    function content(node) {
        const copy = node.cloneNode(true);
        copy.querySelectorAll('script, style, button, input, select, textarea, .notice-dismiss').forEach(el => el.remove());
        return (copy.textContent || '').trim().slice(0, 12000);
    }
    function scan() {
        const root = document.getElementById('wpbody-content');
        if (!root) return;
        root.querySelectorAll('.notice, div.updated, div.error, .update-nag').forEach(node => {
            // Leave form validation, editor messages, list/table status and inline UI in place.
            if (notices.has(node) || node.hidden || node.style.display === 'none' || node.closest('#appartment-notice-panel, .appartment-notice-archive, form, table, .inline, .components-notice, .editor-notices, .block-editor, #poststuff') || node.parentElement.closest('.notice, div.updated, div.error, .update-nag')) return;
            const marker = document.createComment('appartment-notice');
            node.before(marker);
            node.classList.add('appartment-managed-notice');
            notices.set(node, marker);
            if (!panel.hidden) live.append(node);
        });
        updateCounts();
    }
    function setOpen(open) {
        if (open === !panel.hidden) return;
        if (open) {
            previousFocus = document.activeElement;
            scan();
            notices.forEach((marker, node) => live.append(node));
        } else {
            notices.forEach((marker, node) => { if (node.isConnected && marker.isConnected) marker.after(node); });
        }
        panel.hidden = overlay.hidden = !open;
        document.body.classList.toggle('appartment-notice-open', open);
        if (open) close.focus(); else if (previousFocus?.isConnected) previousFocus.focus();
    }
    document.getElementById('appartment-notice-launcher').addEventListener('click', () => setOpen(true));
    document.querySelector('#wp-admin-bar-appartment-notices > a')?.addEventListener('click', event => { event.preventDefault(); setOpen(true); });
    close.addEventListener('click', () => setOpen(false));
    overlay.addEventListener('click', () => setOpen(false));
    toggle.addEventListener('click', () => {
        hidden = !hidden;
        try { localStorage.setItem(config.storage, hidden ? 'hide' : 'show'); } catch (_) { /* Storage is optional. */ }
        preference();
    });
    panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); setOpen(false); }
        if (event.key === 'Tab') {
            const nodes = [...panel.querySelectorAll('a[href], button, input, select, textarea, [tabindex="0"]')].filter(node => !node.disabled && node.getClientRects().length);
            const first = nodes[0], last = nodes[nodes.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    async function persist() {
        if (saving) return;
        const batch = [];
        notices.forEach((marker, node) => {
            const text = content(node);
            if (!text || sent.get(node) === text || batch.length >= 10) return;
            const type = node.matches('.notice-error, .error') ? 'error' : node.matches('.notice-warning, .update-nag') ? 'warning' : node.matches('.notice-success, .updated') ? 'success' : 'info';
            batch.push({ node, text, type, source: config.source, screen: config.screen });
        });
        if (!batch.length) return;
        saving = true;
        try {
            const response = await fetch(config.ajax, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ action: 'appartment_collect_notices', nonce: config.nonce, context: config.context, items: JSON.stringify(batch.map(({node, ...item}) => item)) }) });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error('Archive unavailable');
            batch.forEach(({node, text}) => sent.set(node, text));
            document.getElementById('appartment-notice-save-status').textContent = 'Storico aggiornato. Gli avvisi appena raccolti appariranno nello storico alla prossima apertura.';
        } catch (_) {
            document.getElementById('appartment-notice-save-status').textContent = 'Storico non aggiornato. Gli avvisi restano consultabili qui; ricarica la pagina per riprovare.';
        } finally { saving = false; }
        // Drain larger collections in bounded requests; do not automatically retry failures.
        if (batch.every(({node, text}) => sent.get(node) === text)) setTimeout(persist, 250);
    }
    const observer = new MutationObserver(records => {
        // Ignore our own counter/status updates to prevent observer loops.
        if (!records.some(record => {
            const parent = record.target.nodeType === 1 ? record.target : record.target.parentElement;
            return parent && !parent.closest('.appartment-notice-count, #appartment-notice-save-status, .appartment-notice-archive, #appartment-notice-empty');
        })) return;
        clearTimeout(timer);
        timer = setTimeout(() => { scan(); persist(); }, 250);
    });
    observer.observe(document.getElementById('wpbody-content') || document.body, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['class', 'style', 'hidden'] });
    // The drawer is outside wpbody-content; observe updates/dismissals there too.
    observer.observe(live, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['class', 'style', 'hidden'] });
    const search = document.getElementById('appartment-notice-search');
    const filter = document.getElementById('appartment-notice-filter');
    function filterArchive() {
        let visible = 0;
        document.querySelectorAll('.appartment-archive-item').forEach(item => {
            item.hidden = !(item.textContent.toLocaleLowerCase().includes(search.value.toLocaleLowerCase()) && (!filter.value || item.dataset.type === filter.value));
            if (!item.hidden) visible++;
        });
        const empty = document.getElementById('appartment-archive-empty');
        empty.hidden = visible > 0;
        empty.textContent = 'Nessun avviso corrisponde ai filtri selezionati.';
    }
    search?.addEventListener('input', filterArchive);
    filter?.addEventListener('change', filterArchive);
    scan();
    persist();
})();
