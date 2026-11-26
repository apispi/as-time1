@extends('layouts.panel')

@section('title', 'Dashboard')

@php
    $user = auth()->user();
    $mcp = url('/mcp');
    $api = url('/api/v1');
    $methods = collect([$user->google_id ? 'Google' : null, $user->apispi_id ? 'ApiSpi' : null, $user->password ? 'Password' : null])->filter();
@endphp

@section('panel')
    <div class="panel-head">
        <div>
            <h1>Hi, {{ \Illuminate\Support\Str::before($user->name, ' ') }}</h1>
            <p class="sub">Connect an agent, or call the API directly.</p>
        </div>
        <div class="box stat" style="padding: 0.9rem 1.2rem;">
            <div class="label">Your local time</div>
            <div class="value mono" id="clock" style="font-size: 1.5rem;">--:--:--</div>
            <div class="hint" id="clock-tz"></div>
        </div>
    </div>

    @if (session('status'))
        <div class="flash ok" role="status">{{ session('status') }}</div>
    @endif

    <div class="two">
        <section class="box" aria-labelledby="connect-h">
            <h2 id="connect-h">Connect with MCP</h2>
            <p class="muted" style="margin-bottom: 0.6rem;">Claude Code — run once in a terminal:</p>
            <div class="copy-row">
                <pre class="code" id="cc">claude mcp add --transport http time {{ $mcp }}</pre>
                <button type="button" class="copy-btn" data-copy="cc">Copy</button>
            </div>
            <p class="muted" style="margin-bottom: 0.6rem;">Any other MCP client — server URL:</p>
            <div class="copy-row">
                <pre class="code" id="mcp-url">{{ $mcp }}</pre>
                <button type="button" class="copy-btn" data-copy="mcp-url">Copy</button>
            </div>
            <p class="muted"><a href="{{ route('docs') }}#mcp" style="color: var(--accent);">Setup for Claude apps and other clients →</a></p>
        </section>

        <section class="box" aria-labelledby="api-h">
            <h2 id="api-h">Call the REST API</h2>
            <p class="muted" style="margin-bottom: 0.6rem;">No key needed. Try it:</p>
            <div class="copy-row">
                <pre class="code" id="curl">curl '{{ $api }}/time?timezone={{ config('app.timezone') === 'UTC' ? 'UTC' : config('app.timezone') }}'</pre>
                <button type="button" class="copy-btn" data-copy="curl">Copy</button>
            </div>
            <div class="copy-row">
                <pre class="code" id="curl2">curl '{{ $api }}/timezones?search=sydney'</pre>
                <button type="button" class="copy-btn" data-copy="curl2">Copy</button>
            </div>
            <p class="muted">60 requests per minute per IP. <a href="{{ route('docs') }}#api" style="color: var(--accent);">Full API reference →</a></p>
        </section>
    </div>

    <section class="box" aria-labelledby="acct-h" style="max-width: 36rem;">
        <h2 id="acct-h">Account</h2>
        <dl class="rows">
            <div><dt>Name</dt><dd>{{ $user->name }}</dd></div>
            <div><dt>Email</dt><dd>{{ $user->email }} <span class="pill green">verified</span></dd></div>
            <div><dt>Sign-in</dt><dd>{{ $methods->join(' + ') }}</dd></div>
            <div><dt>Member since</dt><dd>{{ $user->created_at->toFormattedDateString() }}</dd></div>
        </dl>
        <form method="POST" action="{{ route('logout') }}" style="margin-top: 1.1rem;">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Sign out</button>
        </form>
    </section>

    <script>
        (function () {
            var c = document.getElementById('clock'), z = document.getElementById('clock-tz');
            z.textContent = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
            function tick() { c.textContent = new Date().toLocaleTimeString('en-GB', { hour12: false }); }
            tick(); setInterval(tick, 1000);
        })();
    </script>
@endsection
