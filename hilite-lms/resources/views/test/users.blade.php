@extends('layouts.app')
@section('title', 'Users')
@section('nav-users', 'active')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>Users</h1>
        <p>All active users in your organization (admin view)</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button onclick="processQueue()" class="btn" style="background:#10b981; color:#fff; padding:8px 16px; border-radius:6px; border:none; cursor:pointer;">Process Queued Leads</button>
        <button onclick="document.getElementById('add-user-modal').style.display='block'" class="btn" style="background:#6366f1; color:#fff; padding:8px 16px; border-radius:6px; border:none; cursor:pointer;">+ Add User</button>
    </div>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="background:#fff; padding:24px; border-radius:12px; width:400px; max-width:90%; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h2 style="margin-bottom:16px; font-size:1.25rem;">Add New User</h2>
        <form id="add-user-form" onsubmit="createUser(event)">
            <div style="margin-bottom:12px;">
                <label style="display:block; margin-bottom:4px; font-size:14px;">Name</label>
                <input type="text" id="add-name" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block; margin-bottom:4px; font-size:14px;">Email</label>
                <input type="email" id="add-email" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block; margin-bottom:4px; font-size:14px;">Password</label>
                <input type="password" id="add-password" required minlength="8" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:4px; font-size:14px;">Role</label>
                <select id="add-role" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box;">
                    <option value="salesperson">Salesperson</option>
                    <option value="team_lead">Team Lead</option>
                    <option value="manager">Manager</option>
                    <option value="branch_head">Branch Head</option>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" onclick="document.getElementById('add-user-modal').style.display='none'" style="padding:8px 16px; background:#e5e7eb; border:none; border-radius:6px; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:8px 16px; background:#6366f1; color:white; border:none; border-radius:6px; cursor:pointer;">Save User</button>
            </div>
        </form>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Branch</th>
                <th>Team</th>
                <th>Active Leads</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="users-tbody">
            <tr><td colspan="7"><div class="loading-block"><span class="spinner"></span> Loading users…</div></td></tr>
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
// ─── Route Guard ────────────────────────────────────────────────
if (!requireRole(['admin', 'manager'])) {
    throw new Error('access_denied');
}

(async function() {
    const res = await api('/admin/users');
    if (!res) return;
    const data = await res.json();

    if (data.success && data.data.length) {
        document.getElementById('users-tbody').innerHTML = data.data.map(u => {
            const roleClass = u.role === 'admin'
                ? 'badge--admin'
                : u.role === 'manager' ? 'badge--role'
                : 'badge badge--source';

            return `<tr>
                <td><span style="color:var(--text-muted);font-size:12px;font-weight:600;">#${u.id}</span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="
                            width:32px;height:32px;border-radius:50%;
                            background:linear-gradient(135deg,#6366f1,#a855f7);
                            display:flex;align-items:center;justify-content:center;
                            font-weight:700;font-size:13px;color:#fff;flex-shrink:0;
                        ">${(u.name || '?')[0].toUpperCase()}</div>
                        <strong>${u.name}</strong>
                    </div>
                </td>
                <td style="font-size:12px;color:var(--text-muted);">
                    <a href="mailto:${u.email}" style="color:var(--text-muted);">${u.email}</a>
                </td>
                <td>
                    <span class="badge ${roleClass}" style="text-transform:capitalize;">${u.role}</span>
                </td>
                <td style="font-size:13px;">${u.branch_id || '<span style="color:var(--text-muted)">—</span>'}</td>
                <td style="font-size:13px;">${u.team_id || '<span style="color:var(--text-muted)">—</span>'}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <strong style="font-size:15px;">${u.active_leads}</strong>
                        ${u.active_leads > 0 ? `
                            <div class="progress-bar" style="width:60px;">
                                <div class="progress-bar__fill" style="width:${Math.min(u.active_leads * 5, 100)}%;"></div>
                            </div>
                        ` : ''}
                    </div>
                </td>
                <td>
                    ${['admin', 'super_admin'].includes(u.role) ? '' : `<button onclick="deleteUser(${u.id})" style="background:#ef4444; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer; font-size:12px;">Delete</button>`}
                </td>
            </tr>`;
        }).join('');
    } else {
        document.getElementById('users-tbody').innerHTML = `
            <tr><td colspan="7">
                <div class="empty-state">
                    <span class="empty-state__icon">👤</span>
                    <div class="empty-state__text">${data.message || 'No users found or insufficient permissions'}</div>
                </div>
            </td></tr>`;
    }
})();

async function createUser(e) {
    e.preventDefault();
    const payload = {
        name: document.getElementById('add-name').value,
        email: document.getElementById('add-email').value,
        password: document.getElementById('add-password').value,
        role: document.getElementById('add-role').value
    };
    const res = await api('/admin/users', { method: 'POST', body: JSON.stringify(payload) });
    if (res && res.ok) {
        alert('User created successfully');
        location.reload();
    }
}

async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    const res = await api('/admin/users/' + id, { method: 'DELETE' });
    if (res && res.ok) {
        alert('User deleted successfully');
        location.reload();
    }
}

async function processQueue() {
    if (!confirm('This will manually trigger the assignment engine to distribute any waiting leads. Continue?')) return;
    const res = await api('/admin/assignments/process-queue', { method: 'POST' });
    if (res && res.ok) {
        const data = await res.json();
        alert('Queue processed successfully! ' + data.data.assigned_count + ' leads were assigned.');
        location.reload();
    } else {
        alert('Failed to process queue.');
    }
}
</script>
@endsection
