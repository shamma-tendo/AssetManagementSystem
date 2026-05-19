<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Sign In — EcoTrack Industrial Intelligence</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/app.js'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        *{box-sizing:border-box;}
        body{font-family:'Inter',-apple-system,system-ui,sans-serif;background:#020817;color:#f1f5f9;min-height:100vh;overflow-x:hidden;}
        .bg-grid{position:fixed;inset:0;z-index:0;background-image:linear-gradient(rgba(255,255,255,.014) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.014) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;}
        .glass{background:rgba(255,255,255,.05);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);}
        .glass2{background:rgba(255,255,255,.07);backdrop-filter:blur(28px);-webkit-backdrop-filter:blur(28px);border:1px solid rgba(255,255,255,.12);}
        .ggreen{background:linear-gradient(135deg,#10b981,#34d399,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .gblue{background:linear-gradient(135deg,#60a5fa,#818cf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .inp{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:12px;padding:12px 14px;color:#f1f5f9;font-size:.9rem;font-family:inherit;outline:none;transition:border-color .2s,background .2s,box-shadow .2s;}
        .inp:focus{background:rgba(255,255,255,.08);border-color:rgba(16,185,129,.5);box-shadow:0 0 0 3px rgba(16,185,129,.12);}
        .inp::placeholder{color:#475569;}
        .inp-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);pointer-events:none;}
        .btn-submit{width:100%;background:linear-gradient(135deg,#059669,#10b981,#3b82f6);border:1px solid rgba(16,185,129,.3);border-radius:14px;padding:14px;color:#fff;font-size:.95rem;font-weight:700;font-family:inherit;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 0 24px rgba(16,185,129,.25);}
        .btn-submit:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 0 36px rgba(16,185,129,.4),0 8px 24px rgba(0,0,0,.35);}
        .btn-submit:disabled{opacity:.6;cursor:not-allowed;}
        .fc{position:absolute;display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:12px;backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);z-index:2;}
        .fci{width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .fcv{font-size:12px;font-weight:800;line-height:1;}.fcl{font-size:9px;color:#64748b;margin-top:2px;}
        @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
        @keyframes spin{to{transform:rotate(360deg)}}
        .spin{animation:spin 1s linear infinite;}
    </style>
</head>
<body>
<div class="bg-grid"></div>

<div class="relative z-10 flex min-h-screen">

    {{-- LEFT PANEL --}}
    <div class="hidden lg:flex lg:w-3/5 flex-col justify-between p-12 relative overflow-hidden" style="background:linear-gradient(135deg,#020817 0%,#071022 50%,#0a1628 100%);">
        <div style="position:absolute;inset:0;background:radial-gradient(ellipse 700px 500px at 20% 50%,rgba(16,185,129,.08),transparent 70%),radial-gradient(ellipse 500px 400px at 80% 20%,rgba(59,130,246,.07),transparent 60%);pointer-events:none;"></div>
        <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.014) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.014) 1px,transparent 1px);background-size:56px 56px;pointer-events:none;"></div>

        <div class="relative z-10 left-brand">
            <div class="flex items-center gap-3 mb-12">
                <div style="width:44px;height:44px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 24px rgba(16,185,129,.35);flex-shrink:0;">
                    <svg width="22" height="22" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div><div style="font-size:20px;font-weight:900;color:#f1f5f9;letter-spacing:-.02em;line-height:1;">EcoTrack</div><div style="font-size:10px;font-weight:700;color:#10b981;letter-spacing:.12em;text-transform:uppercase;">Industrial Intelligence</div></div>
            </div>
            <h1 class="left-h" style="font-size:clamp(2.2rem,3.5vw,3.4rem);font-weight:900;line-height:.95;letter-spacing:-.04em;color:#f1f5f9;margin-bottom:20px;">Smart Asset<br>Management<br><span class="ggreen">Starts Here.</span></h1>
            <p class="left-p" style="font-size:1rem;color:#64748b;line-height:1.75;max-width:420px;margin-bottom:40px;">Monitor every asset, automate maintenance workflows, and predict failures before they happen — all in a single powerful platform.</p>
            <div class="left-feats" style="display:flex;flex-direction:column;gap:14px;margin-bottom:48px;">
                @foreach(['Real-time asset telemetry & condition monitoring','AI-powered predictive maintenance scheduling','Complete work order lifecycle management'] as $feat)
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:22px;height:22px;background:rgba(16,185,129,.15);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="11" height="11" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                    <span style="font-size:.88rem;color:#94a3b8;">{{ $feat }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="relative z-10 left-kpis" style="display:flex;gap:14px;flex-wrap:wrap;">
            <div class="fc glass kc1" style="background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.2);position:static;"><div class="fci" style="background:rgba(16,185,129,.18);"><svg width="12" height="12" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg></div><div><div class="fcv" style="color:#34d399;">99.9%</div><div class="fcl">System Uptime</div></div></div>
            <div class="fc glass kc2" style="background:rgba(59,130,246,.07);border-color:rgba(59,130,246,.2);position:static;"><div class="fci" style="background:rgba(59,130,246,.15);"><svg width="12" height="12" fill="none" stroke="#60a5fa" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div><div><div class="fcv" style="color:#60a5fa;">{{ $stats['totalAssets'] }}+</div><div class="fcl">Assets Managed</div></div></div>
            <div class="fc glass kc3" style="background:rgba(139,92,246,.07);border-color:rgba(139,92,246,.2);position:static;"><div class="fci" style="background:rgba(139,92,246,.15);"><svg width="12" height="12" fill="none" stroke="#a78bfa" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div><div><div class="fcv" style="color:#c4b5fd;">18+</div><div class="fcl">Platform Features</div></div></div>
        </div>

        <div class="relative z-10 mt-8">
            <p style="font-size:.78rem;color:#1e293b;">© {{ date('Y') }} EcoTrack Industrial Intelligence Platform</p>
        </div>
    </div>

    {{-- RIGHT PANEL --}}
    <div class="w-full lg:w-2/5 flex items-center justify-center p-6 sm:p-10 relative" style="background:linear-gradient(160deg,#020c1a,#020817);">
        <div style="position:absolute;inset:0;background:radial-gradient(ellipse 400px 400px at 50% 30%,rgba(16,185,129,.05),transparent 70%);pointer-events:none;"></div>
        <div class="right-form relative z-10 w-full" style="max-width:400px;">

            {{-- Mobile logo --}}
            <div class="flex lg:hidden items-center gap-3 justify-center mb-8">
                <div style="width:40px;height:40px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:11px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 20px rgba(16,185,129,.3);">
                    <svg width="20" height="20" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div style="font-size:18px;font-weight:900;color:#f1f5f9;letter-spacing:-.02em;">EcoTrack</div>
            </div>

            <div class="glass2 rounded-3xl p-8" style="box-shadow:0 32px 80px rgba(0,0,0,.5);">
                <div style="margin-bottom:28px;">
                    <h2 style="font-size:1.6rem;font-weight:900;color:#f1f5f9;letter-spacing:-.03em;margin-bottom:6px;">Welcome back</h2>
                    <p style="font-size:.9rem;color:#64748b;">Sign in to <span class="ggreen" style="font-weight:700;">EcoTrack</span> to continue</p>
                </div>

                @if(session('success'))
                <div style="margin-bottom:20px;padding:12px 16px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);border-radius:12px;display:flex;align-items:center;gap:10px;">
                    <svg width="16" height="16" fill="none" stroke="#34d399" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    <p style="font-size:.85rem;color:#34d399;font-weight:600;">{{ session('success') }}</p>
                </div>
                @endif

                @if($errors->any())
                <div style="margin-bottom:20px;padding:12px 16px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:12px;display:flex;align-items:flex-start;gap:10px;">
                    <svg width="16" height="16" fill="none" stroke="#f87171" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <p style="font-size:.85rem;color:#f87171;">{{ $errors->first() }}</p>
                </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" x-data="{loading:false,showPass:false}" @submit="loading=true">
                    @csrf
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;">Email Address</label>
                        <div style="position:relative;">
                            <div class="inp-icon"><svg width="16" height="16" fill="none" stroke="#475569" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                            <input type="email" name="email" value="{{ old('email') }}" class="inp" style="padding-left:42px;" placeholder="you@company.com" required autofocus>
                        </div>
                    </div>
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;">Password</label>
                        <div style="position:relative;">
                            <div class="inp-icon"><svg width="16" height="16" fill="none" stroke="#475569" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div>
                            <input :type="showPass?'text':'password'" name="password" class="inp" style="padding-left:42px;padding-right:44px;" placeholder="••••••••" required>
                            <button type="button" @click="showPass=!showPass" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#475569;display:flex;align-items:center;">
                                <svg x-show="!showPass" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPass" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;margin-bottom:24px;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="checkbox" name="remember" style="width:16px;height:16px;border-radius:4px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.05);accent-color:#10b981;">
                            <span style="font-size:.88rem;color:#64748b;">Keep me signed in</span>
                        </label>
                    </div>
                    <button type="submit" :disabled="loading" class="btn-submit">
                        <svg x-show="loading" class="spin" width="16" height="16" fill="none" viewBox="0 0 24 24" style="display:none;"><circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,.25)" stroke-width="4"/><path d="M4 12a8 8 0 018-8" stroke="white" stroke-width="4" stroke-linecap="round"/></svg>
                        <svg x-show="!loading" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span x-text="loading?'Signing In…':'Access EcoTrack'">Access EcoTrack</span>
                    </button>
                </form>

                <div style="margin-top:16px;text-align:center;">
                    <p style="font-size:.85rem;color:#475569;">Don't have an account? <a href="{{ route('register') }}" style="color:#10b981;font-weight:600;text-decoration:none;" class="hover:underline">Create one</a></p>
                </div>
            </div>

            <div style="margin-top:16px;display:flex;flex-direction:column;align-items:center;gap:12px;">
                <div class="glass flex items-center gap-2 px-4 py-2.5 rounded-2xl" style="border:1px solid rgba(16,185,129,.15);">
                    <svg width="13" height="13" fill="none" stroke="#10b981" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span style="font-size:.8rem;color:#34d399;font-weight:600;">Enterprise Secured · SSL Encrypted</span>
                </div>
                <a href="{{ route('landing') }}" style="font-size:.82rem;color:#475569;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:color .2s;" class="hover:text-slate-300">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to home
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    if(window.lucide)lucide.createIcons();
    if(!window.gsap)return;
    gsap.from('.left-brand>*',{x:-50,opacity:0,stagger:.1,duration:.9,ease:'power3.out',delay:.1});
    gsap.from('.left-kpis>*',{x:-40,opacity:0,stagger:.1,duration:.8,ease:'power3.out',delay:.5});
    gsap.from('.right-form',{x:50,opacity:0,duration:1,ease:'power3.out',delay:.2});
    gsap.to('.kc1',{y:-8,duration:4,repeat:-1,yoyo:true,ease:'sine.inOut',delay:.3});
    gsap.to('.kc2',{y:-6,duration:3.5,repeat:-1,yoyo:true,ease:'sine.inOut',delay:1});
    gsap.to('.kc3',{y:-7,duration:4.5,repeat:-1,yoyo:true,ease:'sine.inOut',delay:1.8});
});
</script>
</body>
</html>
