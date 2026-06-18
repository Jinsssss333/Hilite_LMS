@extends('layouts.app')
@section('title', 'Bulk Import')
@section('nav-import', 'active')

@section('content')
<div class="page-header">
    <h1>Bulk CSV Import</h1>
    <p>Upload a CSV file to import leads in bulk — up to 50,000 rows, 10 MB maximum</p>
</div>

<div class="detail-grid">
    {{-- Upload Form --}}
    <div class="card">
        <div class="card__title"><span class="card__title-icon">📤</span> Upload CSV</div>

        <div style="
            background:var(--bg-input);
            border:2px dashed var(--border);
            border-radius:var(--radius);
            padding:40px 32px;
            text-align:center;
            margin-bottom:20px;
            transition:var(--transition);
            cursor:pointer;
        " id="drop-zone"
             onclick="document.getElementById('csv-file').click()">
            <div style="font-size:40px;margin-bottom:10px;">📄</div>
            <div style="font-size:14px;font-weight:600;color:var(--text-secondary);margin-bottom:6px;" id="drop-label">
                Click to select or drag &amp; drop a CSV file
            </div>
            <div style="font-size:12px;color:var(--text-muted);">
                Required: <code>phone</code>, <code>name</code> &nbsp;·&nbsp; Optional: <code>email</code>, <code>notes</code>
            </div>
            <input type="file" id="csv-file" accept=".csv,.txt" style="display:none;">
        </div>

        <div class="form-group">
            <label for="import-source">Source Tag</label>
            <select id="import-source" class="form-control">
                <option value="csv">CSV Import</option>
                <option value="web">Web</option>
                <option value="referral">Referral</option>
                <option value="manual">Manual</option>
            </select>
        </div>

        <button class="btn btn--primary" onclick="startUpload()" id="btn-upload" disabled style="width:100%;justify-content:center;">
            Upload & Import
        </button>

        <div id="upload-error" class="login-error" style="margin-top:14px;"></div>
    </div>

    {{-- CSV Preview --}}
    <div class="card">
        <div class="card__title"><span class="card__title-icon">👀</span> CSV Preview</div>
        <div id="csv-preview">
            <div class="empty-state">
                <div class="empty-state__icon">👀</div>
                <div class="empty-state__text">Select a file to preview the first rows</div>
            </div>
        </div>
    </div>
</div>

{{-- Import Progress --}}
<div class="card" style="margin-top:20px;" id="progress-card" hidden>
    <div class="card__title"><span class="card__title-icon">⚙️</span> Import Progress</div>
    <div id="progress-content">
        <div class="loading-block"><span class="spinner"></span> Starting import…</div>
    </div>
</div>

{{-- Import History --}}
<div class="card" style="margin-top:20px;margin-bottom:32px;">
    <div class="card__title"><span class="card__title-icon">🔍</span> Check Import Job Status</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
        Paste a Job ID below to check status of any previous import.
    </div>
    <div class="filters-row" style="margin-bottom:12px;">
        <div class="form-group">
            <label for="check-uuid">Job UUID</label>
            <input type="text" id="check-uuid" class="form-control" placeholder="e.g. a1b2c3d4-…">
        </div>
        <button class="btn btn--outline" onclick="checkJobStatus()">Check Status</button>
    </div>
    <div id="job-status-result"></div>
</div>
@endsection

@section('scripts')
<script>
let selectedFile = null;
let pollingTimer = null;

// ─── File Selection ─────────────────────────────────────────────
const fileInput = document.getElementById('csv-file');
const dropZone = document.getElementById('drop-zone');
const dropLabel = document.getElementById('drop-label');

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length) handleFile(e.target.files[0]);
});

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = 'var(--accent)';
});

dropZone.addEventListener('dragleave', () => {
    dropZone.style.borderColor = 'var(--border)';
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = 'var(--border)';
    if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
});

function handleFile(file) {
    if (!file.name.match(/\.(csv|txt)$/i)) {
        toast('Please select a .csv or .txt file', 'error');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        toast('File exceeds 10 MB limit', 'error');
        return;
    }
    selectedFile = file;
    dropLabel.innerHTML = `<strong style="color:var(--success)">✓ ${file.name}</strong> <span style="color:var(--text-muted)">(${(file.size / 1024).toFixed(1)} KB)</span>`;
    document.getElementById('btn-upload').disabled = false;
    previewCSV(file);
}

