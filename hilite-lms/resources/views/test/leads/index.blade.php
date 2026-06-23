@extends('layouts.app')
@section('title', 'Leads')
@section('nav-leads', 'active')

@section('content')
<div class="page-header">
    <h1 id="leads-heading">Leads</h1>
    <p id="leads-subhead">Browse and manage lead engagements</p>
    <div class="header-actions">
        <a href="/test/leads/create" class="btn btn--primary btn--sm">✚ New Lead</a>
    </div>
</div>

{{-- Basic filter bar (always visible) --}}
<div class="filters-row">
    <div class="form-group" style="flex:2;min-width:200px;">
        <label for="filter-search">🔍 Search</label>
        <input type="text" id="filter-search" class="form-control"
            placeholder="Phone or name…"
            onkeydown="if(event.key==='Enter') loadLeads(1)">
    </div>
    <div class="form-group">
        <label for="filter-stage">Stage</label>
        <select id="filter-stage" class="form-control">
            <option value="">All Stages</option>
        </select>
    </div>
    <div class="form-group">
        <label for="filter-sort">Sort By</label>
        <select id="filter-sort" class="form-control" onchange="loadLeads(1)">
            <option value="last_activity_at|desc">Last Activity (Newest)</option>
            <option value="last_activity_at|asc">Last Activity (Oldest)</option>
            <option value="created_at|desc">Time Created (Newest)</option>
            <option value="created_at|asc">Time Created (Oldest)</option>
            <option value="lead_score|desc">Priority (Hot to Cold)</option>
            <option value="lead_score|asc">Priority (Cold to Hot)</option>
        </select>
    </div>
    <div class="form-group" style="min-width:unset;flex:0;">
        <label>&nbsp;</label>
        <button class="btn btn--primary" onclick="loadLeads(1)" id="apply-btn">Search</button>
    </div>
    <div class="form-group" style="min-width:unset;flex:0;">
        <label>&nbsp;</label>
        {{-- Progressive Disclosure: Advanced Filters toggle --}}
        <button class="advanced-toggle" id="adv-toggle"
            onclick="toggleAdvanced('adv-toggle','adv-panel')">
            <span class="toggle-label">Advanced Filters</span>
            <span class="toggle-arrow">▼</span>
        </button>
    </div>
</div>

{{-- Advanced panel (hidden by default — Progressive Disclosure) --}}
<div class="advanced-panel" id="adv-panel">
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:14px;">
            Advanced Filters
        </div>
        <div class="form-row">
            <div class="form-group" style="margin-bottom:0;">
                <label for="filter-sla">SLA Status</label>
                <select id="filter-sla" class="form-control">
                    <option value="">All</option>
                    <option value="breached">⚠ Breached</option>
                    <option value="ok">✓ Healthy</option>
                </select>
            </div>
            {{-- Source filter visible only for admin/manager --}}
            <div class="form-group admin-only" style="margin-bottom:0;" id="source-filter-group">
                <label for="filter-source">Lead Source</label>
                <select id="filter-source" class="form-control">
                    <option value="">All Sources</option>
                    <option value="manual">Manual</option>
                    <option value="web">Web</option>
                    <option value="referral">Referral</option>
                    <option value="webhook">Webhook</option>
                    <option value="csv">CSV Import</option>
                </select>
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;">
            <button class="btn btn--primary btn--sm" onclick="loadLeads(1)">Apply Advanced</button>
            <button class="btn btn--ghost btn--sm" onclick="clearFilters()">Clear All</button>
        </div>
    </div>
</div>

{{-- Results meta --}}
<div id="results-meta" style="font-size:12px;color:var(--text-muted);margin:10px 2px;display:flex;align-items:center;gap:8px;">
    <span id="results-count"></span>
    <span id="my-leads-tag" style="display:none;">
        <span class="badge badge--role" style="font-size:10px;">Showing my leads only</span>
    </span>
</div>

