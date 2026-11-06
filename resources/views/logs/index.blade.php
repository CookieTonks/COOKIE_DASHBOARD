@extends('layouts.app')

@section('title', 'Historial')

@php use App\Support\Format; @endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Historial de correos</h1>
            <p>Cada intento de envío, automático o manual.</p>
        </div>
    </div>

    <section class="card">
        @if ($logs->isEmpty())
            <p class="empty">Todavía no se ha enviado ningún recordatorio.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Fecha</th><th>Cliente</th><th>Concepto</th><th>Vence</th><th>Destinatarios</th><th>Origen</th><th>Resultado</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="nowrap">{{ $log->created_at->translatedFormat('j M Y H:i') }}</td>
                                <td class="break">{{ $log->client_name }}</td>
                                <td class="break">{{ $log->concept }}</td>
                                <td class="nowrap">{{ Format::date($log->due_date) }}</td>
                                <td class="break">{{ implode(', ', $log->recipients) ?: '—' }}</td>
                                <td>{{ $log->trigger === 'auto' ? 'Automático' : 'Manual' }}</td>
                                <td class="break">
                                    <span class="badge badge-{{ $log->status }}">{{ $log->status === 'sent' ? 'Enviado' : 'Falló' }}</span>
                                    @if ($log->error) <br><small>{{ $log->error }}</small> @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.pager', ['paginator' => $logs])
        @endif
    </section>
@endsection
