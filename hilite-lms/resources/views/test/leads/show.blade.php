@extends('layouts.app')
@section('title', 'Lead Detail')
@section('nav-leads', 'active')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;gap:16px;">
    <a href="/test/leads" class="btn btn--ghost btn--sm" style="margin-top:4px;flex-shrink:0;">← Back</a>
    <div style="flex:1;">
        <h1 id="lead-title" style="font-size:20px;">Lead #{{ $id }}</h1>
        <p id="lead-subtitle" style="color:var(--text-muted);margin-top:3px;">Loading…</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;margin-top:4px;flex-wrap:wrap;" id="lead-header-badges"></div>
</div>

{{-- Core Info + Primary Actions --}}
<div class="detail-grid" id="lead-detail">
    {{-- Lead Info --}}
    <div class="card" id="lead-info-card">
        <div class="card__title"><span class="card__title-icon">👤</span> Lead Information</div>
        <div class="loading-block" style="padding:36px;"><span class="spinner"></span> Loading…</div>
    </div>

    {{-- Primary Actions Panel --}}
    <div class="card" id="lead-actions-card">
        <div class="card__title"><span class="card__title-icon">⚡</span> Quick Actions</div>

        {{-- Log Activity — primary, always visible --}}
        <div id="primary-actions">
            <div style="font-size:12px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">
                Log Activity
            </div>
            <div class="form-row">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="act-type">Type</label>
                    <select id="act-type" class="form-control">
                        <option value="note">📝 Note</option>
                        <option value="call">📞 Call</option>
                        <option value="visit">🏠 Visit</option>
                        <option value="followup">📅 Follow-up</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="act-followup">Follow-up At</label>
                    <input type="datetime-local" id="act-followup" class="form-control">
                </div>
            </div>
            <div class="form-group" style="margin-top:12px;margin-bottom:12px;">
                <label for="act-notes">Notes</label>
                <textarea id="act-notes" class="form-control" placeholder="Write your notes…" rows="3"></textarea>
            </div>
            <button class="btn btn--primary" onclick="logActivity()" id="btn-activity"
                style="width:100%;justify-content:center;">
                ✓ Log Activity
            </button>
        </div>

        <hr class="divider">

        {{-- Progressive Disclosure: Stage & Assignment hidden behind toggle --}}
        <button class="advanced-toggle" id="adv-actions-toggle" style="width:100%;justify-content:center;"
            onclick="toggleAdvanced('adv-actions-toggle','adv-actions-panel')">
            <span class="toggle-label">Stage &amp; Assignment</span>
            <span class="toggle-arrow">▼</span>
        </button>

        <div class="advanced-panel" id="adv-actions-panel">
            {{-- Stage Update --}}
            <div style="font-size:12px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">
                Move to Stage
            </div>
            <div class="form-row">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="action-stage">Stage</label>
                    <select id="action-stage" class="form-control"></select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="action-disp">Disposition</label>
                    <select id="action-disp" class="form-control">
                        <option value="">None</option>
                    </select>
                </div>
            </div>
            <button class="btn btn--soft btn--sm" onclick="updateStage()" id="btn-stage"
                style="margin-top:10px;width:100%;justify-content:center;">
                Update Stage
            </button>

            {{-- Assignment — admin/manager only --}}
            <div class="admin-only" style="margin-top:20px;">
                <hr class="divider">
                <div style="font-size:12px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">
                    Reassign Lead
                </div>
                <div class="form-group">
                    <label for="action-assign">Assign To</label>
                    <select id="action-assign" class="form-control"></select>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label for="action-reason">Reason <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
                    <input type="text" id="action-reason" class="form-control" placeholder="Reason…">
                </div>
                <button class="btn btn--success btn--sm" onclick="assignLead()" id="btn-assign"
                    style="width:100%;justify-content:center;">
                    Assign Lead
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Activity Timeline --}}
<div class="card" style="margin-top:20px;">
    <div class="section-header" style="margin-bottom:16px;">
        <div class="card__title" style="margin-bottom:0;"><span class="card__title-icon">📝</span> Activity Timeline</div>
        <span id="activity-count" style="font-size:12px;color:var(--text-muted);"></span>
    </div>
    <div id="activity-timeline">
        <div class="loading-block" style="padding:36px;"><span class="spinner"></span></div>
    </div>
