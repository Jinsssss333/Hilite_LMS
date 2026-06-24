@extends('layouts.app')

@section('content')
<div class="max-w-[1200px] w-full mx-auto">
    <!-- Header Section -->
    <header class="flex flex-col gap-2 mb-8">
        <h1 class="text-headline-lg font-headline-lg text-on-surface">Lead Intake</h1>
        <p class="text-body-md text-text-muted">Add a new lead manually or bulk import via CSV.</p>
    </header>

    <!-- Hero Stats Strip -->
    <div class="bg-[#E9EFE1] rounded-[20px] p-6 mb-8 flex flex-col sm:flex-row gap-6 border border-[#d2dcc8]">
        <div class="flex-1">
            <p class="text-label-sm text-secondary uppercase tracking-wider font-bold mb-1">Today's Intake</p>
            <p class="text-headline-lg text-on-surface font-bold">14 <span class="text-body-md text-text-muted font-normal ml-2">leads added</span></p>
        </div>
        <div class="hidden sm:block w-px bg-[#d2dcc8]"></div>
        <div class="flex-1">
            <p class="text-label-sm text-secondary uppercase tracking-wider font-bold mb-1">Upload Errors</p>
            <p class="text-headline-lg text-stage-lost font-bold">0 <span class="text-body-md text-text-muted font-normal ml-2">requires attention</span></p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-8 p-4 rounded-xl border border-[#d2dcc8] bg-[#E9EFE1] text-stage-booked font-medium flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-8 p-4 rounded-xl border border-stage-lost/20 bg-stage-lost/10 text-stage-lost font-medium flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-8 p-4 rounded-xl border border-stage-lost/20 bg-stage-lost/10 text-stage-lost font-medium flex flex-col gap-1">
            @foreach($errors->all() as $error)
                <span class="flex items-center gap-2"><span class="material-symbols-outlined">error</span> {{ $error }}</span>
            @endforeach
        </div>
    @endif

    <!-- Content Panel -->
    <div class="bg-surface-container-lowest border border-border-subtle rounded-[20px] shadow-sm overflow-hidden" x-data="{ tab: 'manual' }">
        <!-- Tabs -->
        <div class="flex border-b border-border-subtle px-6 pt-4 gap-8 bg-surface-container-lowest">
            <button @click="tab = 'manual'" :class="{'border-primary text-on-surface': tab === 'manual', 'border-transparent text-text-muted hover:text-on-surface': tab !== 'manual'}" class="flex flex-col items-center justify-center border-b-2 pb-3 px-2 transition-colors">
                <span class="text-body-sm font-medium">Manual Entry</span>
            </button>
            <button @click="tab = 'bulk'" :class="{'border-primary text-on-surface': tab === 'bulk', 'border-transparent text-text-muted hover:text-on-surface': tab !== 'bulk'}" class="flex flex-col items-center justify-center border-b-2 pb-3 px-2 transition-colors">
                <span class="text-body-sm font-medium">Bulk Import</span>
            </button>
        </div>

        <!-- Tab Contents -->
        <div class="p-6 md:p-8">
            <!-- Manual Entry Form -->
            <form x-show="tab === 'manual'" action="{{ route('leads.manual.post') }}" method="POST" class="flex flex-col gap-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <label class="flex flex-col gap-2">
                        <span class="text-label-md text-on-surface">Full Name</span>
                        <input name="name" class="form-input w-full rounded-xl border border-border-subtle bg-surface-container-lowest h-12 px-4 text-body-md placeholder:text-text-muted" placeholder="e.g. Jane Doe" type="text" required />
                    </label>

                    <!-- Phone -->
                    <label class="flex flex-col gap-2 relative">
                        <span class="text-label-md text-on-surface flex justify-between items-center">
                            <span class="flex items-center gap-1">
                                Phone Number
                                <span class="material-symbols-outlined text-[14px] text-text-muted cursor-help" title="Enter the raw number — country code is detected automatically via libphonenumber.">info</span>
                            </span>
                            <span class="text-stage-interested bg-stage-interested/10 px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wide font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">check_circle</span> Unique
                            </span>
                        </span>
                        <div class="flex">
                            <div class="flex items-center justify-center bg-surface-container-low border border-border-subtle border-r-0 rounded-l-xl px-3 text-text-muted border-collapse">
                                <span class="material-symbols-outlined text-[18px]">language</span>
                                <span class="ml-1 text-sm">+1</span>
                            </div>
                            <input name="phone" class="form-input flex-1 rounded-r-xl border border-border-subtle bg-surface-container-lowest h-12 px-4 text-body-md placeholder:text-text-muted font-mono tracking-wide" placeholder="(555) 000-0000" type="tel" required />
                        </div>
                    </label>

                    <!-- Email -->
                    <label class="flex flex-col gap-2">
                        <span class="text-label-md text-on-surface">Email Address</span>
                        <input name="email" class="form-input w-full rounded-xl border border-border-subtle bg-surface-container-lowest h-12 px-4 text-body-md placeholder:text-text-muted" placeholder="jane@example.com" type="email" />
                    </label>

                    <!-- Source Dropdown -->
                    <label class="flex flex-col gap-2">
                        <span class="text-label-md text-on-surface">Source</span>
                        <select name="source" class="form-input w-full rounded-xl border border-border-subtle bg-surface-container-lowest h-12 px-4 text-body-md text-on-surface" required>
                            <option value="manual">Manual Entry</option>
                            <option value="csv">CSV Import</option>
                            <option value="webhook">Website Webhook</option>
                            <option value="callsync_auto">CallSync Auto</option>
                        </select>
                    </label>

                    <!-- Assigned Rep -->
                    <label class="flex flex-col gap-2">
                        <span class="text-label-md text-on-surface">Assigned Rep</span>
                        <select name="assigned_user_id" class="form-input w-full rounded-xl border border-border-subtle bg-surface-container-lowest h-12 px-4 text-body-md text-on-surface">
                            <option value="unassigned">Unassigned (Auto Route)</option>
                            <option value="1">Admin User</option>
                        </select>
                    </label>

                    <!-- Pipeline Stage -->
                    <label class="flex flex-col gap-2">
                        <span class="text-label-md text-on-surface">Initial Stage</span>
                        <div class="relative">
                            <select disabled class="form-input w-full rounded-xl border border-border-subtle bg-surface-container-lowest h-12 px-4 pl-10 text-body-md text-on-surface opacity-50 cursor-not-allowed">
                                <option value="new">New Lead</option>
                            </select>
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-stage-new"></div>
                        </div>
                    </label>
                </div>
                <!-- Notes Area -->
                <label class="flex flex-col gap-2 mt-2">
                    <span class="text-label-md text-on-surface">Internal Notes</span>
                    <textarea name="notes" class="form-input w-full rounded-xl border border-border-subtle bg-surface-container-lowest p-4 text-body-md placeholder:text-text-muted min-h-[120px] resize-y" placeholder="Add any initial context, preferences, or constraints here..."></textarea>
                </label>
                <!-- Actions -->
                <div class="flex justify-end items-center gap-4 mt-4 pt-6 border-t border-border-subtle">
                    <button type="button" class="px-6 py-2.5 rounded-full border border-border-subtle text-on-surface text-body-sm font-medium hover:bg-surface-container-low transition-colors">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 rounded-full bg-primary text-on-primary text-body-sm font-medium flex items-center gap-2 hover:bg-tertiary transition-colors">
                        <span class="material-symbols-outlined text-[18px]">add</span> Add Lead
                    </button>
                </div>
            </form>

            <!-- Bulk Import Section -->
            <div x-show="tab === 'bulk'" style="display: none;" class="flex flex-col gap-8">
                <!-- Template Download Banner -->
                <div class="flex items-center justify-between bg-surface-container-low p-4 rounded-xl border border-border-subtle">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-text-muted text-[24px]">description</span>
                        <div>
                            <p class="text-body-sm font-medium text-on-surface">Ensure your data matches our format.</p>
                            <p class="text-body-sm text-text-muted">Missing fields may cause import errors.</p>
                        </div>
                    </div>
                    <button type="button" onclick="downloadTemplate()" class="px-4 py-2 rounded-full border border-outline-variant text-on-surface text-body-sm font-medium flex items-center gap-2 hover:bg-surface-variant transition-colors bg-surface-container-lowest">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        Download Template
                    </button>
                </div>

                <!-- Column Reference Card -->
                <div class="bg-surface-container-low border border-border-subtle rounded-xl p-4">
                    <p class="text-label-sm font-bold uppercase tracking-wider text-text-muted mb-3">CSV Column Reference</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-body-sm border-collapse">
                            <thead>
                                <tr class="border-b border-border-subtle">
                                    <th class="pb-2 pr-6 text-label-sm text-text-muted font-bold">Column</th>
                                    <th class="pb-2 pr-6 text-label-sm text-text-muted font-bold">Required</th>
                                    <th class="pb-2 pr-6 text-label-sm text-text-muted font-bold">Format / Example</th>
                                    <th class="pb-2 text-label-sm text-text-muted font-bold">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-subtle">
                                @php
                                $cols = [
                                    ['name',   true,  'Jane Doe',               'Full name of the lead'],
                                    ['phone',  true,  '9876543210',             'Raw number — country auto-detected'],
                                    ['email',  false, 'jane@example.com',        'Optional but recommended'],
                                    ['source', false, 'Website / Referral',      'How the lead was acquired'],
                                    ['region', false, 'Kerala / Dubai / UK',     'City, state, or country'],
                                    ['notes',  false, 'Interested in 2BHK',      'Any extra context'],
                                ];
                                @endphp
                                @foreach($cols as [$col, $req, $example, $note])
                                <tr>
                                    <td class="py-2 pr-6 font-mono text-[12px] text-primary font-semibold">{{ $col }}</td>
                                    <td class="py-2 pr-6">
                                        @if($req)
                                            <span class="text-[10px] font-bold uppercase tracking-wide bg-stage-lost/10 text-stage-lost px-2 py-0.5 rounded-full">Required</span>
                                        @else
                                            <span class="text-[10px] font-bold uppercase tracking-wide bg-surface-container text-text-muted px-2 py-0.5 rounded-full">Optional</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-6 font-mono text-[11px] text-on-surface-variant">{{ $example }}</td>
                                    <td class="py-2 text-[11px] text-text-muted">{{ $note }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Drop Zone Form -->
                <form action="{{ route('leads.import.post') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- Drop Zone -->
                    <div id="drop-zone"
                         class="border-2 border-dashed border-outline-variant rounded-[20px] p-12 flex flex-col items-center justify-center text-center bg-transparent hover:bg-surface-container-low hover:border-primary transition-all cursor-pointer group"
                         onclick="document.getElementById('csv-file-input').click()"
                         ondragover="event.preventDefault(); this.classList.add('bg-surface-container-low','border-primary')"
                         ondragleave="this.classList.remove('bg-surface-container-low','border-primary')"
                         ondrop="handleDrop(event)">
                        
                        <div class="w-16 h-16 rounded-full bg-surface-container-highest flex items-center justify-center mb-4 group-hover:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-outline text-[32px]">cloud_upload</span>
                        </div>
                        <h3 class="text-headline-sm text-on-surface mb-1">Click to upload or drag and drop</h3>
                        <p class="text-body-sm text-text-muted">CSV files only (Max 10MB)</p>
                        
                        <!-- Hidden real file input -->
                        <input id="csv-file-input" name="csv_file" type="file" accept=".csv" class="hidden" onchange="handleFileSelect(this.files)" />
                    </div>

                    <!-- Selected File Preview (hidden until file chosen) -->
                    <div id="file-preview" class="hidden items-center justify-between p-4 rounded-xl border border-primary bg-[#E9EFE1] mt-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary">csv</span>
                            </div>
                            <div>
                                <p id="file-name" class="text-body-sm font-semibold text-on-surface"></p>
                                <p id="file-size" class="text-label-sm text-text-muted mt-0.5"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="clearFile()" class="text-text-muted hover:text-stage-lost transition-colors" title="Remove file">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                            <button type="submit" class="px-4 py-2 rounded-full bg-primary text-on-primary text-body-sm font-medium flex items-center gap-2 hover:bg-tertiary transition-colors">
                                <span class="material-symbols-outlined text-[18px]">upload</span>
                                Start Import
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Recent Imports -->
                <div>
                    <h4 class="text-label-md text-on-surface mb-3 uppercase tracking-wider font-bold">Recent Imports</h4>
                    <div class="flex items-center justify-between p-4 rounded-xl border border-border-subtle bg-surface-container-lowest">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center">
                                <span class="material-symbols-outlined text-text-muted">csv</span>
                            </div>
                            <div>
                                <p class="text-body-sm font-medium text-on-surface">q3_campaign_leads.csv</p>
                                <p class="text-label-sm text-text-muted mt-0.5">Today, 10:42 AM · Completed</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="flex flex-col items-end">
                                <span class="text-label-sm text-text-muted">Processed</span>
                                <span class="text-body-sm font-medium text-on-surface">240</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-label-sm text-text-muted">Created</span>
                                <span class="text-body-sm font-medium text-stage-booked">215</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-label-sm text-text-muted">Duplicates</span>
                                <span class="text-body-sm font-medium text-stage-contacted">20</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-label-sm text-text-muted">Failed</span>
                                <span class="text-body-sm font-medium text-stage-lost">5</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AlpineJS for Tab switching -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
    // ── File helpers ──────────────────────────────────────────────
    function showFilePreview(file) {
        const preview = document.getElementById('file-preview');
        document.getElementById('file-name').textContent = file.name;
        const kb = (file.size / 1024).toFixed(1);
        const mb = (file.size / 1024 / 1024).toFixed(2);
        document.getElementById('file-size').textContent = file.size > 1024 * 1024
            ? `${mb} MB` : `${kb} KB`;
        preview.classList.remove('hidden');
        preview.classList.add('flex');
        document.getElementById('drop-zone').classList.add('hidden');
    }

    function handleFileSelect(files) {
        if (files && files.length > 0) showFilePreview(files[0]);
    }

    function handleDrop(event) {
        event.preventDefault();
        document.getElementById('drop-zone').classList.remove('bg-surface-container-low', 'border-primary');
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            if (!files[0].name.endsWith('.csv')) {
                alert('Only CSV files are allowed.');
                return;
            }
            // Assign the dropped files to the hidden input so the form submits them
            document.getElementById('csv-file-input').files = files;
            showFilePreview(files[0]);
        }
    }

    function clearFile() {
        document.getElementById('csv-file-input').value = '';
        const preview = document.getElementById('file-preview');
        preview.classList.add('hidden');
        preview.classList.remove('flex');
        document.getElementById('drop-zone').classList.remove('hidden');
    }

    // ── Template Download ─────────────────────────────────────────
    function downloadTemplate() {
        const headers = ['name', 'phone', 'email', 'source', 'region', 'notes'];
        const sample  = [
            ['Jane Doe',   '9876543210', 'jane@example.com', 'Website',  'Kerala', 'Interested in 2BHK'],
            ['Rahul Menon','8012345678', 'rahul@gmail.com',  'Referral', 'Dubai',  'Budget ~75L'],
            ['Arjun Shah', '+17025551234','arjun@yahoo.com', 'CSV Import','US',    ''],
        ];
        const rows = [headers, ...sample].map(r => r.map(v => `"${v}"`).join(',')).join('\n');
        const blob = new Blob([rows], { type: 'text/csv' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'hilite_leads_template.csv';
        a.click();
        URL.revokeObjectURL(url);
    }
</script>

<style>
    .form-input {
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 1px var(--color-primary);
    }
    select.form-input {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%231c1b1c%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem top 50%;
        background-size: 0.65rem auto;
    }
</style>
@endsection
