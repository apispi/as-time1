@extends('layouts.panel')

@section('title', 'Admin')

@section('panel')
    @include('admin._head', ['subtitle' => 'Accounts and traffic at a glance.'])

    <div class="cards">
        <div class="box stat"><div class="label">Users</div><div class="value">{{ number_format($stats['users']) }}</div><div class="hint">{{ number_format($stats['verified']) }} verified</div></div>
        <div class="box stat"><div class="label">New this week</div><div class="value">{{ number_format($stats['new_7d']) }}</div><div class="hint">last 7 days</div></div>
        <div class="box stat"><div class="label">API calls today</div><div class="value">{{ number_format($today['api']) }}</div><div class="hint">REST, successful</div></div>
        <div class="box stat"><div class="label">MCP calls today</div><div class="value">{{ number_format($today['mcp']) }}</div><div class="hint">JSON-RPC requests</div></div>
    </div>

    <div class="two">
        @php($max = max(1, max(array_map(fn ($d) => $d['api'] + $d['mcp'], $daily))))
        <section class="box" aria-labelledby="traffic-h">
            <h2 id="traffic-h">Traffic, last 14 days</h2>
            <div class="bars" role="img" aria-label="Daily API and MCP requests over the last 14 days">
                @foreach ($daily as $d)
                    <div class="day" title="{{ \Illuminate\Support\Carbon::parse($d['date'])->format('D j M') }}: {{ $d['api'] }} API, {{ $d['mcp'] }} MCP">
                        <div class="seg mcp" style="height: {{ round($d['mcp'] / $max * 100, 2) }}%"></div>
                        <div class="seg api" style="height: {{ round($d['api'] / $max * 100, 2) }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="bars-axis">
                <span>{{ \Illuminate\Support\Carbon::parse($daily[0]['date'])->format('j M') }}</span>
                <span>peak {{ number_format($max) }}/day</span>
                <span>today</span>
            </div>
            <div class="legend"><span><i style="background: var(--accent)"></i>REST API</span><span><i style="background: #38BDF8"></i>MCP</span></div>
        </section>

        <section class="box" aria-labelledby="methods-h">
            <h2 id="methods-h">Sign-in methods</h2>
            <dl class="rows">
                @foreach ($methods as $label => $count)
                    <div><dt>{{ $label }}</dt><dd>{{ number_format($count) }}</dd></div>
                @endforeach
                <div><dt>Admins</dt><dd>{{ number_format($stats['admins']) }}</dd></div>
            </dl>
            <p class="muted" style="margin-top: 0.7rem;">An account can use more than one method.</p>
        </section>
    </div>

    <section class="box" aria-labelledby="recent-h">
        <h2 id="recent-h">Newest accounts</h2>
        @if ($recent->isEmpty())
            <p class="muted">No accounts yet.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Email</th><th>Name</th><th>Status</th><th>Joined</th></tr></thead>
                    <tbody>
                        @foreach ($recent as $u)
                            <tr>
                                <td>{{ $u->email }}</td>
                                <td>{{ $u->name }}</td>
                                <td>@if ($u->email_verified_at)<span class="pill green">verified</span>@else<span class="pill amber">pending</span>@endif</td>
                                <td class="muted">{{ $u->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p style="margin-top: 0.9rem;"><a href="{{ route('admin.users') }}" style="color: var(--accent);">All users →</a></p>
        @endif
    </section>
@endsection
