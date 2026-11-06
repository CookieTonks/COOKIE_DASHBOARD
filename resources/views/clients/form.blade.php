@extends('layouts.app')

@section('title', $client->exists ? 'Editar cliente' : 'Nuevo cliente')

@section('content')
    <div class="page-head"><h1>{{ $client->exists ? 'Editar cliente' : 'Nuevo cliente' }}</h1></div>

    <form method="post" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}" class="card">
        @csrf
        @if ($client->exists) @method('put') @endif

        <div class="form-grid">
            <div class="field">
                <label for="name">Nombre o razón social</label>
                <input id="name" type="text" name="name" value="{{ old('name', $client->name) }}" required maxlength="255" autofocus>
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="tax_id">RFC (opcional)</label>
                <input id="tax_id" type="text" name="tax_id" value="{{ old('tax_id', $client->tax_id) }}" maxlength="20">
                @error('tax_id') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field field-full">
                <label for="notes">Notas (opcional)</label>
                <textarea id="notes" name="notes">{{ old('notes', $client->notes) }}</textarea>
                @error('notes') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field field-full">
                <label class="check">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $client->active))>
                    Cliente activo (si lo desactivas, se pausan sus recordatorios)
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary">Guardar</button>
            <a class="btn" href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
