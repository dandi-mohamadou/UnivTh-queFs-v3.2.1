// ============================================================
//  UnivThèqueFs — Script principal
//  frontend/js/main.js
// ============================================================

// Chemin API dynamique — fonctionne quel que soit le nom du dossier
const _base = window.location.pathname.split('/frontend/')[0];
const API = _base + '/backend/api/index.php';
let currentUser = null;

// ── Auth Token ──
const getToken = () => localStorage.getItem('ut_token');
const setToken = (t) => localStorage.setItem('ut_token', t);
const clearToken = () => localStorage.removeItem('ut_token');

// ── Navbar scroll ──
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
});

// ── Hamburger ──
document.getElementById('hamburger')?.addEventListener('click', () => {
  document.getElementById('mobileMenu').classList.toggle('open');
});

// ── Counter animation ──
function animateCounters() {
  document.querySelectorAll('.stat-num').forEach(el => {
    const target = +el.dataset.target;
    const step = Math.ceil(target / 60);
    let current = 0;
    const tick = () => {
      current = Math.min(current + step, target);
      el.textContent = current;
      if (current < target) requestAnimationFrame(tick);
    };
    tick();
  });
}
const heroObserver = new IntersectionObserver(entries => {
  if (entries[0].isIntersecting) { animateCounters(); heroObserver.disconnect(); }
}, { threshold: .5 });
const heroStats = document.querySelector('.hero-stats');
if (heroStats) heroObserver.observe(heroStats);


// ── Charger les stats depuis l'API ──
async function loadDynamicStats() {
  try {
    const res  = await fetch(`${API}/stats`);
    const data = await res.json();
    const map = {
      'stat-documents': data.total_documents ?? data.publies,
      'stat-ues':       data.total_ues       ?? 36,
      'stat-profs':     data.total_profs     ?? 10,
      'stat-niveaux':   3,
    };
    document.querySelectorAll('.stat-num[data-target]').forEach(el => {
      const key = el.closest('.stat-item')?.dataset.key;
      if (key && map[key] !== undefined) {
        el.dataset.target = map[key];
      }
    });
  } catch {}
}

// ── Load recent documents ──
async function loadRecentDocs() {
  const container = document.getElementById('recentDocsList');
  if (!container) return;
  try {
    const res  = await fetch(`${API}/documents?limit=6&page=1`);
    const data = await res.json();
    container.innerHTML = '';
    if (!data.data?.length) {
      container.innerHTML = '<p style="color:var(--text-muted);text-align:center;grid-column:1/-1;padding:40px">Aucun document disponible pour le moment.</p>';
      return;
    }
    data.data.forEach(doc => container.appendChild(createDocCard(doc)));
  } catch {
    container.innerHTML = '<p style="color:var(--text-muted);text-align:center;grid-column:1/-1;padding:40px">Connexion au serveur impossible. Assurez-vous que XAMPP est lancé.</p>';
  }
}

function createDocCard(doc) {
  const colorMap = {
    'COURS':'#3B82F6','TD':'#10B981','TP':'#8B5CF6',
    'CC':'#F59E0B','EXAM_SN':'#EF4444','EXAM_SR':'#EC4899','TPE':'#06B6D4'
  };
  const c = colorMap[doc.type_code] || '#64748B';
  const div = document.createElement('div');
  div.className = 'doc-card';
  div.innerHTML = `
    <div class="doc-card__header">
      <div class="doc-card__icon" style="background:${c}22;color:${c}">
        <i class="fas ${doc.icone || 'fa-file'}"></i>
      </div>
      <span class="doc-card__badge" style="background:${c}22;color:${c}">${doc.type_intitule || doc.type_code}</span>
    </div>
    <h4 class="doc-card__title">${escHtml(doc.titre)}</h4>
    <div class="doc-card__meta">
      <span class="doc-card__tag"><i class="fas fa-layer-group"></i> ${doc.niveau}</span>
      <span class="doc-card__tag"><i class="fas fa-calendar-alt"></i> S${doc.semestre}</span>
      <span class="doc-card__tag">${escHtml(doc.ue_code)}</span>
      ${doc.professeur ? `<span class="doc-card__tag">${escHtml(doc.professeur)}</span>` : ''}
      ${doc.format_fichier ? `<span class="doc-card__tag">${doc.format_fichier}</span>` : ''}
    </div>
    <div class="doc-card__footer">
      <div class="doc-card__stats">
        <span><i class="fas fa-eye"></i> ${doc.nb_vues || 0}</span>
        <span><i class="fas fa-download"></i> ${doc.nb_telechargemens || 0}</span>
        ${doc.annee ? `<span>${doc.annee}</span>` : ''}
      </div>
      <button class="doc-card__dl" onclick="downloadDoc(${doc.id},event)" title="Télécharger">
        <i class="fas fa-download"></i>
      </button>
    </div>`;
  div.addEventListener('click', () => openDocModal(doc.id));
  return div;
}

