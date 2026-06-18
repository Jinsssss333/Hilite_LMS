@extends('layouts.app')
@section('title', 'Dashboard')
@section('nav-dashboard', 'active')

@section('content')

{{-- Role-contextual banners --}}
<div class="role-banner role-banner--salesperson animate-in">
    👋 Welcome! You're viewing <strong>your assigned leads</strong> only.
</div>
<div class="role-banner role-banner--admin animate-in">
    🛡️ Admin view — showing <strong>all global data</strong> across the organization.
</div>
<div class="role-banner role-banner--manager animate-in">
    📊 Manager view — you can see <strong>team-wide data</strong>.
</div>

<div class="page-header">
    <h1 id="dashboard-heading">Dashboard</h1>
    <p id="dashboard-subhead">Real-time overview of your lead pipeline</p>
    <div class="header-actions">
        <a href="/test/leads/create" class="btn btn--primary btn--sm">✚ New Lead</a>
        <button class="btn btn--ghost btn--sm" onclick="refreshDashboard()" id="refresh-btn">↻ Refresh</button>
    </div>
</div>

{{-- Stat Cards --}}
<div class="stats-row" id="stats-row">
    <div class="stat-card stat-card--accent animate-in">
        <div class="stat-card__icon stat-card__icon--accent">👥</div>
        <div class="stat-card__label" id="stat-total-label">My Leads</div>
        <div class="stat-card__value stat-card__value--accent" id="stat-total">
            <span class="spinner" style="width:22px;height:22px;border-width:2px;"></span>
        </div>
    </div>
    <div class="stat-card stat-card--danger animate-in">
        <div class="stat-card__icon stat-card__icon--danger">🚨</div>
        <div class="stat-card__label">SLA Breached</div>
        <div class="stat-card__value stat-card__value--danger" id="stat-breached">—</div>
    </div>
    <div class="stat-card stat-card--warning animate-in">
        <div class="stat-card__icon stat-card__icon--warning">📅</div>
        <div class="stat-card__label">Upcoming Follow-ups</div>
        <div class="stat-card__value stat-card__value--warning" id="stat-followups">—</div>
    </div>
    {{-- Pipeline stat — hidden from salesperson --}}
    <div class="stat-card stat-card--success animate-in admin-only">
        <div class="stat-card__icon stat-card__icon--success">🔀</div>
        <div class="stat-card__label">Pipeline Stages</div>
        <div class="stat-card__value stat-card__value--success" id="stat-stages">—</div>
    </div>
</div>

{{-- Main content grid --}}
<div class="detail-grid" style="gap:20px;">
    {{-- Recent Leads --}}
    <div class="card">
        <div class="section-header" style="margin-bottom:16px;">
            <div class="card__title" style="margin-bottom:0;">
                <span class="card__title-icon">👥</span>
                <span id="recent-leads-title">My Recent Leads</span>
            </div>
            <a href="/test/leads" class="btn btn--soft btn--sm">View all →</a>
        </div>
        <div id="recent-leads">
            <div class="loading-block" style="padding:36px;">
                <span class="spinner"></span> Loading leads…
            </div>
        </div>
    </div>

    {{-- Upcoming Follow-ups --}}
    <div class="card">
        <div class="section-header" style="margin-bottom:16px;">
            <div class="card__title" style="margin-bottom:0;">
                <span class="card__title-icon">📅</span>
                Upcoming Follow-ups
            </div>
            <a href="/test/activities" class="btn btn--soft btn--sm">View all →</a>
        </div>
        <div id="upcoming-followups">
            <div class="loading-block" style="padding:36px;">
                <span class="spinner"></span> Loading…
            </div>
        </div>
    </div>
</div>

{{-- Admin-only: Quick Links section --}}
<div class="card admin-only" style="margin-top:20px;">
    <div class="card__title">
        <span class="card__title-icon">⚡</span>
        Quick Admin Actions
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="/test/audit"  class="btn btn--ghost btn--sm">📋 Audit Logs</a>
        <a href="/test/sla"    class="btn btn--ghost btn--sm">⏱️ SLA Policies</a>
        <a href="/test/users"  class="btn btn--ghost btn--sm">👤 Manage Users</a>
        <a href="/test/import" class="btn btn--ghost btn--sm">📤 Bulk Import</a>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Adapt heading based on role
