/**
 * cookies.js — Gestion cookies & sessions côté client
 * UnivThèqueFs v3.1
 */

// ── Helpers cookies JS ────────────────────────────────────────
const Cookies = {
  set(name, value, days = 365) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)};expires=${expires};path=/;SameSite=Lax`;
  },
  get(name) {
    const v = document.cookie.match(`(^|;)\\s*${name}\\s*=\\s*([^;]+)`);
    return v ? decodeURIComponent(v.pop()) : null;
  },
  remove(name) {
    document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/`;
  }
};

// ── CONSENTEMENT COOKIES (RGPD) ───────────────────────────────
const CookieConsent = {
  COOKIE_NAME: 'ut_consent',

  init() {
    if (Cookies.get(this.COOKIE_NAME) !== null) return; // déjà choisi
    this._showBanner();
  },

  _showBanner() {
    const banner = document.createElement('div');
    banner.id = 'cookieBanner';
    banner.innerHTML = `
      <div style="
        position:fixed;bottom:0;left:0;right:0;z-index:9999;
        background:#1E293B;color:#E2E8F0;
        padding:16px 24px;
        display:flex;align-items:center;justify-content:space-between;
        flex-wrap:wrap;gap:12px;
        box-shadow:0 -4px 20px rgba(0,0,0,.3);
        font-family:Arial,sans-serif;font-size:.88rem;
        animation:slideUp .4s ease">
        <div style="flex:1;min-width:260px">
          <span style="font-size:1.1rem">🍪</span>
          <strong style="color:#fff;margin-left:6px">Ce site utilise des cookies</strong>
          <p style="margin:4px 0 0;color:#94A3B8;font-size:.82rem">
            Nous utilisons des cookies pour mémoriser votre connexion, vos préférences
            et votre historique de navigation. Aucune donnée n'est partagée avec des tiers.
          </p>
        </div>
        <div style="display:flex;gap:10px;flex-shrink:0">
          <button id="cookieRefuse" style="
            padding:9px 18px;border-radius:6px;border:1px solid #475569;
            background:transparent;color:#94A3B8;cursor:pointer;font-size:.85rem">
            Refuser
          </button>
          <button id="cookieAccept" style="
            padding:9px 18px;border-radius:6px;border:none;
            background:#2563EB;color:#fff;cursor:pointer;font-size:.85rem;font-weight:600">
            ✓ Accepter
          </button>
        </div>
      </div>`;

    const style = document.createElement('style');
    style.textContent = `@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}`;
    document.head.appendChild(style);
    document.body.appendChild(banner);

    document.getElementById('cookieAccept').onclick = () => this._accept();
    document.getElementById('cookieRefuse').onclick = () => this._refuse();
  },

  _accept() {
    Cookies.set(this.COOKIE_NAME, '1', 365);
    document.getElementById('cookieBanner')?.remove();
    // Notifier l'API
    const _base = window.location.pathname.split('/frontend/')[0];
    fetch(_base + '/backend/api/index.php/consent', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({accepted: true})
    }).catch(()=>{});
    // Activer les fonctionnalités optionnelles
    History.init();
    ThemeManager.init();
  },

  _refuse() {
    Cookies.set(this.COOKIE_NAME, '0', 365);
    document.getElementById('cookieBanner')?.remove();
  },

  hasConsent: () => Cookies.get('ut_consent') === '1',
};

// ── THÈME SOMBRE / CLAIR ──────────────────────────────────────
const ThemeManager = {
  COOKIE: 'ut_theme',

  init() {
    const saved = Cookies.get(this.COOKIE) || 'light';
    this.apply(saved);
    this._addToggleButton();
  },

  apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    Cookies.set(this.COOKIE, theme, 365);
    const btn = document.getElementById('themeToggle');
    if (btn) btn.innerHTML = theme === 'dark'
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>';
  },

  toggle() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    this.apply(current === 'dark' ? 'light' : 'dark');
  },

  _addToggleButton() {
    if (document.getElementById('themeToggle')) return;
    const btn = document.createElement('button');
    btn.id = 'themeToggle';
    btn.title = 'Changer le thème';
    btn.innerHTML = '<i class="fas fa-moon"></i>';
    btn.style.cssText = `
      position:fixed;bottom:24px;right:24px;z-index:1000;
      width:44px;height:44px;border-radius:50%;border:none;
      background:var(--surface,#fff);
      box-shadow:0 2px 12px rgba(0,0,0,.15);
      cursor:pointer;font-size:1rem;
      display:flex;align-items:center;justify-content:center;
      transition:all .2s;color:var(--text,#1E293B)`;
    btn.onmouseover = () => btn.style.transform = 'scale(1.1)';
    btn.onmouseout  = () => btn.style.transform = 'scale(1)';
    btn.onclick = () => ThemeManager.toggle();
    document.body.appendChild(btn);
    // Appliquer thème sauvegardé
    const saved = Cookies.get(this.COOKIE);
    if (saved) this.apply(saved);
  }
};