// ─── CSV Preview ─────────────────────────────────────────────────
function previewCSV(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        const lines = e.target.result.split('\n').filter(l => l.trim());
        const maxPreview = Math.min(lines.length, 8);
        if (maxPreview < 2) {
            document.getElementById('csv-preview').innerHTML = '<div style="color:var(--danger)">File appears empty or has no data rows.</div>';
            return;
        }

        const headers = parseCSVLine(lines[0]);
        let html = `<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">${lines.length - 1} data rows detected</div>`;
        html += '<div class="table-wrap"><table><thead><tr>';
        headers.forEach(h => html += `<th>${h}</th>`);
        html += '</tr></thead><tbody>';

        for (let i = 1; i < maxPreview; i++) {
            const cols = parseCSVLine(lines[i]);
            html += '<tr>';
            headers.forEach((_, j) => html += `<td style="font-size:12px">${cols[j] || ''}</td>`);
            html += '</tr>';
        }

        if (lines.length > maxPreview) {
            html += `<tr><td colspan="${headers.length}" style="text-align:center;color:var(--text-muted);font-size:12px;">… ${lines.length - maxPreview} more rows</td></tr>`;
        }

        html += '</tbody></table></div>';
        document.getElementById('csv-preview').innerHTML = html;
    };
    reader.readAsText(file);
}

function parseCSVLine(line) {
    const result = [];
    let current = '';
    let inQuotes = false;
    for (let i = 0; i < line.length; i++) {
        const c = line[i];
        if (c === '"') { inQuotes = !inQuotes; }
        else if (c === ',' && !inQuotes) { result.push(current.trim()); current = ''; }
        else { current += c; }
    }
    result.push(current.trim());
    return result;
}