{{-- Table --}}
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Stage</th>
                <th>Priority</th>
                <th>SLA</th>
                <th>Last Activity</th>
                {{-- Extra columns hidden from salesperson --}}
                <th class="admin-only" id="col-source">Source</th>
                <th class="admin-only" id="col-assigned">Assigned To</th>
                <th class="admin-only" id="col-email">Email</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="leads-tbody">
            <tr><td colspan="10">
                <div class="loading-block"><span class="spinner"></span> Loading leads…</div>
            </td></tr>
        </tbody>
    </table>
</div>

<div class="pagination" id="pagination"></div>
@endsection

@section('scripts')
<script>
let stagesLoaded = false;

// Role-based adjustments
(function() {
    const cfg = window.__config || {};
    if (!cfg.canSeeGlobal) {
        document.getElementById('leads-heading').textContent   = 'My Leads';
        document.getElementById('leads-subhead').textContent   = 'Your personally assigned lead engagements';
        document.getElementById('my-leads-tag').style.display = 'inline-flex';
    }
})();

async function loadStages() {
    if (stagesLoaded) return;
    const res = await api('/pipeline-stages');
    if (!res) return;
    const data = await res.json();
    if (data.success) {
        const sel = document.getElementById('filter-stage');
        data.data.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id; opt.textContent = s.name;
            sel.appendChild(opt);
        });
        stagesLoaded = true;
    }
}

function clearFilters() {
    document.getElementById('filter-search').value = '';
    document.getElementById('filter-stage').value  = '';
    const slaEl = document.getElementById('filter-sla');
    if (slaEl) slaEl.value = '';
    const srcEl = document.getElementById('filter-source');
    if (srcEl) srcEl.value = '';
    loadLeads(1);
}

