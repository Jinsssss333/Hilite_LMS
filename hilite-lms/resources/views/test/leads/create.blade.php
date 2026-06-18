@extends('layouts.app')
@section('title', 'New Lead')
@section('nav-create', 'active')

@section('content')
<div class="page-header">
    <h1>Create New Lead</h1>
    <p>Add a new lead engagement to the system</p>
    <div class="header-actions">
        <a href="/test/leads" class="btn btn--ghost btn--sm">← Back to Leads</a>
    </div>
</div>

<div style="max-width:640px;">
    {{-- Step 1: Duplicate Check --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card__title">
            <span class="card__title-icon">🔍</span>
            Step 1 — Check for Duplicates
        </div>
        <div class="form-row" style="align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0;flex:2;">
                <label for="check-phone">Phone Number</label>
                <input type="text" id="check-phone" class="form-control"
                    placeholder="+919876543210"
                    onkeydown="if(event.key==='Enter') checkDuplicate()">
            </div>
            <div style="flex-shrink:0;padding-bottom:1px;">
                <button class="btn btn--outline" onclick="checkDuplicate()" id="btn-check">
                    🔍 Check
                </button>
            </div>
        </div>
        <div id="dup-result" style="margin-top:14px;"></div>
    </div>

    {{-- Step 2: Lead Form --}}
    <div class="card">
        <div class="card__title">
            <span class="card__title-icon">✚</span>
            Step 2 — Lead Details
        </div>
        <form id="create-form">
            <div class="form-group">
                <label for="lead-phone">Phone Number <span style="color:var(--danger);">*</span></label>
                <input type="text" id="lead-phone" class="form-control"
                    placeholder="+919876543210" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="lead-name">Full Name</label>
                    <input type="text" id="lead-name" class="form-control" placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label for="lead-email">Email Address</label>
                    <input type="email" id="lead-email" class="form-control" placeholder="john@example.com">
                </div>
            </div>
            <div class="form-group">
                <label for="lead-source">Lead Source</label>
                <select id="lead-source" class="form-control">
                    <option value="manual">📝 Manual Entry</option>
                    <option value="web">🌐 Web</option>
                    <option value="referral">👥 Referral</option>
                    <option value="webhook">🔗 Webhook</option>
                </select>
            </div>
            <div class="form-group">
                <label for="lead-notes">Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                <textarea id="lead-notes" class="form-control"
                    placeholder="Add any initial notes about this lead…" rows="3"></textarea>
            </div>

            <div id="create-result" style="margin-bottom:16px;"></div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn--primary" id="btn-create">
                    ✚ Create Lead
                </button>
                <a href="/test/leads" class="btn btn--ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
async function checkDuplicate() {
    const phone = document.getElementById('check-phone').value.trim();
    if (!phone) return toast('Please enter a phone number', 'warning');

    const btn = document.getElementById('btn-check');
    btn.disabled = true;
    btn.textContent = 'Checking…';

    const res = await api(`/leads/check-duplicate?phone=${encodeURIComponent(phone)}`);
    btn.disabled = false;
    btn.innerHTML = '🔍 Check';
    if (!res) return;

    const data = await res.json();
    const el = document.getElementById('dup-result');

    if (data.success) {
        const d = data.data;
        if (d.engagement_exists_in_company) {
            el.innerHTML = `
                <div class="callout callout--danger">
                    <span>⚠</span>
                    <div>
                        <strong style="color:var(--danger);">Duplicate found in your company</strong><br>
                        <span style="font-size:12px;color:var(--text-secondary);">
                            ${d.assigned_to ? 'Assigned to: <strong>' + d.assigned_to.name + '</strong>' : 'Unassigned'}
                            ${d.stage ? ' — Stage: <span class="badge badge--stage" style="font-size:11px;">' + d.stage + '</span>' : ''}
                        </span>
                    </div>
                </div>`;
        } else if (d.exists) {
            el.innerHTML = `
                <div class="callout callout--warning">
                    <span>ℹ</span>
                    <div>
                        <strong>Lead exists in another company</strong><br>
                        <span style="font-size:12px;color:var(--text-secondary);">You can still create an engagement for your company.</span>
                    </div>
                </div>`;
        } else {
            el.innerHTML = `
                <div class="callout" style="background:var(--success-muted);border-color:rgba(16,185,129,.2);">
                    <span style="color:var(--success);">✓</span>
                    <div>
                        <strong style="color:var(--success);">No duplicates found</strong><br>
                        <span style="font-size:12px;color:var(--text-secondary);">Safe to create a new lead.</span>
                    </div>
                </div>`;
        }
        // Pre-fill the phone field
        document.getElementById('lead-phone').value = phone;
    }
}

document.getElementById('create-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-create');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> Creating…';

    const body = {
        phone:  document.getElementById('lead-phone').value.trim(),
        name:   document.getElementById('lead-name').value.trim()  || null,
        email:  document.getElementById('lead-email').value.trim() || null,
        source: document.getElementById('lead-source').value,
        notes:  document.getElementById('lead-notes').value.trim() || null,
    };

    const res = await api('/leads', { method: 'POST', body: JSON.stringify(body) });
    const data = await res.json();
    btn.disabled = false;
    btn.textContent = '✚ Create Lead';

    const resultEl = document.getElementById('create-result');
    if (data.success) {
        toast('Lead created successfully!', 'success');
        resultEl.innerHTML = `
            <div class="callout" style="background:var(--success-muted);border-color:rgba(16,185,129,.2);">
                <span style="color:var(--success);font-size:16px;">✓</span>
                <div>
                    <strong style="color:var(--success);">${data.message}</strong><br>
                    <span style="font-size:12px;color:var(--text-secondary);">
                        Engagement ID:
                        <a href="/test/leads/${data.data.engagement_id}"
                            style="color:var(--accent);font-weight:600;">#${data.data.engagement_id}</a>
                        ${data.data.is_duplicate ? '<span class="badge badge--role" style="margin-left:6px;">Duplicate</span>' : ''}
                    </span>
                </div>
            </div>`;
        // Reset form after short delay
        setTimeout(() => {
            document.getElementById('create-form').reset();
            document.getElementById('dup-result').innerHTML = '';
        }, 2000);
    } else {
        toast(data.message || 'Failed to create lead', 'error');
        resultEl.innerHTML = `
            <div class="callout callout--danger">
                ⚠ ${data.message || 'Error creating lead. Please try again.'}
            </div>`;
    }
});
</script>
@endsection
