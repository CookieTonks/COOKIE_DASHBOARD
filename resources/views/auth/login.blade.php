@extends('layouts.base')

@section('title', 'Entrar')

@section('body')
    <div class="login-wrap">
        <form method="post" action="{{ route('login') }}" class="card login-card">
            @csrf
            <h1>Recordatorios de pago</h1>
            <small>Entra para administrar a tus clientes.</small>

            <div class="field">
                <label for="email">Correo</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="password">Contraseña</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label class="check"><input type="checkbox" name="remember"> Mantener mi sesión</label>
            </div>
            <button class="btn btn-primary">Entrar</button>
        </form>
    </div>
@endsection
