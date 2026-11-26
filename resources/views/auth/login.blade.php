@extends('layouts.app')

@section('title', 'Sign in')
@section('styles') @include('auth._styles') @endsection

@section('content')
<section class="auth">
    <div class="container">
        <div class="auth-card">
            <h1>Sign in</h1>
            <p class="sub">Welcome back to MCP Time Service.</p>

            @include('auth._social')

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p class="error" id="email-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    @error('password') <p class="error" id="password-error">{{ $message }}</p> @enderror
                </div>
                <label class="check">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))> Keep me signed in
                </label>
                <button type="submit" class="btn btn-block">Sign in</button>
            </form>

            <p class="alt">No account yet? <a href="{{ route('register') }}">Create one</a></p>
        </div>
    </div>
</section>
@endsection
