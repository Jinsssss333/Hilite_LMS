@extends('layouts.app')
@section('title', 'Users')
@section('nav-users', 'active')

@section('content')
<div class="page-header">
    <h1>Users</h1>
    <p>All active users in your organization (admin view)</p>
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
</script>
@endsection
