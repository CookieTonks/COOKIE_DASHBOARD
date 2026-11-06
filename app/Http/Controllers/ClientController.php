<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\DueGenerator;
use App\Support\BusinessCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $clients = Client::query()
            ->withCount(['contacts', 'plans'])
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%$search%")
                ->orWhere('tax_id', 'like', "%$search%")
                ->orWhereHas('contacts', fn ($contact) => $contact
                    ->where('email', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%"))))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('clients.index', compact('clients', 'search'));
    }

    public function create(): View
    {
        return view('clients.form', ['client' => new Client(['active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Client::create($this->validated($request));

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente creado. Ahora agrega sus contactos y su plan de pago.');
    }

    public function show(Client $client): View
    {
        $client->load(['contacts', 'plans']);

        $dues = $client->dues()
            ->with('plan.client')
            ->orderByDesc('due_date')
            ->limit(30)
            ->get();

        return view('clients.show', [
            'client' => $client,
            'dues' => $dues,
            'calendar' => new BusinessCalendar,
        ]);
    }

    public function edit(Client $client): View
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client, DueGenerator $generator): RedirectResponse
    {
        $client->update($this->validated($request));

        if ($client->active) {
            $client->plans()->where('active', true)->get()->each(fn ($plan) => $generator->syncPlan($plan));
        }

        return redirect()->route('clients.show', $client)->with('success', 'Cliente actualizado.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente eliminado.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        return $data + ['active' => $request->boolean('active')];
    }
}
