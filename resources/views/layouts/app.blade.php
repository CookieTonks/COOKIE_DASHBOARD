@extends('layouts.base')

@section('body')
    <nav class="nav">
        <a class="brand" href="{{ route('dashboard') }}">Recordatorios</a>
        <div class="nav-links">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Inicio</a>
            <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">Clientes</a>
            <a href="{{ route('dues.index') }}" class="{{ request()->routeIs('dues.*') ? 'active' : '' }}">Cobros</a>
            <a href="{{ route('logs.index') }}" class="{{ request()->routeIs('logs.*') ? 'active' : '' }}">Historial</a>
            <a href="{{ route('holidays.index') }}" class="{{ request()->routeIs('holidays.*') ? 'active' : '' }}">Días inhábiles</a>
        </div>
        <div class="nav-tools">
            <button type="button" id="theme-toggle" class="btn btn-sm btn-ghost">Tema</button>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-sm btn-ghost">Salir</button>
            </form>
        </div>
    </nav>
    <main>
        @if (config('mail.default') === 'log')
            <div class="alert alert-warn"><strong>El correo aún no está conectado.</strong> Los recordatorios no saldrán hasta que configures el SMTP en <code>.env</code>.</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error" role="alert">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
@endsection
