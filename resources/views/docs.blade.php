@extends('layouts.app')

@section('title', 'Documentation')
@section('description', 'How to connect to MCP Time Service: MCP client setup for Claude and other agents, and the REST API reference.')

@section('styles')
.docs { display: grid; grid-template-columns: 14rem minmax(0, 1fr); gap: 3rem; padding-top: 3rem; padding-bottom: 4rem; }
.toc { position: sticky; top: 5.5rem; align-self: start; font-size: 0.92rem; }
.toc p { color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.75rem; margin: 1.25rem 0 0.4rem; }
.toc p:first-child { margin-top: 0; }
.toc a { display: block; padding: 0.3rem 0; color: var(--muted); text-decoration: none; }
.toc a:hover { color: var(--text); }
@media (max-width: 860px) { .docs { grid-template-columns: minmax(0, 1fr); gap: 1.5rem; } .toc { position: static; } }

.prose { max-width: 46rem; }
.prose h1 { font-size: clamp(2rem, 5vw, 2.6rem); margin-bottom: 0.75rem; }
.prose .lede { color: var(--muted); font-size: 1.1rem; margin-bottom: 1rem; }
.prose h2 { font-size: 1.6rem; margin: 3rem 0 0.75rem; padding-top: 1.5rem; border-top: 1px solid var(--border); }
.prose h3 { font-size: 1.15rem; margin: 2rem 0 0.5rem; }
.prose p, .prose ul, .prose ol { margin-bottom: 1rem; color: var(--muted); }
.prose ul, .prose ol { padding-left: 1.3rem; }
.prose li { margin-bottom: 0.3rem; }
.prose strong { color: var(--text); }
.prose a { color: var(--accent); }
.prose :not(pre) > code { background: var(--surface); border: 1px solid var(--border); border-radius: 0.35rem; padding: 0.1rem 0.4rem; font-size: 0.88em; color: var(--text); }
.prose pre { background: #0B1120; border: 1px solid var(--border); border-radius: 0.75rem; padding: 1rem 1.15rem; overflow-x: auto; font-size: 0.86rem; line-height: 1.7; color: #E2E8F0; margin-bottom: 1rem; }
.endpoint { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; margin: 2rem 0 0.5rem; }
.endpoint .verb { background: rgba(34, 197, 94, 0.15); color: var(--accent); font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; font-weight: 500; padding: 0.15rem 0.5rem; border-radius: 0.35rem; }
.endpoint h3 { margin: 0; font-family: 'JetBrains Mono', monospace; font-size: 1.05rem; font-weight: 500; }
.table-wrap { overflow-x: auto; margin-bottom: 1rem; }
table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
th, td { text-align: left; padding: 0.55rem 0.75rem 0.55rem 0; border-bottom: 1px solid var(--border); vertical-align: top; }
th { color: var(--muted); font-weight: 500; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; }
td { color: var(--muted); }
td:first-child { white-space: nowrap; }
.note { border-left: 3px solid var(--accent); background: var(--surface); border-radius: 0 0.6rem 0.6rem 0; padding: 0.8rem 1rem; margin-bottom: 1rem; }
.note p { margin: 0; }
@endsection

@php
    $base = rtrim(url('/'), '/');
    $mcp = $base.'/mcp';
    $api = $base.'/api/v1';
@endphp

@section('content')
<div class="container docs">
    <nav class="toc" aria-label="On this page">
        <p>Connect with MCP</p>
        <a href="#mcp">Overview</a>
        <a href="#claude-code">Claude Code</a>
        <a href="#claude-apps">Claude apps</a>
        <a href="#other-clients">Other MCP clients</a>
        <a href="#mcp-tools">Tools</a>
        <p>REST API</p>
        <a href="#api">Overview</a>
        <a href="#api-time">Current time</a>
        <a href="#api-convert">Convert</a>
        <a href="#api-difference">Difference</a>
        <a href="#api-timezones">Timezones</a>
        <a href="#errors">Errors and limits</a>
    </nav>

    <article class="prose">
        <h1>Documentation</h1>
        <p class="lede">There are two ways in. AI agents connect over the Model Context Protocol; everything else calls the REST API. Both are free, need no key, and return the same answers.</p>

        <h2 id="mcp">Connect with MCP</h2>
        <p>The server speaks MCP over streamable HTTP at a single URL. There is nothing to install and no authentication.</p>
        <pre><code>{{ $mcp }}</code></pre>

        <h3 id="claude-code">Claude Code</h3>
        <p>Run this once in a terminal. Add <code>--scope user</code> to make it available in every project.</p>
        <pre><code>claude mcp add --transport http time {{ $mcp }}</code></pre>
        <p>Then run <code>/mcp</code> inside Claude Code to confirm that <strong>time</strong> is connected.</p>

        <h3 id="claude-apps">Claude apps (web and desktop)</h3>
        <ol>
            <li>Open <strong>Settings → Connectors</strong>.</li>
            <li>Choose <strong>Add custom connector</strong>.</li>
            <li>Name it <code>Time</code> and paste <code>{{ $mcp }}</code> as the URL.</li>
            <li>Save, then enable the connector in a conversation.</li>
        </ol>

        <h3 id="other-clients">Other MCP clients</h3>
        <p>Most clients that support remote servers take a JSON entry like this one. The key holding the URL varies by client; check its documentation if <code>url</code> is not accepted.</p>
        <pre><code>{
  "mcpServers": {
    "time": {
      "type": "http",
      "url": "{{ $mcp }}"
    }
  }
}</code></pre>
        <p>A client that only supports local (stdio) servers can reach the service through the <code>mcp-remote</code> bridge:</p>
        <pre><code>{
  "mcpServers": {
    "time": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "{{ $mcp }}"]
    }
  }
}</code></pre>
        <p>To check the endpoint without a client, send it a JSON-RPC request:</p>
        <pre><code>curl -X POST {{ $mcp }} \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'</code></pre>

        <h3 id="mcp-tools">Tools</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tool</th><th>Arguments</th><th>Returns</th></tr></thead>
                <tbody>
                    <tr><td><code>get_current_time</code></td><td><code>timezone</code> (optional, default UTC)</td><td>Current time in that timezone</td></tr>
                    <tr><td><code>convert_time</code></td><td><code>time</code>, <code>from_tz</code>, <code>to_tz</code></td><td>The same instant in both timezones</td></tr>
                    <tr><td><code>time_difference</code></td><td><code>start</code>, <code>end</code>, <code>unit</code> (optional)</td><td>Elapsed duration between two timestamps</td></tr>
                </tbody>
            </table>
        </div>
        <p>Each tool returns the same JSON as its REST endpoint below.</p>

        <h2 id="api">REST API</h2>
        <p>For scripts, backends and anything that is not an MCP client. All endpoints are <code>GET</code>, take query parameters and return JSON. Cross-origin requests are allowed, so the API can be called from a browser.</p>
        <pre><code>{{ $api }}</code></pre>
        <p>Timezones are IANA identifiers such as <code>Australia/Sydney</code>. Timestamps are accepted as ISO 8601 or <code>YYYY-MM-DD HH:MM:SS</code>, and always returned as ISO 8601.</p>

        <div class="endpoint" id="api-time"><span class="verb">GET</span><h3>/time</h3></div>
        <p>The current date and time.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Parameter</th><th>Required</th><th>Description</th></tr></thead>
                <tbody><tr><td><code>timezone</code></td><td>No</td><td>IANA timezone. Defaults to <code>UTC</code>.</td></tr></tbody>
            </table>
        </div>
        <pre><code>curl '{{ $api }}/time?timezone=Australia/Sydney'</code></pre>
        <pre><code>{
  "timezone": "Australia/Sydney",
  "datetime": "2026-11-13T09:30:00+11:00",
  "unix_timestamp": 1794522600,
  "utc_offset": "+11:00",
  "is_dst": true,
  "day_of_week": "Friday"
}</code></pre>

        <div class="endpoint" id="api-convert"><span class="verb">GET</span><h3>/convert</h3></div>
        <p>Converts a timestamp from one timezone to another, with daylight saving applied on both sides.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Parameter</th><th>Required</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>time</code></td><td>Yes</td><td>The timestamp to convert, read as local time in <code>from_tz</code>.</td></tr>
                    <tr><td><code>from_tz</code></td><td>Yes</td><td>Source IANA timezone.</td></tr>
                    <tr><td><code>to_tz</code></td><td>Yes</td><td>Target IANA timezone.</td></tr>
                </tbody>
            </table>
        </div>
        <pre><code>curl -G '{{ $api }}/convert' \
  --data-urlencode 'time=2026-07-17 09:30:00' \
  --data-urlencode 'from_tz=Australia/Sydney' \
  --data-urlencode 'to_tz=America/New_York'</code></pre>
        <pre><code>{
  "source": {
    "timezone": "Australia/Sydney",
    "datetime": "2026-07-17T09:30:00+10:00",
    "is_dst": false
  },
  "converted": {
    "timezone": "America/New_York",
    "datetime": "2026-07-16T19:30:00-04:00",
    "is_dst": true
  },
  "offset_difference": "-14 hours"
}</code></pre>

        <div class="endpoint" id="api-difference"><span class="verb">GET</span><h3>/difference</h3></div>
        <p>The elapsed time between two timestamps. Negative when <code>end</code> is before <code>start</code>.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Parameter</th><th>Required</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>start</code></td><td>Yes</td><td>Starting timestamp. Include an offset; without one it is read as UTC.</td></tr>
                    <tr><td><code>end</code></td><td>Yes</td><td>Ending timestamp.</td></tr>
                    <tr><td><code>unit</code></td><td>No</td><td>Adds the total in one unit: <code>seconds</code>, <code>minutes</code>, <code>hours</code>, <code>days</code>, <code>weeks</code>, <code>months</code> or <code>years</code>.</td></tr>
                </tbody>
            </table>
        </div>
        <pre><code>curl -G '{{ $api }}/difference' \
  --data-urlencode 'start=2026-01-01T00:00:00Z' \
  --data-urlencode 'end=2026-03-15T12:00:00Z' \
  --data-urlencode 'unit=hours'</code></pre>
        <pre><code>{
  "start": "2026-01-01T00:00:00+00:00",
  "end": "2026-03-15T12:00:00+00:00",
  "human": "2 months 2 weeks 12 hours",
  "iso8601_duration": "P0Y2M14DT12H0M0S",
  "total_seconds": 6350400,
  "hours": 1764
}</code></pre>

        <div class="endpoint" id="api-timezones"><span class="verb">GET</span><h3>/timezones</h3></div>
        <p>Every timezone identifier the service accepts. No parameters.</p>
        <pre><code>curl '{{ $api }}/timezones'</code></pre>
        <pre><code>{
  "count": 419,
  "timezones": ["Africa/Abidjan", "Africa/Accra", "…"]
}</code></pre>

        <h2 id="errors">Errors and limits</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
                <tbody>
                    <tr><td><code>200</code></td><td>Success.</td></tr>
                    <tr><td><code>422</code></td><td>A parameter is missing or invalid. The body names each one.</td></tr>
                    <tr><td><code>429</code></td><td>Rate limit reached. Wait the number of seconds in the <code>Retry-After</code> header.</td></tr>
                </tbody>
            </table>
        </div>
        <pre><code>{
  "message": "The timezone field must be a valid timezone.",
  "errors": {
    "timezone": ["The timezone field must be a valid timezone."]
  }
}</code></pre>
        <div class="note">
            <p>The REST API allows <strong>60 requests per minute</strong> per IP address. Every response carries <code>X-RateLimit-Limit</code> and <code>X-RateLimit-Remaining</code> headers.</p>
        </div>
    </article>
</div>
@endsection
