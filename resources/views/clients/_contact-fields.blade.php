{{-- $prefix evita ids repetidos cuando hay varios formularios en la página.
     Solo el formulario nuevo recupera old(): si no, un error llenaría también los de edición. --}}
<div class="form-grid">
    <div class="field">
        <label for="{{ $prefix }}-name">Nombre</label>
        <input id="{{ $prefix }}-name" type="text" name="name" value="{{ $contact ? $contact->name : old('name') }}" required maxlength="255">
    </div>
    <div class="field">
        <label for="{{ $prefix }}-email">Correo</label>
        <input id="{{ $prefix }}-email" type="email" name="email" value="{{ $contact ? $contact->email : old('email') }}" required maxlength="255">
    </div>
    <div class="field">
        <label for="{{ $prefix }}-phone">Teléfono (opcional)</label>
        <input id="{{ $prefix }}-phone" type="text" name="phone" value="{{ $contact ? $contact->phone : old('phone') }}" maxlength="30">
    </div>
    <div class="field">
        <label for="{{ $prefix }}-role">Puesto (opcional)</label>
        <input id="{{ $prefix }}-role" type="text" name="role" value="{{ $contact ? $contact->role : old('role') }}" maxlength="100">
    </div>
    <div class="field field-full">
        <label class="check">
            <input type="checkbox" name="receives_reminders" value="1" @checked($contact->receives_reminders ?? true)>
            Recibe los recordatorios de pago
        </label>
    </div>
</div>
