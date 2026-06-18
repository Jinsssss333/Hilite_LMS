@extends('layouts.app')
@section('title', 'SLA Policies')
@section('nav-sla', 'active')

@section('content')
<div class="page-header">
    <h1>SLA Policies</h1>
    <p>Configure response time limits per pipeline stage</p>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Stage</th>
                <th>SLA Days</th>
                <th>Escalate To</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="sla-tbody">
            <tr><td colspan="5"><div class="loading-block"><span class="spinner"></span> Loading SLA policies…</div></td></tr>
        </tbody>
    </table>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:var(--bg-modal);backdrop-filter:blur(8px);z-index:300;align-items:center;justify-content:center;">
    <div class="card animate-in" style="width:440px;max-width:90vw;box-shadow:var(--shadow-lg);">
        <div class="card__title"><span class="card__title-icon">⏱️</span> Edit SLA Policy</div>
        <input type="hidden" id="edit-id">

        <div id="edit-stage-name" style="margin-bottom:18px;padding:12px 16px;background:var(--bg-hover);border-radius:var(--radius-sm);font-weight:600;font-size:14px;"></div>

        <div class="form-row">
            <div class="form-group" style="margin-bottom:0;">
                <label for="edit-days">SLA Days</label>
                <input type="number" id="edit-days" class="form-control" min="1" max="365" placeholder="e.g. 3">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label for="edit-role">Escalate To Role</label>
                <select id="edit-role" class="form-control">
                    <option value="">None</option>
                    <option value="team_lead">Team Lead</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:20px;">
            <button class="btn btn--primary" onclick="saveSla()" id="btn-save">Save Changes</button>
            <button class="btn btn--ghost" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ─── Route Guard: Admin/Manager only ────────────────────────────
if (!requireRole(['admin', 'manager'])) {
    throw new Error('access_denied');
}

async function loadSla() {
    const tbody = document.getElementById('sla-tbody');
    tbody.innerHTML = '<tr><td colspan="5"><div class="loading-block"><span class="spinner"></span> Loading…</div></td></tr>';

    const res = await api('/admin/sla-policies');
    if (!res) return;
    const data = await res.json();

    if (data.success && data.data.length) {
        tbody.innerHTML = data.data.map(p => `
            <tr>
                <td><span style="color:var(--text-muted);font-size:12px;font-weight:600;">#${p.id}</span></td>
                <td><strong>${p.stage_name || '—'}</strong></td>
                <td>
                    <span class="badge badge--role">${p.sla_days} days</span>
                </td>
                <td>
                    ${p.escalate_to_role
                        ? `<span class="badge badge--admin" style="text-transform:capitalize;">${p.escalate_to_role.replace('_', ' ')}</span>`
                        : '<span style="color:var(--text-muted);">—</span>'}
                </td>
                <td>
                    <button class="btn btn--outline btn--sm"
                        onclick="openEdit(${p.id}, ${p.sla_days}, '${p.escalate_to_role || ''}', '${p.stage_name || ''}')">
                        ✏ Edit
                    </button>
                </td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = `<tr><td colspan="5">
            <div class="empty-state">
                <span class="empty-state__icon">⏱️</span>
                <div class="empty-state__text">${data.message || 'No SLA policies or insufficient permissions'}</div>
            </div>
        </td></tr>`;
    }
}

function openEdit(id, days, role, stageName) {
    document.getElementById('edit-id').value  = id;
    document.getElementById('edit-days').value = days;
    document.getElementById('edit-role').value = role;
    document.getElementById('edit-stage-name').textContent = `Stage: ${stageName || '#' + id}`;
    const modal = document.getElementById('edit-modal');
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

// Close on backdrop click
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

async function saveSla() {
    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.textContent = 'Saving…';

    const id = document.getElementById('edit-id').value;
    const res = await api(`/admin/sla-policies/${id}`, {
        method: 'PATCH',
        body: JSON.stringify({
            sla_days:         parseInt(document.getElementById('edit-days').value),
            escalate_to_role: document.getElementById('edit-role').value || null,
        }),
    });
    const data = await res.json();
    btn.disabled = false;
    btn.textContent = 'Save Changes';

    if (data.success) {
        toast('SLA policy updated successfully!', 'success');
        closeModal();
        loadSla();
    } else {
        toast(data.message || 'Failed to update SLA policy', 'error');
    }
}

loadSla();
</script>
@endsection
