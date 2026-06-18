<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Hilite LMS</title>
    <meta name="description" content="Sign in to Hilite LMS Lead Management System">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/test-ui.css') }}">
    <style>
        html, body { overflow: auto; }

        .login-features {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 32px;
        }

        .login-feature {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            font-weight: 500;
            color: var(--text-muted);
            background: var(--bg-body);
            border: 1px solid var(--border);
            padding: 4px 10px;
            border-radius: var(--radius-full);
        }

        .login-feature__dot {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
        }

        .pwd-wrapper {
            position: relative;
        }

        .pwd-wrapper .form-control {
            padding-right: 44px;
        }

        .pwd-toggle {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 16px;
            padding: 4px;
            transition: var(--transition);
            display: flex; align-items: center;
        }

        .pwd-toggle:hover { color: var(--accent); }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card animate-in">
            <div class="login-card__logo">
                <div class="login-card__logo-icon">H</div>
            </div>

            <h1>Welcome back</h1>
            <p class="login-card__subtitle">Sign in to your Hilite LMS account</p>

            <div class="login-features">
                <span class="login-feature"><span class="login-feature__dot"></span>Lead Management</span>
                <span class="login-feature"><span class="login-feature__dot"></span>Pipeline Tracking</span>
                <span class="login-feature"><span class="login-feature__dot"></span>SLA Monitoring</span>
            </div>

            <div class="login-error" id="login-error"></div>

            <form id="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" class="form-control"
                        placeholder="you@example.com"
                        required autofocus autocomplete="email">
                </div>
                <div class="form-group" style="margin-bottom:24px;">
                    <label for="password">Password</label>
                    <div class="pwd-wrapper">
                        <input type="password" id="password" class="form-control"
                            placeholder="Enter your password"
                            required autocomplete="current-password">
                        <button type="button" class="pwd-toggle" onclick="togglePassword()" title="Show/hide password">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn--primary btn--lg" style="width:100%;" id="login-btn">
                    Sign In →
                </button>
            </form>

            <p style="text-align:center;margin-top:20px;font-size:11.5px;color:var(--text-muted);">
                🔐 Secured by Hilite LMS Authentication
            </p>
        </div>
    </div>

    <script>
        if (localStorage.getItem('hilite_token')) {
            window.location.href = '/test/dashboard';
        }

        function togglePassword() {
            const pwd = document.getElementById('password');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
        }

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn   = document.getElementById('login-btn');
            const errEl = document.getElementById('login-error');
            errEl.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner" style="width:15px;height:15px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff;"></span> Signing in…';

            try {
                const res = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        email:    document.getElementById('email').value,
                        password: document.getElementById('password').value,
                    }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    localStorage.setItem('hilite_token', data.data.token);
                    localStorage.setItem('hilite_user', JSON.stringify(data.data.user));
                    btn.innerHTML = '✓ Success! Redirecting…';
                    setTimeout(() => window.location.href = '/test/dashboard', 400);
                } else {
                    errEl.innerHTML = `<svg width="14" height="14" fill="none" stroke="#DC2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> ${data.message || 'Invalid credentials'}`;
                    errEl.style.display = 'flex';
                    btn.disabled = false;
                    btn.textContent = 'Sign In →';
                }
            } catch {
                errEl.innerHTML = '⚠ Network error — is the server running?';
                errEl.style.display = 'flex';
                btn.disabled = false;
                btn.textContent = 'Sign In →';
            }
        });
    </script>
</body>
</html>