async function loadLeads(page = 1) {
    const cfg     = window.__config || {};
    const tbody   = document.getElementById('leads-tbody');
    const applyBtn = document.getElementById('apply-btn');
    const colCount = cfg.canSeeGlobal ? 11 : 8;

    tbody.innerHTML = `<tr><td colspan="${colCount}">
        <div class="loading-block"><span class="spinner"></span> Loading…</div>
    </td></tr>`;
    if (applyBtn) { applyBtn.disabled = true; applyBtn.textContent = '…'; }

    // Role-based API params
    let url = `/leads?page=${page}&per_page=20`;
    if (!cfg.canSeeGlobal) url += '&assigned_to_me=1';

    const search = document.getElementById('filter-search').value.trim();
    const stage  = document.getElementById('filter-stage').value;
    const slaEl  = document.getElementById('filter-sla');
    const srcEl  = document.getElementById('filter-source');
    const sortEl = document.getElementById('filter-sort');
    const sla    = slaEl?.value || '';
    const source = srcEl?.value || '';
    const sortVal= sortEl?.value || '';

    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (stage)  url += `&stage_id=${stage}`;
    if (sla === 'breached') url += '&sla_breached=1';
    if (sla === 'ok')       url += '&sla_breached=0';
    if (source) url += `&source=${source}`;
    
    if (sortVal) {
        const [sortBy, sortDir] = sortVal.split('|');
        url += `&sort_by=${sortBy}&sort_dir=${sortDir}`;
    }

    const res = await api(url);
    if (applyBtn) { applyBtn.disabled = false; applyBtn.textContent = 'Search'; }
    if (!res) return;
    const data = await res.json();

    if (data.success) {
        const meta = data.meta;
        if (meta) {
            const from = ((meta.current_page - 1) * meta.per_page) + 1;
            const to   = Math.min(meta.current_page * meta.per_page, meta.total);
            document.getElementById('results-count').textContent =
                `Showing ${from}–${to} of ${meta.total} leads`;
        }

        if (!data.data.length) {
            tbody.innerHTML = `<tr><td colspan="${colCount}">
                <div class="empty-state">
                    <span class="empty-state__icon">🔍</span>
                    <div class="empty-state__text">No leads found</div>
                    <div class="empty-state__sub">Try adjusting your search filters</div>
                </div>
            </td></tr>`;
            document.getElementById('pagination').innerHTML = '';
            return;
        }

        tbody.innerHTML = data.data.map(l => {
            const sc = l.stage?.color || '#7C3AED';
            const score = l.lead_score || 0;
            const rating = l.lead_rating || 'Cold';
            const badgeClass = 'badge--' + rating.toLowerCase().replace(' ', '-');

            // Base columns (all roles see these)
            let row = `<tr class="clickable" onclick="window.location='/test/leads/${l.engagement_id}'">
                <td>
                    <div style="font-weight:600;">${l.name || '<span style="color:var(--text-muted);">Unnamed</span>'}</div>
                    <div style="font-size:11px;color:var(--text-muted);">#${l.engagement_id}</div>
                </td>
                <td><code style="font-size:11px;">${l.phone_e164}</code></td>
                <td>
                    <span class="badge badge--stage"
                        style="background:${sc}15;color:${sc};border-color:${sc}30;">
                        ${l.stage?.name || '—'}
                    </span>
                </td>
                <td>
                    <span class="badge ${badgeClass}">${rating}</span>
                    <small style="color:var(--text-muted); margin-left:4px;">${score} / 100</small>
                </td>
                <td>
                    ${l.sla_breached
                        ? '<span class="badge badge--sla-breached">⚠ Breached</span>'
                        : '<span class="badge badge--sla-ok">✓ OK</span>'}
                </td>
                <td style="font-size:12px;color:var(--text-muted);">${relativeTime(l.last_activity_at)}</td>`;

            // Admin-only columns
            if (cfg.canSeeGlobal) {
                row += `
                <td>${l.source ? `<span class="badge badge--source">${l.source}</span>` : '<span style="color:var(--text-muted)">—</span>'}</td>
                <td>${l.assigned_to ? `<strong style="font-size:13px;">${l.assigned_to.name}</strong>` : '<span style="color:var(--text-muted);">Unassigned</span>'}</td>
                <td style="font-size:12px;color:var(--text-muted);">${l.email || '—'}</td>`;
            }

            // More menu (contextual, secondary actions — Progressive Disclosure)
            const menuId = `menu-${l.engagement_id}`;
            row += `
                <td style="font-size:12px;color:var(--text-muted);">${relativeTime(l.created_at)}</td>
                <td onclick="event.stopPropagation()">
                    <div class="more-menu">
                        <button class="more-menu__btn" onclick="openMoreMenu('${menuId}')" title="More actions">⋯</button>
                        <div class="more-menu__dropdown" id="${menuId}">
                            <div class="more-menu__item" onclick="window.location='/test/leads/${l.engagement_id}'">
                                👁 View Detail
                            </div>
                            <hr class="more-menu__divider">
                            <div class="more-menu__item" onclick="copyToClipboard('${l.phone_e164}')">
                                📋 Copy Phone
                            </div>
                        </div>
                    </div>
                </td>
            </tr>`;
            return row;
        }).join('');

        // Pagination
        if (meta) {
            const totalPages = Math.ceil(meta.total / meta.per_page);
            let ph = `<div class="pagination__info">Page ${meta.current_page} of ${totalPages}</div>`;
            ph += '<div class="pagination__controls">';
            if (meta.current_page > 1)
                ph += `<button class="btn btn--outline btn--sm" onclick="loadLeads(${meta.current_page - 1})">← Prev</button>`;
            if (meta.current_page < totalPages)
                ph += `<button class="btn btn--outline btn--sm" onclick="loadLeads(${meta.current_page + 1})">Next →</button>`;
            ph += '</div>';
            document.getElementById('pagination').innerHTML = ph;
        }
    } else {
        tbody.innerHTML = `<tr><td colspan="10">
            <div class="callout callout--danger" style="margin:16px;">⚠ ${data.message || 'Error loading leads'}</div>
        </td></tr>`;
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => toast('Phone copied!', 'success'));
    closeAllMenus();
}

loadStages();
loadLeads();
</script>
@endsection
