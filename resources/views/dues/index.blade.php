@extends('layouts.app')

@section('title', 'Cobros')

@section('content')
    <div class="page-head"><h1>Cobros</h1></div>

    <nav class="tabs" aria-label="Filtrar cobros">
        @foreach (['pendientes' => 'Pendientes', 'vencidos' => 'Vencidos', 'pagados' => 'Pagados', 'cancelados' => 'Cancelados'] as $key => $label)
            <a href="{{ route('dues.index', ['estado' => $key]) }}" class="{{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <section class="card">
        @if ($dues->isEmpty())
            <p class="empty">No hay cobros en esta vista.</p>
        @else
            @include('dues._table', ['dues' => $dues])
            @include('partials.pager', ['paginator' => $dues])
        @endif
    </section>
@endsection
