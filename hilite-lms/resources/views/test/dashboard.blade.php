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
    <h1 id="dashboard-heading">Pipeline Overview</h1>
    <div class="header-actions">
        <button class="btn btn--ghost" onclick="refreshDashboard()" id="refresh-btn">↻</button>
    </div>
</div>

{{-- Pill Navigation --}}
<div class="pill-nav">
    <button class="pill active">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        All Leads
    </button>
    <button class="pill">🔥 Hot Leads</button>
    <button class="pill">⚠️ Breached SLA</button>
    <button class="pill">✅ Closed Won</button>
</div>

{{-- Main content grid --}}
<div class="section-header" style="margin-top: 32px;">
    <h2 id="recent-leads-title">Recent Opportunities</h2>
    <a href="/test/leads" class="btn btn--ghost btn--sm" style="border:none;">View all →</a>
</div>

<div id="recent-leads" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:20px; margin-bottom:40px;">
    <div class="loading-block" style="padding:36px; grid-column:1/-1;">
        <span class="spinner"></span> Loading opportunities…
    </div>
</div>

{{-- Upcoming Follow-ups --}}
<div class="section-header">
    <h2>Upcoming Activities</h2>
</div>
<div class="card bg-pastel-white">
    <div id="upcoming-followups">
        <div class="loading-block" style="padding:36px;">
            <span class="spinner"></span> Loading…
        </div>
    </div>
</div>

{{-- Template for Right Sidebar Stats --}}
<template id="right-sidebar-stats-template">
    <div style="background:#FFF; border-radius:var(--radius-sm); padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
        <h3 style="font-size:14px; margin-bottom:16px;">Activity Summary</h3>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <div>
                <div style="font-size:24px; font-weight:800;" id="stat-total">...</div>
                <div style="font-size:12px; color:var(--text-muted);" id="stat-total-label">Total Leads</div>
            </div>
            <div style="display:flex; gap:12px;">
                <div style="flex:1; background:var(--pastel-purple); padding:12px; border-radius:12px;">
                    <div style="font-size:18px; font-weight:800; color:#2e3b5a;" id="stat-breached">...</div>
                    <div style="font-size:11px; font-weight:600; color:#2e3b5a; opacity:0.8;">Breached</div>
                </div>
                <div style="flex:1; background:var(--pastel-orange); padding:12px; border-radius:12px;">
                    <div style="font-size:18px; font-weight:800; color:#5a3c1e;" id="stat-followups">...</div>
                    <div style="font-size:11px; font-weight:600; color:#5a3c1e; opacity:0.8;">Follow-ups</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="admin-only" style="background:var(--pastel-green); border-radius:var(--radius-sm); padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
        <div style="font-size:24px; font-weight:800; color:#1e4a36;" id="stat-stages">...</div>
        <div style="font-size:12px; font-weight:600; color:#1e4a36; opacity:0.8;">Pipeline Stages</div>
    </div>
    
    <a href="/test/leads/create" class="btn btn--primary" style="width:100%; border-radius:12px; padding:12px;">✚ Add New Lead</a>
</template>

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
        document.getElementById('dashboard-heading').textContent    = 'My Pipeline';
        document.getElementById('recent-leads-title').textContent   = 'My Recent Leads';
    } else {
        document.getElementById('dashboard-heading').textContent    = 'Pipeline Overview';
        document.getElementById('recent-leads-title').textContent   = 'Recent Opportunities';
    }
    
    // Inject right sidebar stats
    const portal = document.getElementById('right-sidebar-portal');
    const template = document.getElementById('right-sidebar-stats-template');
    if (portal && template) {
        portal.appendChild(template.content.cloneNode(true));
        
        if (cfg && !cfg.canSeeGlobal) {
            document.getElementById('stat-total-label').textContent = 'My Leads';
            document.querySelectorAll('.admin-only').forEach(el => el.style.display = 'none');
        } else {
            document.getElementById('stat-total-label').textContent = 'Total Leads';
        }
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
        const pastelColors = ['bg-pastel-pink', 'bg-pastel-purple', 'bg-pastel-green', 'bg-pastel-orange'];
        
        const recentHtml = leadsData.data.length
            ? leadsData.data.map((l, i) => {
                if (l.sla_breached) breached++;
                const colorClass = pastelColors[i % pastelColors.length];
                const score = l.lead_score || 0;
                
                return `
                <div class="card ${colorClass}" style="cursor:pointer; display:flex; flex-direction:column; justify-content:space-between; min-height:160px;" onclick="window.location='/test/leads/${l.engagement_id}'">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div class="card__title-icon" style="background:rgba(255,255,255,0.5);">👤</div>
                            <div style="background:rgba(255,255,255,0.5); padding:4px 8px; border-radius:8px; font-size:12px; font-weight:700;">★ ${score}</div>
                        </div>
                        <h3 style="font-size:20px; font-weight:700; margin:16px 0 8px; line-height:1.2;">${l.name || '—'}</h3>
                        <div style="font-size:13px; opacity:0.8;">${l.stage?.name || '—'}</div>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px;">
                        <div style="font-size:12px; font-weight:600;">${l.sla_breached ? '⚠️ Breached' : '✓ On Track'}</div>
                        <div style="display:flex; margin-left:-8px;">
                            <!-- Placeholder for assigned user avatars -->
                            <div style="width:24px; height:24px; border-radius:50%; background:#FFF; border:2px solid transparent; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:bold; color:#333;">A</div>
                        </div>
                    </div>
                </div>`;
            }).join('')
            : `<div class="empty-state" style="grid-column:1/-1;">
                <span class="empty-state__icon">📭</span>
                <div class="empty-state__text">No leads yet</div>
                <div class="empty-state__sub">Create your first lead to get started</div>
               </div>`;

        document.getElementById('recent-leads').innerHTML = recentHtml;
        document.getElementById('stat-breached').textContent = breached;
    }

    // Fetch Metrics
    const metricsRes = await api('/leads/metrics');
    if (metricsRes) {
        const metricsData = await metricsRes.json();
        if (metricsData.success) {
            // These elements have been removed from the top row to restore the old layout
            // If they are added back elsewhere, they can be updated here
            const elVeryHot = document.getElementById('stat-very-hot');
            const elHot = document.getElementById('stat-hot');
            const elAvg = document.getElementById('stat-avg-score');
            
            if (elVeryHot) elVeryHot.textContent = metricsData.data.very_hot;
            if (elHot) elHot.textContent = metricsData.data.hot;
            if (elAvg) elAvg.textContent = metricsData.data.avg_score;
        }
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
