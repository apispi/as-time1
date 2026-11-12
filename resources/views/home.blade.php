<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MCP Time Service — Accurate time for your AI agents</title>
    <meta name="description" content="An MCP server that gives AI agents accurate current time, timezone conversion, and date utilities via the Model Context Protocol.">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0F172A;
            --surface: #1E293B;
            --surface-2: #334155;
            --border: rgba(148, 163, 184, 0.18);
            --text: #F8FAFC;
            --muted: #CBD5E1;
            --accent: #22C55E;
            --accent-dark: #16A34A;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3 { font-family: 'Space Grotesk', system-ui, sans-serif; line-height: 1.15; }
        code, pre, .mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        .container { max-width: 72rem; margin: 0 auto; padding: 0 1.5rem; }
        a { color: inherit; }

        /* Header */
        header {
            position: sticky; top: 0; z-index: 50;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }
        .nav { display: flex; align-items: center; justify-content: space-between; height: 4rem; }
        .logo { display: flex; align-items: center; gap: 0.6rem; font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1.1rem; text-decoration: none; }
        .logo svg { color: var(--accent); }
        .nav-links { display: flex; align-items: center; gap: 1.75rem; }
        .nav-links a { color: var(--muted); text-decoration: none; font-size: 0.95rem; transition: color 0.2s; }
        .nav-links a:hover { color: var(--text); }
        .nav-links a:focus-visible, .btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; border-radius: 4px; }
        @media (max-width: 640px) { .nav-links a:not(.btn) { display: none; } }

        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: var(--accent); color: #052e16;
            font-weight: 700; text-decoration: none; cursor: pointer;
            padding: 0.7rem 1.4rem; border-radius: 0.6rem; border: none; font-size: 1rem;
            min-height: 44px; transition: background-color 0.2s;
        }
        .btn:hover { background: #4ADE80; }
        .btn-outline {
            background: transparent; color: var(--text);
            border: 1px solid var(--surface-2); font-weight: 500;
        }
        .btn-outline:hover { background: var(--surface); border-color: var(--muted); }
        .nav .btn { padding: 0.5rem 1.1rem; font-size: 0.9rem; }

        /* Hero */
        .hero { padding: 5.5rem 0 4.5rem; text-align: center; position: relative; overflow: hidden; }
        .hero::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(60rem 24rem at 50% -6rem, rgba(34, 197, 94, 0.14), transparent 70%);
        }
        .hero > .container { position: relative; }
        .badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            border: 1px solid var(--border); background: var(--surface);
            color: var(--muted); border-radius: 999px;
            padding: 0.35rem 0.9rem; font-size: 0.85rem; margin-bottom: 1.5rem;
        }
        .badge .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); }
        .hero h1 { font-size: clamp(2.4rem, 6vw, 4rem); font-weight: 700; letter-spacing: -0.02em; margin-bottom: 1.25rem; }
        .hero h1 .accent { color: var(--accent); }
        .hero p.lede { font-size: 1.2rem; color: var(--muted); max-width: 42rem; margin: 0 auto 2.25rem; }
        .hero-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem; }

        .clock-card {
            display: inline-block; background: var(--surface); border: 1px solid var(--border);
            border-radius: 1rem; padding: 1.5rem 2.5rem;
        }
        .clock-card .label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--muted); margin-bottom: 0.4rem; }
        .clock-card time { font-family: 'JetBrains Mono', monospace; font-size: clamp(1.6rem, 4vw, 2.4rem); font-weight: 500; color: var(--accent); }
        .clock-card .tz { font-size: 0.9rem; color: var(--muted); margin-top: 0.3rem; }

        /* Sections */
        section { padding: 4.5rem 0; }
        section h2 { font-size: clamp(1.8rem, 4vw, 2.5rem); margin-bottom: 0.75rem; text-align: center; }
        .section-sub { color: var(--muted); text-align: center; max-width: 38rem; margin: 0 auto 3rem; }

        .grid { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); }
        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 1rem; padding: 1.75rem; transition: border-color 0.2s;
        }
        .card:hover { border-color: rgba(34, 197, 94, 0.45); }
        .card .icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.75rem; height: 2.75rem; border-radius: 0.75rem;
            background: rgba(34, 197, 94, 0.12); color: var(--accent); margin-bottom: 1.1rem;
        }
        .card h3 { font-size: 1.15rem; margin-bottom: 0.5rem; }
        .card p { color: var(--muted); font-size: 0.95rem; }

        /* Tools */
        .tool { display: flex; flex-direction: column; gap: 0.5rem; }
        .tool code.name { color: var(--accent); font-size: 1rem; font-weight: 500; }
        .tool .args { font-size: 0.85rem; color: var(--muted); }
        .tool .args code { background: var(--bg); border: 1px solid var(--border); border-radius: 0.35rem; padding: 0.1rem 0.4rem; }

        /* Quick start */
        .snippet {
            background: #0B1120; border: 1px solid var(--border); border-radius: 1rem;
            max-width: 46rem; margin: 0 auto; overflow: hidden;
        }
        .snippet-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.6rem 1.1rem; border-bottom: 1px solid var(--border);
            color: var(--muted); font-size: 0.85rem;
        }
        .snippet pre { padding: 1.25rem; overflow-x: auto; font-size: 0.88rem; line-height: 1.7; color: #E2E8F0; }
        .snippet .k { color: #7DD3FC; }
        .snippet .s { color: #86EFAC; }

        /* CTA */
        .cta-panel {
            background: linear-gradient(135deg, var(--surface), #14532D 140%);
            border: 1px solid rgba(34, 197, 94, 0.35);
            border-radius: 1.25rem; padding: 3.5rem 2rem; text-align: center;
        }
        .cta-panel h2 { margin-bottom: 0.75rem; }
        .cta-panel p { color: var(--muted); margin-bottom: 2rem; }

        footer { border-top: 1px solid var(--border); padding: 2.5rem 0; }
        .footer-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; color: var(--muted); font-size: 0.9rem; }
        .footer-row a { text-decoration: none; transition: color 0.2s; }
        .footer-row a:hover { color: var(--text); }
        .footer-links { display: flex; gap: 1.5rem; }
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
                <a href="#features">Features</a>
                <a href="#tools">Tools</a>
                <a href="{{ route('docs') }}">Docs</a>
                @auth
                    <a href="{{ route('account') }}" class="btn">Account</a>
                @else
                    <a href="{{ route('login') }}" class="btn">Sign in</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <span class="badge"><span class="dot" aria-hidden="true"></span> Model Context Protocol · v1.0</span>
                <h1>Accurate time for your <span class="accent">AI agents</span></h1>
                <p class="lede">
                    Language models don't know what time it is. MCP Time Service gives any
                    MCP-compatible agent reliable current time, timezone conversion, and date
                    utilities — with a single line of configuration.
                </p>
                <div class="hero-actions">
                    <a href="#quickstart" class="btn">Connect in 60 seconds</a>
                    <a href="#tools" class="btn btn-outline">Browse the tools</a>
                </div>
                <div class="clock-card">
                    <div class="label">Server time — live</div>
                    <time id="live-clock" datetime="">--:--:--</time>
                    <div class="tz" id="live-tz">UTC</div>
                </div>
            </div>
        </section>

        <section id="features">
            <div class="container">
                <h2>Why agents need a time server</h2>
                <p class="section-sub">Deterministic, timezone-aware answers instead of hallucinated timestamps.</p>
                <div class="grid">
                    <div class="card">
                        <span class="icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </span>
                        <h3>Real-time accuracy</h3>
                        <p>Every call returns the actual current time from a synchronized clock — never the model's training-data guess.</p>
                    </div>
                    <div class="card">
                        <span class="icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        </span>
                        <h3>Full IANA timezone data</h3>
                        <p>Convert between any of the 400+ IANA timezones with correct daylight-saving handling, all year round.</p>
                    </div>
                    <div class="card">
                        <span class="icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        </span>
                        <h3>Standard MCP transport</h3>
                        <p>Speaks plain Model Context Protocol over stdio or HTTP. Works with Claude, IDE agents, and any MCP client.</p>
                    </div>
                    <div class="card">
                        <span class="icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </span>
                        <h3>Zero data collected</h3>
                        <p>The service is stateless. No prompts, arguments, or identifiers are stored — requests in, timestamps out.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="tools">
            <div class="container">
                <h2>Available tools</h2>
                <p class="section-sub">Three focused tools your agent can call through MCP.</p>
                <div class="grid">
                    <div class="card tool">
                        <code class="name">get_current_time</code>
                        <p>Returns the current date and time in any timezone, formatted as ISO&nbsp;8601.</p>
                        <p class="args">Args: <code>timezone</code> (optional, default UTC)</p>
                    </div>
                    <div class="card tool">
                        <code class="name">convert_time</code>
                        <p>Converts a timestamp from one timezone to another with DST awareness.</p>
                        <p class="args">Args: <code>time</code>, <code>from_tz</code>, <code>to_tz</code></p>
                    </div>
                    <div class="card tool">
                        <code class="name">time_difference</code>
                        <p>Computes the elapsed duration between two timestamps in human or machine format.</p>
                        <p class="args">Args: <code>start</code>, <code>end</code>, <code>unit</code> (optional)</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="quickstart">
            <div class="container">
                <h2>Quick start</h2>
                <p class="section-sub">Add the server to your MCP client configuration and the tools appear automatically.</p>
                <div class="snippet">
                    <div class="snippet-bar">
                        <span class="mono">mcp.json</span>
                        <span>MCP client configuration</span>
                    </div>
                    <pre><code>{
  <span class="k">"mcpServers"</span>: {
    <span class="k">"time"</span>: {
      <span class="k">"url"</span>: <span class="s">"{{ url('/mcp') }}"</span>,
      <span class="k">"transport"</span>: <span class="s">"http"</span>
    }
  }
}</code></pre>
                </div>
                <p class="section-sub" style="margin-top: 1.5rem; margin-bottom: 0;">Client-specific steps and the REST API are in the <a href="{{ route('docs') }}" style="color: var(--accent);">documentation</a>.</p>
            </div>
        </section>

        <section>
            <div class="container">
                <div class="cta-panel">
                    <h2>Give your agent a watch</h2>
                    <p>Free to use, no API key required. Connect your MCP client and start asking about time.</p>
                    <a href="{{ route('docs') }}" class="btn">Read the docs</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-row">
            <span>© {{ date('Y') }} MCP Time Service. All rights reserved.</span>
            <nav class="footer-links" aria-label="Footer navigation">
                <a href="#features">Features</a>
                <a href="#tools">Tools</a>
                <a href="{{ route('docs') }}">Docs</a>
                <a href="/up">Status</a>
            </nav>
        </div>
    </footer>

    <script>
        (function () {
            var clock = document.getElementById('live-clock');
            var tz = document.getElementById('live-tz');
            var zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
            tz.textContent = zone;
            function tick() {
                var now = new Date();
                clock.textContent = now.toLocaleTimeString('en-GB', { hour12: false });
                clock.setAttribute('datetime', now.toISOString());
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
</body>
</html>