// ── HISTORIQUE DE NAVIGATION ──────────────────────────────────
const History = {
  COOKIE: 'ut_history',
  MAX: 8,

  init() {
    this._renderWidget();
  },

  add(code, titre) {
    if (!CookieConsent.hasConsent()) return;
    let history = this.get();
    history = history.filter(h => h.code !== code);
    history.unshift({ code, titre, ts: Date.now() });
    history = history.slice(0, this.MAX);
    Cookies.set(this.COOKIE, JSON.stringify(history), 7);
    this._renderWidget();

    // Notifier l'API
    const _base = window.location.pathname.split('/frontend/')[0];
    fetch(_base + '/backend/api/index.php/history', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({code, titre})
    }).catch(()=>{});
  },

  get() {
    try { return JSON.parse(Cookies.get(this.COOKIE) || '[]'); }
    catch { return []; }
  },

  _renderWidget() {
    const history = this.get();
    if (!history.length) return;

    let widget = document.getElementById('historyWidget');
    if (!widget) {
      widget = document.createElement('div');
      widget.id = 'historyWidget';
      widget.style.cssText = `
        position:fixed;bottom:118px;right:24px;z-index:999;
        background:var(--surface,#fff);border-radius:12px;
        box-shadow:0 4px 20px rgba(0,0,0,.12);
        border:1px solid var(--border,#E2E8F0);
        width:220px;overflow:hidden;
        font-family:Arial,sans-serif;font-size:.82rem;
        display:none;`;
      document.body.appendChild(widget);

      const trigger = document.createElement('button');
      trigger.id = 'historyTrigger';
      trigger.title = 'Historique récent';
      trigger.style.cssText = `
        position:fixed;bottom:74px;right:24px;z-index:1000;
        width:36px;height:36px;border-radius:50%;border:none;
        background:var(--primary,#2563EB);color:#fff;
        cursor:pointer;font-size:.85rem;
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 2px 8px rgba(37,99,235,.4)`;
      trigger.innerHTML = '<i class="fas fa-history"></i>';
      trigger.onclick = () => {
        widget.style.display = widget.style.display === 'none' ? 'block' : 'none';
      };
      document.body.appendChild(trigger);
    }

    widget.innerHTML = `
      <div style="padding:10px 14px;border-bottom:1px solid var(--border,#E2E8F0);font-weight:700;color:var(--text,#1E293B)">
        <i class="fas fa-history" style="color:#2563EB"></i> Récemment consultés
      </div>
      ${history.map(h => `
        <div onclick="window.location.href='#'" style="
          padding:8px 14px;cursor:pointer;
          border-bottom:1px solid var(--border,#F1F5F9);
          transition:background .15s"
          onmouseover="this.style.background='#F8FAFC'"
          onmouseout="this.style.background=''">
          <div style="font-weight:600;color:#2563EB">${h.code}</div>
          <div style="color:var(--text-muted,#64748B);font-size:.78rem;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${h.titre}</div>
        </div>`).join('')}`;
  }
};

// ── REMEMBER ME (côté JS) ─────────────────────────────────────
const RememberMe = {
  /** À appeler lors du login si "remember me" est coché */
  isChecked() {
    return document.getElementById('rememberMe')?.checked || false;
  }
};

// ── INITIALISATION ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Toujours init le consentement
  CookieConsent.init();

  // Init conditionnels si consentement déjà donné
  if (CookieConsent.hasConsent()) {
    ThemeManager.init();
    History.init();
  }
});
