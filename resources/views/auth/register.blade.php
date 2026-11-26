@extends('layouts.app')

@section('title', 'Create account')
@section('styles') @include('auth._styles') @endsection

@section('content')
<section class="auth">
    <div class="container">
        <div class="auth-card">
            <h1>Create your account</h1>
            <p class="sub">The time tools stay free and need no account; this is for what comes next.</p>

            @include('auth._social')

            <form method="POST" action="{{ route('register') }}" novalidate>
                @csrf
                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus
                        @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name') <p class="error" id="name-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p class="error" id="email-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required aria-describedby="password-hint"
                        @error('password') aria-invalid="true" @enderror>
                    <p class="hint" id="password-hint">At least 8 characters.</p>
                    @error('password') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-block">Create account</button>
            </form>

            <p class="alt">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
        </div>
    </div>
</section>
@endsection
