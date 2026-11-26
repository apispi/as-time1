<div class="panel-head">
    <div>
        <h1>Admin</h1>
        <p class="sub">{{ $subtitle }}</p>
    </div>
</div>
<nav class="tabs" aria-label="Admin sections">
    <a href="{{ route('admin.overview') }}" @if (request()->routeIs('admin.overview')) aria-current="page" @endif>Overview</a>
    <a href="{{ route('admin.users') }}" @if (request()->routeIs('admin.users')) aria-current="page" @endif>Users</a>
    <a href="{{ route('admin.system') }}" @if (request()->routeIs('admin.system')) aria-current="page" @endif>System</a>
</nav>
@if (session('status'))
    <div class="flash ok" role="status">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="flash err" role="alert">{{ $errors->first() }}</div>
@endif