(function() {
    const cfg = window.__config;
    if (cfg && !cfg.canSeeGlobal) {
        document.getElementById('dashboard-heading').textContent    = 'My Dashboard';
        document.getElementById('dashboard-subhead').textContent    = 'Your personal lead overview';
        document.getElementById('stat-total-label').textContent     = 'My Leads';
        document.getElementById('recent-leads-title').textContent   = 'My Recent Leads';
    } else {
        document.getElementById('dashboard-heading').textContent    = 'Dashboard';
        document.getElementById('dashboard-subhead').textContent    = 'Organization-wide lead overview';
        document.getElementById('stat-total-label').textContent     = 'Total Leads';
        document.getElementById('recent-leads-title').textContent   = 'Recent Leads';
    }
})();

async function refreshDashboard() {
    const btn = document.getElementById('refresh-btn');
    btn.disabled = true; btn.textContent = '↻ Refreshing…';
    await loadDashboard();
    btn.disabled = false; btn.textContent = '↻ Refresh';
    toast('Dashboard refreshed', 'success');
}

async function loadDashboard() {
    const cfg = window.__config || {};

    // Build leads URL — salesperson only sees own leads
    const leadsUrl = cfg.canSeeGlobal
        ? '/leads?per_page=5'
        : '/leads?per_page=5&assigned_to_me=1';

    const leadsRes = await api(leadsUrl);
    if (!leadsRes) return;
    const leadsData = await leadsRes.json();

    if (leadsData.success) {
        document.getElementById('stat-total').textContent =
            leadsData.meta?.total ?? leadsData.data.length;

        let breached = 0;
        const recentHtml = leadsData.data.length
            ? `<table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Stage</th>
                        <th>Activity</th>
                        <th>SLA</th>
                    </tr>
                </thead>
                <tbody>
                    ${leadsData.data.map(l => {
                        if (l.sla_breached) breached++;
                        const sc = l.stage?.color || '#7C3AED';
                        return `<tr class="clickable" onclick="window.location='/test/leads/${l.engagement_id}'">
                            <td><strong>${l.name || '—'}</strong></td>
                            <td><code style="font-size:11px;">${l.phone_e164}</code></td>
                            <td>
                                <span class="badge badge--stage"
                                    style="background:${sc}18;color:${sc};border-color:${sc}30;">
                                    ${l.stage?.name || '—'}
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted);">${relativeTime(l.last_activity_at)}</td>
                            <td>${l.sla_breached
                                ? '<span class="badge badge--sla-breached">⚠ Breached</span>'
                                : '<span class="badge badge--sla-ok">✓ OK</span>'}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
               </table>`
            : `<div class="empty-state">
                <span class="empty-state__icon">📭</span>
                <div class="empty-state__text">No leads yet</div>
                <div class="empty-state__sub">Create your first lead to get started</div>
               </div>`;

        document.getElementById('recent-leads').innerHTML = recentHtml;
        document.getElementById('stat-breached').textContent = breached;
    }

    // Follow-ups
    const fuRes = await api('/activities/upcoming?days=7');
    if (!fuRes) return;
    const fuData = await fuRes.json();

    if (fuData.success) {
        document.getElementById('stat-followups').textContent = fuData.data.length;

        const fuHtml = fuData.data.length
            ? `<div class="timeline">
                ${fuData.data.slice(0, 5).map(a => `
                    <div class="timeline__item">
                        <div class="timeline__meta">
                            ${formatDate(a.follow_up_at)}
                            ${a.overdue ? ' <strong style="color:var(--danger);">● Overdue</strong>' : ''}
                        </div>
                        <div class="timeline__content">
                            <span class="timeline__type">${a.type}</span>
                            — ${a.lead_name || a.phone_e164}
                            ${a.notes ? `<br><small style="color:var(--text-muted);font-size:11px;">${a.notes}</small>` : ''}
                        </div>
                    </div>
                `).join('')}
               </div>`
            : `<div class="empty-state">
                <span class="empty-state__icon">✅</span>
                <div class="empty-state__text">All clear!</div>
                <div class="empty-state__sub">No follow-ups in the next 7 days</div>
               </div>`;

        document.getElementById('upcoming-followups').innerHTML = fuHtml;
    }

    // Pipeline stats (admin/manager only)
    if (cfg.canSeeGlobal) {
        const stRes = await api('/pipeline-stages');
        if (stRes) {
            const stData = await stRes.json();
            if (stData.success) {
                document.getElementById('stat-stages').textContent = stData.data.length;
            }
        }
    }
}

loadDashboard();
</script>
@endsection
