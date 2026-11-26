@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.final.templateTitle') }}
@endsection

@section('title')
    <i class="fa fa-flag-checkered fa-fw" aria-hidden="true"></i>
    {{ trans('installer_messages.final.title') }}
@endsection

@section('container')

    <p><strong>Initial accounts, when seeded, are admin@example.com and owner@example.com.</strong></p>
    <p>Passwords come from SEED_ADMIN_PASSWORD and SEED_OWNER_PASSWORD, or from storage/app/seeded-credentials.txt when those variables are empty. Change both passwords immediately and delete that file. Do not use a shared default password.</p>

    @if(session('message')['dbOutputLog'])
        <p><strong><small>{{ trans('installer_messages.final.migration') }}</small></strong></p>
        <pre><code>{{ session('message')['dbOutputLog'] }}</code></pre>
    @endif

    <p><strong><small>{{ trans('installer_messages.final.console') }}</small></strong></p>
    <pre><code>{{ $finalMessages }}</code></pre>

    <p><strong><small>{{ trans('installer_messages.final.log') }}</small></strong></p>
    <pre><code>{{ $finalStatusMessage }}</code></pre>

    <p><strong><small>{{ trans('installer_messages.final.env') }}</small></strong></p>
    <pre><code>{{ $finalEnvFile }}</code></pre>

    <div class="buttons">
        <a href="{{ url('/') }}" class="button">{{ trans('installer_messages.final.exit') }}</a>
    </div>

@endsection
