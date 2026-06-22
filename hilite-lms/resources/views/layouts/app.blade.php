<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'HiLITE LMS') }}</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    
    <!-- Hotwire Turbo for SPA-like navigation speed -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.4/dist/turbo.es2017-esm.js"></script>
    
    <!-- Alpine.js for lightweight UI interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-body-md text-body-md bg-background text-on-background h-screen flex antialiased overflow-hidden w-full">
    
    <!-- SideNavBar (Stitch Design) -->
    <aside class="fixed left-0 top-0 h-full w-[240px] flex flex-col p-4 border-r border-outline-variant z-50 bg-surface hidden md:flex">
        <div class="mb-10 px-4">
            <h1 class="font-headline-sm text-headline-sm font-extrabold text-primary">HiLITE LMS</h1>
            <p class="font-label-sm text-label-sm text-text-muted mt-1">Real Estate Lead Mgmt</p>
        </div>
        
        @php $navRole = \App\Http\Helpers\AuthHelper::user()?->role ?? ''; @endphp
        <nav class="flex-1 space-y-2">
            <a href="{{ route('dashboard.salesperson') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-transform active:scale-[0.98] {{ request()->routeIs('dashboard.*') ? 'bg-surface-container-high text-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface duration-200' }}">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-body-md text-body-md">Dashboard</span>
            </a>

            <a href="{{ route('leads.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-transform active:scale-[0.98] {{ request()->routeIs('leads.index') ? 'bg-surface-container-high text-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface duration-200' }}">
                <span class="material-symbols-outlined">person_search</span>
                <span class="font-body-md text-body-md">{{ in_array($navRole, ['admin','manager','branch_head']) ? 'All Leads' : 'My Leads' }}</span>
            </a>

            <a href="{{ route('leads.calendar') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-transform active:scale-[0.98] {{ request()->routeIs('leads.calendar') ? 'bg-surface-container-high text-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface duration-200' }}">
                <span class="material-symbols-outlined">event_upcoming</span>
                <span class="font-body-md text-body-md">Follow-ups</span>
            </a>

            @if($navRole !== 'salesperson')
            <a href="{{ route('dashboard.assignment') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-transform active:scale-[0.98] {{ request()->routeIs('dashboard.assignment') ? 'bg-surface-container-high text-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface duration-200' }}">
                <span class="material-symbols-outlined">groups</span>
                <span class="font-body-md text-body-md">Team / Assign</span>
            </a>

            <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-transform active:scale-[0.98] {{ request()->routeIs('reports.*') ? 'bg-surface-container-high text-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface duration-200' }}">
                <span class="material-symbols-outlined">analytics</span>
                <span class="font-body-md text-body-md">Reports</span>
            </a>
            @endif

            @if(in_array($navRole, ['admin', 'super_admin']))
            <a href="{{ route('admin.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-transform active:scale-[0.98] {{ request()->routeIs('admin.*') ? 'bg-surface-container-high text-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface duration-200' }}">
                <span class="material-symbols-outlined">admin_panel_settings</span>
                <span class="font-body-md text-body-md">Admin</span>
            </a>
            @endif
        </nav>

        <a href="{{ route('leads.import') }}" class="mt-auto w-full py-4 bg-primary text-on-primary rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-opacity-90 transition-all">
            <span class="material-symbols-outlined">add_circle</span>
            Add New Lead
        </a>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col md:ml-[240px] h-screen relative min-w-0 w-full">
        <!-- TopNavBar (Stitch Design + Alpine Logic) -->
        <header class="sticky top-0 h-16 bg-surface flex justify-between items-center px-8 z-40 border-b border-border-subtle">
            <!-- Mobile Menu Toggle -->
            <button class="md:hidden text-on-surface p-2 -ml-2 rounded-lg hover:bg-surface-container-low">
                <span class="material-symbols-outlined">menu</span>
            </button>
            
            <!-- Search -->
            <div class="flex items-center gap-4 flex-1 max-w-xl hidden sm:flex ml-4 md:ml-0">
                <div class="relative w-full focus-within:ring-2 focus-within:ring-primary rounded-xl">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input class="w-full bg-surface-container-low border-none rounded-xl py-2 pl-10 pr-4 font-body-md text-body-md focus:ring-0" placeholder="Search leads, tasks, or properties..." type="text">
                </div>
            </div>
            
            <!-- Actions & Profile -->
            <div class="flex items-center gap-6 ml-auto pl-4">
                <div x-data="{ 
                    open: false,
                    notifications: [
                        { id: 1, title: '3 New Leads Assigned', desc: 'Check your Assignment Hub to process them.', time: '10 mins ago', type: 'info', link: '/dashboard/assignment' },
                        { id: 2, title: 'Site Visit Reminder', desc: 'Meeting with Kiran Verma in 1 hour.', time: '2 hours ago', type: 'info', link: '/leads/calendar' }
                    ],
                    remove(id) { this.notifications = this.notifications.filter(n => n.id !== id); },
                    removeAll() { this.notifications = []; }
                }" class="relative flex items-center">
                    <button @click="open = !open" @click.outside="open = false" class="text-on-surface-variant hover:text-primary transition-colors relative">
                        <span class="material-symbols-outlined">notifications</span>
                        <span x-show="notifications.length > 0" class="absolute top-0 right-0 w-2 h-2 bg-stage-lost rounded-full" style="display: none;"></span>
                    </button>
                    
                    <!-- Notification Dropdown -->
                    <div x-show="open" x-transition style="display: none;" class="absolute top-full right-0 mt-4 w-80 bg-surface-container-lowest border border-border-subtle rounded-xl shadow-lg flex flex-col z-50 overflow-hidden transform origin-top-right">
                        <div class="px-4 py-3 border-b border-border-subtle bg-surface flex justify-between items-center">
                            <h4 class="font-headline-sm text-on-surface m-0">Notifications</h4>
                            <span x-show="notifications.length > 0" x-text="notifications.length + ' New'" class="bg-stage-new/10 text-stage-new text-[10px] font-bold px-2 py-0.5 rounded-full" style="display: none;"></span>
                        </div>
                        <div class="max-h-[300px] overflow-y-auto">
                            <template x-for="item in notifications" :key="item.id">
                                <div class="relative group block border-b border-border-subtle hover:bg-surface-container-low transition-colors">
                                    <a :href="item.link" class="block px-4 py-3 pr-10">
                                        <p class="font-label-md text-on-surface mb-0.5" x-text="item.title"></p>
                                        <p class="font-body-sm leading-snug" :class="item.type === 'warning' ? 'text-stage-lost' : 'text-text-muted'" x-text="item.desc"></p>
                                    </a>
                                    <button @click="remove(item.id)" class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-text-muted hover:text-stage-lost opacity-0 group-hover:opacity-100 transition-opacity rounded-full hover:bg-surface-container">
                                        <span class="material-symbols-outlined text-[16px]">check</span>
                                    </button>
                                </div>
                            </template>
                            <div x-show="notifications.length === 0" class="px-4 py-8 text-center" style="display: none;">
                                <p class="text-body-sm text-text-muted">You're all caught up!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="https://hilitegroup.com/contact-us/" target="_blank" class="text-on-surface-variant hover:text-primary transition-colors hidden sm:block" title="Help Center">
                    <span class="material-symbols-outlined">help_outline</span>
                </a>
                
                <div class="flex items-center gap-3 border-l border-outline-variant pl-6">
                    <div class="text-right hidden lg:block">
                        <p class="font-label-md text-label-md text-on-surface">{{ \App\Http\Helpers\AuthHelper::user()->name ?? 'User' }}</p>
                        <p class="font-label-sm text-label-sm text-text-muted uppercase">{{ str_replace('_', ' ', \App\Http\Helpers\AuthHelper::user()->role ?? 'Role') }}</p>
                    </div>
                    <a href="/profile" class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-lg border border-border-subtle hover:bg-surface-container-high transition-colors">
                        {{ substr(\App\Http\Helpers\AuthHelper::user()->name ?? 'U', 0, 1) }}
                    </a>
                </div>
            </div>
        </header>
        
        <!-- Page Canvas -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden px-4 md:px-8 pb-12 pt-4 min-w-0 w-full">
            @yield('content')
        </main>
    </div>

    <!-- Modals area -->
    @stack('modals')
    
    <!-- Global Toast Container -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[100] flex flex-col gap-2 pointer-events-none"></div>
    
    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            // Icon based on type
            const icon = type === 'success' ? 'check_circle' : (type === 'info' ? 'info' : 'error');
            const iconColor = type === 'success' ? 'text-primary' : (type === 'info' ? 'text-[#3b82f6]' : 'text-stage-lost');
            
            toast.className = `bg-surface-container-lowest border border-border-subtle shadow-lg rounded-xl p-4 flex items-center gap-3 transform transition-all duration-300 translate-y-10 opacity-0 pointer-events-auto w-max max-w-sm`;
            toast.innerHTML = `
                <span class="material-symbols-outlined ${iconColor}">${icon}</span>
                <p class="font-body-sm text-on-surface m-0">${message}</p>
            `;
            
            container.appendChild(toast);
            
            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
            });
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

    </script>
</body>
</html>
