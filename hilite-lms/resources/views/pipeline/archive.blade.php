@extends('layouts.app')

@section('content')
<!-- Page Header & Filters -->
<div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h2 class="font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary tracking-tight">Closed Deals</h2>
        <p class="font-body-md text-body-md text-text-muted mt-1 max-w-xl">Archive of leads that have reached a terminal stage. Review booked successes and analyze lost opportunities.</p>
    </div>
    <div class="flex items-center gap-3 bg-surface-container-low p-1 rounded-lg border border-border-subtle self-start sm:self-auto">
        <button class="px-4 py-1.5 rounded-md font-label-md text-label-md bg-surface border border-border-subtle shadow-sm text-primary flex items-center gap-2" id="toggle-booked">
            <span class="w-2 h-2 rounded-full bg-stage-booked"></span>
            Booked
        </button>
        <button class="px-4 py-1.5 rounded-md font-label-md text-label-md text-text-muted hover:text-primary transition-colors flex items-center gap-2" id="toggle-lost">
            <span class="w-2 h-2 rounded-full bg-stage-lost opacity-50"></span>
            Lost / Dropped
        </button>
    </div>
</div>

<!-- Bento Grid Archive View -->
<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 auto-rows-max" id="archive-grid">
    <!-- Booked Card 1 -->
    <article class="bg-surface-container-lowest border border-border-subtle rounded-xl p-card-padding hover:border-primary/20 hover:shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-all duration-300 flex flex-col group archive-card booked-card">
        <div class="flex justify-between items-start mb-4">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-stage-booked/10 border border-stage-booked/20 text-stage-booked font-label-sm text-label-sm uppercase tracking-wide">
                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                Booked
            </div>
            <button class="text-text-muted opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:text-primary">
                <span class="material-symbols-outlined text-[20px]">more_vert</span>
            </button>
        </div>
        <div class="mb-6 flex-1">
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-1 truncate">The Zenith Penthouse - Unit 402</h3>
            <div class="flex items-center gap-2 text-text-muted font-body-sm text-body-sm mb-4">
                <span class="material-symbols-outlined text-[16px]">person</span>
                Eleanor Vance
            </div>
            <div class="grid grid-cols-2 gap-4 bg-surface-container-low p-3 rounded-lg border border-border-subtle/50">
                <div>
                    <p class="font-label-sm text-label-sm text-text-muted uppercase mb-0.5">Deal Value</p>
                    <p class="font-headline-sm text-[16px] text-primary">$1,250,000</p>
                </div>
                <div>
                    <p class="font-label-sm text-label-sm text-text-muted uppercase mb-0.5">Closed Date</p>
                    <p class="font-body-md text-body-md text-on-surface-variant">Oct 24, 2023</p>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-border-subtle/50 mt-auto">
            <div class="flex items-center gap-2 text-text-muted font-body-sm text-body-sm">
                <div class="w-6 h-6 rounded-full bg-surface-dim overflow-hidden">
                    <img alt="Agent Avatar" class="w-full h-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCh6Gj0j-QJM3achoTy8nqcHKPAacN4-2UoGOlvYvj1jdw9ybZS7jBmBTSkQ5N_SZcWq4mPdGKkgulHYJ6f5y0NvlBZB1hyY8VIr4QlV6wHWed50BZEgYwfdcmSWMX3fiK6IZ0HCEzsrGVYWl5xflEogYY1LHXl5Tcbmo-wPmo3p-A_EeCtjbZ_Q6EoEatBoFb4cyXmIMREBPqk5vCZbKaCunkGhP8risNKaSdqF6oH-e2jDPA-EkPRwoT9NB7Xr3ERbW1GMNzJsok">
                </div>
                <span class="">Closed by M. Smith</span>
            </div>
            <button class="font-label-md text-label-md text-primary hover:underline underline-offset-4">View Record</button>
        </div>
    </article>

    <!-- Booked Card 2 -->
    <article class="bg-surface-container-lowest border border-border-subtle rounded-xl p-card-padding hover:border-primary/20 hover:shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-all duration-300 flex flex-col group archive-card booked-card">
        <div class="flex justify-between items-start mb-4">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-stage-booked/10 border border-stage-booked/20 text-stage-booked font-label-sm text-label-sm uppercase tracking-wide">
                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                Booked
            </div>
            <button class="text-text-muted opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:text-primary">
                <span class="material-symbols-outlined text-[20px]">more_vert</span>
            </button>
        </div>
        <div class="mb-6 flex-1">
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-1 truncate">Oakwood Townhomes - Plot B</h3>
            <div class="flex items-center gap-2 text-text-muted font-body-sm text-body-sm mb-4">
                <span class="material-symbols-outlined text-[16px]">person</span>
                David &amp; Sarah Miller
            </div>
            <div class="grid grid-cols-2 gap-4 bg-surface-container-low p-3 rounded-lg border border-border-subtle/50">
                <div>
                    <p class="font-label-sm text-label-sm text-text-muted uppercase mb-0.5">Deal Value</p>
                    <p class="font-headline-sm text-[16px] text-primary">$485,000</p>
                </div>
                <div>
                    <p class="font-label-sm text-label-sm text-text-muted uppercase mb-0.5">Closed Date</p>
                    <p class="font-body-md text-body-md text-on-surface-variant">Oct 18, 2023</p>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-border-subtle/50 mt-auto">
            <div class="flex items-center gap-2 text-text-muted font-body-sm text-body-sm">
                <div class="w-6 h-6 rounded-full bg-surface-dim overflow-hidden">
                    <img alt="Agent Avatar" class="w-full h-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAAQqlXp0vDeNNxneE73ncQX5Xx6dMXIZFrM9yboIu_5yq1YiGcMK_eYT3fpjV0bEnVKKuBCsB_RaP8NLRE51xx19dNhXfRl12ggs_ks4XooTqfXW9ygqX0nZTIGeNKKQtFfLxbCkGCJnevvCDKYVQSIionBk-OQ5v2vgNl-1WI_mDjI1Pnwlyi_fG6bxPn4kzziNiIGYanp3etfp4HNdsIT3WjzevjGHFiNIjgI69IkvoUR--X7Pfcw8GdTf4jXLH9LSWZWgy26wM">
                </div>
                <span class="">Closed by J. Doe</span>
            </div>
            <button class="font-label-md text-label-md text-primary hover:underline underline-offset-4">View Record</button>
        </div>
    </article>

    <!-- Lost Card 1 -->
    <article class="bg-surface-container-lowest border border-border-subtle rounded-xl p-card-padding hover:border-error/20 hover:shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-all duration-300 flex flex-col group archive-card lost-card hidden">
        <div class="flex justify-between items-start mb-4">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-stage-lost/10 border border-stage-lost/20 text-stage-lost font-label-sm text-label-sm uppercase tracking-wide">
                <span class="material-symbols-outlined text-[14px]">cancel</span>
                Lost
            </div>
            <button class="text-text-muted opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:text-primary">
                <span class="material-symbols-outlined text-[20px]">more_vert</span>
            </button>
        </div>
        <div class="mb-6 flex-1">
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-1 truncate">Riverfront Villa 12</h3>
            <div class="flex items-center gap-2 text-text-muted font-body-sm text-body-sm mb-4">
                <span class="material-symbols-outlined text-[16px]">person</span>
                Thomas Anderson
            </div>
            <div class="bg-surface-container-low p-3 rounded-lg border border-border-subtle/50">
                <p class="font-label-sm text-label-sm text-text-muted uppercase mb-1">Reason</p>
                <p class="font-body-md text-body-md text-on-surface-variant flex items-start gap-2">
                    <span class="material-symbols-outlined text-[16px] text-stage-lost mt-0.5">money_off</span>
                    Financing fell through. Client could not secure mortgage approval within timeline.
                </p>
            </div>
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-border-subtle/50 mt-auto">
            <div class="flex items-center gap-2 text-text-muted font-body-sm text-body-sm">
                <span class="material-symbols-outlined text-[16px]">event</span>
                <span class="">Final Act: Oct 20</span>
            </div>
            <button class="font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors">Re-engage</button>
        </div>
    </article>
