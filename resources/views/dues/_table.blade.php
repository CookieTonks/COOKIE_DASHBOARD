@php use App\Support\Format; @endphp

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Vence</th>
                <th>Cliente</th>
                <th>Concepto</th>
                <th class="num">Monto</th>
                <th>Estado</th>
                <th>Recordatorio</th>
                <th><span class="sr-only" hidden>Acciones</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dues as $due)
                <tr>
                    <td class="nowrap">{{ Format::date($due->due_date) }}</td>
                    <td class="break"><a href="{{ route('clients.show', $due->plan->client) }}">{{ $due->plan->client->name }}</a></td>
                    <td class="break">{{ $due->plan->concept }}</td>
                    <td class="num">{{ Format::money($due->amount, $due->currency) }}</td>
                    <td><span class="badge badge-{{ $due->statusKey() }}">{{ $due->statusLabel() }}</span></td>
                    <td class="nowrap">
                        @if ($due->reminder_sent_at)
                            <span class="badge badge-sent">Enviado</span>
                            <small>{{ $due->reminder_sent_at->translatedFormat('j M') }}</small>
                        @elseif ($due->status === \App\Models\PaymentDue::PENDING && ! $due->isOverdue())
                            <small>Sale el {{ Format::date($calendar->reminderDate($due->due_date)) }}</small>
                        @else
                            <small>—</small>
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            @if ($due->status === \App\Models\PaymentDue::PENDING)
                                <form method="post" action="{{ route('dues.paid', $due) }}">
                                    @csrf
                                    <button class="btn btn-sm">Pagado</button>
                                </form>
                                <a class="btn btn-sm btn-ghost" href="{{ route('dues.preview', $due) }}" target="_blank" rel="noopener">Ver correo</a>
                                <form method="post" action="{{ route('dues.send', $due) }}"
                                      data-confirm="¿Enviar el recordatorio de {{ $due->plan->concept }} a {{ $due->plan->client->name }} ahora?">
                                    @csrf
                                    <button class="btn btn-sm btn-ghost">{{ $due->reminder_sent_at ? 'Reenviar' : 'Enviar' }}</button>
                                </form>
                                <form method="post" action="{{ route('dues.cancel', $due) }}"
                                      data-confirm="¿Cancelar este cobro? Ya no se enviará recordatorio.">
                                    @csrf
                                    <button class="btn btn-sm btn-ghost">Cancelar</button>
                                </form>
                            @else
                                <form method="post" action="{{ route('dues.reopen', $due) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-ghost">Reabrir</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
