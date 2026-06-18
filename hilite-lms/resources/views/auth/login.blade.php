@extends('layouts.guest')

@section('content')
<main class="w-full max-w-[440px] bg-surface-container-lowest border border-border-subtle rounded-[24px] p-8 sm:p-12 shadow-sm transition-all duration-300 relative overflow-hidden">
    <!-- Subtle decorative element -->
    <div class="absolute top-0 right-0 w-32 h-32 bg-surface-container-high rounded-bl-full opacity-50 -z-10 transform translate-x-1/3 -translate-y-1/3"></div>
    
    <!-- Branding -->
    <div class="flex flex-col items-center mb-10 text-center">
        <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center mb-6">
            <span class="material-symbols-outlined text-on-primary" style="font-size: 28px; font-variation-settings: 'FILL' 1;">dataset</span>
        </div>
        <h1 class="font-headline-lg text-headline-lg text-primary tracking-tight">HiLITE LMS</h1>
        <p class="font-body-md text-body-md text-text-muted mt-2">Sign in to access your dashboard</p>
    </div>

    <!-- Login Form -->
    <form class="space-y-6" action="{{ route('login.post') }}" method="POST">
        @csrf
        @if($errors->any())
        <div class="bg-stage-lost/10 border border-stage-lost/30 text-stage-lost rounded-xl px-4 py-3 text-body-sm mb-4">
            {{ $errors->first() }}
        </div>
        @endif
        <!-- Email Field -->
        <div class="space-y-2">
            <label class="block font-label-md text-label-md text-on-surface-variant ml-1" for="email">Email Address</label>
            <div class="relative input-focus-ring rounded-lg border border-border-subtle bg-surface-container-lowest transition-shadow duration-200">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-text-muted" style="font-size: 20px;">mail</span>
                </div>
                <input class="block w-full pl-11 pr-4 py-3.5 bg-transparent border-none font-body-md text-body-md text-primary placeholder:text-surface-tint focus:ring-0 rounded-lg outline-none" id="email" name="email" placeholder="name@hilite.com" required="" type="email">
            </div>
        </div>

        <!-- Password Field -->
        <div class="space-y-2">
            <div class="flex items-center justify-between ml-1 pr-1">
                <label class="block font-label-md text-label-md text-on-surface-variant" for="password">Password</label>
                <a class="font-label-md text-label-md text-text-muted hover:text-primary transition-colors" href="#">Forgot?</a>
            </div>
            <div class="relative input-focus-ring rounded-lg border border-border-subtle bg-surface-container-lowest transition-shadow duration-200">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-text-muted" style="font-size: 20px;">lock</span>
                </div>
                <input class="block w-full pl-11 pr-12 py-3.5 bg-transparent border-none font-body-md text-body-md text-primary placeholder:text-surface-tint focus:ring-0 rounded-lg outline-none" id="password" name="password" placeholder="••••••••" required="" type="password">
                <button id="toggle-password" class="absolute inset-y-0 right-0 pr-4 flex items-center text-text-muted hover:text-primary transition-colors focus:outline-none" type="button" onclick="
                    var inp = document.getElementById('password');
                    var icon = document.getElementById('toggle-eye-icon');
                    if (inp.type === 'password') {
                        inp.type = 'text';
                        icon.textContent = 'visibility';
                    } else {
                        inp.type = 'password';
                        icon.textContent = 'visibility_off';
                    }
                ">
                    <span id="toggle-eye-icon" class="material-symbols-outlined" style="font-size: 20px;">visibility_off</span>
                </button>
            </div>
        </div>

        <!-- Actions -->
        <div class="pt-4">
            <button class="w-full flex items-center justify-center bg-primary text-on-primary font-label-md text-label-md py-4 px-6 rounded-full hover:bg-on-surface-variant transition-colors duration-200 shadow-sm active:scale-[0.98]" type="submit">
                Sign In
                <span class="material-symbols-outlined ml-2" style="font-size: 18px;">arrow_forward</span>
            </button>
        </div>
    </form>

    <!-- Footer -->
    <div class="mt-8 text-center">
        <p class="font-body-sm text-body-sm text-text-muted">
            Don't have an account? 
            <a class="font-label-sm text-label-sm text-primary hover:underline underline-offset-4 ml-1" href="#">Contact Admin</a>
        </p>
    </div>
</main>
@endsection
