@extends('layouts.app')

@section('title', 'Inicio')

@php use App\Support\Format; @endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Inicio</h1>
            <p>{{ ucfirst(now()->translatedFormat('l j \d\e F \d\e Y')) }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('clients.create') }}">Nuevo cliente</a>
    </div>

    @if ($failed->isNotEmpty())
        <div class="alert alert-error" role="alert">
            <strong>{{ $failed->count() === 1 ? 'Un recordatorio no se pudo enviar' : $failed->count().' recordatorios no se pudieron enviar' }}.</strong>
            Se reintentan a las 12:00 y a las 16:00.
            <ul>
                @foreach ($failed as $due)
                    <li>
                        <a href="{{ route('clients.show', $due->plan->client) }}">{{ $due->plan->client->name }}</a>
                        ({{ $due->plan->concept }}): {{ $due->logs->first()->error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($clientsWithoutContacts->isNotEmpty())
        <div class="alert alert-warn">
            <strong>Estos clientes no recibirán recordatorios porque no tienen un contacto activo:</strong>
            @foreach ($clientsWithoutContacts as $client)
                <a href="{{ route('clients.show', $client) }}">{{ $client->name }}</a>@if (! $loop->last), @endif
            @endforeach
        </div>
    @endif

    <section class="stats" aria-label="Resumen">
        <div class="stat">
            <div class="stat-label">Vencido sin pagar</div>
            <div class="stat-value">{{ Format::totals($overdue) }}</div>
            <div class="stat-sub">{{ $overdue->count() }} {{ $overdue->count() === 1 ? 'cobro' : 'cobros' }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Por cobrar en 30 días</div>
            <div class="stat-value">{{ Format::totals($upcoming) }}</div>
            <div class="stat-sub">{{ $upcoming->count() }} {{ $upcoming->count() === 1 ? 'cobro' : 'cobros' }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Clientes activos</div>
            <div class="stat-value">{{ $activeClients }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Recordatorios enviados este mes</div>
            <div class="stat-value">{{ $sentThisMonth }}</div>
        </div>
    </section>

    @if ($overdue->isNotEmpty())
        <section class="card">
            <div class="card-head"><h2>Vencidos</h2></div>
            @include('dues._table', ['dues' => $overdue])
        </section>
    @endif

    <section class="card">
        <div class="card-head">
            <h2>Próximos 30 días</h2>
            <a href="{{ route('dues.index') }}">Ver todos los cobros</a>
        </div>
        @if ($upcoming->isEmpty())
            <p class="empty">No hay cobros en los próximos 30 días.</p>
        @else
            @include('dues._table', ['dues' => $upcoming])
        @endif
    </section>
@endsection
