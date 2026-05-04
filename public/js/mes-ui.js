/**
 * MES UI helpers — toast, confirm, filtri persistenti, loading states.
 * Carica via mes.blade.php / app.blade.php.
 *
 * Uso:
 *   MES.toast('Salvato', 'success');
 *   MES.toast('Errore', 'danger', 5000);
 *   MES.confirm('Eliminare?', 'Azione irreversibile').then(ok => { if (ok) ... });
 *   MES.persistFilters('owner', ['filterCommessa', 'filterCliente']);
 *   MES.loading(buttonEl, true);
 */
(function (window) {
    'use strict';

    const MES = window.MES = window.MES || {};

    // ======== TOAST ========
    let toastContainer;
    function getToastContainer() {
        if (toastContainer) return toastContainer;
        toastContainer = document.createElement('div');
        toastContainer.id = 'mes-toast-container';
        toastContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;display:flex;flex-direction:column;gap:8px;max-width:380px;';
        document.body.appendChild(toastContainer);
        return toastContainer;
    }

    const toastColors = {
        success: { bg: '#d1fae5', border: '#10b981', icon: '✓', color: '#065f46' },
        danger:  { bg: '#fee2e2', border: '#dc2626', icon: '✕', color: '#7f1d1d' },
        warning: { bg: '#fef3c7', border: '#f59e0b', icon: '⚠', color: '#78350f' },
        info:    { bg: '#dbeafe', border: '#2563eb', icon: 'ℹ', color: '#1e3a8a' },
    };

    MES.toast = function (msg, type = 'info', duration = 3500) {
        const c = getToastContainer();
        const cfg = toastColors[type] || toastColors.info;
        const el = document.createElement('div');
        el.style.cssText = `
            background:${cfg.bg}; border-left:4px solid ${cfg.border}; color:${cfg.color};
            padding:12px 14px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.12);
            font-size:13px; display:flex; align-items:flex-start; gap:10px;
            animation:mes-toast-in 0.2s ease-out; max-width:100%; word-break:break-word;`;
        el.innerHTML = `
            <span style="font-size:16px; line-height:1.3; font-weight:bold;">${cfg.icon}</span>
            <div style="flex:1;">${escapeHtml(msg)}</div>
            <button style="background:none;border:none;color:${cfg.color};cursor:pointer;padding:0;font-size:18px;line-height:1;opacity:0.6;" aria-label="Chiudi">×</button>
        `;
        el.querySelector('button').addEventListener('click', () => removeToast(el));
        c.appendChild(el);
        if (duration > 0) setTimeout(() => removeToast(el), duration);
        return el;
    };

    function removeToast(el) {
        el.style.transition = 'opacity 0.2s, transform 0.2s';
        el.style.opacity = '0';
        el.style.transform = 'translateX(20px)';
        setTimeout(() => el.remove(), 200);
    }

    // Iniettiamo CSS animazione una sola volta
    if (!document.getElementById('mes-toast-css')) {
        const s = document.createElement('style');
        s.id = 'mes-toast-css';
        s.textContent = `@keyframes mes-toast-in{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}`;
        document.head.appendChild(s);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // ======== CONFIRM (Promise-based) ========
    MES.confirm = function (title, body = '', okLabel = 'Conferma', okClass = 'btn-danger') {
        return new Promise(resolve => {
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:10001;display:flex;align-items:center;justify-content:center;';
            overlay.innerHTML = `
                <div style="background:#fff;border-radius:10px;max-width:480px;width:90%;padding:0;box-shadow:0 10px 30px rgba(0,0,0,0.25);overflow:hidden;">
                    <div style="padding:18px 20px;border-bottom:1px solid #e5e7eb;">
                        <h5 style="margin:0;font-size:16px;font-weight:600;">${escapeHtml(title)}</h5>
                    </div>
                    <div style="padding:18px 20px;color:#374151;font-size:14px;">${escapeHtml(body)}</div>
                    <div style="padding:14px 20px;background:#f9fafb;display:flex;justify-content:flex-end;gap:8px;">
                        <button class="btn btn-secondary btn-sm mes-cancel">Annulla</button>
                        <button class="btn ${escapeHtml(okClass)} btn-sm mes-ok">${escapeHtml(okLabel)}</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
            const close = (val) => { overlay.remove(); resolve(val); };
            overlay.querySelector('.mes-cancel').addEventListener('click', () => close(false));
            overlay.querySelector('.mes-ok').addEventListener('click', () => close(true));
            overlay.addEventListener('click', e => { if (e.target === overlay) close(false); });
            overlay.querySelector('.mes-ok').focus();
        });
    };

    // ======== FILTRI PERSISTENTI localStorage ========
    MES.persistFilters = function (namespace, inputIds) {
        const key = 'mes_filters_' + namespace;
        // Restore
        try {
            const saved = JSON.parse(localStorage.getItem(key) || '{}');
            inputIds.forEach(id => {
                const el = document.getElementById(id);
                if (el && saved[id] !== undefined) el.value = saved[id];
            });
        } catch (e) {}
        // Save on change
        inputIds.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const evt = (el.tagName === 'SELECT') ? 'change' : 'input';
            el.addEventListener(evt, () => {
                try {
                    const cur = JSON.parse(localStorage.getItem(key) || '{}');
                    cur[id] = el.value;
                    localStorage.setItem(key, JSON.stringify(cur));
                } catch (e) {}
            });
        });
    };

    MES.clearFilters = function (namespace) {
        try { localStorage.removeItem('mes_filters_' + namespace); } catch (e) {}
    };

    // ======== LOADING button helper ========
    MES.loading = function (btn, on) {
        if (!btn) return;
        if (on) {
            btn.dataset.origText = btn.dataset.origText || btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Attendi...';
        } else {
            if (btn.dataset.origText) btn.innerHTML = btn.dataset.origText;
            btn.disabled = false;
        }
    };

    // ======== Fetch wrapper con feedback ========
    MES.fetch = async function (url, options = {}, opts = {}) {
        try {
            const r = await fetch(url, options);
            if (r.status === 419) { MES.toast('Sessione scaduta, ricarica pagina', 'warning'); throw new Error('csrf'); }
            if (r.status === 429) { MES.toast('Troppe richieste, attendi', 'warning'); throw new Error('throttle'); }
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r;
        } catch (e) {
            if (opts.silent !== true) MES.toast(opts.errorMsg || 'Errore di connessione', 'danger');
            throw e;
        }
    };

})(window);