</div>

{{-- Assignment History — progressive disclosure accordion --}}
<div class="card" style="margin-top:20px;margin-bottom:32px;">
    <div class="accordion" id="assign-hist-accordion" style="border:none;margin-bottom:0;">
        <div class="accordion__header" onclick="toggleAccordion(this)">
            <div class="accordion__title">
                <span style="font-size:13px;">📋</span>
                Assignment History
            </div>
            <span class="accordion__arrow">▼</span>
        </div>
        <div class="accordion__body">
            <div class="accordion__content">
                <div id="assignment-history">
                    <div class="loading-block" style="padding:24px;"><span class="spinner"></span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const engagementId = {{ $id }};
let currentStageOrder = 0;
let currentStageName = '';
let isLeadClosed = false;
let allStages = [];

async function loadDetail() {
    const res = await api(`/leads/${engagementId}`);
    if (!res) return;
    const data = await res.json();

    if (!data.success) {
        document.getElementById('lead-info-card').innerHTML =
            `<div class="card__title"><span class="card__title-icon">👤</span> Lead Information</div>
             <div class="callout callout--danger">⚠ ${data.message}</div>`;
        return;
    }

    const l = data.data;
    
    currentStageOrder = l.stage?.order || 0;
    currentStageName = (l.stage?.name || '').toLowerCase().trim();
    isLeadClosed = l.stage?.is_closed || false;
    
    document.getElementById('lead-title').textContent   = l.name || `Lead #${engagementId}`;
    document.getElementById('lead-subtitle').textContent = l.phone_e164;

    const sc = l.stage?.color || '#7C3AED';
    const score = l.lead_score || 0;
    const rating = l.lead_rating || 'Cold';
    const badgeClass = 'badge--' + rating.toLowerCase().replace(' ', '-');

    let badges = '';
    badges += `<span class="badge ${badgeClass}">${rating}</span>`;
    if (l.stage) badges += `<span class="badge badge--stage" style="background:${sc}15;color:${sc};border-color:${sc}30;">${l.stage.name}</span>`;
    if (l.sla_breached) badges += `<span class="badge badge--sla-breached">⚠ SLA Breached</span>`;
    document.getElementById('lead-header-badges').innerHTML = badges;

    // Build info card
    document.getElementById('lead-info-card').innerHTML = `
        <div class="card__title"><span class="card__title-icon">👤</span> Lead Information</div>
        <div class="detail-field">
            <div class="detail-field__label">Phone</div>
            <div class="detail-field__value"><code>${l.phone_e164}</code></div>
        </div>
        <div class="detail-field">
            <div class="detail-field__label">Name</div>
            <div class="detail-field__value">${l.name || '<span style="color:var(--text-muted)">Not provided</span>'}</div>
        </div>
        <div class="detail-field">
            <div class="detail-field__label">Email</div>
            <div class="detail-field__value">${l.email
                ? `<a href="mailto:${l.email}" style="color:var(--accent);">${l.email}</a>`
                : '<span style="color:var(--text-muted)">—</span>'}</div>
        </div>
        <div class="detail-field">
            <div class="detail-field__label">Priority</div>
            <div class="detail-field__value">
                <span class="badge ${badgeClass}">${rating}</span>
                <span style="color:var(--text-muted);font-size:13px;margin-left:6px;">${score} / 100</span>
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field__label">Source</div>
            <div class="detail-field__value">${l.source
                ? `<span class="badge badge--source">${l.source}</span>`
                : '<span style="color:var(--text-muted)">—</span>'}</div>
        </div>
        <div class="detail-field">
            <div class="detail-field__label">Stage</div>
            <div class="detail-field__value">
                <span class="badge badge--stage" style="background:${sc}15;color:${sc};border-color:${sc}30;">
                    ${l.stage?.name || '—'}
                </span>
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field__label">Assigned To</div>
            <div class="detail-field__value">${l.assigned_to
                ? `<strong>${l.assigned_to.name}</strong>`
                : '<span style="color:var(--text-muted)">Unassigned</span>'}</div>
        </div>
        <div class="detail-field">
            <div class="detail-field__label">SLA Due</div>
            <div class="detail-field__value" style="display:flex;align-items:center;gap:8px;">
                <span>${l.sla_due_at ? formatDate(l.sla_due_at) : '—'}</span>
                ${l.sla_breached ? '<span class="badge badge--sla-breached">⚠ BREACHED</span>' : ''}
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field__label">Last Activity</div>
            <div class="detail-field__value">${l.last_activity_at
                ? `${formatDate(l.last_activity_at)} <span style="color:var(--text-muted);font-size:12px;">(${relativeTime(l.last_activity_at)})</span>`
                : '<span style="color:var(--text-muted)">No activity yet</span>'}</div>
        </div>
        <div class="detail-field">
            <div class="detail-field__label">Created</div>
            <div class="detail-field__value">${formatDate(l.created_at)}</div>
        </div>
    `;

    // Timeline
    const actEl = document.getElementById('activity-timeline');
    const cntEl = document.getElementById('activity-count');
    if (l.activities?.length) {
        if (cntEl) cntEl.textContent = `${l.activities.length} activities`;
        actEl.innerHTML = '<div class="timeline">'
            + l.activities.map(a => `
                <div class="timeline__item">
                    <div class="timeline__meta">
                        ${formatDate(a.created_at)} — by <strong>${a.created_by?.name || 'Unknown'}</strong>
                    </div>
                    <div class="timeline__content">
                        <span class="timeline__type">${a.type}</span>
                        ${a.disposition ? ` &mdash; <span class="badge badge--role" style="font-size:10px;">${a.disposition.label}</span>` : ''}
                        ${a.notes ? `<br><span style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">${a.notes}</span>` : ''}
                        ${a.follow_up_at ? `<br><span style="color:var(--warning);font-size:12px;margin-top:4px;display:block;">⏰ Follow-up: ${formatDate(a.follow_up_at)}</span>` : ''}
                    </div>
                </div>
            `).join('') + '</div>';
    } else {
        actEl.innerHTML = `<div class="empty-state">
            <span class="empty-state__icon">📝</span>
            <div class="empty-state__text">No activities yet</div>
            <div class="empty-state__sub">Log the first activity using the panel above</div>
        </div>`;
    }

    // Assignment history (in accordion)
    const ahEl = document.getElementById('assignment-history');
    if (l.assignment_history?.length) {
        ahEl.innerHTML = '<div class="timeline">'
            + l.assignment_history.map(a => `
                <div class="timeline__item">
                    <div class="timeline__meta">${formatDate(a.assigned_at)}</div>
                    <div class="timeline__content">
                        Assigned to <strong>${a.assigned_to.name}</strong>
                        by <strong>${a.assigned_by.name}</strong>
                        ${a.reason ? `<br><span style="color:var(--text-muted);font-size:12px;">${a.reason}</span>` : ''}
                    </div>
                </div>
            `).join('') + '</div>';
    } else {
        ahEl.innerHTML = `<div class="empty-state" style="padding:32px;">
            <div class="empty-state__text">No assignment history</div>
        </div>`;
    }
    
    // Now that we have the lead's current stage, render the options correctly
    renderStageOptions();
}

async function loadStages() {
    const res = await api('/pipeline-stages');
    if (!res) return;
    const data = await res.json();
    if (data.success) {
        allStages = data.data;
        renderStageOptions();
    }
}

function renderStageOptions() {
    if (!allStages.length) return;
    
    // The exact state machine requested by the user
    const allowedTransitions = {
        'new': ['contacted', 'not interested', 'lost'],
        'contacted': ['interested', 'site visit scheduled', 'not interested', 'lost'],
        'interested': ['site visit scheduled', 'negotiation', 'not interested', 'lost'],
        'site visit scheduled': ['negotiation', 'booked', 'not interested', 'lost'],
        'negotiation': ['booked', 'lost'],
        'booked': [],
        'lost': ['new', 'contacted'],
        'not interested': ['new', 'contacted']
    };
    
    let allowedStages = [];
    
    if (allowedTransitions[currentStageName]) {
        // Enforce the explicit mapping
        const allowedNames = allowedTransitions[currentStageName];
        allowedStages = allStages.filter(s => allowedNames.includes(s.name.toLowerCase().trim()));
    } else {
        // Fallback to order-based logic if the name doesn't match the map
        if (isLeadClosed) {
            const firstOpen = allStages.find(s => !s.is_closed);
            allowedStages = allStages.filter(s => s.id === firstOpen?.id || s.is_closed);
        } else {
            allowedStages = allStages.filter(s => s.order > currentStageOrder || s.is_closed);
        }
    }
    
    // Always include the current stage so the dropdown doesn't blank out
    if (!allowedStages.find(s => s.name.toLowerCase().trim() === currentStageName)) {
        const curr = allStages.find(s => s.name.toLowerCase().trim() === currentStageName);
        if (curr) allowedStages.unshift(curr);
    }
    
    document.getElementById('action-stage').innerHTML =
        allowedStages.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
}

async function loadDispositions() {
    const res = await api('/dispositions');
    if (!res) return;
    const data = await res.json();
    if (data.success) {
        document.getElementById('action-disp').innerHTML =
            '<option value="">None</option>'
            + data.data.map(d => `<option value="${d.id}">${d.label}</option>`).join('');
    }
}

async function loadAssignable() {
    const cfg = window.__config || {};
    if (!cfg.canSeeGlobal) return; // Salesperson can't reassign
    const res = await api('/users/assignable');
    if (!res) return;
    const data = await res.json();
    if (data.success) {
        const sel = document.getElementById('action-assign');
        if (sel) sel.innerHTML = data.data.map(u =>
            `<option value="${u.id}">${u.name} (${u.role}) — ${u.active_leads} leads</option>`
        ).join('');
    }
}

async function updateStage() {
    const btn = document.getElementById('btn-stage');
    btn.disabled = true; btn.textContent = 'Updating…';
    const res = await api(`/engagements/${engagementId}/stage`, {
        method: 'PATCH',
        body: JSON.stringify({
            stage_id: parseInt(document.getElementById('action-stage').value),
            disposition_id: document.getElementById('action-disp').value
                ? parseInt(document.getElementById('action-disp').value) : null,
        }),
    });
    const data = await res.json();
    btn.disabled = false; btn.textContent = 'Update Stage';
    if (data.success) { 
        toast('Stage updated!', 'success'); 
        await loadDetail(); 
        renderStageOptions();
    }
    else toast(data.message || 'Error', 'error');
}

async function assignLead() {
    const btn = document.getElementById('btn-assign');
    btn.disabled = true; btn.textContent = 'Assigning…';
    const res = await api(`/engagements/${engagementId}/assign`, {
        method: 'PATCH',
        body: JSON.stringify({
            assign_to_user_id: parseInt(document.getElementById('action-assign').value),
            reason: document.getElementById('action-reason').value || null,
        }),
    });
    const data = await res.json();
    btn.disabled = false; btn.textContent = 'Assign Lead';
    if (data.success) {
        toast('Lead assigned!', 'success');
        document.getElementById('action-reason').value = '';
        loadDetail();
    } else toast(data.message || 'Error', 'error');
}

async function logActivity() {
    const btn = document.getElementById('btn-activity');
    btn.disabled = true; btn.textContent = 'Logging…';
    const body = {
        type: document.getElementById('act-type').value,
        notes: document.getElementById('act-notes').value || null,
    };
    const fu = document.getElementById('act-followup').value;
    if (fu) body.follow_up_at = new Date(fu).toISOString();
    const dv = document.getElementById('action-disp')?.value;
    if (dv) body.disposition_id = parseInt(dv);

    const res = await api(`/engagements/${engagementId}/activities`, {
        method: 'POST', body: JSON.stringify(body),
    });
    const data = await res.json();
    btn.disabled = false; btn.innerHTML = '✓ Log Activity';
    if (data.success) {
        toast('Activity logged!', 'success');
        document.getElementById('act-notes').value = '';
        document.getElementById('act-followup').value = '';
        await loadDetail();
        renderStageOptions();
    } else toast(data.message || 'Error', 'error');
}

async function init() {
    await loadStages();
    await loadDetail();
    loadDispositions();
    loadAssignable();
}

init();
</script>
@endsection
