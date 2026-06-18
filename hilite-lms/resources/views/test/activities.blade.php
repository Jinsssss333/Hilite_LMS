@extends('layouts.app')
@section('title', 'Follow-ups')
@section('nav-activities', 'active')

@section('content')
<div class="page-header">
    <h1>Follow-ups</h1>
    <p>Upcoming and overdue activities across all leads</p>
</div>

{{-- Filters --}}
<div class="filters-row">
    <div class="form-group">
        <label for="filter-days">📅 Horizon</label>
        <select id="filter-days" class="form-control">
            <option value="1">Today</option>
            <option value="3">Next 3 days</option>
            <option value="7" selected>Next 7 days</option>
            <option value="14">Next 14 days</option>
            <option value="30">Next 30 days</option>
        </select>
    </div>
    <div class="form-group" style="min-width:unset;flex:0;">
        <label>&nbsp;</label>
        <button class="btn btn--primary" onclick="loadFollowups()" id="refresh-btn">Refresh</button>
    </div>
    <div id="followup-meta" style="margin-left:auto;font-size:12px;color:var(--text-muted);display:flex;align-items:flex-end;padding-bottom:1px;"></div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Follow-up At</th>
                <th>Status</th>
                <th>Lead</th>
                <th>Phone</th>
                <th>Stage</th>
                <th>Type</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody id="followups-tbody">
            <tr><td colspan="7"><div class="loading-block"><span class="spinner"></span> Loading follow-ups…</div></td></tr>
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
async function loadFollowups() {
    const days  = document.getElementById('filter-days').value;
    const tbody = document.getElementById('followups-tbody');
    const btn   = document.getElementById('refresh-btn');
    tbody.innerHTML = '<tr><td colspan="7"><div class="loading-block"><span class="spinner"></span> Loading…</div></td></tr>';
    btn.disabled = true;
    btn.textContent = 'Loading…';

    const res = await api(`/activities/upcoming?days=${days}`);
    btn.disabled = false;
    btn.textContent = 'Refresh';
    if (!res) return;
    const data = await res.json();

    if (data.success && data.data.length) {
        const overdue  = data.data.filter(a => a.overdue).length;
        const upcoming = data.data.length - overdue;
        document.getElementById('followup-meta').innerHTML =
            `<span style="color:var(--danger);font-weight:600;">${overdue} overdue</span>
             &nbsp;·&nbsp;
             <span>${upcoming} upcoming</span>`;

        tbody.innerHTML = data.data.map(a => `
            <tr class="clickable" onclick="window.location='/test/leads/${a.engagement_id}'">
                <td>
                    <div style="font-weight:600;font-size:13px;">${formatDate(a.follow_up_at)}</div>
                    <div style="font-size:11px;color:var(--text-muted);">${relativeTime(a.follow_up_at)}</div>
                </td>
                <td>
                    ${a.overdue
                        ? '<span class="badge badge--sla-breached">⚠ Overdue</span>'
                        : '<span class="badge badge--sla-ok">✓ Upcoming</span>'}
                </td>
                <td><strong>${a.lead_name || '—'}</strong></td>
                <td><code style="font-size:11px;">${a.phone_e164}</code></td>
                <td>
                    <span class="badge badge--role" style="font-size:11px;">${a.stage || '—'}</span>
                </td>
                <td>
                    <span style="text-transform:capitalize;font-weight:500;">${a.type}</span>
                </td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-muted);font-size:12px;">
                    ${a.notes || '<span style="opacity:.5;">—</span>'}
                </td>
            </tr>
        `).join('');
    } else {
        document.getElementById('followup-meta').innerHTML = '';
        tbody.innerHTML = `<tr><td colspan="7">
            <div class="empty-state">
                <span class="empty-state__icon">✅</span>
                <div class="empty-state__text">All clear!</div>
                <div class="empty-state__sub">No follow-ups scheduled in this period</div>
            </div>
        </td></tr>`;
    }
}

// Auto-reload when filter changes
document.getElementById('filter-days').addEventListener('change', loadFollowups);

loadFollowups();
</script>
@endsection