async function downloadDoc(id, e) {
  e?.stopPropagation();
  if (!getToken()) { showAuthModal(); showToast('Connectez-vous pour télécharger', 'info'); return; }
  window.open(`${API}/documents/${id}/download`, '_blank');
}

async function openDocModal(id) {
  // Simple view: naviguer vers la page de détail
  window.location.href = `pages/document.html?id=${id}`;
}

// ── Auth Modal ──
function showAuthModal() {
  document.getElementById('authModal').classList.add('open');
}
function hideAuthModal() {
  document.getElementById('authModal').classList.remove('open');
}
function closeModal(e) {
  if (e.target.id === 'authModal') hideAuthModal();
}
function switchTab(tab) {
  document.querySelectorAll('.modal-tab').forEach((t,i) =>
    t.classList.toggle('active', (i===0 && tab==='login')||(i===1 && tab==='register')));
  document.getElementById('tab-login').style.display  = tab==='login'  ? '' : 'none';
  document.getElementById('tab-register').style.display = tab==='register' ? '' : 'none';
}

async function doLogin() {
  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value;
  const errEl = document.getElementById('loginError');
  errEl.style.display = 'none';
  if (!email || !password) { errEl.textContent = 'Veuillez remplir tous les champs.'; errEl.style.display='block'; return; }
  try {
    const res  = await fetch(`${API}/auth/login`, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({email, password, remember_me: document.getElementById('rememberMe')?.checked || false})
    });
    const data = await res.json();
    if (data.success) {
      setToken(data.token);
      currentUser = data.user;
      hideAuthModal();
      updateAuthUI();
      showToast(`Bienvenue, ${data.user.prenom} !`, 'success');
    } else {
      errEl.textContent = data.message || 'Identifiants incorrects';
      errEl.style.display = 'block';
    }
  } catch { errEl.textContent = 'Erreur serveur'; errEl.style.display='block'; }
}

async function doRegister() {
  const nom = document.getElementById('regNom').value.trim();
  const prenom = document.getElementById('regPrenom').value.trim();
  const email = document.getElementById('regEmail').value.trim();
  const password = document.getElementById('regPassword').value;
  const niveau_id = document.getElementById('regNiveau').value;
  const errEl = document.getElementById('regError');
  errEl.style.display = 'none';
  if (!nom||!prenom||!email||!password) { errEl.textContent='Tous les champs sont requis'; errEl.style.display='block'; return; }
  try {
    const res  = await fetch(`${API}/auth/register`, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({nom, prenom, email, mot_de_passe: password, niveau_id})
    });
    const data = await res.json();
    if (data.success) {
      showToast('Compte créé ! Connectez-vous.', 'success');
      switchTab('login');
    } else {
      errEl.textContent = data.message || 'Erreur inscription';
      errEl.style.display = 'block';
    }
  } catch { errEl.textContent = 'Erreur serveur'; errEl.style.display='block'; }
}

function updateAuthUI() {
  const btn = document.getElementById('authBtn');
  const adminLink = document.getElementById('adminLink');
  if (currentUser) {
    btn.innerHTML = `<i class="fas fa-user"></i> ${currentUser.prenom}`;
    btn.onclick = doLogout;
    if (currentUser.role === 'admin') adminLink.style.display = '';
  } else {
    btn.innerHTML = `<i class="fas fa-user"></i> Connexion`;
    btn.onclick = showAuthModal;
    adminLink.style.display = 'none';
  }
}

function doLogout() {
  clearToken(); currentUser = null;
  updateAuthUI();
  showToast('Déconnecté', 'info');
}

// ── Toast ──
function showToast(msg, type='info') {
  const toast = document.getElementById('toast');
  const icons = { success:'fa-check-circle', error:'fa-exclamation-circle', info:'fa-info-circle' };
  toast.innerHTML = `<i class="fas ${icons[type]||icons.info}"></i> ${msg}`;
  toast.className = `toast ${type} show`;
  setTimeout(() => toast.classList.remove('show'), 3500);
}

// ── Utility ──
function escHtml(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function filterByType(typeId) {
  document.getElementById('adv-type').value = typeId;
  document.getElementById('search-section').scrollIntoView({behavior:'smooth'});
  setTimeout(performSearch, 400);
}

function loadMoreRecent() {
  showToast('Fonctionnalité disponible avec le serveur XAMPP', 'info');
}

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
  loadRecentDocs();
  // Restore session
  const token = getToken();
  if (token) {
    fetch(`${API}/auth/me`, { headers: { Authorization: `Bearer ${token}` } })
      .then(r => r.json())
      .then(d => { if (d.success) { currentUser = d.user; updateAuthUI(); } })
      .catch(() => {});
  }
});
