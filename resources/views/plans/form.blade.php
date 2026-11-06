@extends('layouts.app')

@section('title', $plan->exists ? 'Editar plan' : 'Nuevo plan')

@php use App\Enums\Cycle; @endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $plan->exists ? 'Editar plan de pago' : 'Nuevo plan de pago' }}</h1>
            <p><a href="{{ route('clients.show', $client) }}">{{ $client->name }}</a></p>
        </div>
    </div>

    <form method="post" action="{{ $plan->exists ? route('clients.plans.update', [$client, $plan]) : route('clients.plans.store', $client) }}" class="card">
        @csrf
        @if ($plan->exists) @method('put') @endif

        <div class="form-grid">
            <div class="field field-full">
                <label for="concept">Concepto</label>
                <input id="concept" type="text" name="concept" value="{{ old('concept', $plan->concept) }}" required maxlength="255" placeholder="Ej. Mantenimiento del sitio web" autofocus>
                @error('concept') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="amount">Monto</label>
                <input id="amount" type="number" name="amount" value="{{ old('amount', $plan->amount) }}" required min="0.01" step="0.01">
                @error('amount') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="currency">Moneda</label>
                <select id="currency" name="currency">
                    @foreach (['MXN', 'USD'] as $currency)
                        <option value="{{ $currency }}" @selected(old('currency', $plan->currency) === $currency)>{{ $currency }}</option>
                    @endforeach
                </select>
                @error('currency') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="cycle">Ciclo</label>
                <select id="cycle" name="cycle">
                    @foreach (Cycle::cases() as $cycle)
                        <option value="{{ $cycle->value }}" @selected(old('cycle', $plan->cycle?->value) === $cycle->value)>{{ $cycle->label() }}</option>
                    @endforeach
                </select>
                @error('cycle') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="next_date">Próxima fecha de pago</label>
                <input id="next_date" type="date" name="next_date" value="{{ old('next_date', $nextDate?->toDateString()) }}" required>
                <div class="hint">Los pagos siguientes se calculan desde esta fecha.</div>
                @error('next_date') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="ends_on">Termina el (opcional)</label>
                <input id="ends_on" type="date" name="ends_on" value="{{ old('ends_on', $plan->ends_on?->toDateString()) }}">
                <div class="hint">Déjalo vacío si el plan no tiene fin.</div>
                @error('ends_on') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field field-full">
                <label class="check">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $plan->active))>
                    Plan activo (si lo desactivas, se pausan sus recordatorios)
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary">Guardar</button>
            <a class="btn" href="{{ route('clients.show', $client) }}">Cancelar</a>
        </div>
    </form>

    @if ($plan->exists)
        <form method="post" action="{{ route('clients.plans.destroy', [$client, $plan]) }}"
              data-confirm="¿Eliminar este plan y todos sus cobros? No se puede deshacer.">
            @csrf @method('delete')
            <button class="btn btn-danger">Eliminar plan</button>
        </form>
    @endif
@endsection
