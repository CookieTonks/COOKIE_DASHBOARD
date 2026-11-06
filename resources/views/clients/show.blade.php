@extends('layouts.app')

@section('title', $client->name)

@php use App\Support\Format; @endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $client->name }}</h1>
            <p>
                <span class="badge badge-{{ $client->active ? 'on' : 'off' }}">{{ $client->active ? 'Activo' : 'Pausado' }}</span>
                @if ($client->tax_id) RFC {{ $client->tax_id }} @endif
            </p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('clients.edit', $client) }}">Editar</a>
            <form method="post" action="{{ route('clients.destroy', $client) }}"
                  data-confirm="¿Eliminar a {{ $client->name }} con sus contactos, planes y cobros? No se puede deshacer.">
                @csrf @method('delete')
                <button class="btn btn-danger">Eliminar</button>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-error" role="alert">
            <strong>Revisa los datos:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($client->notes)
        <section class="card"><h2>Notas</h2><p style="white-space: pre-line; margin-bottom: 0">{{ $client->notes }}</p></section>
    @endif

    <div class="grid-2" style="margin-bottom: 20px">
        <section class="card">
            <div class="card-head"><h2>Contactos</h2></div>

            @forelse ($client->contacts as $contact)
                <div class="contact">
                    <div class="contact-top">
                        <div>
                            <strong>{{ $contact->name }}</strong>
                            @if ($contact->role) <small>· {{ $contact->role }}</small> @endif
                            <br><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                            @if ($contact->phone) <small>· {{ $contact->phone }}</small> @endif
                        </div>
                        <span class="badge badge-{{ $contact->receives_reminders ? 'on' : 'off' }}">{{ $contact->receives_reminders ? 'Recibe recordatorios' : 'No recibe' }}</span>
                    </div>
                    <details class="edit">
                        <summary>Editar contacto</summary>
                        <form method="post" action="{{ route('clients.contacts.update', [$client, $contact]) }}" style="margin-top: 10px">
                            @csrf @method('put')
                            @include('clients._contact-fields', ['prefix' => 'contact-'.$contact->id, 'contact' => $contact])
                            <div class="form-actions"><button class="btn btn-sm btn-primary">Guardar</button></div>
                        </form>
                        <form method="post" action="{{ route('clients.contacts.destroy', [$client, $contact]) }}" style="margin-top: 6px"
                              data-confirm="¿Eliminar a {{ $contact->name }}?">
                            @csrf @method('delete')
                            <button class="btn btn-sm btn-danger">Eliminar contacto</button>
                        </form>
                    </details>
                </div>
            @empty
                <p class="muted">Sin contactos. Agrega al menos uno para que salgan los recordatorios.</p>
            @endforelse

            <details class="edit" @if ($client->contacts->isEmpty() || $errors->any()) open @endif>
                <summary>Agregar contacto</summary>
                <form method="post" action="{{ route('clients.contacts.store', $client) }}" style="margin-top: 10px">
                    @csrf
                    @include('clients._contact-fields', ['prefix' => 'new-contact', 'contact' => null])
                    <div class="form-actions"><button class="btn btn-sm btn-primary">Agregar</button></div>
                </form>
            </details>
        </section>

        <section class="card">
            <div class="card-head">
                <h2>Planes de pago</h2>
                <a class="btn btn-sm btn-primary" href="{{ route('clients.plans.create', $client) }}">Nuevo plan</a>
            </div>
            @if ($client->plans->isEmpty())
                <p class="muted">Sin planes. Crea uno con su fecha de pago y su ciclo.</p>
            @else
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Concepto y ciclo</th><th class="num">Monto</th><th>Próxima fecha</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($client->plans as $plan)
                                <tr>
                                    <td class="break">
                                        {{ $plan->concept }}
                                        @unless ($plan->active) <span class="badge badge-off">Pausado</span> @endunless
                                        <br><small>{{ $plan->cycle->label() }}</small>
                                    </td>
                                    <td class="num">{{ Format::money($plan->amount, $plan->currency) }}</td>
                                    <td class="nowrap">{{ Format::date($plan->nextDate()) }}</td>
                                    <td><a href="{{ route('clients.plans.edit', [$client, $plan]) }}">Editar</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <section class="card">
        <div class="card-head"><h2>Cobros</h2></div>
        @if ($dues->isEmpty())
            <p class="empty">Los cobros aparecen aquí al crear un plan de pago.</p>
        @else
            @include('dues._table', ['dues' => $dues])
        @endif
    </section>
@endsection
