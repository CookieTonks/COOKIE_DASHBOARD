<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $client->contacts()->create($this->validated($request));

        return back()->with('success', 'Contacto agregado.');
    }

    public function update(Request $request, Client $client, Contact $contact): RedirectResponse
    {
        $contact->update($this->validated($request));

        return back()->with('success', 'Contacto actualizado.');
    }

    public function destroy(Client $client, Contact $contact): RedirectResponse
    {
        $contact->delete();

        return back()->with('success', 'Contacto eliminado.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Sin "ascii" pasaría dueña@..., que el envío rechaza y bloquea a todo el cliente.
            'email' => ['required', 'ascii', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'string', 'max:100'],
        ]);

        return $data + ['receives_reminders' => $request->boolean('receives_reminders')];
    }
}
