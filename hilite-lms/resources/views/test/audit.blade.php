@extends('layouts.app')
@section('title', 'Audit Logs')
@section('nav-audit', 'active')

@section('content')
<div class="page-header">
    <h1>Audit Logs</h1>
    <p>Full traceability of all changes across lead engagements</p>
</div>

{{-- Filters --}}
<div class="filters-row">
    <div class="form-group">
        <label for="filter-action">Action Type</label>
        <select id="filter-action" class="form-control">
            <option value="">All Actions</option>
            <option value="activity_logged">📝 Activity Logged</option>
            <option value="stage_changed">🔀 Stage Changed</option>
            <option value="lead_assigned">👤 Lead Assigned</option>
            <option value="lead_created">✚ Lead Created</option>
        </select>
    </div>
    <div class="form-group">
        <label for="filter-from">From Date</label>
        <input type="date" id="filter-from" class="form-control">
    </div>
    <div class="form-group">
        <label for="filter-to">To Date</label>
        <input type="date" id="filter-to" class="form-control">
    </div>
    <div class="form-group" style="min-width:unset;flex:0;">
        <label>&nbsp;</label>
        <button class="btn btn--primary" onclick="loadLogs(1)" id="apply-btn">Apply</button>
    </div>
    <div class="form-group" style="min-width:unset;flex:0;">
        <label>&nbsp;</label>
        <button class="btn btn--ghost" onclick="clearAuditFilters()">Clear</button>
    </div>
</div>

{{-- Results meta --}}
<div id="audit-meta" style="font-size:12px;color:var(--text-muted);margin-bottom:10px;padding:0 2px;"></div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Action</th>
                <th>Actor</th>
                <th>Lead</th>
                <th>Before</th>
                <th>After</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody id="logs-tbody">
            <tr><td colspan="7"><div class="loading-block"><span class="spinner"></span> Loading audit logs…</div></td></tr>
        </tbody>
    </table>
</div>

<div class="pagination" id="pagination"></div>
@endsection

@section('scripts')
<script>
// ─── Route Guard: Admin/Manager only ────────────────────────────
if (!requireRole(['admin', 'manager'])) {
    // Access denied block rendered by requireRole(); stop loading
    // eslint-disable-next-line no-throw-literal
    throw new Error('access_denied');
}

function clearAuditFilters() {
    document.getElementById('filter-action').value = '';
    document.getElementById('filter-from').value = '';
    document.getElementById('filter-to').value = '';
    loadLogs(1);
}

const ACTION_LABELS = {
    activity_logged: { icon: '📝', color: 'var(--accent)' },
    stage_changed:   { icon: '🔀', color: 'var(--warning)' },
    lead_assigned:   { icon: '👤', color: 'var(--info)' },
    lead_created:    { icon: '✚', color: 'var(--success)' },
};

function actionBadge(action) {
    const def = ACTION_LABELS[action] || { icon: '•', color: 'var(--text-muted)' };
    const label = action.replace(/_/g, ' ');
    return `<span class="badge" style="background:${def.color}15;color:${def.color};border:1px solid ${def.color}30;text-transform:capitalize;">
        ${def.icon} ${label}
    </span>`;
}

function jsonPill(obj) {
    if (!obj) return '<span style="color:var(--text-muted);">—</span>';
    const str = JSON.stringify(obj);
    return `<span title="${str.replace(/"/g, '&quot;')}" style="
        font-family:monospace;font-size:11px;
        color:var(--cyan);
        max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
        display:inline-block;vertical-align:bottom;
    ">${str}</span>`;
}

async function loadLogs(page = 1) {
    const tbody   = document.getElementById('logs-tbody');
    const applyBtn = document.getElementById('apply-btn');
    tbody.innerHTML = '<tr><td colspan="7"><div class="loading-block"><span class="spinner"></span> Loading…</div></td></tr>';
    if (applyBtn) { applyBtn.disabled = true; applyBtn.textContent = '…'; }

    let url = `/audit-logs?page=${page}&per_page=25`;
    const action = document.getElementById('filter-action').value;
    const from   = document.getElementById('filter-from').value;
    const to     = document.getElementById('filter-to').value;

    if (action) url += `&action=${action}`;
    if (from)   url += `&from_date=${from}`;
    if (to)     url += `&to_date=${to}`;

    const res = await api(url);
    if (applyBtn) { applyBtn.disabled = false; applyBtn.textContent = 'Apply'; }
    if (!res) return;
    const data = await res.json();

    if (data.success && data.data.length) {
        const meta = data.meta;
        if (meta) {
            document.getElementById('audit-meta').textContent =
                `Showing ${((meta.current_page-1)*meta.per_page)+1}–${Math.min(meta.current_page*meta.per_page, meta.total)} of ${meta.total} entries`;
        }

        tbody.innerHTML = data.data.map(l => `
            <tr>
                <td><span style="color:var(--text-muted);font-size:12px;font-weight:600;">#${l.id}</span></td>
                <td>${actionBadge(l.action)}</td>
                <td>
                    <div style="font-weight:500;">${l.actor ? l.actor.name : '—'}</div>
                </td>
                <td>
                    ${l.engagement_id
                        ? `<a href="/test/leads/${l.engagement_id}"
                            style="color:var(--accent);font-weight:600;">#${l.engagement_id}</a>`
                        : '<span style="color:var(--text-muted);">—</span>'}
                </td>
                <td>${jsonPill(l.before)}</td>
                <td>${jsonPill(l.after)}</td>
                <td>
                    <div style="font-size:12.5px;">${formatDate(l.created_at)}</div>
                    <div style="font-size:11px;color:var(--text-muted);">${relativeTime(l.created_at)}</div>
                </td>
            </tr>
        `).join('');

        if (meta) {
            const totalPages = Math.ceil(meta.total / meta.per_page);
            let paginationHtml = `<div class="pagination__info">Page ${meta.current_page} of ${totalPages}</div>`;
            paginationHtml += '<div class="pagination__controls">';
            if (meta.current_page > 1)
                paginationHtml += `<button class="btn btn--outline btn--sm" onclick="loadLogs(${meta.current_page - 1})">← Previous</button>`;
            if (meta.current_page < totalPages)
                paginationHtml += `<button class="btn btn--outline btn--sm" onclick="loadLogs(${meta.current_page + 1})">Next →</button>`;
            paginationHtml += '</div>';
            document.getElementById('pagination').innerHTML = paginationHtml;
        }
    } else {
        document.getElementById('audit-meta').textContent = '';
        tbody.innerHTML = `<tr><td colspan="7">
            <div class="empty-state">
                <span class="empty-state__icon">📋</span>
                <div class="empty-state__text">${data.message || 'No audit logs found'}</div>
                <div class="empty-state__sub">Try adjusting your filters</div>
            </div>
        </td></tr>`;
        document.getElementById('pagination').innerHTML = '';
    }
}

loadLogs();
</script>
@endsection
