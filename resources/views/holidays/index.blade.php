@extends('layouts.app')

@section('title', 'Días inhábiles')

@php use App\Support\Format; @endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Días inhábiles</h1>
            <p>Sábados y domingos nunca cuentan como hábiles. Aquí van los feriados y tus días de cierre.</p>
        </div>
    </div>

    <form method="post" action="{{ route('holidays.store') }}" class="card">
        <div class="card-head"><h2>Agregar día</h2></div>
        @csrf
        <div class="form-grid">
            <div class="field">
                <label for="date">Fecha</label>
                <input id="date" type="date" name="date" value="{{ old('date') }}" required>
                @error('date') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="name">Motivo</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Cierre de fin de año">
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Agregar</button></div>
    </form>

    @foreach ($holidays as $year => $days)
        <section class="card">
            <div class="card-head"><h2>{{ $year }}</h2></div>
            <div class="table-wrap">
                <table>
                    <tbody>
                        @foreach ($days as $holiday)
                            <tr>
                                <td class="nowrap">{{ Format::date($holiday->date) }}</td>
                                <td class="break">{{ $holiday->name }}</td>
                                <td>
                                    <div class="row-actions">
                                        <form method="post" action="{{ route('holidays.destroy', $holiday) }}"
                                              data-confirm="¿Quitar {{ $holiday->name }}? Ese día contará como hábil.">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-ghost">Quitar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
@endsection
