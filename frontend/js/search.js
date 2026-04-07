// ============================================================
//  UnivThèqueFs — Module Recherche
//  frontend/js/search.js
// ============================================================

let searchPage = 1;
let searchTimeout;

// ── Recherche globale (navbar) ──
document.getElementById('globalSearch')?.addEventListener('input', function() {
  clearTimeout(searchTimeout);
  const q = this.value.trim();
  const box = document.getElementById('searchResults');
  if (q.length < 2) { box.classList.remove('active'); return; }
  searchTimeout = setTimeout(() => quickSearch(q, box), 350);
});

document.addEventListener('click', e => {
  if (!e.target.closest('.search-box'))
    document.getElementById('searchResults')?.classList.remove('active');
});

async function quickSearch(q, container) {
  try {
    const res  = await fetch(`${API}/documents?q=${encodeURIComponent(q)}&limit=5`);
    const data = await res.json();
    container.innerHTML = '';
    if (!data.data?.length) {
      container.innerHTML = '<div class="search-result-item" style="color:var(--text-muted);font-size:.85rem">Aucun résultat</div>';
    } else {
      data.data.forEach(doc => {
        const el = document.createElement('div');
        el.className = 'search-result-item';
        el.innerHTML = `
          <div style="width:34px;height:34px;border-radius:8px;background:#EFF6FF;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fas fa-file-alt" style="color:var(--primary);font-size:.85rem"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.88rem;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(doc.titre)}</div>
            <div style="font-size:.75rem;color:var(--text-muted)">${doc.niveau} · S${doc.semestre} · ${escHtml(doc.ue_code)}</div>
          </div>
          <span style="font-size:.72rem;font-weight:600;padding:3px 8px;border-radius:4px;background:#EFF6FF;color:var(--primary)">${doc.type_code}</span>`;
        el.addEventListener('click', () => { window.location.href = `pages/document.html?id=${doc.id}`; });
        container.appendChild(el);
      });
    }
    container.classList.add('active');
  } catch { container.classList.remove('active'); }
}

// ── Recherche avancée ──
async function performSearch() {
  searchPage = 1;
  await _doSearch();
}

async function _doSearch() {
  const q         = document.getElementById('adv-keyword')?.value.trim() || '';
  const niveau    = document.getElementById('adv-niveau')?.value || '';
  const semestre  = document.getElementById('adv-semestre')?.value || '';
  const typeDoc   = document.getElementById('adv-type')?.value || '';
  const profId    = document.getElementById('adv-prof')?.value || '';
  const anneeId   = document.getElementById('adv-annee')?.value || '';

  const params = new URLSearchParams({ limit: 12, page: searchPage });
  if (q)        params.set('q', q);
  if (niveau)   params.set('niveau', niveau);
  if (semestre) params.set('semestre', semestre);
  if (typeDoc)  params.set('type_doc_id', typeDoc);
  if (profId)   params.set('professeur_id', profId);
  if (anneeId)  params.set('annee_id', anneeId);

  const container = document.getElementById('searchResultsContainer');
  const list      = document.getElementById('resultsList');
  const countEl   = document.getElementById('resultsCount');
  const pagEl     = document.getElementById('paginationContainer');

  container.style.display = 'block';
  list.innerHTML = '<div style="text-align:center;padding:40px;color:rgba(255,255,255,.5)"><i class="fas fa-circle-notch fa-spin fa-2x"></i></div>';

  try {
    const res  = await fetch(`${API}/documents?${params}`);
    const data = await res.json();

    countEl.textContent = `${data.total} document${data.total !== 1 ? 's' : ''} trouvé${data.total !== 1 ? 's' : ''}`;
    list.innerHTML = '';

    if (!data.data?.length) {
      list.innerHTML = '<div style="text-align:center;padding:40px;color:rgba(255,255,255,.5)">Aucun document ne correspond à vos critères.</div>';
      pagEl.innerHTML = '';
      return;
    }

    const colorMap = {
      'COURS':'#3B82F6','TD':'#10B981','TP':'#8B5CF6',
      'CC':'#F59E0B','EXAM_SN':'#EF4444','EXAM_SR':'#EC4899','TPE':'#06B6D4'
    };

    data.data.forEach(doc => {
      const c   = colorMap[doc.type_code] || '#64748B';
      const el  = document.createElement('div');
      el.className = 'doc-result-item';
      el.innerHTML = `
        <div class="doc-result-icon" style="background:${c}22;color:${c}">
          <i class="fas ${doc.icone || 'fa-file'}"></i>
        </div>
        <div class="doc-result-info">
          <div class="doc-result-title">${escHtml(doc.titre)}</div>
          <div class="doc-result-meta">
            <span><i class="fas fa-layer-group"></i> ${doc.niveau}</span>
            <span>Semestre ${doc.semestre}</span>
            <span>${escHtml(doc.ue_code)} — ${escHtml(doc.ue_intitule)}</span>
            ${doc.professeur ? `<span><i class="fas fa-user-tie"></i> ${escHtml(doc.professeur)}</span>` : ''}
            ${doc.annee ? `<span><i class="fas fa-calendar"></i> ${doc.annee}</span>` : ''}
          </div>
        </div>
        <div class="doc-result-actions">
          <span style="font-size:.72rem;padding:4px 10px;border-radius:6px;background:${c}22;color:${c};font-weight:700">${doc.type_code}</span>
          <button onclick="downloadDoc(${doc.id},event)" style="width:34px;height:34px;border-radius:8px;background:var(--accent);color:#fff;border:none;cursor:pointer;font-size:.8rem">
            <i class="fas fa-download"></i>
          </button>
        </div>`;
      el.addEventListener('click', () => { window.location.href = `pages/document.html?id=${doc.id}`; });
      list.appendChild(el);
    });

    // Pagination
    renderPagination(pagEl, data.page, data.pages);

  } catch {
    list.innerHTML = '<div style="text-align:center;padding:40px;color:rgba(255,255,255,.5)">Erreur de connexion. Vérifiez que XAMPP est démarré.</div>';
  }
}

function renderPagination(container, current, total) {
  container.innerHTML = '';
  if (total <= 1) return;
  const make = (label, page, active=false, disabled=false) => {
    const b = document.createElement('button');
    b.className = `page-btn${active?' active':''}`;
    b.innerHTML = label; b.disabled = disabled;
    b.onclick = async () => { searchPage = page; await _doSearch(); };
    return b;
  };
  if (current > 1) container.appendChild(make('<i class="fas fa-chevron-left"></i>', current-1));
  for (let i=1; i<=total; i++) {
    if (total > 7 && Math.abs(i-current) > 2 && i !== 1 && i !== total) {
      if (i === 2 || i === total-1) container.appendChild(make('…', i, false, true));
      continue;
    }
    container.appendChild(make(i, i, i===current));
  }
  if (current < total) container.appendChild(make('<i class="fas fa-chevron-right"></i>', current+1));
}

function resetSearch() {
  ['adv-keyword','adv-niveau','adv-semestre','adv-type','adv-prof','adv-annee'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  const container = document.getElementById('searchResultsContainer');
  if (container) container.style.display = 'none';
}
