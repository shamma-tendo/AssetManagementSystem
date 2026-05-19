<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Access Denied — EcoTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',-apple-system,sans-serif;background:#020817;color:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
.bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;}
.bg-glow{position:fixed;inset:0;background:radial-gradient(ellipse 600px 400px at 50% 30%,rgba(239,68,68,.06),transparent 70%);pointer-events:none;}
.card{position:relative;z-index:1;width:100%;max-width:460px;background:rgba(255,255,255,.05);backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.09);border-radius:28px;padding:48px 40px;text-align:center;box-shadow:0 32px 80px rgba(0,0,0,.5);}
.icon-wrap{width:72px;height:72px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;}
.code{font-size:4rem;font-weight:900;color:rgba(239,68,68,.25);letter-spacing:-.04em;line-height:1;margin-bottom:4px;}
h1{font-size:1.4rem;font-weight:800;color:#f1f5f9;letter-spacing:-.02em;margin-bottom:10px;}
p{font-size:.9rem;color:#64748b;line-height:1.65;margin-bottom:6px;}
.reason{font-size:.82rem;color:#ef4444;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.15);border-radius:10px;padding:10px 16px;margin:20px 0 28px;}
.btn-back{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#059669,#10b981);border:none;border-radius:12px;padding:12px 24px;color:#fff;font-size:.9rem;font-weight:700;font-family:inherit;cursor:pointer;text-decoration:none;transition:all .2s;}
.btn-back:hover{transform:translateY(-2px);box-shadow:0 0 24px rgba(16,185,129,.4);}
.btn-dash{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:12px 24px;color:#94a3b8;font-size:.9rem;font-weight:600;font-family:inherit;cursor:pointer;text-decoration:none;transition:all .2s;margin-left:10px;}
.btn-dash:hover{background:rgba(255,255,255,.08);color:#f1f5f9;}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-glow"></div>

<div class="card">
    <div class="icon-wrap">
        <svg width="32" height="32" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>

    <div class="code">403</div>
    <h1>Access Denied</h1>
    <p>You don't have the required permissions to access this resource.</p>

    @if($exception->getMessage())
    <div class="reason">{{ $exception->getMessage() }}</div>
    @else
    <div class="reason">Contact your system administrator to request elevated access.</div>
    @endif

    <div>
        <a href="javascript:history.back()" class="btn-back">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Go Back
        </a>
        <a href="{{ route('dashboard') }}" class="btn-dash">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
    </div>
</div>
</body>
</html>
