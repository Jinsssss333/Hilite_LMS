@extends('layouts.app')
@section('title', 'Pipeline Stages')
@section('nav-pipeline', 'active')

@section('content')
<div class="page-header">
    <h1>Pipeline Stages</h1>
    <p>Visual overview of your company's lead pipeline configuration</p>
</div>

{{-- Pipeline Visual --}}
<div id="pipeline-visual" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:32px;"></div>

{{-- Stages Table --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card__title"><span class="card__title-icon">🔀</span> All Pipeline Stages</div>
    <div class="table-wrap" style="border:none;border-radius:0;background:transparent;box-shadow:none;">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Stage Name</th>
                    <th>Color</th>
                    <th>SLA Days</th>
                    <th>Closed?</th>
                </tr>
            </thead>
            <tbody id="stages-tbody">
                <tr><td colspan="5"><div class="loading-block"><span class="spinner"></span> Loading stages…</div></td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Dispositions Table --}}
<div class="card">
    <div class="card__title"><span class="card__title-icon">🏷️</span> Dispositions</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Available outcome labels per stage</p>
    <div class="table-wrap" style="border:none;border-radius:0;background:transparent;box-shadow:none;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Label</th>
                    <th>Stage</th>
                </tr>
            </thead>
            <tbody id="disp-tbody">
                <tr><td colspan="3"><div class="loading-block"><span class="spinner"></span> Loading…</div></td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
(async function() {
    // Pipeline stages
    const stRes = await api('/pipeline-stages');
    if (!stRes) return;
    const stData = await stRes.json();
    const stageMap = {};

    if (stData.success) {
        stData.data.forEach(s => stageMap[s.id] = s.name);

        // Pipeline visual
        const visualEl = document.getElementById('pipeline-visual');
        visualEl.innerHTML = stData.data.map((s, i) => `
            <div style="
                display:flex;align-items:center;gap:8px;
                background:${s.color}12;
                border:1px solid ${s.color}35;
                border-radius:var(--radius-sm);
                padding:10px 16px;
                min-width:140px;
                position:relative;
            ">
                <div style="
                    width:10px;height:10px;
                    border-radius:50%;
                    background:${s.color};
                    flex-shrink:0;
                    box-shadow:0 0 8px ${s.color}80;
                "></div>
                <div>
                    <div style="font-weight:700;font-size:13px;color:${s.color};">${s.name}</div>
                    <div style="font-size:11px;color:var(--text-muted);">
                        ${s.sla_days ? `${s.sla_days}d SLA` : 'No SLA'}
                        ${s.is_closed ? ' · Closed' : ''}
                    </div>
                </div>
                ${i < stData.data.length - 1 ? '<div style="margin-left:auto;color:var(--text-muted);font-size:18px;">→</div>' : ''}
            </div>
        `).join('');

        // Stages table
        document.getElementById('stages-tbody').innerHTML = stData.data.map(s => `
            <tr>
                <td>
                    <span style="
                        display:inline-flex;align-items:center;justify-content:center;
                        width:26px;height:26px;
                        background:var(--bg-hover);
                        border-radius:6px;
                        font-weight:700;font-size:12px;
                        color:var(--text-muted);
                    ">${s.order}</span>
                </td>
                <td><strong>${s.name}</strong></td>
                <td>
                    <div class="color-swatch">
                        <span class="color-swatch__dot" style="background:${s.color};box-shadow:0 0 6px ${s.color}60;"></span>
                        <code style="font-size:12px;">${s.color}</code>
                    </div>
                </td>
                <td>
                    ${s.sla_days
                        ? `<span class="badge badge--role">${s.sla_days} days</span>`
                        : '<span style="color:var(--text-muted);">—</span>'}
                </td>
                <td>
                    ${s.is_closed
                        ? '<span class="badge badge--sla-breached">Yes</span>'
                        : '<span class="badge badge--sla-ok">No</span>'}
                </td>
            </tr>
        `).join('');
    }

    // Dispositions
    const dRes = await api('/dispositions');
    if (dRes) {
        const dData = await dRes.json();
        if (dData.success) {
            if (dData.data.length) {
                document.getElementById('disp-tbody').innerHTML = dData.data.map(d => `
                    <tr>
                        <td><span style="color:var(--text-muted);font-size:12px;font-weight:600;">#${d.id}</span></td>
                        <td><strong>${d.label}</strong></td>
                        <td>
                            ${d.stage_id
                                ? `<span class="badge badge--role" style="font-size:11px;">${stageMap[d.stage_id] || 'Stage #' + d.stage_id}</span>`
                                : '<span style="color:var(--text-muted);">Global</span>'}
                        </td>
                    </tr>
                `).join('');
            } else {
                document.getElementById('disp-tbody').innerHTML = `
                    <tr><td colspan="3">
                        <div class="empty-state" style="padding:32px;">
                            <div class="empty-state__text">No dispositions configured</div>
                        </div>
                    </td></tr>`;
            }
        }
    }
})();
</script>
@endsection
