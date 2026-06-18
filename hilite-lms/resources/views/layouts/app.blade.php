<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hilite LMS') — Hilite CRM</title>
    <meta name="description" content="Hilite LMS — Lead Management System">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/test-ui.css') }}">
</head>
<body>
    {{-- Top Bar --}}
    <header class="topbar">
        <div class="topbar__brand">
            <span class="topbar__logo">H</span>
            <span class="topbar__title">Hilite LMS <small>CRM</small></span>
        </div>

        {{-- Role-aware top nav (collapsed on mobile) --}}
        <div class="topbar__center" id="topbar-center" style="display:none;">
            {{-- Populated by JS based on role --}}
        </div>

        <div class="topbar__user" id="topbar-user" style="display:flex;align-items:center;gap:16px;">
            {{-- Availability Toggle --}}
            <div id="availability-wrapper" style="display:none;align-items:center;gap:8px;font-size:12px;color:var(--text-muted);font-weight:500;">
                <label class="switch">
                    <input type="checkbox" id="availability-toggle" onchange="toggleAvailability(this.checked)">
                    <span class="slider"></span>
                </label>
                <span id="availability-label">Available</span>
            </div>

            <div class="topbar__user-info" id="topbar-user-info" style="display:none">
                <div class="topbar__avatar" id="topbar-avatar">?</div>
                <span id="topbar-username"></span>
                <span id="topbar-role" class="badge badge--role" style="font-size:10px;"></span>
            </div>
            <button class="btn btn--ghost btn--sm" onclick="doLogout()" id="logout-btn" style="display:none">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sign Out
            </button>
        </div>
    </header>

    <div class="app-shell">
        {{-- Sidebar — roles control visibility of items via CSS + JS body classes --}}
        <nav class="sidebar" id="sidebar">
            <div class="sidebar__section">Main</div>

            <a href="/test/dashboard" class="sidebar__link @yield('nav-dashboard')">
                <span class="sidebar__icon">📊</span> Dashboard
            </a>
            <a href="/test/leads" class="sidebar__link @yield('nav-leads')">
                <span class="sidebar__icon">👥</span> Leads
            </a>
            <a href="/test/leads/create" class="sidebar__link @yield('nav-create')">
                <span class="sidebar__icon">✚</span> New Lead
            </a>
            <a href="/test/activities" class="sidebar__link @yield('nav-activities')">
                <span class="sidebar__icon">📅</span> Follow-ups
            </a>

            {{-- Management section — hidden for salesperson role --}}
            <div class="sidebar__divider admin-only" style="display:block;"></div>
            <div class="sidebar__section admin-only">Management</div>
            <a href="/test/pipeline" class="sidebar__link admin-only @yield('nav-pipeline')">
                <span class="sidebar__icon">🔀</span> Pipeline
            </a>
            <a href="/test/import" class="sidebar__link admin-only @yield('nav-import')">
                <span class="sidebar__icon">📤</span> Bulk Import
            </a>
            <a href="/test/users" class="sidebar__link admin-only @yield('nav-users')">
                <span class="sidebar__icon">👤</span> Users
            </a>

            {{-- System section — admin/manager only --}}
            <div class="sidebar__divider admin-only" style="display:block;"></div>
            <div class="sidebar__section admin-only">System</div>
            <a href="/test/audit" class="sidebar__link admin-only @yield('nav-audit')">
                <span class="sidebar__icon">📋</span> Audit Logs
            </a>
            <a href="/test/sla" class="sidebar__link admin-only @yield('nav-sla')">
                <span class="sidebar__icon">⏱️</span> SLA Policies
            </a>
        </nav>

        {{-- Main Content --}}
        <main class="main-content">
            @yield('content')
        </main>
    </div>

    {{-- Toast container --}}
    <div id="toast-container"></div>

    {{-- More-menu overlay closer --}}
    <div id="menu-overlay" onclick="closeAllMenus()" style="display:none;position:fixed;inset:0;z-index:99;"></div>

    <script>
    /* ══════════════════════════════════════════════════════════════
       ROLE SYSTEM
       ══════════════════════════════════════════════════════════════ */
    const ROLES = {
        admin:       { label: 'Admin',       canSeeAudit: true,  canSeeUsers: true,   canSeeSLA: true,   canSeeGlobal: true  },
        manager:     { label: 'Manager',     canSeeAudit: true,  canSeeUsers: true,   canSeeSLA: true,   canSeeGlobal: true  },
        team_lead:   { label: 'Team Lead',   canSeeAudit: false, canSeeUsers: false,  canSeeSLA: false,  canSeeGlobal: true  },
        salesperson: { label: 'Salesperson', canSeeAudit: false, canSeeUsers: false,  canSeeSLA: false,  canSeeGlobal: false },
    };

    function getRoleConfig(role) {
        return ROLES[role] || ROLES.salesperson;
    }

    /* ══════════════════════════════════════════════════════════════
       API HELPERS
       ══════════════════════════════════════════════════════════════ */
    const API_BASE = '/api';

    function getToken() { return localStorage.getItem('hilite_token'); }
    function setToken(t) { localStorage.setItem('hilite_token', t); }
    function setUser(u)  { localStorage.setItem('hilite_user', JSON.stringify(u)); }
    function getUser()   {
        try { return JSON.parse(localStorage.getItem('hilite_user')); } catch { return null; }
    }
    function clearAuth() {
        localStorage.removeItem('hilite_token');
        localStorage.removeItem('hilite_user');
    }

    async function api(path, options = {}) {
        const token = getToken();
        if (!token && !path.includes('/auth/login')) {
            window.location.href = '/test/login';
            return;
        }
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
        };
        const res = await fetch(`${API_BASE}${path}`, { ...options, headers });
        if (res.status === 401) { clearAuth(); window.location.href = '/test/login'; return; }
        return res;
    }

    async function doLogout() {
        const btn = document.getElementById('logout-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Signing out…'; }
        try { await api('/auth/logout', { method: 'POST' }); } catch {}
        clearAuth();
        window.location.href = '/test/login';
    }

    async function toggleAvailability(isAvailable) {
        const lbl = document.getElementById('availability-label');
        lbl.textContent = 'Updating…';
        
        const res = await api('/auth/availability', {
            method: 'PATCH',
            body: JSON.stringify({ is_available: isAvailable })
        });
        
        if (res && res.ok) {
            const data = await res.json();
            const user = getUser();
            if (user) {
                user.is_available = data.data.is_available;
                setUser(user);
            }
            lbl.textContent = data.data.is_available ? 'Available' : 'On Break';
            lbl.style.color = data.data.is_available ? '#059669' : 'var(--text-muted)';
            toast(data.data.is_available ? 'You are now marked as Available.' : 'You are now marked as On Break. You will not receive new leads.', 'success');
        } else {
            // Revert on failure
            document.getElementById('availability-toggle').checked = !isAvailable;
            lbl.textContent = !isAvailable ? 'Available' : 'On Break';
            toast('Failed to update availability', 'error');
        }
    }

    /* ══════════════════════════════════════════════════════════════
       TOAST NOTIFICATIONS
       ══════════════════════════════════════════════════════════════ */
    function toast(message, type = 'info') {
        const icons = {
            success: `<svg width="14" height="14" fill="none" stroke="#059669" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>`,
            error:   `<svg width="14" height="14" fill="none" stroke="#DC2626" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
            warning: `<svg width="14" height="14" fill="none" stroke="#D97706" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
            info:    `<svg width="14" height="14" fill="none" stroke="#7C3AED" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
        };
        const container = document.getElementById('toast-container');
        const el = document.createElement('div');
        el.className = `toast toast--${type}`;
        el.innerHTML = `<span>${icons[type] || icons.info}</span> ${message}`;
        container.appendChild(el);
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('toast--visible')));
        setTimeout(() => {
            el.classList.remove('toast--visible');
            setTimeout(() => el.remove(), 400);
        }, 4000);
    }

    /* ══════════════════════════════════════════════════════════════
       AUTH GUARD & ROLE APPLICATION
       ══════════════════════════════════════════════════════════════ */
    (function() {
        const isLoginPage = window.location.pathname === '/test/login';
        const token = getToken();

        if (!token && !isLoginPage) { window.location.href = '/test/login'; return; }
        if (!token || isLoginPage)  return;

        const user   = getUser();
        const role   = user?.role || 'salesperson';
        const config = getRoleConfig(role);

        // Apply role class to body for CSS-based visibility
        document.body.classList.add(`role-${role}`);

        // Availability Toggle
        const availWrapper = document.getElementById('availability-wrapper');
        const availToggle  = document.getElementById('availability-toggle');
        const availLabel   = document.getElementById('availability-label');
        
        if (availWrapper && user) {
            availWrapper.style.display = 'flex';
            const isAvailable = user.is_available ?? true; // fallback to true
            availToggle.checked = isAvailable;
            availLabel.textContent = isAvailable ? 'Available' : 'On Break';
            availLabel.style.color = isAvailable ? '#059669' : 'var(--text-muted)';
        }

        // Show user info in topbar
        const userInfoEl = document.getElementById('topbar-user-info');
        const logoutBtn  = document.getElementById('logout-btn');
        const avatarEl   = document.getElementById('topbar-avatar');
        const nameEl     = document.getElementById('topbar-username');
        const roleEl     = document.getElementById('topbar-role');

        if (userInfoEl) userInfoEl.style.display = 'flex';
        if (logoutBtn)  logoutBtn.style.display  = 'inline-flex';

        if (user) {
            if (avatarEl) avatarEl.textContent = (user.name || '?')[0].toUpperCase();
            if (nameEl)   nameEl.textContent   = user.name;
            if (roleEl) {
                roleEl.textContent = config.label;
                roleEl.className   = `badge ${role === 'admin' ? 'badge--admin' : role === 'manager' ? 'badge--warning' : 'badge--role'}`;
            }
        }

        // Role-based sidebar: hide admin-only links from salesperson/team_lead
        const adminItems = document.querySelectorAll('.admin-only');
        if (!config.canSeeGlobal) {
            // Salesperson: hide everything in Management & System sections
            adminItems.forEach(el => {
                el.style.display = 'none';
            });
        }

        // Role-based route guard: redirect if user lands on restricted page
        const restrictedRoutes = {
            '/test/audit': (cfg) => !cfg.canSeeAudit,
            '/test/sla':   (cfg) => !cfg.canSeeSLA,
            '/test/users': (cfg) => !cfg.canSeeUsers,
        };

        const currentPath = window.location.pathname;
        if (restrictedRoutes[currentPath]?.(config)) {
            document.getElementById('sidebar')?.remove();
            // Show access denied inline – actual content will call `requireRole()`
        }

        // Expose role config globally for page scripts
        window.__role   = role;
        window.__config = config;
        window.__user   = user;
    })();

    /* ══════════════════════════════════════════════════════════════
       ROLE GUARD HELPER (called per-page)
       ══════════════════════════════════════════════════════════════ */
    function requireRole(allowedRoles, redirectUrl = '/test/dashboard') {
        const role = window.__role || 'salesperson';
        if (!allowedRoles.includes(role)) {
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.innerHTML = `
                    <div class="access-denied animate-in">
                        <div class="access-denied__icon">🔒</div>
                        <h2>Access Restricted</h2>
                        <p>You don't have permission to view this page. This area is reserved for ${allowedRoles.join(' or ')} roles.</p>
                        <a href="${redirectUrl}" class="btn btn--primary">← Go to Dashboard</a>
                    </div>`;
            }
            return false;
        }
        return true;
    }

    /* ══════════════════════════════════════════════════════════════
       PROGRESSIVE DISCLOSURE HELPERS
       ══════════════════════════════════════════════════════════════ */

    // Advanced toggle button
    function toggleAdvanced(toggleId, panelId) {
        const toggle = document.getElementById(toggleId);
        const panel  = document.getElementById(panelId);
        if (!toggle || !panel) return;
        const isOpen = panel.classList.contains('open');
        panel.classList.toggle('open', !isOpen);
        toggle.classList.toggle('open', !isOpen);
        toggle.querySelector('.toggle-label').textContent = isOpen ? 'Advanced Options' : 'Hide Options';
    }

    // Accordion
    function toggleAccordion(el) {
        const accordion = el.closest('.accordion');
        accordion.classList.toggle('open');
    }

    // More menu
    function openMoreMenu(menuId) {
        closeAllMenus();
        const menu = document.getElementById(menuId);
        if (!menu) return;
        menu.classList.add('open');
        document.getElementById('menu-overlay').style.display = 'block';
    }

    function closeAllMenus() {
        document.querySelectorAll('.more-menu__dropdown.open').forEach(m => m.classList.remove('open'));
        document.getElementById('menu-overlay').style.display = 'none';
    }

    /* ══════════════════════════════════════════════════════════════
       DATE / TIME HELPERS
       ══════════════════════════════════════════════════════════════ */
    function relativeTime(isoStr) {
        if (!isoStr) return '—';
        const diff = Math.floor((Date.now() - new Date(isoStr)) / 1000);
        if (diff < 60)     return 'just now';
        if (diff < 3600)   return Math.floor(diff / 60)   + 'm ago';
        if (diff < 86400)  return Math.floor(diff / 3600)  + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        return new Date(isoStr).toLocaleDateString('en-US', { month:'short', day:'numeric' });
    }

    function formatDate(isoStr) {
        if (!isoStr) return '—';
        return new Date(isoStr).toLocaleString('en-US', {
            month:'short', day:'numeric', year:'numeric',
            hour:'2-digit', minute:'2-digit'
        });
    }

    function formatDateOnly(isoStr) {
        if (!isoStr) return '—';
        return new Date(isoStr).toLocaleDateString('en-US', {
            month:'short', day:'numeric', year:'numeric'
        });
    }
    </script>
    @yield('scripts')
</body>
</html>
