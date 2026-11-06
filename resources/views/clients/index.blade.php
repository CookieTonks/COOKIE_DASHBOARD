@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="page-head">
        <h1>Clientes</h1>
        <a class="btn btn-primary" href="{{ route('clients.create') }}">Nuevo cliente</a>
    </div>

    <section class="card">
        <form class="search" method="get" role="search">
            <input type="search" name="q" value="{{ $search }}" placeholder="Buscar por nombre, RFC o contacto" aria-label="Buscar clientes">
            <button class="btn">Buscar</button>
        </form>

        @if ($clients->isEmpty())
            <p class="empty">
                @if ($search !== '') No hay clientes que coincidan con "{{ $search }}". @else Aún no tienes clientes. Da de alta el primero. @endif
            </p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Cliente</th><th>RFC</th><th class="num">Contactos</th><th class="num">Planes</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                            <tr>
                                <td class="break"><a href="{{ route('clients.show', $client) }}">{{ $client->name }}</a></td>
                                <td class="nowrap">{{ $client->tax_id ?: '—' }}</td>
                                <td class="num">{{ $client->contacts_count }}</td>
                                <td class="num">{{ $client->plans_count }}</td>
                                <td><span class="badge badge-{{ $client->active ? 'on' : 'off' }}">{{ $client->active ? 'Activo' : 'Pausado' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.pager', ['paginator' => $clients])
        @endif
    </section>
@endsection
