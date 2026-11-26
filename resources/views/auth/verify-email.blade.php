@extends('layouts.app')

@section('title', 'Verify your email')
@section('styles') @include('auth._styles')
.ok { border: 1px solid rgba(34, 197, 94, 0.4); background: rgba(34, 197, 94, 0.08); color: #86EFAC; border-radius: 0.6rem; padding: 0.7rem 0.9rem; font-size: 0.9rem; margin-bottom: 1rem; }
.actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }
@endsection

@section('content')
<section class="auth">
    <div class="container">
        <div class="auth-card">
            <h1>Check your inbox</h1>
            <p class="sub">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Open it to finish creating your account. The link expires in 60 minutes.</p>

            @if (session('status'))
                <div class="ok" role="status">{{ session('status') }}</div>
            @endif

            <div class="actions">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn">Resend the link</button>
                </form>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">Sign out</button>
                </form>
            </div>
            <p class="alt">Wrong address? Sign out and register again.</p>
        </div>
    </div>
</section>
@endsection
