{{-- Shared shell for the signed-in areas: dashboard and admin. --}}
@extends('layouts.app')

@section('styles')
.panel { padding: 2.5rem 0 4rem; }
.panel-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.panel-head h1 { font-size: clamp(1.7rem, 4vw, 2.2rem); }
.panel-head .sub { color: var(--muted); }
.tabs { display: flex; gap: 0.25rem; border-bottom: 1px solid var(--border); margin-bottom: 2rem; overflow-x: auto; }
.tabs a { padding: 0.7rem 1rem; color: var(--muted); text-decoration: none; border-bottom: 2px solid transparent; white-space: nowrap; }
.tabs a:hover { color: var(--text); }
.tabs a[aria-current=page] { color: var(--text); border-bottom-color: var(--accent); }
.cards { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); margin-bottom: 2rem; }
.box { background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.4rem; }
.box h2 { font-size: 1.1rem; margin-bottom: 0.9rem; }
.stat .label { color: var(--muted); font-size: 0.85rem; }
.stat .value { font-family: 'Space Grotesk', sans-serif; font-size: 2rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.stat .hint { color: var(--muted); font-size: 0.8rem; }
.two { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); margin-bottom: 2rem; }
.rows { border-top: 1px solid var(--border); }
.rows > div { display: flex; justify-content: space-between; gap: 1rem; padding: 0.65rem 0; border-bottom: 1px solid var(--border); font-size: 0.95rem; }
.rows dt { color: var(--muted); }
.rows dd { text-align: right; overflow-wrap: anywhere; font-variant-numeric: tabular-nums; }
.flash { border-radius: 0.6rem; padding: 0.7rem 0.9rem; font-size: 0.92rem; margin-bottom: 1.25rem; }
.flash.ok { border: 1px solid rgba(34, 197, 94, 0.4); background: rgba(34, 197, 94, 0.08); color: #86EFAC; }
.flash.err { border: 1px solid rgba(252, 165, 165, 0.4); background: rgba(252, 165, 165, 0.08); color: var(--danger); }
pre.code { background: #0B1120; border: 1px solid var(--border); border-radius: 0.6rem; padding: 0.8rem 1rem; overflow-x: auto; font-size: 0.85rem; color: #E2E8F0; margin-bottom: 0.75rem; }
.copy-row { position: relative; }
.copy-btn { position: absolute; top: 0.45rem; right: 0.45rem; background: var(--surface-2); color: var(--text); border: none; border-radius: 0.4rem; padding: 0.3rem 0.6rem; font: inherit; font-size: 0.78rem; cursor: pointer; min-height: 32px; }
.copy-btn:hover { background: #475569; }
.muted { color: var(--muted); font-size: 0.9rem; }
.pill { display: inline-block; font-size: 0.75rem; padding: 0.1rem 0.5rem; border-radius: 999px; border: 1px solid var(--border); color: var(--muted); white-space: nowrap; }
.pill.green { color: #86EFAC; border-color: rgba(34, 197, 94, 0.4); }
.pill.amber { color: #FCD34D; border-color: rgba(252, 211, 77, 0.4); }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
th, td { text-align: left; padding: 0.65rem 0.75rem 0.65rem 0; border-bottom: 1px solid var(--border); vertical-align: middle; }
th { color: var(--muted); font-weight: 500; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; }
td .actions { display: flex; gap: 0.4rem; justify-content: flex-end; flex-wrap: wrap; }
.btn-sm { padding: 0.35rem 0.75rem; font-size: 0.82rem; min-height: 34px; border-radius: 0.45rem; }
.btn-danger { background: transparent; color: var(--danger); border: 1px solid rgba(252, 165, 165, 0.4); font-weight: 500; }
.btn-danger:hover { background: rgba(252, 165, 165, 0.1); }
.toolbar { display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 1rem; }
.toolbar input[type=search] { flex: 1; min-width: 12rem; min-height: 40px; padding: 0.5rem 0.8rem; font: inherit; color: var(--text); background: var(--bg); border: 1px solid var(--surface-2); border-radius: 0.6rem; }
.toolbar select { min-height: 40px; padding: 0.5rem; font: inherit; color: var(--text); background: var(--bg); border: 1px solid var(--surface-2); border-radius: 0.6rem; }
.bars { display: flex; align-items: flex-end; gap: 4px; height: 9rem; padding-top: 0.5rem; }
.bars .day { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; gap: 2px; height: 100%; min-width: 0; }
.bars .seg { border-radius: 3px 3px 0 0; min-height: 0; }
.bars .seg.api { background: var(--accent); }
.bars .seg.mcp { background: #38BDF8; border-radius: 3px 3px 0 0; }
.bars-axis { display: flex; justify-content: space-between; color: var(--muted); font-size: 0.75rem; margin-top: 0.4rem; }
.legend { display: flex; gap: 1rem; font-size: 0.82rem; color: var(--muted); margin-top: 0.6rem; }
.legend i { display: inline-block; width: 0.7rem; height: 0.7rem; border-radius: 2px; margin-right: 0.35rem; vertical-align: -1px; }
.check-row { display: flex; gap: 0.7rem; padding: 0.7rem 0; border-bottom: 1px solid var(--border); }
.check-row .mark { flex: none; width: 1.4rem; text-align: center; font-weight: 700; }
.check-row .mark.yes { color: var(--accent); }
.check-row .mark.no { color: #FCD34D; }
.pagination { display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem; }
@yield('panel-styles')
@endsection

@section('content')
<div class="container panel">
    @yield('panel')
</div>
<script>
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy]');
        if (!btn) return;
        var text = document.getElementById(btn.dataset.copy).innerText;
        navigator.clipboard.writeText(text).then(function () {
            var was = btn.textContent; btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = was; }, 1500);
        });
    });
    document.addEventListener('submit', function (e) {
        var msg = e.target.dataset.confirm;
        if (msg && !confirm(msg)) e.preventDefault();
    });
</script>
@endsection
