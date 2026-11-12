@extends('layouts.app')

@section('title', 'Account')
@section('styles') @include('auth._styles')
.rows { margin: 1.25rem 0 1.5rem; border-top: 1px solid var(--border); }
.rows div { display: flex; justify-content: space-between; gap: 1rem; padding: 0.7rem 0; border-bottom: 1px solid var(--border); font-size: 0.95rem; }
.rows dt { color: var(--muted); }
.rows dd { text-align: right; overflow-wrap: anywhere; }
.actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }
@endsection

@section('content')
<section class="auth">
    <div class="container">
        <div class="auth-card">
            <h1>Your account</h1>
            <p class="sub">Signed in as {{ auth()->user()->name }}.</p>
            <dl class="rows">
                <div><dt>Email</dt><dd>{{ auth()->user()->email }}</dd></div>
                <div><dt>Sign-in</dt><dd>{{ collect([auth()->user()->google_id ? 'Google' : null, auth()->user()->password ? 'Password' : null])->filter()->join(' + ') }}</dd></div>
                <div><dt>Member since</dt><dd>{{ auth()->user()->created_at->toFormattedDateString() }}</dd></div>
            </dl>
            <div class="actions">
                <a href="{{ route('docs') }}" class="btn">Connect a client</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
