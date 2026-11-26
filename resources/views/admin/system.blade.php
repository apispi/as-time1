@extends('layouts.panel')

@section('title', 'System — Admin')

@section('panel')
    @include('admin._head', ['subtitle' => 'Configuration health for this deployment.'])

    <div class="two">
        <section class="box" aria-labelledby="checks-h">
            <h2 id="checks-h">Checks</h2>
            @foreach ($checks as [$label, $ok, $fix])
                <div class="check-row">
                    <span class="mark {{ $ok ? 'yes' : 'no' }}" aria-hidden="true">{{ $ok ? '✓' : '!' }}</span>
                    <div>
                        <div>{{ $label }} <span style="position:absolute;left:-9999px">{{ $ok ? 'passing' : 'needs attention' }}</span></div>
                        @unless ($ok)<div class="muted">{{ $fix }}</div>@endunless
                    </div>
                </div>
            @endforeach
        </section>

        <section class="box" aria-labelledby="facts-h">
            <h2 id="facts-h">Deployment</h2>
            <dl class="rows">
                @foreach ($facts as $k => $v)
                    <div><dt>{{ $k }}</dt><dd class="mono" style="font-size: 0.85rem;">{{ $v }}</dd></div>
                @endforeach
            </dl>
        </section>
    </div>
@endsection
