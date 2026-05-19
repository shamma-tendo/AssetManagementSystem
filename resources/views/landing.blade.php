<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>EcoTrack — Smart Industrial Asset Intelligence</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/app.js'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        *{box-sizing:border-box;}html{scroll-behavior:smooth;}
        body{font-family:'Inter',-apple-system,system-ui,sans-serif;background:#020817;color:#f1f5f9;overflow-x:hidden;}
        .bg-scene{position:fixed;inset:0;z-index:-1;background:radial-gradient(ellipse 800px 500px at 15% 40%,rgba(59,130,246,.08),transparent 70%),radial-gradient(ellipse 600px 400px at 85% 20%,rgba(16,185,129,.07),transparent 60%),radial-gradient(ellipse 500px 350px at 55% 85%,rgba(139,92,246,.05),transparent 60%),#020817;}
        .bg-grid{position:fixed;inset:0;z-index:-1;background-image:linear-gradient(rgba(255,255,255,.014) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.014) 1px,transparent 1px);background-size:64px 64px;}
        .glass{background:rgba(255,255,255,.05);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.08);}
        .glass2{background:rgba(255,255,255,.07);backdrop-filter:blur(28px);-webkit-backdrop-filter:blur(28px);border:1px solid rgba(255,255,255,.11);}
        .gblue{background:linear-gradient(135deg,#60a5fa,#818cf8,#c084fc);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .ggreen{background:linear-gradient(135deg,#10b981,#34d399,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .btnp{background:linear-gradient(135deg,#1d4ed8,#4f46e5);border:1px solid rgba(96,165,250,.25);transition:all .25s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
        .btnp:hover{transform:translateY(-2px);box-shadow:0 0 28px rgba(96,165,250,.4);}
        .btng{background:linear-gradient(135deg,#059669,#10b981);border:1px solid rgba(16,185,129,.3);transition:all .25s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
        .btng:hover{transform:translateY(-2px);box-shadow:0 0 28px rgba(16,185,129,.35);}
        .btno{border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);transition:all .25s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
        .btno:hover{background:rgba(255,255,255,.09);transform:translateY(-1px);}
        #nav{position:fixed;top:14px;left:50%;transform:translateX(-50%);width:calc(100% - 40px);max-width:1280px;z-index:100;background:rgba(2,8,23,.72);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.08);border-radius:18px;transition:background .3s,box-shadow .3s;}
        #nav.s{background:rgba(2,8,23,.93);box-shadow:0 8px 40px rgba(0,0,0,.55);}
        .nl{color:#94a3b8;font-size:14px;font-weight:500;text-decoration:none;transition:color .2s;}.nl:hover{color:#f1f5f9;}
        .badge{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;}
        .div{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.07),transparent);margin:0;}
        .mock-wrap{border-radius:14px;border:1px solid rgba(255,255,255,.09);box-shadow:0 40px 80px rgba(0,0,0,.6);transform:perspective(1200px) rotateY(-10deg) rotateX(3deg);transition:transform .5s;}
        .mock-wrap:hover{transform:perspective(1200px) rotateY(-5deg) rotateX(1deg);}
        .mc{background:#111827;padding:8px 12px;display:flex;align-items:center;gap:8px;border-bottom:1px solid rgba(255,255,255,.06);border-radius:14px 14px 0 0;}
        .md{width:9px;height:9px;border-radius:50%;}
        .mu{flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:5px;padding:3px 10px;font-size:9px;color:#64748b;font-family:monospace;}
        .mb{display:flex;background:#0d1b2e;min-height:235px;border-radius:0 0 14px 14px;overflow:hidden;}
        .msb{width:38px;background:rgba(0,0,0,.3);border-right:1px solid rgba(255,255,255,.05);display:flex;flex-direction:column;align-items:center;padding:10px 0;gap:8px;}
        .mn{width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.15);}.mn.on{background:#3b82f6;box-shadow:0 0 6px rgba(59,130,246,.6);}
        .mm{flex:1;padding:10px;overflow:hidden;}
        .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:4px;margin-bottom:8px;}
        .kpi{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:6px;padding:5px 4px;}
        .kv{font-size:9px;font-weight:800;color:#f1f5f9;}.kv.g{color:#34d399;}.kv.b{color:#60a5fa;}.kv.o{color:#fb923c;}
        .kl{font-size:6px;color:#475569;margin-top:1px;}
        .chart-area{background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:6px;padding:6px;margin-bottom:8px;}
        .cline{stroke-dasharray:300;stroke-dashoffset:300;animation:draw 2.2s ease forwards .7s;}
        @keyframes draw{to{stroke-dashoffset:0;}}
        .wol{display:flex;flex-direction:column;gap:3px;}
        .wor{display:flex;align-items:center;gap:5px;background:rgba(255,255,255,.02);border-radius:4px;padding:4px 5px;}
        .wb{font-size:5px;font-weight:700;border-radius:3px;padding:1px 4px;}
        .wb.r{background:rgba(239,68,68,.2);color:#fca5a5;}.wb.y{background:rgba(234,179,8,.2);color:#fde047;}.wb.g{background:rgba(16,185,129,.2);color:#6ee7b7;}
        .wn{font-size:7px;color:#cbd5e1;flex:1;}.wt{font-size:6px;color:#475569;}
        .fc{position:absolute;display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:12px;backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);min-width:130px;z-index:10;}
        .fci{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .fcv{font-size:13px;font-weight:800;line-height:1;}.fcl{font-size:9px;color:#64748b;margin-top:2px;}
        .feat{transition:transform .35s ease,box-shadow .35s ease,border-color .35s ease,background .35s ease;transform-style:preserve-3d;cursor:default;}
        .feat:hover{transform:translateY(-8px);border-color:rgba(59,130,246,.3)!important;box-shadow:0 32px 64px rgba(0,0,0,.4),0 0 40px rgba(59,130,246,.08);background:rgba(255,255,255,.08)!important;}
        .snum{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;}
        .prev{border:1px solid rgba(255,255,255,.08);border-radius:14px;overflow:hidden;transition:transform .4s,box-shadow .4s;}
        .prev:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 40px 80px rgba(0,0,0,.5);}
        .gr{will-change:transform,opacity;}
        @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
        ::-webkit-scrollbar{width:4px;}::-webkit-scrollbar-track{background:#0a1628;}::-webkit-scrollbar-thumb{background:rgba(59,130,246,.4);border-radius:2px;}
    </style>
</head>
<body>
<div class="bg-scene"></div><div class="bg-grid"></div>

{{-- NAV --}}
<nav id="nav" x-data="{o:false}">
    <div class="px-5 py-3.5 flex items-center justify-between">
        <a href="{{ route('landing') }}" class="flex items-center gap-3" style="text-decoration:none;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 18px rgba(16,185,129,.3);flex-shrink:0;">
                <svg width="18" height="18" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div><div style="font-size:16px;font-weight:900;color:#f1f5f9;letter-spacing:-.02em;line-height:1.1;">EcoTrack</div><div style="font-size:9px;font-weight:700;color:#10b981;letter-spacing:.13em;text-transform:uppercase;">Industrial Intelligence Platform</div></div>
        </a>
        <div class="hidden lg:flex items-center gap-7">
            <a href="#features" class="nl">Features</a><a href="#solutions" class="nl">Solutions</a><a href="#analytics" class="nl">Analytics</a><a href="#how" class="nl">Platform</a><a href="#trust" class="nl">About</a>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}" class="btno hidden sm:inline-flex px-4 py-2 rounded-xl text-sm font-semibold text-slate-300">Sign In</a>
            <a href="{{ route('login') }}" class="btng hidden sm:inline-flex px-4 py-2 rounded-xl text-sm font-bold text-white">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Launch Dashboard
            </a>
            <button @click="o=!o" class="lg:hidden p-2 text-slate-400 rounded-xl" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
    <div x-show="o" x-transition class="lg:hidden px-5 pb-4 flex flex-col gap-1 border-t" style="border-color:rgba(255,255,255,.05);">
        <a href="#features" class="nl py-2.5 block" @click="o=false">Features</a><a href="#how" class="nl py-2.5 block" @click="o=false">Platform</a>
        <a href="{{ route('login') }}" class="btng justify-center px-4 py-2.5 rounded-xl text-sm font-bold text-white mt-2">Launch Dashboard</a>
    </div>
</nav>

{{-- HERO --}}
<section style="min-height:100vh;display:flex;align-items:center;padding-top:90px;padding-bottom:60px;">
    <div class="max-w-7xl mx-auto px-6 w-full">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div class="hero-left">
                <div class="badge glass mb-6 gr" style="border:1px solid rgba(16,185,129,.2);color:#34d399;"><span style="width:7px;height:7px;background:#10b981;border-radius:50%;animation:blink 1.5s ease-in-out infinite;"></span>Smart Industrial Asset Intelligence</div>
                <h1 class="gr" style="font-size:clamp(2.6rem,4.5vw,4.2rem);font-weight:900;line-height:.95;letter-spacing:-.04em;color:#f1f5f9;margin-bottom:20px;">Industrial Asset<br>Management,<br><span class="ggreen">Reimagined.</span></h1>
                <p class="gr" style="font-size:1.05rem;line-height:1.75;color:#94a3b8;max-width:460px;margin-bottom:32px;">Monitor assets, automate maintenance, reduce downtime, and unlock predictive intelligence across your entire facility — from one platform.</p>
                <div class="flex flex-wrap gap-3 gr" style="margin-bottom:32px;">
                    <a href="{{ route('login') }}" class="btng px-7 py-3.5 rounded-2xl text-base font-bold text-white" style="box-shadow:0 0 24px rgba(16,185,129,.25);">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Launch Dashboard
                    </a>
                    <a href="#features" class="btno px-7 py-3.5 rounded-2xl text-base font-semibold text-slate-300">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 15l5-3-5-3v6z" fill="currentColor"/></svg>Watch Demo
                    </a>
                </div>
                <div class="flex flex-wrap gap-3 gr">
                    <div class="glass flex items-center gap-2 px-3 py-2 rounded-xl" style="border-color:rgba(16,185,129,.15);"><span style="width:6px;height:6px;background:#10b981;border-radius:50%;animation:blink 2s ease-in-out infinite;"></span><span style="color:#34d399;font-size:11px;font-weight:600;">99.9% Uptime SLA</span></div>
                    <div class="glass flex items-center gap-2 px-3 py-2 rounded-xl"><svg width="11" height="11" fill="none" stroke="#60a5fa" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg><span style="color:#94a3b8;font-size:11px;font-weight:500;">Enterprise Secured</span></div>
                    <div class="glass flex items-center gap-2 px-3 py-2 rounded-xl"><svg width="11" height="11" fill="none" stroke="#a78bfa" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg><span style="color:#94a3b8;font-size:11px;font-weight:500;">AI-Powered</span></div>
                </div>
            </div>
            <div class="hero-right hidden lg:block relative" style="height:480px;">
                <div style="position:absolute;inset:0;padding:30px 0 30px 50px;">
                    <div class="mock-wrap">
                        <div class="mc"><div style="display:flex;gap:5px;"><span class="md" style="background:#ff5f57;"></span><span class="md" style="background:#ffbd2e;"></span><span class="md" style="background:#28c840;"></span></div><div class="mu">app.ecotrack.io/dashboard</div></div>
                        <div class="mb">
                            <div class="msb">
                                <div style="width:24px;height:24px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:7px;font-weight:900;color:#fff;margin-bottom:6px;">ET</div>
                                <div class="mn on"></div><div class="mn"></div><div class="mn"></div><div class="mn"></div>
                            </div>
                            <div class="mm">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;"><span style="font-size:8px;font-weight:700;color:#e2e8f0;">Operations Overview</span><span style="font-size:7px;color:#10b981;display:flex;align-items:center;gap:3px;"><span style="width:4px;height:4px;background:#10b981;border-radius:50%;animation:blink 1.5s ease-in-out infinite;"></span>Live</span></div>
                                <div class="kpis">
                                    <div class="kpi"><div class="kv g">{{ $stats['totalAssets'] }}+</div><div class="kl">Assets</div></div>
                                    <div class="kpi"><div class="kv b">99.9%</div><div class="kl">Uptime</div></div>
                                    <div class="kpi"><div class="kv">{{ $stats['workOrders'] }}</div><div class="kl">Work Orders</div></div>
                                    <div class="kpi"><div class="kv o">8</div><div class="kl">⚠ Alerts</div></div>
                                </div>
                                <div class="chart-area"><div style="font-size:7px;color:#64748b;margin-bottom:4px;">Asset Utilization — 7 Day Trend</div>
                                    <svg viewBox="0 0 240 40" style="width:100%;height:32px;display:block;"><defs><linearGradient id="cg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#3b82f6" stop-opacity=".35"/><stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/></linearGradient></defs>
                                        <path d="M0,32 C25,28 50,22 80,18 S120,11 150,14 S195,8 220,5 S235,4 240,3" stroke="#3b82f6" stroke-width="1.5" fill="none" class="cline"/>
                                        <path d="M0,32 C25,28 50,22 80,18 S120,11 150,14 S195,8 220,5 S235,4 240,3 L240,40 L0,40Z" fill="url(#cg)"/>
                                    </svg>
                                </div>
                                <div class="wol">
                                    <div class="wor"><span class="wb r">URGENT</span><span class="wn">CNC-420-B Overhaul</span><span class="wt">Now</span></div>
                                    <div class="wor"><span class="wb y">SCHEDULED</span><span class="wn">PMP-112 Inspection</span><span class="wt">2h</span></div>
                                    <div class="wor"><span class="wb g">DONE</span><span class="wn">TRK-8821 Oil Change</span><span class="wt">1d</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fc glass fc1" style="top:12%;left:-2%;background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.2);"><div class="fci" style="background:rgba(16,185,129,.18);"><svg width="13" height="13" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg></div><div><div class="fcv" style="color:#34d399;">99.9%</div><div class="fcl">System Uptime</div></div></div>
                <div class="fc glass fc2" style="top:4%;right:-4%;background:rgba(251,146,60,.07);border-color:rgba(251,146,60,.2);"><div class="fci" style="background:rgba(251,146,60,.15);"><svg width="13" height="13" fill="none" stroke="#fb923c" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/></svg></div><div><div class="fcv" style="color:#fb923c;">3 Critical</div><div class="fcl">Alerts Active</div></div></div>
                <div class="fc glass fc3" style="bottom:18%;right:-2%;background:rgba(139,92,246,.07);border-color:rgba(139,92,246,.2);"><div class="fci" style="background:rgba(139,92,246,.15);"><svg width="13" height="13" fill="none" stroke="#a78bfa" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div><div><div class="fcv" style="color:#c4b5fd;">+23% Risk</div><div class="fcl">AI Predicted</div></div></div>
            </div>
        </div>
    </div>
</section>

{{-- STATS --}}
<div class="div"></div>
<section id="stats" style="padding:72px 0;">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            @php $st=[['end'=>$stats['totalAssets'],'suf'=>'+','lbl'=>'Assets Managed','c'=>'#3b82f6','bg'=>'rgba(59,130,246,.1)','ic'=>'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'],['end'=>99,'suf'=>'.9%','lbl'=>'Uptime Reliability','c'=>'#10b981','bg'=>'rgba(16,185,129,.1)','ic'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],['end'=>18,'suf'=>'+','lbl'=>'Platform Features','c'=>'#8b5cf6','bg'=>'rgba(139,92,246,.1)','ic'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],['end'=>$stats['workOrders'],'suf'=>'+','lbl'=>'Work Orders Done','c'=>'#06b6d4','bg'=>'rgba(6,182,212,.1)','ic'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4']]; @endphp
            @foreach($st as $s)
            <div class="glass rounded-2xl p-6 text-center gr" style="border:1px solid rgba(255,255,255,.06);">
                <div style="width:44px;height:44px;background:{{ $s['bg'] }};border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><svg width="20" height="20" fill="none" stroke="{{ $s['c'] }}" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['ic'] }}"/></svg></div>
                <div class="ctr" style="font-size:2.5rem;font-weight:900;letter-spacing:-.03em;color:{{ $s['c'] }};line-height:1;" data-end="{{ $s['end'] }}" data-suf="{{ $s['suf'] }}">0</div>
                <div style="font-size:11px;color:#64748b;font-weight:600;letter-spacing:.05em;text-transform:uppercase;margin-top:6px;">{{ $s['lbl'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- FEATURES --}}
<div class="div"></div>
<section id="features" style="padding:96px 0;">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center gr" style="margin-bottom:56px;">
            <div class="badge glass mb-5" style="border:1px solid rgba(139,92,246,.2);color:#c084fc;">Core Capabilities</div>
            <h2 style="font-size:clamp(2rem,3.5vw,3rem);font-weight:900;letter-spacing:-.04em;color:#f1f5f9;margin-bottom:14px;">Built for <span class="gblue">Industrial Scale</span></h2>
            <p style="color:#64748b;max-width:520px;margin:0 auto;font-size:.95rem;line-height:1.7;">Everything your facility needs — from asset procurement to predictive failure prevention.</p>
        </div>
        @php $fg=[
            ['ic'=>'database','c'=>'#3b82f6','bg'=>'rgba(59,130,246,.1)','t'=>'Asset Intelligence','d'=>'Complete asset lifecycle management.','i'=>['Asset Registry CRUD','Smart Asset Search','Category Tracking']],
            ['ic'=>'wrench','c'=>'#10b981','bg'=>'rgba(16,185,129,.1)','t'=>'Smart Maintenance','d'=>'Automate workflows and prevent failures.','i'=>['Work Order Management','Maintenance Scheduling','Predictive Maintenance']],
            ['ic'=>'activity','c'=>'#06b6d4','bg'=>'rgba(6,182,212,.1)','t'=>'IoT Monitoring','d'=>'Real-time sensor telemetry on all assets.','i'=>['Live Telemetry Feeds','Sensor Integration','Automated Alerts']],
            ['ic'=>'package','c'=>'#8b5cf6','bg'=>'rgba(139,92,246,.1)','t'=>'Inventory & Ops','d'=>'Smart parts inventory and supplier management.','i'=>['Inventory Management','Real-Time Tracking','Supplier Management']],
            ['ic'=>'bar-chart-2','c'=>'#f59e0b','bg'=>'rgba(245,158,11,.1)','t'=>'Analytics','d'=>'Deep performance and cost analytics.','i'=>['MTTR/MTBF Analytics','Cost Reports','CSV/Excel Export']],
            ['ic'=>'shield','c'=>'#ec4899','bg'=>'rgba(236,72,153,.1)','t'=>'Enterprise Ready','d'=>'Role-based access, API and mobile support.','i'=>['Role-Based Auth','Mobile API','API Docs']],
        ]; @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($fg as $f)
            <div class="feat glass rounded-2xl p-7 gr" style="border:1px solid rgba(255,255,255,.06);">
                <div style="width:48px;height:48px;background:{{ $f['bg'] }};border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;"><i data-lucide="{{ $f['ic'] }}" style="width:22px;height:22px;color:{{ $f['c'] }};"></i></div>
                <h3 style="font-size:1rem;font-weight:800;color:#f1f5f9;margin-bottom:8px;">{{ $f['t'] }}</h3>
                <p style="font-size:.83rem;color:#64748b;line-height:1.65;margin-bottom:16px;">{{ $f['d'] }}</p>
                <div style="display:flex;flex-direction:column;gap:6px;">@foreach($f['i'] as $it)<div style="display:flex;align-items:center;gap:8px;"><span style="width:5px;height:5px;border-radius:50%;background:{{ $f['c'] }};flex-shrink:0;"></span><span style="font-size:.8rem;color:#94a3b8;">{{ $it }}</span></div>@endforeach</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- HOW IT WORKS --}}
<div class="div"></div>
<section id="how" style="padding:96px 0;">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center gr" style="margin-bottom:64px;">
            <div class="badge glass mb-5" style="border:1px solid rgba(16,185,129,.2);color:#34d399;">How It Works</div>
            <h2 style="font-size:clamp(2rem,3.5vw,3rem);font-weight:900;letter-spacing:-.04em;color:#f1f5f9;">Three steps to <span class="ggreen">operational excellence</span></h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
            <div class="hidden md:block" style="position:absolute;top:26px;left:calc(16.5% + 26px);right:calc(16.5% + 26px);height:1px;background:linear-gradient(90deg,rgba(59,130,246,.5),rgba(16,185,129,.5));"></div>
            @php $steps=[['n'=>'01','c'=>'#3b82f6','bg'=>'rgba(59,130,246,.1)','bd'=>'rgba(59,130,246,.25)','ic'=>'database','t'=>'Register & Configure','d'=>'Add assets, assign categories, set locations, define maintenance schedules and alert thresholds.'],['n'=>'02','c'=>'#8b5cf6','bg'=>'rgba(139,92,246,.1)','bd'=>'rgba(139,92,246,.25)','ic'=>'activity','t'=>'Monitor & Automate','d'=>'Connect sensors for live telemetry, auto-generate work orders, and track inventory in real time.'],['n'=>'03','c'=>'#10b981','bg'=>'rgba(16,185,129,.1)','bd'=>'rgba(16,185,129,.25)','ic'=>'zap','t'=>'Predict & Optimize','d'=>'Leverage AI failure prediction, analyze MTTR/MTBF trends, and continuously cut downtime costs.']]; @endphp
            @foreach($steps as $step)
            <div class="text-center gr">
                <div class="snum" style="background:{{ $step['bg'] }};border:1px solid {{ $step['bd'] }};"><i data-lucide="{{ $step['ic'] }}" style="width:24px;height:24px;color:{{ $step['c'] }};"></i></div>
                <div style="font-size:10px;font-weight:800;color:{{ $step['c'] }};letter-spacing:.12em;text-transform:uppercase;margin-bottom:8px;">{{ $step['n'] }}</div>
                <h3 style="font-size:1.05rem;font-weight:800;color:#f1f5f9;margin-bottom:10px;">{{ $step['t'] }}</h3>
                <p style="font-size:.85rem;color:#64748b;line-height:1.7;">{{ $step['d'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- TRUST --}}
<div class="div"></div>
<section id="trust" style="padding:96px 0;">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <div class="gr">
            <div style="font-size:1.7rem;font-weight:800;color:#f1f5f9;line-height:1.35;margin-bottom:20px;letter-spacing:-.02em;">"Trusted by industrial teams worldwide to <span class="ggreen">reduce downtime</span> and optimize asset performance."</div>
            <p style="color:#64748b;font-size:.95rem;margin-bottom:36px;">From small workshops to enterprise-scale manufacturing plants — EcoTrack delivers real results.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <div class="glass flex items-center gap-2.5 px-5 py-3 rounded-2xl" style="border:1px solid rgba(16,185,129,.15);"><span style="width:7px;height:7px;background:#10b981;border-radius:50%;animation:blink 1.5s ease-in-out infinite;"></span><span style="color:#34d399;font-size:.85rem;font-weight:700;">99.9% Uptime</span></div>
                <div class="glass flex items-center gap-2.5 px-5 py-3 rounded-2xl"><svg width="13" height="13" fill="none" stroke="#60a5fa" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg><span style="color:#94a3b8;font-size:.85rem;font-weight:600;">Enterprise Security</span></div>
                <div class="glass flex items-center gap-2.5 px-5 py-3 rounded-2xl"><svg width="13" height="13" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg><span style="color:#94a3b8;font-size:.85rem;font-weight:600;">40% Less Downtime</span></div>
                <div class="glass flex items-center gap-2.5 px-5 py-3 rounded-2xl"><svg width="13" height="13" fill="none" stroke="#a78bfa" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span style="color:#94a3b8;font-size:.85rem;font-weight:600;">60% Faster Orders</span></div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<div class="div"></div>
<section style="padding:96px 0;">
    <div class="max-w-4xl mx-auto px-6">
        <div class="glass2 rounded-3xl p-12 md:p-16 text-center relative overflow-hidden gr" style="border:1px solid rgba(255,255,255,.1);">
            <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 50% at 50% 0%,rgba(59,130,246,.08),transparent),radial-gradient(ellipse 50% 40% at 50% 100%,rgba(16,185,129,.07),transparent);pointer-events:none;border-radius:inherit;"></div>
            <div style="position:absolute;top:0;left:25%;right:25%;height:1px;background:linear-gradient(90deg,transparent,rgba(16,185,129,.4),transparent);"></div>
            <div class="relative">
                <div class="badge glass mb-6" style="border:1px solid rgba(16,185,129,.2);color:#34d399;"><span style="width:7px;height:7px;background:#10b981;border-radius:50%;animation:blink 1.5s ease-in-out infinite;"></span>System Online — Ready to Deploy</div>
                <h2 style="font-size:clamp(2rem,3.5vw,3.2rem);font-weight:900;letter-spacing:-.04em;color:#f1f5f9;margin-bottom:14px;line-height:1.05;">Ready to Modernize<br><span class="ggreen">Industrial Operations?</span></h2>
                <p style="color:#94a3b8;font-size:1.05rem;margin-bottom:40px;max-width:480px;margin-left:auto;margin-right:auto;">Sign in and gain complete visibility over every asset, work order, and inventory item in your facility.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('login') }}" class="btng px-8 py-4 rounded-2xl text-base font-bold text-white" style="box-shadow:0 0 30px rgba(16,185,129,.3);"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Launch EcoTrack</a>
                    <a href="#features" class="btno px-8 py-4 rounded-2xl text-base font-semibold text-slate-300"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>Book a Demo</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer style="padding:48px 0 32px;border-top:1px solid rgba(255,255,255,.06);">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center gap-2.5 mb-4"><div style="width:32px;height:32px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:8px;display:flex;align-items:center;justify-content:center;"><svg width="15" height="15" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div><span style="font-size:15px;font-weight:900;color:#f1f5f9;letter-spacing:-.02em;">EcoTrack</span></div>
                <p style="font-size:.8rem;color:#475569;line-height:1.65;">Smart Industrial Asset Intelligence Platform.</p>
            </div>
            <div><div style="font-size:11px;font-weight:700;color:#f1f5f9;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;">Platform</div>@foreach(['Dashboard','Asset Registry','Maintenance','Inventory','Analytics'] as $lnk)<a href="{{ route('login') }}" style="display:block;font-size:.82rem;color:#475569;text-decoration:none;margin-bottom:8px;" class="hover:text-slate-300">{{ $lnk }}</a>@endforeach</div>
            <div><div style="font-size:11px;font-weight:700;color:#f1f5f9;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;">Company</div>@foreach(['About EcoTrack','Documentation','API Reference','Support'] as $lnk)<a href="#" style="display:block;font-size:.82rem;color:#475569;text-decoration:none;margin-bottom:8px;">{{ $lnk }}</a>@endforeach</div>
            <div><div style="font-size:11px;font-weight:700;color:#f1f5f9;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;">Legal</div>@foreach(['Privacy Policy','Terms of Service','Security'] as $lnk)<a href="#" style="display:block;font-size:.82rem;color:#475569;text-decoration:none;margin-bottom:8px;">{{ $lnk }}</a>@endforeach</div>
        </div>
        <div class="div" style="margin-bottom:24px;"></div>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <p style="font-size:.78rem;color:#334155;">© {{ date('Y') }} EcoTrack Industrial Intelligence Platform. All rights reserved.</p>
            <div class="flex items-center gap-2"><span style="width:6px;height:6px;background:#10b981;border-radius:50%;animation:blink 2s ease-in-out infinite;"></span><span style="font-size:.78rem;color:#10b981;font-weight:600;">All systems operational</span></div>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    if(window.lucide)lucide.createIcons();
    if(!window.gsap||!window.ScrollTrigger)return;
    gsap.registerPlugin(ScrollTrigger);
    gsap.from('.hero-left>*',{y:55,opacity:0,stagger:.12,duration:1,ease:'power3.out',delay:.15,clearProps:'all'});
    gsap.from('.hero-right',{x:60,opacity:0,duration:1.3,ease:'power3.out',delay:.3,clearProps:'all'});
    gsap.to('.fc1',{y:-14,duration:3.8,repeat:-1,yoyo:true,ease:'sine.inOut'});
    gsap.to('.fc2',{y:-11,duration:4.5,repeat:-1,yoyo:true,ease:'sine.inOut',delay:.9});
    gsap.to('.fc3',{y:-9,duration:3.3,repeat:-1,yoyo:true,ease:'sine.inOut',delay:1.7});
    gsap.utils.toArray('.gr').forEach((el,i)=>{gsap.from(el,{scrollTrigger:{trigger:el,start:'top 88%',once:true},y:38,opacity:0,duration:.8,ease:'power3.out',delay:i%4*.07});});
    ScrollTrigger.create({start:'top -60',onUpdate:s=>document.getElementById('nav').classList.toggle('s',s.scroll()>60)});
    document.querySelectorAll('.ctr').forEach(el=>{
        const end=parseInt(el.dataset.end)||0,suf=el.dataset.suf||'';
        ScrollTrigger.create({trigger:el,start:'top 85%',once:true,onEnter:()=>{
            const o={v:0};gsap.to(o,{v:end,duration:2,ease:'power2.out',onUpdate:()=>el.textContent=Math.round(o.v).toLocaleString()+suf});
        }});
    });
    document.querySelectorAll('.feat').forEach(c=>{
        c.addEventListener('mousemove',e=>{const r=c.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;gsap.to(c,{rotateY:x*9,rotateX:-y*9,duration:.3});});
        c.addEventListener('mouseleave',()=>gsap.to(c,{rotateY:0,rotateX:0,duration:.6,ease:'elastic.out(1,.5)'}));
    });
});
</script>
</body>
</html>