// ─── Upload ──────────────────────────────────────────────────────
async function startUpload() {
    if (!selectedFile) return toast('Select a file first', 'warning');

    const btn = document.getElementById('btn-upload');
    const errEl = document.getElementById('upload-error');
    errEl.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Uploading…';

    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('source', document.getElementById('import-source').value);

    try {
        const token = getToken();
        const res = await fetch('/api/leads/import', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`,
            },
            body: formData,
        });

        const data = await res.json();

        if (res.ok && data.success) {
            toast('Upload accepted! Processing in background.', 'success');
            startPolling(data.data.job_id);
        } else {
            errEl.textContent = data.message || 'Upload failed';
            errEl.style.display = 'block';
            toast(data.message || 'Upload failed', 'error');
        }
    } catch (err) {
        errEl.textContent = 'Network error — is the API running?';
        errEl.style.display = 'block';
    }

    btn.disabled = false;
    btn.textContent = 'Upload & Import';
}

// ─── Poll for Status ─────────────────────────────────────────────
function startPolling(jobId) {
    const card = document.getElementById('progress-card');
    card.hidden = false;
    card.scrollIntoView({ behavior: 'smooth' });

    pollStatus(jobId);
    pollingTimer = setInterval(() => pollStatus(jobId), 3000);
}

async function pollStatus(jobId) {
    const res = await api(`/leads/import/${jobId}/status`);
    if (!res) return stopPolling();
    const data = await res.json();

    if (!data.success) {
        document.getElementById('progress-content').innerHTML = `<div style="color:var(--danger)">${data.message || 'Error fetching status'}</div>`;
        stopPolling();
        return;
    }

    const d = data.data;
    const isComplete = d.status === 'completed' || d.status === 'failed';

    const percent = d.total_rows > 0 ? Math.round((d.processed / d.total_rows) * 100) : 0;

    let html = `
        <div class="stats-row" style="margin-bottom:16px;">
            <div class="stat-card">
                <div class="stat-card__label">Status</div>
                <div class="stat-card__value" style="font-size:18px;color:${d.status === 'completed' ? 'var(--success)' : d.status === 'failed' ? 'var(--danger)' : 'var(--warning)'};text-transform:capitalize;">
                    ${d.status === 'processing' ? '<span class="spinner" style="margin-right:8px;"></span>' : ''}${d.status}
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">Progress</div>
                <div class="stat-card__value stat-card__value--accent" style="font-size:18px;">${d.processed ?? 0} / ${d.total_rows}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">Created</div>
                <div class="stat-card__value stat-card__value--success" style="font-size:18px;">${d.created ?? 0}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">Duplicates</div>
                <div class="stat-card__value stat-card__value--warning" style="font-size:18px;">${d.duplicates_attached ?? 0}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">Failed</div>
                <div class="stat-card__value stat-card__value--danger" style="font-size:18px;">${d.failed ?? 0}</div>
            </div>
        </div>

        <div style="background:var(--bg-input);border-radius:var(--radius-sm);height:8px;overflow:hidden;margin-bottom:16px;">
            <div style="width:${percent}%;height:100%;background:linear-gradient(90deg,var(--accent),var(--success));transition:width .5s ease;border-radius:var(--radius-sm);"></div>
        </div>

        <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;">Job ID: <code>${d.job_id}</code></div>
    `;

    if (d.errors && d.errors.length > 0) {
        html += `
            <div style="margin-top:14px;">
                <div class="card__title">Errors (${d.errors.length})</div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Row</th><th>Reason</th></tr></thead>
                        <tbody>
                            ${d.errors.slice(0, 20).map(e => `<tr><td style="color:var(--text-muted)">${e.row}</td><td style="color:var(--danger);font-size:12px;">${e.reason}</td></tr>`).join('')}
                            ${d.errors.length > 20 ? `<tr><td colspan="2" style="text-align:center;color:var(--text-muted);font-size:12px;">… ${d.errors.length - 20} more errors</td></tr>` : ''}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    document.getElementById('progress-content').innerHTML = html;

    if (isComplete) {
        stopPolling();
        toast(d.status === 'completed' ? 'Import completed!' : 'Import finished with errors.', d.status === 'completed' ? 'success' : 'warning');
    }
}

function stopPolling() {
    if (pollingTimer) {
        clearInterval(pollingTimer);
        pollingTimer = null;
    }
}

// ─── Manual Job Check ────────────────────────────────────────────
async function checkJobStatus() {
    const uuid = document.getElementById('check-uuid').value.trim();
    if (!uuid) return toast('Enter a Job UUID', 'warning');

    const resultEl = document.getElementById('job-status-result');
    resultEl.innerHTML = '<div class="loading-block"><span class="spinner"></span> Fetching…</div>';

    const res = await api(`/leads/import/${uuid}/status`);
    if (!res) { resultEl.innerHTML = ''; return; }
    const data = await res.json();

    if (data.success) {
        const d = data.data;
        resultEl.innerHTML = `
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-card__label">Status</div>
                    <div class="stat-card__value" style="font-size:16px;text-transform:capitalize;color:${d.status === 'completed' ? 'var(--success)' : d.status === 'failed' ? 'var(--danger)' : 'var(--warning)'};">${d.status}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__label">Total Rows</div>
                    <div class="stat-card__value" style="font-size:16px;">${d.total_rows}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__label">Processed</div>
                    <div class="stat-card__value" style="font-size:16px;">${d.processed ?? 0}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__label">Created</div>
                    <div class="stat-card__value stat-card__value--success" style="font-size:16px;">${d.created ?? 0}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__label">Duplicates</div>
                    <div class="stat-card__value stat-card__value--warning" style="font-size:16px;">${d.duplicates_attached ?? 0}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__label">Failed</div>
                    <div class="stat-card__value stat-card__value--danger" style="font-size:16px;">${d.failed ?? 0}</div>
                </div>
            </div>
            ${d.errors && d.errors.length ? '<div style="margin-top:8px;font-size:12px;color:var(--danger)">' + d.errors.length + ' error(s) — see full details above by re-running the import.</div>' : ''}
        `;
    } else {
        resultEl.innerHTML = `<div style="color:var(--danger)">${data.message || 'Job not found'}</div>`;
    }
}
</script>
@endsection
