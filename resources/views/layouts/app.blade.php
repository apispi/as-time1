<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — MCP Time Service</title>
    @hasSection('description')<meta name="description" content="@yield('description')">@endif
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0F172A; --surface: #1E293B; --surface-2: #334155;
            --border: rgba(148, 163, 184, 0.18);
            --text: #F8FAFC; --muted: #CBD5E1;
            --accent: #22C55E; --danger: #FCA5A5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
        html { scroll-behavior: smooth; scroll-padding-top: 5rem; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif; background: var(--bg); color: var(--text);
            line-height: 1.6; -webkit-font-smoothing: antialiased;
            min-height: 100vh; display: flex; flex-direction: column;
        }
        main { flex: 1; }
        h1, h2, h3 { font-family: 'Space Grotesk', system-ui, sans-serif; line-height: 1.15; }
        code, pre, .mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        .container { max-width: 72rem; margin: 0 auto; padding: 0 1.5rem; }
        a { color: inherit; }

        header {
            position: sticky; top: 0; z-index: 50; background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px); border-bottom: 1px solid var(--border);
        }
        .nav { display: flex; align-items: center; justify-content: space-between; height: 4rem; }
        .logo { display: flex; align-items: center; gap: 0.6rem; font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1.1rem; text-decoration: none; }
        .logo svg { color: var(--accent); }
        .nav-links { display: flex; align-items: center; gap: 1.75rem; }
        .nav-links a { color: var(--muted); text-decoration: none; font-size: 0.95rem; transition: color 0.2s; }
        .nav-links a:hover { color: var(--text); }
        a:focus-visible, button:focus-visible, input:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; border-radius: 4px; }
        @media (max-width: 640px) { .nav-links a:not(.btn) { display: none; } }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            background: var(--accent); color: #052e16; font-family: inherit;
            font-weight: 700; text-decoration: none; cursor: pointer;
            padding: 0.7rem 1.4rem; border-radius: 0.6rem; border: none; font-size: 1rem;
            min-height: 44px; transition: background-color 0.2s;
        }
        .btn:hover { background: #4ADE80; }
        .btn-outline { background: transparent; color: var(--text); border: 1px solid var(--surface-2); font-weight: 500; }
        .btn-outline:hover { background: var(--surface); border-color: var(--muted); }
        .nav .btn { padding: 0.5rem 1.1rem; font-size: 0.9rem; color: #052e16; }
        .btn-block { width: 100%; }

        footer { border-top: 1px solid var(--border); padding: 2.5rem 0; }
        .footer-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; color: var(--muted); font-size: 0.9rem; }
        .footer-row a { text-decoration: none; transition: color 0.2s; }
        .footer-row a:hover { color: var(--text); }
        .footer-links { display: flex; gap: 1.5rem; }
        @yield('styles')
    </style>
</head>
<body>
    <header>
        <div class="container nav">
            <a href="/" class="logo" aria-label="MCP Time Service home">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                MCP Time Service
            </a>
            <nav class="nav-links" aria-label="Main navigation">
                <a href="/#tools">Tools</a>
                <a href="{{ route('docs') }}">Docs</a>
                @auth
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.overview') }}">Admin</a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="btn">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn">Sign in</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        <div class="container footer-row">
            <span>© {{ date('Y') }} MCP Time Service. All rights reserved.</span>
            <nav class="footer-links" aria-label="Footer navigation">
                <a href="/">Home</a>
                <a href="{{ route('docs') }}">Docs</a>
                <a href="/up">Status</a>
            </nav>
        </div>
    </footer>
</body>
</html>
