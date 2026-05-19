<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Create Account — EcoTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
@vite(['resources/css/app.css','resources/js/app.js'])
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',-apple-system,sans-serif;background:#020817;color:#f1f5f9;min-height:100vh;}
.page-wrap{min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px 60px;}
.card{width:100%;max-width:520px;background:rgba(255,255,255,.06);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.1);border-radius:28px;padding:40px;box-shadow:0 32px 80px rgba(0,0,0,.55);}
.bg-grid{position:fixed;inset:0;z-index:0;background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;}
.bg-glow{position:fixed;inset:0;z-index:0;background:radial-gradient(ellipse 700px 500px at 50% 0%,rgba(16,185,129,.07),transparent 70%);pointer-events:none;}
.relative-z{position:relative;z-index:1;}
.lbl{display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em;margin-bottom:7px;}
.inp{display:block;width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:12px 16px;color:#f1f5f9;font-size:.92rem;font-family:inherit;outline:none;transition:border-color .2s,box-shadow .2s;}
.inp:focus{border-color:rgba(16,185,129,.55);box-shadow:0 0 0 3px rgba(16,185,129,.1);}
.inp::placeholder{color:#3d4f63;}
.pw-wrap{display:flex;align-items:stretch;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:12px;overflow:hidden;transition:border-color .2s,box-shadow .2s;}
.pw-wrap:focus-within{border-color:rgba(16,185,129,.55);box-shadow:0 0 0 3px rgba(16,185,129,.1);}
.pw-inp{flex:1;background:none;border:none;padding:12px 16px;color:#f1f5f9;font-size:.92rem;font-family:inherit;outline:none;}
.pw-inp::placeholder{color:#3d4f63;}
.pw-eye{background:none;border:none;padding:0 14px;cursor:pointer;color:#475569;display:flex;align-items:center;flex-shrink:0;transition:color .2s;}
.pw-eye:hover{color:#94a3b8;}
.sel{display:block;width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:12px 16px;color:#f1f5f9;font-size:.92rem;font-family:inherit;outline:none;appearance:none;cursor:pointer;transition:border-color .2s,box-shadow .2s;}
.sel:focus{border-color:rgba(16,185,129,.55);box-shadow:0 0 0 3px rgba(16,185,129,.1);}
.sel option{background:#0f172a;}
.sel-wrap{position:relative;}
.sel-arrow{position:absolute;right:14px;top:50%;transform:translateY(-50%);pointer-events:none;color:#475569;}
.f{margin-bottom:18px;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;}
.btn{width:100%;background:linear-gradient(135deg,#059669,#10b981,#3b82f6);border:none;border-radius:14px;padding:14px;color:#fff;font-size:.95rem;font-weight:700;font-family:inherit;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 0 24px rgba(16,185,129,.3);transition:all .25s;margin-top:8px;}
.btn:hover:not([disabled]){transform:translateY(-2px);box-shadow:0 0 36px rgba(16,185,129,.45);}
.btn[disabled]{opacity:.6;cursor:not-allowed;}
.err-box{margin-bottom:20px;padding:12px 16px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:12px;}
.ok-box{margin-bottom:20px;padding:12px 16px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:12px;}
.ggreen{background:linear-gradient(135deg,#10b981,#34d399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
@keyframes spin{to{transform:rotate(360deg)}}
.spin{animation:spin .9s linear infinite;}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-glow"></div>

<div class="page-wrap relative-z">
<div class="card">

    {{-- Logo --}}
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:28px;">
        <a href="{{ route('landing') }}" style="display:flex;align-items:center;gap:12px;text-decoration:none;">
            <div style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 22px rgba(16,185,129,.4);flex-shrink:0;">
                <svg width="22" height="22" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <div style="font-size:19px;font-weight:900;color:#f1f5f9;letter-spacing:-.02em;line-height:1.1;">EcoTrack</div>
                <div style="font-size:10px;font-weight:700;color:#10b981;letter-spacing:.1em;text-transform:uppercase;">Industrial Intelligence</div>
            </div>
        </a>
    </div>

    {{-- Heading --}}
    <div style="margin-bottom:28px;">
        <h1 style="font-size:1.55rem;font-weight:900;color:#f1f5f9;letter-spacing:-.03em;margin-bottom:6px;">Create your account</h1>
        <p style="font-size:.9rem;color:#64748b;">Join <span class="ggreen" style="font-weight:700;">EcoTrack</span> and start managing assets intelligently</p>
    </div>

    {{-- Errors --}}
    @if($errors->any())
    <div class="err-box">
        <ul style="list-style:none;display:flex;flex-direction:column;gap:4px;">
            @foreach($errors->all() as $err)
            <li style="font-size:.85rem;color:#f87171;display:flex;align-items:center;gap:8px;">
                <svg width="13" height="13" fill="none" stroke="#f87171" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                {{ $err }}
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="ok-box" style="display:flex;align-items:center;gap:8px;">
        <svg width="15" height="15" fill="none" stroke="#34d399" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p style="font-size:.85rem;color:#34d399;font-weight:600;">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('register.post') }}" x-data="{loading:false,sp:false,sc:false}" @submit="loading=true">
        @csrf

        {{-- First + Last Name --}}
        <div class="grid2">
            <div>
                <label class="lbl" for="first_name">First Name</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}"
                       class="inp" placeholder="John" required autofocus>
            </div>
            <div>
                <label class="lbl" for="last_name">Last Name</label>
                <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}"
                       class="inp" placeholder="Smith" required>
            </div>
        </div>

        {{-- Email --}}
        <div class="f">
            <label class="lbl" for="email">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="inp" placeholder="you@company.com" required>
        </div>

        {{-- Password --}}
        <div class="f">
            <label class="lbl" for="password">Password</label>
            <div class="pw-wrap">
                <input id="password" :type="sp?'text':'password'" name="password"
                       class="pw-inp" placeholder="Min. 8 characters" required>
                <button type="button" class="pw-eye" @click="sp=!sp" tabindex="-1">
                    <svg x-show="!sp" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="sp" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
        </div>

        {{-- Confirm Password --}}
        <div class="f">
            <label class="lbl" for="password_confirmation">Confirm Password</label>
            <div class="pw-wrap">
                <input id="password_confirmation" :type="sc?'text':'password'" name="password_confirmation"
                       class="pw-inp" placeholder="Repeat your password" required>
                <button type="button" class="pw-eye" @click="sc=!sc" tabindex="-1">
                    <svg x-show="!sc" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="sc" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
        </div>

        {{-- Role --}}
        <div class="f">
            <label class="lbl" for="role">Your Role</label>
            <div class="sel-wrap">
                <select id="role" name="role" class="sel" required>
                    <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select your role…</option>
                    <option value="viewer"     {{ old('role')=='viewer'     ? 'selected' : '' }}>Viewer — Read-only access</option>
                    <option value="technician" {{ old('role')=='technician' ? 'selected' : '' }}>Technician — Work orders &amp; assets</option>
                    <option value="auditor"    {{ old('role')=='auditor'    ? 'selected' : '' }}>Auditor — Reports &amp; audit logs</option>
                    <option value="manager"    {{ old('role')=='manager'    ? 'selected' : '' }}>Manager — Full operations access</option>
                </select>
                <span class="sel-arrow">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </span>
            </div>
            <p style="font-size:.78rem;color:#475569;margin-top:6px;">Admin accounts are provisioned by your system administrator.</p>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn" :disabled="loading">
            <svg x-show="loading" class="spin" width="16" height="16" fill="none" viewBox="0 0 24 24" style="display:none;">
                <circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,.3)" stroke-width="3"/>
                <path d="M4 12a8 8 0 018-8" stroke="white" stroke-width="3" stroke-linecap="round"/>
            </svg>
            <svg x-show="!loading" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <span x-text="loading ? 'Creating Account…' : 'Create Account'">Create Account</span>
        </button>
    </form>

    {{-- Footer links --}}
    <div style="margin-top:22px;display:flex;flex-direction:column;align-items:center;gap:12px;">
        <p style="font-size:.86rem;color:#475569;">Already have an account?
            <a href="{{ route('login') }}" style="color:#10b981;font-weight:700;text-decoration:none;">Sign in</a>
        </p>
        <a href="{{ route('landing') }}" style="font-size:.82rem;color:#334155;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:color .2s;">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to home
        </a>
        <div style="display:flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(16,185,129,.07);border:1px solid rgba(16,185,129,.15);border-radius:20px;">
            <svg width="12" height="12" fill="none" stroke="#10b981" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span style="font-size:.78rem;color:#34d399;font-weight:600;">Enterprise Secured · SSL Encrypted</span>
        </div>
    </div>

</div>
</div>

</body>
</html>
