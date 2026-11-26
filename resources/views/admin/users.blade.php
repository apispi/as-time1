@extends('layouts.panel')

@section('title', 'Users — Admin')

@section('panel')
    @include('admin._head', ['subtitle' => number_format($users->total()).' '.\Illuminate\Support\Str::plural('account', $users->total()).($search !== '' || $filter ? ' matching' : '')])

    <form class="toolbar" method="GET" action="{{ route('admin.users') }}" role="search">
        <label for="q" class="sr-only" style="position:absolute;left:-9999px">Search users</label>
        <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Search name or email">
        <label for="filter" style="position:absolute;left:-9999px">Filter</label>
        <select id="filter" name="filter">
            <option value="">All users</option>
            <option value="unverified" @selected($filter === 'unverified')>Pending verification</option>
            <option value="admins" @selected($filter === 'admins')>Admins</option>
        </select>
        <button type="submit" class="btn btn-sm">Search</button>
    </form>

    <div class="box table-wrap" style="padding: 0.5rem 1.2rem;">
        <table>
            <thead><tr><th>User</th><th>Sign-in</th><th>Status</th><th>Joined</th><th><span style="position:absolute;left:-9999px">Actions</span></th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>
                            <div>{{ $u->name }} @if ($u->is_admin)<span class="pill green">admin</span>@endif</div>
                            <div class="muted">{{ $u->email }}</div>
                        </td>
                        <td class="muted">{{ collect([$u->google_id ? 'Google' : null, $u->apispi_id ? 'ApiSpi' : null, $u->password ? 'Password' : null])->filter()->join(', ') ?: '—' }}</td>
                        <td>@if ($u->email_verified_at)<span class="pill green">verified</span>@else<span class="pill amber">pending</span>@endif</td>
                        <td class="muted" title="{{ $u->created_at }}">{{ $u->created_at->toFormattedDateString() }}</td>
                        <td>
                            @unless ($u->is(auth()->user()))
                                <div class="actions">
                                    @unless ($u->email_verified_at)
                                        <form method="POST" action="{{ route('admin.users.verify', $u) }}">@csrf<button class="btn btn-outline btn-sm">Mark verified</button></form>
                                    @endunless
                                    <form method="POST" action="{{ route('admin.users.admin', $u) }}" data-confirm="{{ $u->is_admin ? 'Remove admin access for '.$u->email.'?' : 'Give '.$u->email.' full admin access?' }}">
                                        @csrf<button class="btn btn-outline btn-sm">{{ $u->is_admin ? 'Remove admin' : 'Make admin' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" data-confirm="Delete {{ $u->email }}? This cannot be undone.">
                                        @csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            @else
                                <div class="actions"><span class="muted">you</span></div>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted" style="padding: 1.5rem 0;">No users match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <nav class="pagination" aria-label="Pages">
            @if ($users->previousPageUrl())<a class="btn btn-outline btn-sm" href="{{ $users->previousPageUrl() }}">← Newer</a>@endif
            <span class="muted" style="align-self: center;">Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>
            @if ($users->nextPageUrl())<a class="btn btn-outline btn-sm" href="{{ $users->nextPageUrl() }}">Older →</a>@endif
        </nav>
    @endif
@endsection