</div>

<script>
    // Simple toggle logic
    document.addEventListener('DOMContentLoaded', () => {
        const btnBooked = document.getElementById('toggle-booked');
        const btnLost = document.getElementById('toggle-lost');
        const bookedCards = document.querySelectorAll('.booked-card');
        const lostCards = document.querySelectorAll('.lost-card');

        btnBooked.addEventListener('click', () => {
            // Style active
            btnBooked.className = "px-4 py-1.5 rounded-md font-label-md text-label-md bg-surface border border-border-subtle shadow-sm text-primary flex items-center gap-2";
            btnBooked.querySelector('span').classList.remove('opacity-50');
            
            // Style inactive
            btnLost.className = "px-4 py-1.5 rounded-md font-label-md text-label-md text-text-muted hover:text-primary transition-colors flex items-center gap-2";
            btnLost.querySelector('span').classList.add('opacity-50');

            // Show/Hide
            bookedCards.forEach(c => c.classList.remove('hidden'));
            lostCards.forEach(c => c.classList.add('hidden'));
        });

        btnLost.addEventListener('click', () => {
            // Style active
            btnLost.className = "px-4 py-1.5 rounded-md font-label-md text-label-md bg-surface border border-border-subtle shadow-sm text-primary flex items-center gap-2";
            btnLost.querySelector('span').classList.remove('opacity-50');
            
            // Style inactive
            btnBooked.className = "px-4 py-1.5 rounded-md font-label-md text-label-md text-text-muted hover:text-primary transition-colors flex items-center gap-2";
            btnBooked.querySelector('span').classList.add('opacity-50');

            // Show/Hide
            lostCards.forEach(c => c.classList.remove('hidden'));
            bookedCards.forEach(c => c.classList.add('hidden'));
        });
    });
</script>
@endsection
